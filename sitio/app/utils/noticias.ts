/**
 * Lógica pura de las noticias del sitio (M01-6, RF-01-013).
 *
 * Está fuera de las páginas y de la tarjeta para poder probarla sin montar Nuxt.
 * Nada de aquí inventa contenido: si no hay dato, devuelve vacío o `null`.
 */

/** Lo que la API entrega de la imagen de una noticia. */
export interface ImagenNoticia {
  url: string
  alt: string | null
}

/** Una noticia como la entrega `GET /noticias` (el listado no trae el cuerpo). */
export interface NoticiaResumen {
  id: number
  type: 'noticias'
  slug: string
  titulo: string
  descripcion_corta: string
  fecha_publicacion: string
  destacada: boolean
  imagen: ImagenNoticia | null
  contenido?: string
}

/**
 * CT-01 (estado vacío): ni datos de ejemplo ni de otra entidad, y con el motivo.
 * Se dice qué pasa con la Entidad, no con el servidor: que la API no conteste es
 * otro estado (`error`) y no se confunde con éste.
 */
export const TEXTO_SIN_NOTICIAS =
  'No consta información publicada: la Entidad aún no ha publicado noticias.'

/** `error` = la API no contestó; `vacio` = contestó y no hay noticias; `con-noticias`. */
export type EstadoNoticias = 'error' | 'vacio' | 'con-noticias'

export function estadoDeNoticias(respuesta: { data: readonly unknown[] } | null): EstadoNoticias {
  if (respuesta === null) return 'error'
  return respuesta.data.length === 0 ? 'vacio' : 'con-noticias'
}

/**
 * «1 de noviembre de 2026», en español y en la hora de Colombia. La zona se fija
 * a propósito: sin ella, el servidor que renderiza (UTC) mostraría el día
 * siguiente para una noticia publicada de noche. Una fecha ilegible da `''`.
 */
export function fechaLarga(iso: string): string {
  const fecha = new Date(iso)
  if (Number.isNaN(fecha.getTime())) return ''

  return new Intl.DateTimeFormat('es-CO', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    timeZone: 'America/Bogota',
  }).format(fecha)
}

/**
 * La dirección de la imagen, sólo si es la ruta estable de la propia API. Una
 * URL absoluta, de protocolo relativo o con otro esquema no se pinta: el sitio
 * no carga imágenes de donde la API no las sirve.
 */
export function urlDeImagen(imagen: ImagenNoticia | null): string | null {
  if (imagen === null) return null
  return /^\/api\/v1\/noticias\/[a-z0-9-]+\/imagen$/.test(imagen.url) ? imagen.url : null
}
