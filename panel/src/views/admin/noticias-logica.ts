/**
 * Lógica pura de la pantalla de noticias: pasar entre lo que entrega la API y lo
 * que edita el formulario, y comprobar antes de enviar lo que el servidor exige.
 *
 * El servidor valida siempre (título ≤ 150, descripción ≤ 200, imagen con texto
 * alternativo para publicar). Aquí se evita enviar lo que rechazaría y se explica
 * en palabras de quien edita. El texto alternativo es obligatorio aquí mismo:
 * una imagen sin descripción no pasa (Res. MinTIC 1519/2020, Anexo 2).
 */
import type { NoticiaInput, NoticiaPanelItem } from '@/types/api'

export const TITULO_MAX = 150
export const DESCRIPCION_MAX = 200
export const ALT_MAX = 250

export interface FormularioNoticia {
  titulo: string
  descripcion_corta: string
  /** Texto plano; un párrafo por bloque, separados por una línea en blanco. */
  contenido: string
  /** `AAAA-MM-DDTHH:mm`, como lo pide el campo de fecha y hora del navegador. */
  fecha_publicacion: string
  destacada: boolean
  activo: boolean
  imagen_alt: string
}

export interface ContextoImagen {
  /** La noticia ya tiene imagen guardada en el servidor. */
  tieneImagen: boolean
  /** Se eligió una imagen nueva que aún no se ha subido. */
  imagenNueva: boolean
}

const dos = (n: number): string => String(n).padStart(2, '0')

/** ISO con zona → `AAAA-MM-DDTHH:mm` en hora local. */
function aCampoLocal(iso: string): string {
  const f = new Date(iso)
  if (Number.isNaN(f.getTime())) return ''
  return `${f.getFullYear()}-${dos(f.getMonth() + 1)}-${dos(f.getDate())}T${dos(f.getHours())}:${dos(f.getMinutes())}`
}

/** `AAAA-MM-DDTHH:mm` local → ISO 8601 con la zona de quien edita. */
function aIsoConZona(local: string): string {
  const f = new Date(local)
  const minutos = -f.getTimezoneOffset()
  const signo = minutos >= 0 ? '+' : '-'
  const abs = Math.abs(minutos)
  return `${local}:00${signo}${dos(Math.floor(abs / 60))}:${dos(abs % 60)}`
}

export function formularioVacio(ahora: Date = new Date()): FormularioNoticia {
  return {
    titulo: '',
    descripcion_corta: '',
    contenido: '',
    fecha_publicacion: aCampoLocal(ahora.toISOString()),
    destacada: false,
    activo: false,
    imagen_alt: '',
  }
}

export function formularioDesde(noticia: NoticiaPanelItem): FormularioNoticia {
  return {
    titulo: noticia.titulo,
    descripcion_corta: noticia.descripcion_corta,
    contenido: noticia.contenido ?? '',
    fecha_publicacion: aCampoLocal(noticia.fecha_publicacion),
    destacada: noticia.destacada,
    activo: noticia.activo,
    imagen_alt: noticia.imagen?.alt ?? '',
  }
}

/**
 * Lo que se envía al servidor: sólo los campos que cambiaron.
 *
 * Al **crear** (`completa`) la fecha de publicación viaja siempre: el servidor la
 * exige, y la que trae el formulario por defecto no es un «cambio» de la persona,
 * así que sin esto no se enviaba y el guardado fallaba con un 422.
 */
export function entradaDesde(
  formulario: FormularioNoticia,
  original: FormularioNoticia,
  opciones: { completa?: boolean } = {},
): NoticiaInput {
  const entrada: NoticiaInput = {}

  if (formulario.titulo.trim() !== original.titulo.trim()) entrada.titulo = formulario.titulo.trim()
  if (formulario.descripcion_corta.trim() !== original.descripcion_corta.trim()) {
    entrada.descripcion_corta = formulario.descripcion_corta.trim()
  }
  if (formulario.contenido.trim() !== original.contenido.trim()) entrada.contenido = formulario.contenido.trim()
  if (
    formulario.fecha_publicacion !== '' &&
    (opciones.completa === true || formulario.fecha_publicacion !== original.fecha_publicacion)
  ) {
    entrada.fecha_publicacion = aIsoConZona(formulario.fecha_publicacion)
  }
  if (formulario.destacada !== original.destacada) entrada.destacada = formulario.destacada
  if (formulario.activo !== original.activo) entrada.activo = formulario.activo
  if (formulario.imagen_alt.trim() !== original.imagen_alt.trim()) entrada.imagen_alt = formulario.imagen_alt.trim()

  return entrada
}

export function hayCambios(formulario: FormularioNoticia, original: FormularioNoticia): boolean {
  return JSON.stringify(formulario) !== JSON.stringify(original)
}

/** Cuántos caracteres quedan; negativo si se pasó. */
export function caracteresRestantes(texto: string, maximo: number): number {
  return maximo - texto.length
}

/** Lo que se puede decir antes de enviar. Lista vacía = se puede intentar guardar. */
export function problemasLocales(formulario: FormularioNoticia, imagen: ContextoImagen): string[] {
  const problemas: string[] = []

  if (formulario.titulo.trim() === '') problemas.push('Falta el título de la noticia.')
  else if (formulario.titulo.length > TITULO_MAX) {
    problemas.push(`El título pasa de ${TITULO_MAX} caracteres (tiene ${formulario.titulo.length}).`)
  }

  if (formulario.descripcion_corta.trim() === '') problemas.push('Falta la descripción corta.')
  else if (formulario.descripcion_corta.length > DESCRIPCION_MAX) {
    problemas.push(`La descripción corta pasa de ${DESCRIPCION_MAX} caracteres (tiene ${formulario.descripcion_corta.length}).`)
  }

  if (formulario.contenido.trim() === '') problemas.push('Falta el contenido de la noticia.')

  const hayImagen = imagen.tieneImagen || imagen.imagenNueva
  if (formulario.activo && !hayImagen) problemas.push('Para publicar la noticia hace falta una imagen.')

  if (hayImagen) {
    if (formulario.imagen_alt.trim() === '') {
      problemas.push('Escriba la descripción de la imagen (texto alternativo): es obligatoria para quien no puede verla.')
    } else if (formulario.imagen_alt.length > ALT_MAX) {
      problemas.push(`La descripción de la imagen pasa de ${ALT_MAX} caracteres (tiene ${formulario.imagen_alt.length}).`)
    }
  }

  return problemas
}
