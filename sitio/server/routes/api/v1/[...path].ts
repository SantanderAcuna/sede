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
 * **Respaldo (T9.4, Fase 0C).** Las lecturas `GET` de la lista blanca (`respaldo-api.ts`) pasan
 * por el respaldo: si la API no contesta (red, 5xx o más de 4 s) y hay copia, se sirve la última respuesta
 * válida guardada en Redis, con la cabecera `X-Sede-Respaldo: <edad en s>` y una línea de registro.
 * Esa petición sale SIN `Cookie` ni `Authorization`: su respuesta es la misma para cualquiera. Todo
 * lo demás (búsqueda, filtros, `HEAD`, rutas fuera de la lista) sigue por `proxyRequest` como antes,
 * y si la API cae sin copia disponible el sitio falla igual que antes (502).
 *
 * **Qué NO hace, a propósito.**
 *   - Sólo GET y HEAD: el renderizado únicamente lee. Una escritura que pasara
 *     por aquí heredaría una confianza que no le corresponde.
 *   - Nunca reenvía una `X-Sede-Cliente-Ip` que ya viniera en la petición: la
 *     sobrescribe siempre con lo que dedujo de `X-Real-IP` (o la deja vacía).
 *   - No es un proxy abierto: `destinoInterno` exige el mismo origen y `/api/v1`.
 */
import { CABECERA_CLIENTE, destinoInterno, ipDelVisitante } from '../../../utils/canal-interno'
import { entornoDelSitio } from '../../../utils/respaldo-sitio'

export default defineEventHandler(async (evento) => {
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
  const cabecerasPropias = {
    [CABECERA_CLIENTE]: ipDelVisitante(getRequestHeader(evento, 'x-real-ip')),
    // La API ve el mismo anfitrión que veía por el camino público.
    ...(anfitrion ? { host: anfitrion } : {}),
  }

  if (evento.method === 'GET') {
    let atencion
    try {
      atencion = await entornoDelSitio(useRuntimeConfig(evento)).atender({
        metodo: evento.method,
        destino,
        anfitrion: anfitrion ?? '',
        // Lo que reenviaría el proxy; el respaldo le quita `cookie` y `authorization`.
        cabeceras: { ...getProxyRequestHeaders(evento), ...cabecerasPropias },
      })
    } catch (causa) {
      // API caída y sin copia: el mismo error que da `proxyRequest` ante un fallo de red.
      throw createError({ statusCode: 502, statusMessage: 'Bad Gateway', cause: causa })
    }
    if (atencion !== null) {
      setResponseStatus(evento, atencion.estado)
      if (atencion.tipo !== '') setResponseHeader(evento, 'content-type', atencion.tipo)
      for (const [nombre, valor] of Object.entries(atencion.cabeceras)) {
        setResponseHeader(evento, nombre, valor)
      }
      return atencion.cuerpo
    }
  }

  return proxyRequest(evento, destino, {
    headers: cabecerasPropias,
    // Una API que no contesta no debe dejar colgado el renderizado: a los 20 s se
    // corta y la página muestra su estado de error.
    fetchOptions: { signal: AbortSignal.timeout(20_000), redirect: 'manual' },
  })
})
