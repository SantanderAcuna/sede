/**
 * Lógica de la página de resultados de la búsqueda (T-22, E3-3).
 *
 * Todo lo que no necesita Nuxt ni el DOM vive aquí, para poder probarlo con
 * `vitest` sin levantar el sitio: qué dice la URL, cómo se lee la respuesta de la
 * API, cómo se escribe una fecha y cómo se recorta un extracto. La página sólo
 * conecta estas piezas con la ruta y la plantilla.
 *
 * Las reglas son las de `docs/busqueda-interaccion.md` §5.
 */
import { esRutaInterna, MAXIMO_CARACTERES, MINIMO_CARACTERES } from './useSugerenciasBusqueda'

/** Resultados por página (§5.1). */
export const RESULTADOS_POR_PAGINA = 10

/** Largo máximo del extracto que se enseña (§5.1). */
export const EXTRACTO_MAXIMO = 200

export type TipoRecurso = 'tramite' | 'politica'

/** El filtro de la URL: vacío es «Todo». */
export type FiltroTipo = '' | TipoRecurso

export interface ResultadoBusqueda {
  id: number
  titulo: string
  categoria: string
  tipoRecurso: TipoRecurso
  extracto: string | null
  /** `AAAA-MM-DD`, tal como llega, o `null`. */
  fecha: string | null
  url: string
}

export interface PaginaDeResultados {
  resultados: ResultadoBusqueda[]
  total: number
  ultimaPagina: number
}

/** Lo que dice la URL sobre el término, antes de preguntarle nada a la API. */
export type EstadoDelTermino = 'sin-termino' | 'corta' | 'larga' | 'valida'

/**
 * Colapsa espacios y recorta, **sin** cortar en el máximo: la página tiene que
 * poder decir «use menos de 120 caracteres», y para eso necesita saber que se
 * pasó.
 */
export function limpiarTermino(valor: unknown): string {
  const texto = Array.isArray(valor) ? valor[0] : valor
  return typeof texto === 'string' ? texto.replace(/\s+/g, ' ').trim() : ''
}

export function estadoDelTermino(termino: string): EstadoDelTermino {
  if (termino === '') return 'sin-termino'
  if (termino.length < MINIMO_CARACTERES) return 'corta'
  if (termino.length > MAXIMO_CARACTERES) return 'larga'
  return 'valida'
}

/** `tipo` de la URL: cualquier valor que no sea uno de los dos tipos es «Todo». */
export function leerFiltro(valor: unknown): FiltroTipo {
  const texto = Array.isArray(valor) ? valor[0] : valor
  return texto === 'tramite' || texto === 'politica' ? texto : ''
}

/** `page` de la URL: un entero desde 1; lo demás es la primera página. */
export function leerPagina(valor: unknown): number {
  const texto = Array.isArray(valor) ? valor[0] : valor
  if (typeof texto !== 'string' || !/^\d{1,6}$/.test(texto)) return 1
  return Math.max(1, Number.parseInt(texto, 10))
}

function esObjeto(valor: unknown): valor is Record<string, unknown> {
  return typeof valor === 'object' && valor !== null && !Array.isArray(valor)
}

function leerResultado(crudo: unknown): ResultadoBusqueda | null {
  if (!esObjeto(crudo)) return null
  const { id, titulo, categoria, tipo_recurso: tipoRecurso, extracto, fecha, url } = crudo
  if (typeof id !== 'number' || typeof titulo !== 'string' || titulo.trim() === '') return null
  if (typeof categoria !== 'string') return null
  if (tipoRecurso !== 'tramite' && tipoRecurso !== 'politica') return null
  // El resultado lleva a la dirección que manda la API: sólo si es una ruta del sitio.
  if (!esRutaInterna(url)) return null
  return {
    id,
    titulo,
    categoria,
    tipoRecurso,
    extracto: typeof extracto === 'string' && extracto.trim() !== '' ? extracto : null,
    fecha: typeof fecha === 'string' && /^\d{4}-\d{2}-\d{2}/.test(fecha) ? fecha.slice(0, 10) : null,
    url,
  }
}

/**
 * Lee el cuerpo de `GET /buscar`. Devuelve `null` si no tiene la forma del
 * contrato (se trata como un fallo del servicio, no como «sin resultados»);
 * los resultados sueltos que no la tengan se descartan.
 */
export function analizarResultados(cuerpo: unknown): PaginaDeResultados | null {
  if (!esObjeto(cuerpo) || !Array.isArray(cuerpo.data) || !esObjeto(cuerpo.meta)) return null
  const { total, last_page: ultimaPagina } = cuerpo.meta
  if (typeof total !== 'number' || !Number.isInteger(total) || total < 0) return null
  if (typeof ultimaPagina !== 'number' || !Number.isInteger(ultimaPagina) || ultimaPagina < 1) return null
  const resultados = cuerpo.data.map(leerResultado).filter((r): r is ResultadoBusqueda => r !== null)
  return { resultados, total, ultimaPagina }
}

const FORMATO_FECHA = new Intl.DateTimeFormat('es-CO', {
  day: 'numeric',
  month: 'long',
  year: 'numeric',
  // La fecha es de calendario, sin hora: en UTC no se corre un día según dónde se lea.
  timeZone: 'UTC',
})

/**
 * «Publicado el 14 de marzo de 2026» (trámites: la fecha en que la sede lo
 * publicó) o «Vigente desde el 1 de enero de 2026» (políticas). `null` si no hay
 * fecha o no es una fecha: no se dibuja nada.
 */
export function etiquetaDeFecha(tipo: TipoRecurso, fecha: string | null): string | null {
  if (fecha === null) return null
  const momento = new Date(`${fecha}T00:00:00Z`)
  if (Number.isNaN(momento.getTime())) return null
  const escrita = FORMATO_FECHA.format(momento)
  return tipo === 'tramite' ? `Publicado el ${escrita}` : `Vigente desde el ${escrita}`
}

/** El extracto como máximo en 200 caracteres, cortado en el último espacio. */
export function recortarExtracto(extracto: string): string {
  if (extracto.length <= EXTRACTO_MAXIMO) return extracto
  const corte = extracto.slice(0, EXTRACTO_MAXIMO)
  const ultimoEspacio = corte.lastIndexOf(' ')
  return `${(ultimoEspacio > 0 ? corte.slice(0, ultimoEspacio) : corte).trimEnd()}…`
}

/** «N resultados para «q».» (§7). */
export function textoResumen(total: number, termino: string): string {
  return `${total} ${total === 1 ? 'resultado' : 'resultados'} para «${termino}».`
}
