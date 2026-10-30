/**
 * Canal interno del renderizado en servidor hacia la API (T-22, ADR-0018).
 *
 * **Cuándo se ejecuta esto.** Sólo cuando el propio sitio, mientras renderiza una
 * página, pide datos con una URL relativa (`/api/v1/...`): Nitro resuelve esa
 * petición internamente, sin red, y llega aquí. Las peticiones del navegador a
 * `/api/v1` NO pasan por este archivo: nginx las manda directo a PHP, porque su
 * regla `location /api/v1` es más específica que el `location /` del sitio.
 *
 * **Qué hace.** La reenvía por la red interna a nginx (`NUXT_API_INTERNA`, un
 * puerto que no se publica) añadiendo `X-Sede-Cliente-Ip` con la IP del
 * visitante, que nginx dejó en `X-Real-IP` al entrar. Así la API cuenta el cupo
 * por visitante y no por servidor del sitio.
 *
 * **Qué NO hace, a propósito.**
 *   - Sólo GET y HEAD: el renderizado únicamente lee. Una escritura que pasara
 *     por aquí heredaría una confianza que no le corresponde.
 *   - Nunca reenvía una `X-Sede-Cliente-Ip` que ya viniera en la petición: la
 *     sobrescribe siempre con lo que dedujo de `X-Real-IP` (o la deja vacía).
 *   - No es un proxy abierto: `destinoInterno` exige el mismo origen y `/api/v1`.
 */
import { CABECERA_CLIENTE, destinoInterno, ipDelVisitante } from '../../../utils/canal-interno'

export default defineEventHandler((evento) => {
  if (evento.method !== 'GET' && evento.method !== 'HEAD') {
    setResponseHeader(evento, 'Allow', 'GET, HEAD')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }

  const base = String(useRuntimeConfig(evento).apiInterna ?? '')
  const destino = destinoInterno(base, evento.path)
  if (destino === null) {
    // Sin destino interno configurado (o ruta fuera de /api/v1) el sitio no
    // inventa otro camino: declara que la API no está disponible.
    throw createError({ statusCode: 503, statusMessage: 'API interna no configurada' })
  }

  const anfitrion = getRequestHeader(evento, 'x-forwarded-host')

  return proxyRequest(evento, destino, {
    headers: {
      [CABECERA_CLIENTE]: ipDelVisitante(getRequestHeader(evento, 'x-real-ip')),
      // La API ve el mismo anfitrión que veía por el camino público.
      ...(anfitrion ? { host: anfitrion } : {}),
    },
    // Una API que no contesta no debe dejar colgado el renderizado: a los 20 s se
    // corta y la página muestra su estado de error.
    fetchOptions: { signal: AbortSignal.timeout(20_000), redirect: 'manual' },
  })
})
