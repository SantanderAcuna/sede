/**
 * Lógica pura del carrusel de la portada: pasar entre lo que entrega la API y lo que edita
 * el formulario, comprobar antes de enviar lo que el servidor exige y reordenar.
 *
 * El servidor valida siempre. Aquí se evita enviar lo que rechazaría y se explica en
 * palabras de quien edita. El texto alternativo es obligatorio con imagen
 * (Res. MinTIC 1519/2020, Anexo 2).
 */
import type { BannerEntrada, BannerPanelItem, EstadoBanner } from '@/types/api'

export const TITULO_MAX = 80
export const DESCRIPCION_MAX = 200
export const ALT_MAX = 250
export const ENLACE_MAX = 500

export interface FormularioBanner {
  titulo: string
  descripcion: string
  enlace: string
  /** `AAAA-MM-DDTHH:mm` local; vacío = sin límite. */
  vigente_desde: string
  vigente_hasta: string
  activo: boolean
  imagen_alt: string
}

export interface ContextoImagen {
  tieneImagen: boolean
  imagenNueva: boolean
}

export type CampoBanner = 'titulo' | 'descripcion' | 'enlace' | 'vigente_desde' | 'vigente_hasta' | 'imagen' | 'imagen_alt'

export interface ProblemaDeCampo {
  campo: CampoBanner
  mensaje: string
}

const dos = (n: number): string => String(n).padStart(2, '0')

function aCampoLocal(iso: string | null | undefined): string {
  if (!iso) return ''
  const f = new Date(iso)
  if (Number.isNaN(f.getTime())) return ''
  return `${f.getFullYear()}-${dos(f.getMonth() + 1)}-${dos(f.getDate())}T${dos(f.getHours())}:${dos(f.getMinutes())}`
}

function aIsoConZona(local: string): string {
  const f = new Date(local)
  const minutos = -f.getTimezoneOffset()
  const signo = minutos >= 0 ? '+' : '-'
  const abs = Math.abs(minutos)
  return `${local}:00${signo}${dos(Math.floor(abs / 60))}:${dos(abs % 60)}`
}

/** Ruta del sitio (`/transparencia`) o dirección `https://`. Vacío vale: el enlace es opcional. */
export function enlaceValido(valor: string): boolean {
  if (valor === '') return true
  if (valor.length > ENLACE_MAX) return false
  return /^\/(?!\/)[^\s\\]*$/.test(valor) || /^https:\/\/[^\s/$.?#].[^\s]*$/i.test(valor)
}

export function formularioVacio(): FormularioBanner {
  return { titulo: '', descripcion: '', enlace: '', vigente_desde: '', vigente_hasta: '', activo: false, imagen_alt: '' }
}

export function formularioDesde(b: BannerPanelItem): FormularioBanner {
  return {
    titulo: b.titulo,
    descripcion: b.descripcion ?? '',
    enlace: b.enlace ?? '',
    vigente_desde: aCampoLocal(b.vigente_desde),
    vigente_hasta: aCampoLocal(b.vigente_hasta),
    activo: b.activo,
    imagen_alt: b.imagen?.alt ?? '',
  }
}

/** Sólo viajan los campos que cambiaron; los vacíos opcionales viajan como `null`. */
export function entradaDesde(
  f: FormularioBanner,
  original: FormularioBanner,
  opciones: { completa?: boolean } = {},
): BannerEntrada {
  const completa = opciones.completa === true
  const e: BannerEntrada = {}
  const cambio = (a: string, b: string): boolean => completa || a.trim() !== b.trim()

  if (cambio(f.titulo, original.titulo)) e.titulo = f.titulo.trim()
  if (cambio(f.descripcion, original.descripcion) && (completa ? f.descripcion.trim() !== '' : true)) {
    e.descripcion = f.descripcion.trim() === '' ? null : f.descripcion.trim()
  }
  if (cambio(f.enlace, original.enlace) && (completa ? f.enlace.trim() !== '' : true)) {
    e.enlace = f.enlace.trim() === '' ? null : f.enlace.trim()
  }
  if (f.vigente_desde !== original.vigente_desde) e.vigente_desde = f.vigente_desde === '' ? null : aIsoConZona(f.vigente_desde)
  if (f.vigente_hasta !== original.vigente_hasta) e.vigente_hasta = f.vigente_hasta === '' ? null : aIsoConZona(f.vigente_hasta)
  if (completa ? f.activo : f.activo !== original.activo) e.activo = f.activo
  if (f.imagen_alt.trim() !== original.imagen_alt.trim()) e.imagen_alt = f.imagen_alt.trim()
  return e
}

export function hayCambios(f: FormularioBanner, original: FormularioBanner): boolean {
  return JSON.stringify(f) !== JSON.stringify(original)
}

export function caracteresRestantes(texto: string, maximo: number): number {
  return maximo - texto.length
}

export function problemasPorCampo(f: FormularioBanner, imagen: ContextoImagen): ProblemaDeCampo[] {
  const p: ProblemaDeCampo[] = []
  const agregar = (campo: CampoBanner, mensaje: string): void => {
    p.push({ campo, mensaje })
  }

  if (f.titulo.trim() === '') agregar('titulo', 'Falta el título de la diapositiva.')
  else if (f.titulo.length > TITULO_MAX) agregar('titulo', `El título pasa de ${TITULO_MAX} caracteres (tiene ${f.titulo.length}).`)

  if (f.descripcion.length > DESCRIPCION_MAX) {
    agregar('descripcion', `La descripción pasa de ${DESCRIPCION_MAX} caracteres (tiene ${f.descripcion.length}).`)
  }

  if (!enlaceValido(f.enlace.trim())) {
    agregar('enlace', 'El enlace debe ser una ruta del sitio (por ejemplo /transparencia) o una dirección que empiece por https://.')
  }

  if (f.vigente_desde !== '' && f.vigente_hasta !== '' && new Date(f.vigente_hasta) <= new Date(f.vigente_desde)) {
    agregar('vigente_hasta', 'La fecha de fin debe ser posterior a la de inicio.')
  }

  const hayImagen = imagen.tieneImagen || imagen.imagenNueva
  if (f.activo && !hayImagen) agregar('imagen', 'Para publicar la diapositiva hace falta una imagen.')
  if (hayImagen) {
    if (f.imagen_alt.trim() === '') {
      agregar('imagen_alt', 'Escriba la descripción de la imagen (texto alternativo): es obligatoria para quien no puede verla.')
    } else if (f.imagen_alt.length > ALT_MAX) {
      agregar('imagen_alt', `La descripción de la imagen pasa de ${ALT_MAX} caracteres (tiene ${f.imagen_alt.length}).`)
    }
  }
  return p
}

/** Nueva posición de `uuid` en la lista (un lugar arriba con -1, abajo con +1). No muta. */
export function mover(uuids: string[], uuid: string, delta: -1 | 1): string[] {
  const i = uuids.indexOf(uuid)
  const j = i + delta
  if (i < 0 || j < 0 || j >= uuids.length) return [...uuids]
  const copia = [...uuids]
  ;[copia[i], copia[j]] = [copia[j] as string, copia[i] as string]
  return copia
}

const TEXTO_ESTADO: Record<EstadoBanner, string> = {
  borrador: 'Borrador',
  programado: 'Programada',
  vigente: 'Publicada',
  vencido: 'Vencida',
}

export function textoDeEstado(estado: EstadoBanner): string {
  return TEXTO_ESTADO[estado]
}

/** Frase que explica el estado a quien administra. */
export function explicacionDeEstado(estado: EstadoBanner): string {
  switch (estado) {
    case 'borrador':
      return 'No se ve en el sitio.'
    case 'programado':
      return 'Se verá desde la fecha de inicio.'
    case 'vigente':
      return 'Se ve ahora en la portada.'
    case 'vencido':
      return 'Pasó su fecha de fin: ya no se ve.'
  }
}
