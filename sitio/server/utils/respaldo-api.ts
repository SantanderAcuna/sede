/**
 * Núcleo del respaldo de la API (T9.2, D-0C.4).
 *
 * Si la API no contesta (red caída, tiempo agotado, 5xx), la sede sigue mostrando la **última
 * respuesta válida** de las lecturas públicas. Nada más: un 404, un 410 o un 429 son respuestas de
 * la API y se pasan tal cual, y lo que sea de una persona (cookies, `private`, URLs firmadas) no se
 * guarda nunca.
 *
 * Es lógica pura, como `canal-interno.ts`: no importa Nitro ni Redis. El almacén (T9.3), el
 * transporte y el reloj se inyectan, así que cada regla se prueba sin servidor. La integración en
 * `[...path].ts` es T9.4.
 */

/** Cuánto vive un respaldo: pasado esto no se sirve, aunque el almacén aún lo tenga. */
export const EDAD_MAXIMA_MS = 24 * 60 * 60 * 1000
/** Tiempo de espera de las lecturas respaldables. HIPÓTESIS (H31): se mide en T9.6. */
export const TIEMPO_ESPERA_MS = 4000
/** Fallos seguidos que abren el cortocircuito. */
export const FALLOS_PARA_ABRIR = 3
/** Cuánto sirve el respaldo sin llamar a la API antes de probar con una petición. */
export const PAUSA_CIRCUITO_MS = 30_000
/** Tope del cuerpo guardado: una lista enorme no debe llenar el almacén. */
export const TOPE_BYTES = 1024 * 1024
const PAGINA_MAXIMA = 10_000

/** Lo que el almacén guarda por clave. */
export interface EntradaRespaldo {
  cuerpo: string
  tipo: string
  guardadoEn: number
}

/** El almacén (Redis en T9.3). Cualquier fallo suyo se trata como «no hay respaldo». */
export interface Almacen {
  leer(clave: string): Promise<EntradaRespaldo | null>
  guardar(clave: string, entrada: EntradaRespaldo, ttlSegundos: number): Promise<void>
}

/** La respuesta de la API ya leída. El núcleo no depende de cómo vengan escritos los nombres. */
export interface RespuestaApi {
  estado: number
  cabeceras: Record<string, string | undefined>
  cuerpo: string
}

export type Traer = (
  ruta: string,
  query: Record<string, string>,
  cabeceras: Record<string, string>,
  tiempoMs: number,
) => Promise<RespuestaApi>

export type MotivoRespaldo = 'red' | 'tiempo' | '5xx' | 'circuito'

export interface Resultado {
  origen: 'api' | 'respaldo'
  estado: number
  tipo: string
  cuerpo: string
  /** Solo con origen «respaldo»: cuánto hace que se guardó. */
  edadMs?: number
  /** Solo con origen «respaldo»: por qué no se usó la API (para el registro). */
  motivo?: MotivoRespaldo
}

export interface PeticionRespaldable {
  ruta: string
  query: Record<string, string>
  cabeceras: Record<string, string>
  /** El `Host` por el que llegó el visitante. */
  anfitrion: string
}

export interface OpcionesRespaldo {
  almacen: Almacen
  traer: Traer
  ahora: () => number
  /** El dominio configurado (`SEDE_DOMINIO`): solo se guarda lo pedido por él (H34). */
  dominio: string
  topeBytes?: number
}

const ESPACIO = '[a-z0-9][a-z0-9-]{0,199}'
const RUTAS_LISTA = new Set(['/tramites', '/noticias', '/politicas'])
const RUTAS_SIN_PARAMETROS = [
  /^\/identidad\/footer$/,
  /^\/banners$/,
  new RegExp(`^/(?:tramites|noticias|politicas)/${ESPACIO}$`),
]

function enteroEnRango(valor: unknown, maximo: number): number | null {
  if (typeof valor !== 'string' || !/^[1-9]\d{0,5}$/.test(valor)) return null
  const n = Number(valor)
  return n <= maximo ? n : null
}

/**
 * La clave del respaldo, o `null` si la lectura no se respalda. Solo lecturas públicas de la lista
 * blanca, sin filtros ni búsqueda (H32) y con `page` y `per_page` acotados (B7). El orden de los
 * parámetros no cambia la clave. El prefijo `sitio:` es el que permite el ACL de Redis (T9.3).
 */
export function claveDeRespaldo(ruta: string, query: Record<string, string>): string | null {
  const nombres = Object.keys(query)
  let permitidos: string[]
  if (RUTAS_LISTA.has(ruta)) permitidos = ['page', 'per_page']
  else if (RUTAS_SIN_PARAMETROS.some(r => r.test(ruta))) permitidos = []
  else return null

  if (nombres.some(n => !permitidos.includes(n))) return null
  if (query.page !== undefined && enteroEnRango(query.page, PAGINA_MAXIMA) === null) return null
  if (query.per_page !== undefined && enteroEnRango(query.per_page, 100) === null) return null

  const parametros = nombres.sort().map(n => `${n}=${query[n]}`).join('&')
  return `sitio:respaldo:${ruta}${parametros ? `?${parametros}` : ''}`
}

const CABECERAS_PERSONALES = new Set(['cookie', 'authorization', 'proxy-authorization'])

/** La petición respaldable sale sin credenciales: su respuesta es la misma para cualquiera (H33). */
function cabecerasSinCredenciales(cabeceras: Record<string, string>): Record<string, string> {
  const salida: Record<string, string> = {}
  for (const [nombre, valor] of Object.entries(cabeceras)) {
    if (!CABECERAS_PERSONALES.has(nombre.toLowerCase())) salida[nombre] = valor
  }
  return salida
}

function anfitrionLimpio(valor: string): string {
  return valor.trim().toLowerCase().replace(/:\d+$/, '')
}

const URL_FIRMADA = /[?&;](?:signature|expires|x-amz-signature|x-amz-expires)=/i

function ultimaPagina(cuerpo: string): number | null {
  try {
    const ultima = (JSON.parse(cuerpo) as { meta?: { last_page?: unknown } })?.meta?.last_page
    return typeof ultima === 'number' && Number.isFinite(ultima) ? ultima : null
  } catch {
    return null
  }
}

function cabecera(respuesta: RespuestaApi, nombre: string): string | undefined {
  for (const [clave, valor] of Object.entries(respuesta.cabeceras)) {
    if (clave.toLowerCase() === nombre) return valor
  }
  return undefined
}

function sePuedeGuardar(
  peticion: PeticionRespaldable,
  respuesta: RespuestaApi,
  opciones: OpcionesRespaldo,
): boolean {
  if (respuesta.estado !== 200) return false
  if (cabecera(respuesta, 'set-cookie') !== undefined) return false
  if (/\b(?:private|no-store)\b/i.test(cabecera(respuesta, 'cache-control') ?? '')) return false
  if (anfitrionLimpio(peticion.anfitrion) !== anfitrionLimpio(opciones.dominio)) return false
  if (new TextEncoder().encode(respuesta.cuerpo).length > (opciones.topeBytes ?? TOPE_BYTES)) return false
  if (URL_FIRMADA.test(respuesta.cuerpo)) return false

  // Una página fuera del rango real no se guarda: no hay respaldo útil de lo que no existe.
  const pagina = enteroEnRango(peticion.query.page, PAGINA_MAXIMA)
  if (pagina !== null && pagina > 1) {
    const ultima = ultimaPagina(respuesta.cuerpo)
    if (ultima === null || pagina > ultima) return false
  }
  return true
}

function motivoDeExcepcion(fallo: unknown): MotivoRespaldo {
  const nombre = (fallo as { name?: unknown } | null)?.name
  return nombre === 'TimeoutError' || nombre === 'AbortError' ? 'tiempo' : 'red'
}

export function crearRespaldo(opciones: OpcionesRespaldo) {
  const { almacen, traer, ahora } = opciones
  let fallosSeguidos = 0
  let abiertoHasta = 0
  let probando = false

  async function leerRespaldo(clave: string): Promise<EntradaRespaldo | null> {
    try {
      const entrada = await almacen.leer(clave)
      if (entrada === null) return null
      return ahora() - entrada.guardadoEn <= EDAD_MAXIMA_MS ? entrada : null
    } catch {
      return null
    }
  }

  function comoRespaldo(entrada: EntradaRespaldo, motivo: MotivoRespaldo): Resultado {
    return { origen: 'respaldo', estado: 200, tipo: entrada.tipo, cuerpo: entrada.cuerpo, edadMs: ahora() - entrada.guardadoEn, motivo }
  }

  function registrarFallo(): void {
    fallosSeguidos += 1
    if (fallosSeguidos >= FALLOS_PARA_ABRIR) abiertoHasta = ahora() + PAUSA_CIRCUITO_MS
  }

  /**
   * `null` si la lectura no se respalda: quien llama la sirve como hoy. En cualquier otro caso
   * devuelve la respuesta de la API, o el respaldo cuando la API no contesta.
   */
  async function resolver(peticion: PeticionRespaldable): Promise<Resultado | null> {
    const clave = claveDeRespaldo(peticion.ruta, peticion.query)
    if (clave === null) return null

    // Circuito abierto: se sirve el respaldo sin llamar. Si no lo hay, se llama como hoy.
    // Pasada la pausa, UNA petición hace de sonda; las demás siguen con el respaldo.
    const abierto = fallosSeguidos >= FALLOS_PARA_ABRIR
    let esSonda = false
    if (abierto) {
      if (ahora() < abiertoHasta || probando) {
        const entrada = await leerRespaldo(clave)
        if (entrada !== null) return comoRespaldo(entrada, 'circuito')
      } else {
        probando = true
        esSonda = true
      }
    }

    try {
      let respuesta: RespuestaApi
      try {
        respuesta = await traer(
          peticion.ruta,
          peticion.query,
          cabecerasSinCredenciales(peticion.cabeceras),
          TIEMPO_ESPERA_MS,
        )
      } catch (fallo) {
        registrarFallo()
        const entrada = await leerRespaldo(clave)
        if (entrada !== null) return comoRespaldo(entrada, motivoDeExcepcion(fallo))
        throw fallo
      }

      if (respuesta.estado >= 500) {
        registrarFallo()
        const entrada = await leerRespaldo(clave)
        return entrada !== null
          ? comoRespaldo(entrada, '5xx')
          : { origen: 'api', estado: respuesta.estado, tipo: cabecera(respuesta, 'content-type') ?? '', cuerpo: respuesta.cuerpo }
      }

      // La API contestó. El 429 lo pone nginx, no la API: no dice nada de su salud.
      if (respuesta.estado !== 429) {
        fallosSeguidos = 0
        abiertoHasta = 0
      }

      if (sePuedeGuardar(peticion, respuesta, opciones)) {
        try {
          await almacen.guardar(
            clave,
            { cuerpo: respuesta.cuerpo, tipo: cabecera(respuesta, 'content-type') ?? 'application/json', guardadoEn: ahora() },
            EDAD_MAXIMA_MS / 1000,
          )
        } catch {
          // El almacén es una ayuda: si falla, la respuesta de la API sigue su camino.
        }
      }
      return { origen: 'api', estado: respuesta.estado, tipo: cabecera(respuesta, 'content-type') ?? '', cuerpo: respuesta.cuerpo }
    } finally {
      if (esSonda) {
        probando = false
      }
    }
  }

  return { resolver }
}
