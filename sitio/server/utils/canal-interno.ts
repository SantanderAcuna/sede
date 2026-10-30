/**
 * Lógica pura del canal interno sitio → API (T-22, hallazgo del cupo compartido).
 *
 * Vive aparte de la ruta (`server/routes/api/v1/[...path].ts`) por una razón de
 * pruebas: la ruta necesita el servidor de Nitro en marcha, y lo que aquí se
 * decide —qué cabecera se reenvía y a dónde— es lo que, si falla, rompe en
 * silencio la propiedad que se busca (que cada visitante tenga su propio cupo).
 * Como funciones puras se prueban con Vitest sin levantar nada.
 *
 * Por qué existe el canal (ver `docs/busqueda.md` §2.5 y ADR-0018):
 *
 *   Antes, el renderizado en servidor llamaba a la API por su dirección pública
 *   (`https://<dominio>/api/v1`). La petición salía del contenedor del sitio, daba
 *   la vuelta por Internet y entraba otra vez por Cloudflare: la API veía **la IP
 *   del servidor del sitio**, no la del visitante. Todos los ciudadanos
 *   compartían un único cupo de peticiones (medido: dos clientes distintos
 *   recibieron el error en el mismo segundo).
 *
 *   Ahora el sitio llama a la API por la red interna, a un puerto de nginx que
 *   sólo existe dentro de Docker, y le dice a quién representa en la cabecera
 *   `X-Sede-Cliente-Ip`. nginx sólo la cree en ese puerto (`conf.d/interno.conf`).
 */
import { isIP } from 'node:net'

/** Cabecera con la que el sitio declara la IP del visitante a nginx. */
export const CABECERA_CLIENTE = 'x-sede-cliente-ip'

/** Prefijo de la API: lo único que el canal interno acepta reenviar. */
const PREFIJO_API = '/api/v1'

/**
 * Devuelve la IP del visitante si es una dirección IPv4 o IPv6 válida, o `''`.
 *
 * Se valida aunque el valor venga de nuestro propio nginx: la cabecera termina
 * siendo la identidad con la que se cuenta el cupo, y un valor basura (o con
 * saltos de línea) no debe llegar a la configuración del límite. Ante la duda
 * devuelve vacío y nginx cae a su comportamiento seguro: contar la petición
 * contra el servidor del sitio, como antes.
 */
export function ipDelVisitante(valor: string | string[] | null | undefined): string {
  const bruto = Array.isArray(valor) ? valor[0] : valor
  const candidato = (bruto ?? '').trim()

  // 45 caracteres es el máximo de una IPv6 con IPv4 incrustada. El `%` descarta
  // los identificadores de zona (`fe80::1%eth0`), que no son una IP de cliente.
  if (candidato.length === 0 || candidato.length > 45 || candidato.includes('%')) {
    return ''
  }
  return isIP(candidato) === 0 ? '' : candidato
}

/**
 * Construye la URL interna a la que se reenvía la petición, o `null` si no debe
 * reenviarse.
 *
 * Reglas, todas pensadas para que el canal no sea un proxy abierto:
 *   - la base tiene que ser una URL `http(s)` válida;
 *   - el resultado tiene que quedar en el MISMO origen que la base (una ruta tipo
 *     `//otro-host/x` o `/\otro` no puede cambiar de destino);
 *   - la ruta, ya normalizada, tiene que seguir bajo `/api/v1` (un `..` no puede
 *     sacarla de ahí).
 */
export function destinoInterno(base: string, rutaConConsulta: string): string | null {
  let origen: URL
  try {
    origen = new URL(base)
  } catch {
    return null
  }
  if (origen.protocol !== 'http:' && origen.protocol !== 'https:') {
    return null
  }

  let destino: URL
  try {
    destino = new URL(rutaConConsulta, origen)
  } catch {
    return null
  }
  if (destino.origin !== origen.origin) {
    return null
  }
  const ruta = destino.pathname
  if (ruta !== PREFIJO_API && !ruta.startsWith(`${PREFIJO_API}/`)) {
    return null
  }
  return destino.toString()
}
