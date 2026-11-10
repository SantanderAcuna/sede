/**
 * Sugerencias del buscador mientras se escribe (T-22, entrega 3 · E3-1).
 *
 * Es la **lógica** del desplegable de sugerencias, sin ningún marcado: cuándo
 * preguntar a la API, qué respuesta vale y qué estado le toca enseñar al
 * componente. El combobox accesible que la usa es E3-2. Está en un composable por
 * una razón concreta: las reglas de abajo son las que protegen al servidor y al
 * ciudadano, y se prueban mejor con temporizadores simulados que a través del DOM.
 *
 * Las reglas son las de `docs/busqueda-interaccion.md` §4.5 (aprobada el
 * 2026-11-10); cada constante y cada rama dice cuál es.
 *
 *  1. **Mínimo 3 caracteres y máximo 120**, tras recortar y colapsar espacios.
 *  2. **Pausa de 350 ms** desde la última tecla (D-I1): una petición por pausa.
 *  3. **Sólo vale la última respuesta.** Cada petición lleva un número y un
 *     `AbortController`; al lanzar otra se cancela la anterior y se ignora lo que
 *     llegue con un número que ya no es el último, sea un resultado o un error.
 *  4. **Sin peticiones durante una composición** (las tildes con tecla muerta no
 *     son texto terminado).
 *  5. **Memoria breve, sólo en variables:** 60 s y 30 entradas como máximo. Nada
 *     en `localStorage`, `sessionStorage`, cookies ni IndexedDB (D-I6): lo que el
 *     ciudadano escribe puede ser un número de documento.
 *  6. **Sin reintento automático.** Tras un fallo, no antes de 2 s; tras un 429, no
 *     antes de que pase `Retry-After` (D-I7).
 *  7. **Nada de lo escrito sale de la petición:** no se registra en consola, no
 *     viaja a ningún otro sitio y los errores se tragan sin su texto.
 *
 * **Sólo en el cliente.** Todo el trabajo nace de un gesto de la persona (escribir,
 * pulsar una tecla), y los gestos no existen en el servidor; por eso nada aquí
 * arranca un temporizador ni una petición al renderizar. El estado es de cada
 * llamada y no del módulo, para no mezclar visitantes en el servidor.
 */
import { getCurrentScope, onScopeDispose, readonly, ref } from 'vue'

import type { components } from '~~/types/openapi'

/** Sugerencia tal como la devuelve el contrato (`SugerenciaBusquedaItem`). */
type SugerenciaApi = components['schemas']['SugerenciaBusquedaItem']

/** Lo único que el combobox necesita de cada sugerencia. */
export interface Sugerencia {
  id: number
  titulo: string
  categoria: string
  tipoRecurso: string
  /** Ruta dentro del sitio. Ya validada: empieza por `/` y no sale del dominio. */
  url: string
}

/**
 * Cómo terminó la última consulta resuelta. Mientras otra está en vuelo el
 * estado **no cambia**: la lista previa se conserva y nunca hay un vacío
 * intermedio (§4.4). Que algo está cargando lo dicen `cargando` y
 * `cargandoVisible`.
 */
export type EstadoSugerencias =
  | 'inactivo'
  | 'listo'
  | 'sin-resultados'
  | 'error'
  | 'limitado'

/** Lo que `pedir` entrega de una respuesta HTTP, sea cual sea su estado. */
export interface RespuestaHttp {
  status: number
  headers: { get: (nombre: string) => string | null }
  cuerpo: unknown
}

/** Hace la petición. Se inyecta para poder probar el resto sin red. */
export type PedirSugerencias = (
  consulta: string,
  limite: number,
  senal: AbortSignal,
) => Promise<RespuestaHttp>

export interface OpcionesSugerencias {
  pedir?: PedirSugerencias
}

/** Pausa desde la última tecla (D-I1). La auditoría decía 250; se aprobó 350. */
export const PAUSA_MS = 350
export const MINIMO_CARACTERES = 3
/** Es el máximo que acepta la API; el campo lleva el mismo `maxlength`. */
export const MAXIMO_CARACTERES = 120
/** Cuántas sugerencias se piden y se enseñan (D-I2). La API admite hasta 10. */
export const LIMITE_SUGERENCIAS = 6
/** Tras este tiempo sin respuesta aparece el indicador de carga (§4.4). */
export const INDICADOR_CARGA_MS = 200
export const CACHE_VIGENCIA_MS = 60_000
export const CACHE_MAXIMO = 30
/** Tras un fallo no se vuelve a preguntar antes de este tiempo (§4.5, regla 6). */
export const ESPERA_TRAS_FALLO_MS = 2000
/**
 * Tope de `Retry-After` y valor cuando falta o no se entiende. El límite de la
 * API es «por minuto», así que esperar más de 60 s nunca hace falta, y sin
 * cabecera lo prudente es esperar la ventana entera.
 */
export const ESPERA_MAXIMA_429_S = 60

/**
 * Una ruta que pertenece a este sitio: empieza por `/` y no es una dirección sin
 * esquema (`//otro.sitio`) ni lleva barras invertidas, espacios o caracteres de
 * control, que algunos navegadores normalizan a otro destino. La API es de
 * confianza, pero lo que se navega con un Intro merece una segunda comprobación
 * en el borde.
 */
export function esRutaInterna(url: unknown): url is string {
  if (typeof url !== 'string') return false
  if (!url.startsWith('/') || url.startsWith('//')) return false
  // eslint-disable-next-line no-control-regex -- los caracteres de control son justo lo que se rechaza
  return !/[\\\s\u0000-\u001f\u007f]/.test(url)
}

/** Recorta, colapsa espacios y corta en el máximo que acepta la API. */
export function normalizarConsulta(texto: string): string {
  return texto.replace(/\s+/g, ' ').trim().slice(0, MAXIMO_CARACTERES)
}

/** Clave de la memoria: sin tildes ni mayúsculas, como las trata el servidor. */
function claveDeMemoria(consulta: string): string {
  return consulta
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
}

/**
 * Segundos que hay que esperar según `Retry-After`, que admite un número de
 * segundos o una fecha HTTP. Siempre entre 1 y 60; sin cabecera, o ilegible, 60.
 */
export function segundosDeEspera(cabecera: string | null, ahora: number = Date.now()): number {
  const valor = (cabecera ?? '').trim()
  let segundos = Number.NaN
  if (/^\d+$/.test(valor)) {
    segundos = Number.parseInt(valor, 10)
  } else if (valor !== '') {
    const fecha = Date.parse(valor)
    if (!Number.isNaN(fecha)) segundos = Math.ceil((fecha - ahora) / 1000)
  }
  if (Number.isNaN(segundos)) return ESPERA_MAXIMA_429_S
  return Math.min(Math.max(segundos, 1), ESPERA_MAXIMA_429_S)
}

/**
 * Convierte el cuerpo de la API en sugerencias, o `null` si no tiene la forma
 * del contrato. Las que no sirven (sin título, o con una `url` que no es del
 * sitio) se descartan una a una: un elemento malo no tira la lista entera.
 */
export function analizarSugerencias(cuerpo: unknown): Sugerencia[] | null {
  if (typeof cuerpo !== 'object' || cuerpo === null) return null
  const datos = (cuerpo as { data?: unknown }).data
  if (!Array.isArray(datos)) return null

  const validas: Sugerencia[] = []
  for (const crudo of datos as Partial<SugerenciaApi>[]) {
    if (typeof crudo !== 'object' || crudo === null) continue
    const { id, titulo, categoria, tipo_recurso: tipoRecurso, url } = crudo
    if (
      typeof id !== 'number'
      || typeof titulo !== 'string'
      || titulo.trim() === ''
      || typeof categoria !== 'string'
      || typeof tipoRecurso !== 'string'
      || !esRutaInterna(url)
    ) {
      continue
    }
    validas.push({ id, titulo, categoria, tipoRecurso, url })
  }
  return validas.slice(0, LIMITE_SUGERENCIAS)
}

/** El `$fetch.raw` de Nuxt, en lo que esta función necesita de él. */
export type FetchCrudo = (
  url: string,
  opciones: {
    query: Record<string, string | number>
    signal: AbortSignal
    ignoreResponseError: true
  },
) => Promise<{ status: number, headers: { get: (nombre: string) => string | null }, _data?: unknown }>

/**
 * Construye la petición real a `GET /busqueda/sugerencias`. Va al navegador
 * —no por el servidor del sitio— a propósito: así la API ve la IP del
 * ciudadano y el límite por IP se reparte entre personas (ADR-0018).
 * `ignoreResponseError` hace que un 429 o un 422 lleguen como respuesta y no
 * como excepción, porque cada uno tiene su tratamiento.
 */
export function crearPeticionSugerencias(fetcher: FetchCrudo, urlApi: string): PedirSugerencias {
  const base = urlApi.replace(/\/+$/, '')
  return async (consulta, limite, senal) => {
    const respuesta = await fetcher(`${base}/busqueda/sugerencias`, {
      query: { q: consulta, limite },
      signal: senal,
      ignoreResponseError: true,
    })
    return { status: respuesta.status, headers: respuesta.headers, cuerpo: respuesta._data }
  }
}

interface EntradaDeMemoria {
  guardadaEn: number
  sugerencias: Sugerencia[]
}

export function useSugerenciasBusqueda(opciones: OpcionesSugerencias = {}) {
  // La petición real se crea al primer uso, no al montar: un buscador que no
  // enseña sugerencias (el del catálogo) no debe tocar `$fetch` ni la configuración.
  let pedirReal: PedirSugerencias | undefined
  const pedir: PedirSugerencias =
    opciones.pedir ?? ((consulta, limite, senal) => (pedirReal ??= pedirPorDefecto())(consulta, limite, senal))

  const sugerencias = ref<Sugerencia[]>([])
  const estado = ref<EstadoSugerencias>('inactivo')
  const esperaSegundos = ref(0)
  const cargando = ref(false)
  const cargandoVisible = ref(false)

  const memoria = new Map<string, EntradaDeMemoria>()

  let textoActual = ''
  let enComposicion = false
  let numeroPeticion = 0
  let consultaEnVuelo: string | null = null
  let controlador: AbortController | null = null
  let bloqueadoHasta = 0
  let temporizadorPausa: ReturnType<typeof setTimeout> | undefined
  let temporizadorCarga: ReturnType<typeof setTimeout> | undefined
  let temporizadorEspera: ReturnType<typeof setTimeout> | undefined

  function detenerPausa(): void {
    clearTimeout(temporizadorPausa)
    temporizadorPausa = undefined
  }

  /** Cancela lo que esté en vuelo y hace que su respuesta, si llega, se ignore. */
  function soltarPeticion(): void {
    numeroPeticion += 1
    controlador?.abort()
    controlador = null
    consultaEnVuelo = null
    clearTimeout(temporizadorCarga)
    temporizadorCarga = undefined
    cargando.value = false
    cargandoVisible.value = false
  }

  function guardarEnMemoria(consulta: string, lista: Sugerencia[]): void {
    const clave = claveDeMemoria(consulta)
    memoria.delete(clave)
    memoria.set(clave, { guardadaEn: Date.now(), sugerencias: lista })
    if (memoria.size > CACHE_MAXIMO) {
      const masAntigua = memoria.keys().next().value
      if (masAntigua !== undefined) memoria.delete(masAntigua)
    }
  }

  function leerDeMemoria(consulta: string): Sugerencia[] | null {
    const clave = claveDeMemoria(consulta)
    const entrada = memoria.get(clave)
    if (!entrada) return null
    if (Date.now() - entrada.guardadaEn >= CACHE_VIGENCIA_MS) {
      memoria.delete(clave)
      return null
    }
    return entrada.sugerencias
  }

  function aplicar(lista: Sugerencia[]): void {
    sugerencias.value = lista
    estado.value = lista.length > 0 ? 'listo' : 'sin-resultados'
    esperaSegundos.value = 0
  }

  function fallar(): void {
    estado.value = 'error'
    esperaSegundos.value = 0
    bloqueadoHasta = Date.now() + ESPERA_TRAS_FALLO_MS
  }

  function limitar(cabeceraReintento: string | null): void {
    const segundos = segundosDeEspera(cabeceraReintento)
    sugerencias.value = []
    estado.value = 'limitado'
    esperaSegundos.value = segundos
    bloqueadoHasta = Date.now() + segundos * 1000
    // Cuando pasa la espera el aviso deja de ser cierto: se retira solo. No se
    // vuelve a preguntar por su cuenta; la siguiente tecla lo hará (regla 6).
    clearTimeout(temporizadorEspera)
    temporizadorEspera = setTimeout(() => {
      if (estado.value === 'limitado') {
        estado.value = 'inactivo'
        esperaSegundos.value = 0
      }
      temporizadorEspera = undefined
    }, segundos * 1000)
  }

  async function lanzar(consulta: string): Promise<void> {
    if (consulta.length < MINIMO_CARACTERES) return
    // Un espacio de más al final no es otra consulta: no se cancela y repite.
    if (consulta === consultaEnVuelo) return
    // Pausa de 429 o de fallo: no se agrava el problema (D-I7).
    if (Date.now() < bloqueadoHasta) return

    const guardada = leerDeMemoria(consulta)
    if (guardada) {
      soltarPeticion()
      aplicar(guardada)
      return
    }

    soltarPeticion()
    const numero = numeroPeticion
    const propio = new AbortController()
    controlador = propio
    consultaEnVuelo = consulta
    cargando.value = true
    temporizadorCarga = setTimeout(() => {
      if (numero === numeroPeticion) cargandoVisible.value = true
    }, INDICADOR_CARGA_MS)

    let respuesta: RespuestaHttp
    try {
      respuesta = await pedir(consulta, LIMITE_SUGERENCIAS, propio.signal)
    } catch {
      // Cancelada por una consulta posterior, o fallo de red. Sólo el último
      // intento puede contar un fallo; el texto nunca se registra.
      if (numero === numeroPeticion) {
        soltarPeticion()
        fallar()
      }
      return
    }
    // Una respuesta que no es la última no pinta nada, ni siquiera un error.
    if (numero !== numeroPeticion) return
    soltarPeticion()

    if (respuesta.status === 429) {
      limitar(respuesta.headers.get('Retry-After'))
      return
    }
    // El cliente no manda menos de 3 ni más de 120, así que un 422 no debería
    // ocurrir; si ocurre se trata como «sin resultados», no como un fallo.
    if (respuesta.status === 422) {
      aplicar([])
      return
    }
    const lista = respuesta.status === 200 ? analizarSugerencias(respuesta.cuerpo) : null
    if (lista === null) {
      fallar()
      return
    }
    guardarEnMemoria(consulta, lista)
    aplicar(lista)
  }

  /** El texto del campo cambió. Pregunta a la API tras la pausa. */
  function escribir(texto: string): void {
    textoActual = normalizarConsulta(texto)
    detenerPausa()
    if (textoActual.length < MINIMO_CARACTERES) {
      // Se borró hasta quedar corto: nada que sugerir ni que esperar.
      reiniciar()
      return
    }
    if (enComposicion) return
    temporizadorPausa = setTimeout(() => {
      temporizadorPausa = undefined
      void lanzar(textoActual)
    }, PAUSA_MS)
  }

  /** Pregunta ya, sin esperar la pausa (↓ con el campo ya escrito, §4.3). */
  function pedirYa(texto: string): void {
    textoActual = normalizarConsulta(texto)
    detenerPausa()
    if (enComposicion) return
    void lanzar(textoActual)
  }

  function empezarComposicion(): void {
    enComposicion = true
    detenerPausa()
  }

  function terminarComposicion(texto: string): void {
    enComposicion = false
    escribir(texto)
  }

  /**
   * Deja de trabajar (Esc, Tab, perder el foco) sin tocar lo que ya se enseña.
   * Una respuesta que llegara después se ignora.
   */
  function cancelar(): void {
    detenerPausa()
    soltarPeticion()
  }

  /**
   * Deja el estado como al principio (campo vacío o limpiado). Conserva la
   * memoria y la pausa de un 429 o un fallo: borrar el campo no es una forma de
   * saltarse el límite.
   */
  function reiniciar(): void {
    cancelar()
    sugerencias.value = []
    if (estado.value !== 'limitado') {
      estado.value = 'inactivo'
      esperaSegundos.value = 0
    }
  }

  if (getCurrentScope()) {
    onScopeDispose(() => {
      cancelar()
      clearTimeout(temporizadorEspera)
    })
  }

  return {
    sugerencias: readonly(sugerencias),
    estado: readonly(estado),
    esperaSegundos: readonly(esperaSegundos),
    cargando: readonly(cargando),
    cargandoVisible: readonly(cargandoVisible),
    escribir,
    pedirYa,
    empezarComposicion,
    terminarComposicion,
    cancelar,
    reiniciar,
  }
}

/** La petición real, con la dirección de la API del entorno. */
function pedirPorDefecto(): PedirSugerencias {
  return crearPeticionSugerencias($fetch.raw as unknown as FetchCrudo, useRuntimeConfig().public.apiUrl)
}
