/**
 * Cliente único de la API pública (T9.1, D-0C.1).
 *
 * Antes, quince archivos leían `public.apiUrl`, repetían `.replace(/\/+$/, '')` y cada uno
 * decidía a mano qué era «no existe» y qué era «la API no contesta». Esto lo reúne en un solo
 * sitio, sin Nuxt ni Axios: recibe el transporte (`$fetch` / `useRequestFetch()`) ya hecho, así
 * que se prueba como lógica pura y sirve igual en el navegador, en las páginas y en las rutas
 * del servidor.
 *
 * No cambia lo que ve nadie: `leer` devuelve lo mismo que el transporte y solo **clasifica** el
 * fallo. El error lanzado conserva `statusCode`, `data` y `response`, de modo que el código que
 * ya los lee sigue funcionando.
 *
 * Los tres errores (y la regla que los separa) son los que usará la caché de última respuesta
 * válida de T9.2: solo `ApiNoDisponible` autoriza servir un respaldo; un 404 (`ApiNoEncontrada`)
 * y un rechazo (`ApiRechazada`: 401, 403, 410, 422, 429…) se pasan tal cual.
 */

/**
 * Opciones que se pasan al transporte sin tocarlas. Son las que el sitio usa hoy; tipadas (y no un
 * `Record` abierto) para que los callbacks, como `onResponse`, conserven su tipo en quien los escribe.
 */
export interface OpcionesTraer {
  query?: Record<string, unknown>
  method?: string
  body?: unknown
  timeout?: number
  keepalive?: boolean
  credentials?: 'omit' | 'same-origin' | 'include'
  ignoreResponseError?: boolean
  onResponse?: (contexto: { response: { status: number; headers: { get(nombre: string): string | null } } }) => void
}

/** El transporte: lo que devuelve `useRequestFetch()` o `$fetch`, visto desde aquí. */
export type Traer = <T = unknown>(url: string, opciones?: OpcionesTraer) => Promise<T>

interface DatosDelFallo {
  statusCode?: number
  statusMessage?: string
  data?: unknown
  response?: unknown
  cause?: unknown
}

/** Base de los tres errores de la API. */
export class ErrorApi extends Error {
  /**
   * Nombre explícito en cada clase: el paquete de producción minifica y `new.target.name` saldría
   * como una letra suelta, que es justo lo que el registro de T9.2 quiere escribir.
   */
  override name = 'ErrorApi'
  statusCode?: number
  /** Nuxt lo serializa con el error del servidor al navegador: se conserva igual que en `FetchError`. */
  statusMessage?: string
  data?: unknown
  response?: unknown

  constructor(mensaje: string, datos: DatosDelFallo = {}) {
    super(mensaje, datos.cause === undefined ? undefined : { cause: datos.cause })
    this.statusCode = datos.statusCode
    this.statusMessage = datos.statusMessage
    this.data = datos.data
    this.response = datos.response
  }
}

/** La API contestó 404: el recurso no existe o no está publicado. */
export class ApiNoEncontrada extends ErrorApi {
  override name = 'ApiNoEncontrada'
}

/** La API no pudo contestar: red caída, tiempo agotado o 5xx. */
export class ApiNoDisponible extends ErrorApi {
  override name = 'ApiNoDisponible'
}

/** La API contestó y rechazó la petición (cualquier otro 4xx): no es una caída. */
export class ApiRechazada extends ErrorApi {
  override name = 'ApiRechazada'
}

/** La dirección base sin barras finales; un valor ausente es una cadena vacía. */
export function normalizarBase(url: unknown): string {
  return String(url ?? '').replace(/\/+$/, '')
}

function estadoDe(fallo: unknown): number | undefined {
  if (typeof fallo !== 'object' || fallo === null) return undefined
  const { statusCode, response } = fallo as { statusCode?: unknown; response?: { status?: unknown } }
  const estado = statusCode ?? response?.status
  return typeof estado === 'number' ? estado : undefined
}

/**
 * Clasifica un fallo del transporte. Sin estado HTTP (red, tiempo agotado, cancelación o un valor
 * que ni siquiera es un `Error`) cuenta como «no disponible»: la API no contestó.
 */
export function clasificarFallo(fallo: unknown): ErrorApi {
  if (fallo instanceof ErrorApi) return fallo

  const estado = estadoDe(fallo)
  const mensaje = fallo instanceof Error ? fallo.message : 'La API no respondió.'
  const datos: DatosDelFallo = {
    statusCode: estado,
    statusMessage: typeof (fallo as { statusMessage?: unknown } | null)?.statusMessage === 'string'
      ? (fallo as { statusMessage: string }).statusMessage
      : undefined,
    data: (fallo as { data?: unknown } | null)?.data,
    response: (fallo as { response?: unknown } | null)?.response,
    cause: fallo,
  }

  if (estado === 404) return new ApiNoEncontrada(mensaje, datos)
  if (estado !== undefined && estado >= 400 && estado < 500) return new ApiRechazada(mensaje, datos)
  return new ApiNoDisponible(mensaje, datos)
}

export interface ClienteApi {
  /** La base ya normalizada (`/api/v1`, sin barra final). */
  readonly base: string
  /** Lee `base + ruta`. Un fallo se lanza como `ApiNoEncontrada`, `ApiNoDisponible` o `ApiRechazada`. */
  leer<T = unknown>(ruta: string, opciones?: OpcionesTraer): Promise<T>
  /** Como `leer`, pero un 404 devuelve `null` («no existe»); cualquier otro fallo se propaga. */
  leerONulo<T = unknown>(ruta: string, opciones?: OpcionesTraer): Promise<T | null>
}

export function crearClienteApi(traer: Traer, baseCruda: unknown): ClienteApi {
  const base = normalizarBase(baseCruda)

  async function leer<T>(ruta: string, opciones?: OpcionesTraer): Promise<T> {
    try {
      return await traer<T>(`${base}${ruta}`, opciones)
    } catch (fallo) {
      throw clasificarFallo(fallo)
    }
  }

  async function leerONulo<T>(ruta: string, opciones?: OpcionesTraer): Promise<T | null> {
    try {
      return await leer<T>(ruta, opciones)
    } catch (fallo) {
      if (fallo instanceof ApiNoEncontrada) return null
      throw fallo
    }
  }

  return { base, leer, leerONulo }
}
