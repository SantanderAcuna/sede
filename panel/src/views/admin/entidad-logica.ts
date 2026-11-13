/**
 * Lógica pura del formulario de la Entidad: pasar entre lo que entrega la API y lo que
 * edita el formulario, decidir qué se envía y comprobar lo que se puede comprobar antes.
 *
 * Fuera del componente para probarla sin montar nada. Los límites repiten los del servidor
 * (`ActualizarEntidadRequest`): el servidor siempre vuelve a validar.
 */
import type { EntidadInput, EntidadItem, EntidadPolitica, EntidadRed } from '@/types/api'

/** Campos de texto del formulario y su largo máximo (el del servidor). */
export const CAMPOS_TEXTO = {
  nombre: 200,
  sigla: 20,
  nit: 30,
  direccion: 240,
  municipio: 120,
  departamento: 120,
  pais: 80,
  telefono: 40,
  linea_atencion: 40,
  linea_gratuita: 40,
  linea_anticorrupcion: 40,
  correo_atencion: 240,
  correo_notificaciones_judiciales: 240,
  horario: 240,
  codigo_postal: 20,
  dominio: 240,
} as const

export type CampoTexto = keyof typeof CAMPOS_TEXTO

export const REDES_SOCIALES = [
  { valor: 'facebook', etiqueta: 'Facebook' },
  { valor: 'instagram', etiqueta: 'Instagram' },
  { valor: 'x', etiqueta: 'X (antes Twitter)' },
  { valor: 'youtube', etiqueta: 'YouTube' },
  { valor: 'linkedin', etiqueta: 'LinkedIn' },
] as const

export type FormularioEntidad = Record<CampoTexto, string> & {
  redes: EntidadRed[]
  politicas: EntidadPolitica[]
}

export interface ProblemaEntidad {
  /** `nombre`, `correo_atencion`… o `redes.2` / `politicas.0` para las listas. */
  campo: string
  mensaje: string
}

const CLAVES = Object.keys(CAMPOS_TEXTO) as CampoTexto[]

const ETIQUETAS: Record<CampoTexto, string> = {
  nombre: 'El nombre de la entidad',
  sigla: 'La sigla',
  nit: 'El NIT',
  direccion: 'La dirección',
  municipio: 'El municipio',
  departamento: 'El departamento',
  pais: 'El país',
  telefono: 'El teléfono principal',
  linea_atencion: 'La línea de atención',
  linea_gratuita: 'La línea gratuita',
  linea_anticorrupcion: 'La línea anticorrupción',
  correo_atencion: 'El correo de atención',
  correo_notificaciones_judiciales: 'El correo de notificaciones judiciales',
  horario: 'El horario',
  codigo_postal: 'El código postal',
  dominio: 'El dominio',
}

export function formularioDesde(entidad: EntidadItem): FormularioEntidad {
  const f = { redes: [], politicas: [] } as unknown as FormularioEntidad
  for (const clave of CLAVES) f[clave] = (entidad[clave] as string | null | undefined) ?? ''
  f.redes = (entidad.redes ?? []).map((r) => ({ red: r.red, url: r.url }))
  f.politicas = (entidad.politicas ?? []).map((p) => ({ slug: p.slug, nombre: p.nombre }))
  return f
}

/** Lo que se envía a `PATCH /panel/entidad`: solo lo que cambió. Vacío = `null` (borrar el dato). */
export function entradaDesde(formulario: FormularioEntidad, original: FormularioEntidad): EntidadInput {
  const entrada: Record<string, unknown> = {}
  for (const clave of CLAVES) {
    const nuevo = formulario[clave].trim()
    if (nuevo === original[clave].trim()) continue
    entrada[clave] = nuevo === '' ? null : nuevo
  }
  if (JSON.stringify(limpiarRedes(formulario.redes)) !== JSON.stringify(limpiarRedes(original.redes))) {
    entrada.redes = limpiarRedes(formulario.redes)
  }
  if (JSON.stringify(limpiarPoliticas(formulario.politicas)) !== JSON.stringify(limpiarPoliticas(original.politicas))) {
    entrada.politicas = limpiarPoliticas(formulario.politicas)
  }
  return entrada as EntidadInput
}

const limpiarRedes = (redes: readonly EntidadRed[]): EntidadRed[] =>
  redes.map((r) => ({ red: r.red, url: r.url.trim() }))
const limpiarPoliticas = (politicas: readonly EntidadPolitica[]): EntidadPolitica[] =>
  politicas.map((p) => ({ slug: p.slug.trim(), nombre: p.nombre.trim() }))

export function hayCambios(formulario: FormularioEntidad, original: FormularioEntidad): boolean {
  return Object.keys(entradaDesde(formulario, original)).length > 0
}

const CORREO = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
const DIRECCION_WEB = /^https?:\/\/\S+$/i

/** Lo que se puede decir antes de enviar. Lista vacía = se puede intentar guardar. */
export function problemasPorCampo(formulario: FormularioEntidad): ProblemaEntidad[] {
  const problemas: ProblemaEntidad[] = []
  const agregar = (campo: string, mensaje: string) => problemas.push({ campo, mensaje })

  if (formulario.nombre.trim() === '') agregar('nombre', 'Escriba el nombre de la entidad: es obligatorio.')

  for (const clave of CLAVES) {
    if (formulario[clave].trim().length > CAMPOS_TEXTO[clave]) {
      agregar(clave, `${ETIQUETAS[clave]} admite hasta ${CAMPOS_TEXTO[clave]} caracteres.`)
    }
  }

  for (const clave of ['correo_atencion', 'correo_notificaciones_judiciales'] as const) {
    const valor = formulario[clave].trim()
    if (valor !== '' && !CORREO.test(valor)) agregar(clave, `${ETIQUETAS[clave]} debe ser una dirección válida, como nombre@dominio.gov.co.`)
  }

  const dominio = formulario.dominio.trim()
  if (dominio !== '' && !DIRECCION_WEB.test(dominio)) {
    agregar('dominio', 'El dominio debe empezar por http:// o https://, sin espacios.')
  }

  formulario.redes.forEach((red, i) => {
    if (!DIRECCION_WEB.test(red.url.trim())) {
      agregar(`redes.${i}`, 'La dirección de la red social debe empezar por http:// o https://, sin espacios.')
    }
  })

  const vistos = new Set<string>()
  formulario.politicas.forEach((p, i) => {
    const slug = p.slug.trim()
    if (p.nombre.trim() === '') agregar(`politicas.${i}`, 'Cada política del pie necesita un nombre.')
    else if (slug === '') agregar(`politicas.${i}`, 'Elija a qué política lleva este enlace.')
    else if (vistos.has(slug)) agregar(`politicas.${i}`, 'Esta política ya está en la lista.')
    vistos.add(slug)
  })

  return problemas
}
