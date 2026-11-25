/**
 * Lo que la ruta `/api/v1/[...path]` decide antes de tocar el núcleo del respaldo (T9.4).
 *
 * Vive aparte de la ruta por la misma razón que `canal-interno.ts`: la ruta necesita el servidor de
 * Nitro; esto es lógica pura y se prueba sin levantar nada. Tres piezas:
 *
 *   - `peticionRespaldable`: lee la URL ya validada por `destinoInterno` y la deja como la espera el
 *     núcleo. Lo ambiguo (un parámetro repetido) NO se interpreta: se devuelve `null` y la ruta sigue
 *     como antes, porque `Record<string, string>` no puede representar dos valores y la clave del
 *     respaldo mentiría sobre lo que se pidió.
 *   - `atenderConRespaldo`: solo `GET`. Traduce el `Resultado` del núcleo a lo que la ruta responde y
 *     deja la única línea de registro cuando se sirvió una copia.
 *   - `crearTraer`: la única salida hacia la API para las lecturas respaldables. Reconstruye la URL
 *     con `destinoInterno` (el mismo guardián que usa el proxy: mismo origen y bajo `/api/v1`) y no
 *     espera más del tiempo que le da el núcleo, también al leer el cuerpo.
 */
import { destinoInterno } from './canal-interno'
import type { PeticionRespaldable, RespuestaApi, Resultado, Traer } from './respaldo-api'

const PREFIJO_API = '/api/v1'

/** Lo único que la ruta necesita del núcleo (se sustituye en las pruebas). */
export interface Resolutor {
  resolver(peticion: PeticionRespaldable): Promise<Resultado | null>
}

export interface EntornoRespaldo {
  respaldo: Resolutor
  /** Una línea estructurada para el registro del servidor. */
  registrar(linea: Record<string, unknown>): void
}

export interface LecturaEntrante {
  metodo: string
  /** La URL interna completa, ya validada por `destinoInterno`. */
  destino: string
  /** El `Host` por el que llegó el visitante (`x-forwarded-host`). */
  anfitrion: string
  cabeceras: Record<string, string>
}

/** Lo que la ruta responde cuando el respaldo se encargó de la lectura. */
export interface Atencion {
  estado: number
  tipo: string
  cuerpo: string
  cabeceras: Record<string, string>
}

export function peticionRespaldable(
  destino: string,
  anfitrion: string,
  cabeceras: Record<string, string>,
): PeticionRespaldable | null {
  let url: URL
  try {
    url = new URL(destino)
  } catch {
    return null
  }
  if (!url.pathname.startsWith(`${PREFIJO_API}/`)) return null

  // Sin prototipo: un parámetro llamado `__proto__` queda como propio y el núcleo lo rechaza, en vez
  // de perderse y dejar pasar la petición como si estuviera limpia.
  const query: Record<string, string> = Object.create(null)
  for (const nombre of new Set(url.searchParams.keys())) {
    const valores = url.searchParams.getAll(nombre)
    if (valores.length !== 1) return null
    query[nombre] = valores[0]!
  }
  return { ruta: url.pathname.slice(PREFIJO_API.length), query, cabeceras, anfitrion }
}

/**
 * `null` si la lectura no es del respaldo (no es `GET`, no se puede interpretar o el núcleo la
 * descarta): la ruta la sirve como hoy. Si el núcleo lanza (API caída y sin copia) el error sube
 * tal cual y la ruta responde como lo hace el proxy ante un fallo de red.
 */
export async function atenderConRespaldo(entorno: EntornoRespaldo, lectura: LecturaEntrante): Promise<Atencion | null> {
  if (lectura.metodo !== 'GET') return null
  const peticion = peticionRespaldable(lectura.destino, lectura.anfitrion, lectura.cabeceras)
  if (peticion === null) return null

  const resultado = await entorno.respaldo.resolver(peticion)
  if (resultado === null) return null

  if (resultado.origen === 'respaldo') {
    const edad = Math.max(0, Math.floor((resultado.edadMs ?? 0) / 1000))
    // Ni los parámetros ni las cabeceras entran al registro: pueden llevar datos de la persona.
    entorno.registrar({ evento: 'respaldo_servido', ruta: peticion.ruta, motivo: resultado.motivo, edad_s: edad })
    return {
      estado: resultado.estado,
      tipo: resultado.tipo,
      cuerpo: resultado.cuerpo,
      cabeceras: { 'x-sede-respaldo': String(edad) },
    }
  }

  return {
    estado: resultado.estado,
    tipo: resultado.tipo,
    cuerpo: resultado.cuerpo,
    cabeceras: resultado.retryAfter !== undefined ? { 'retry-after': resultado.retryAfter } : {},
  }
}

/** La lectura de la API para el núcleo. `fetchFn` se inyecta solo para probar. */
export function crearTraer(base: string, fetchFn?: typeof fetch): Traer {
  return async (ruta, query, cabeceras, tiempoMs): Promise<RespuestaApi> => {
    const parametros = new URLSearchParams(query).toString()
    const destino = destinoInterno(base, `${PREFIJO_API}${ruta}${parametros ? `?${parametros}` : ''}`)
    if (destino === null) throw new Error('El destino no es del canal interno.')

    const respuesta = await (fetchFn ?? globalThis.fetch)(destino, {
      method: 'GET',
      headers: cabeceras,
      redirect: 'manual',
      // Cubre la espera de las cabeceras Y la lectura del cuerpo: una API que contesta y se cuelga
      // a mitad del cuerpo también se corta.
      signal: AbortSignal.timeout(tiempoMs),
    })
    const cuerpo = await respuesta.text()
    return { estado: respuesta.status, cabeceras: Object.fromEntries(respuesta.headers.entries()), cuerpo }
  }
}
