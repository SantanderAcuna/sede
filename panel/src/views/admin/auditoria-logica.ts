/**
 * Auditoría: cómo se dice el detalle de un registro y qué se filtra.
 *
 * La frase de la acción (`descripcion`) y el nombre del módulo (`recurso_nombre`) llegan ya dichos desde el
 * servidor, que es la única fuente (y una prueba rastrea que no falte ninguna). Aquí solo se arma el DETALLE
 * a partir de `cambios`: el título o nombre, qué se intentaba y qué campos tocó o fallaron, con los nombres que
 * la persona ve en pantalla. Un dato que no se conoce se muestra tal cual, nunca se oculta.
 */

/** Los nombres de campo del servidor, como se ven en pantalla. */
const CAMPOS: Record<string, string> = {
  titulo: 'título',
  contenido: 'texto',
  descripcion: 'descripción',
  descripcion_corta: 'resumen',
  resumen: 'resumen',
  nombre: 'nombre',
  fecha_publicacion: 'fecha de publicación',
  destacada: 'destacada',
  activo: 'publicación',
  publicada: 'publicación',
  publicado: 'publicación',
  imagen_alt: 'texto de la imagen',
  enlace: 'enlace',
  vigente_desde: 'vigente desde',
  vigente_hasta: 'vigente hasta',
  secciones: 'secciones',
  version: 'versión',
  slug: 'dirección',
  telefono: 'teléfono',
  direccion: 'dirección',
  correo: 'correo',
  email: 'correo',
  modalidad: 'modalidad',
  tiene_costo: 'costo',
  url_ficha_gov_co: 'ficha GOV.CO',
  estado: 'estado',
  roles: 'roles',
  permisos: 'permisos',
}

export function nombreDeCampo(campo: string): string {
  return CAMPOS[campo] ?? campo
}

const RECHAZOS = new Set(['acceso_denegado', 'datos_rechazados', 'conflicto_rechazado'])

/** Un intento que el sistema no dejó pasar (a diferencia de un cambio hecho). */
export function esRechazo(accion: string): boolean {
  return RECHAZOS.has(accion)
}

const VERBOS: Record<string, string> = { POST: 'Crear', PUT: 'Cambiar', PATCH: 'Cambiar', DELETE: 'Eliminar' }

function lista(valor: unknown): string[] {
  return Array.isArray(valor) ? valor.map((v) => String(v)) : []
}

/** El módulo del que habla una ruta del panel: `api/v1/panel/noticias/{uuid}` → `noticias`. */
function moduloDeRuta(ruta: string): string {
  return /panel\/([a-z0-9-]+)/.exec(ruta)?.[1] ?? 'panel'
}

/** El detalle en una línea, o `''` si el registro no trae nada que decir. */
export function detalleDe(cambios: Record<string, unknown> | null | undefined): string {
  if (!cambios) return ''
  const partes: string[] = []

  const rotulo = [cambios.titulo, cambios.nombre, cambios.rol].find((v) => typeof v === 'string' && v !== '')
  if (typeof rotulo === 'string') partes.push(`«${rotulo}»`)

  if (typeof cambios.metodo === 'string' && typeof cambios.ruta === 'string') {
    partes.push(`${VERBOS[cambios.metodo] ?? cambios.metodo} en «${moduloDeRuta(cambios.ruta)}»`)
  }

  if (typeof cambios.correo === 'string' && cambios.correo !== '') partes.push(`Correo: ${cambios.correo}`)
  if (typeof cambios.email === 'string' && cambios.email !== '') partes.push(`Correo: ${cambios.email}`)
  if (typeof cambios.registro === 'string' && cambios.registro !== '') partes.push(`Registro: ${cambios.registro}`)
  if (typeof cambios.estado === 'string' && cambios.estado !== '') partes.push(`Estado: ${cambios.estado}`)
  if (typeof cambios.motivo === 'string' && cambios.motivo !== '') partes.push(`Motivo: ${cambios.motivo}`)

  const roles = lista(cambios.roles)
  if (roles.length > 0) partes.push(`Roles: ${roles.join(', ')}`)
  const permisos = lista(cambios.permisos)
  if (permisos.length > 0) partes.push(`Permisos: ${permisos.length}`)

  const campos = lista(cambios.campos)
  if (campos.length > 0) {
    const rechazo = cambios.estado === 422
    partes.push(`${rechazo ? 'Campos con error' : 'Campos'}: ${campos.map(nombreDeCampo).join(', ')}`)
  }

  return partes.join(' · ')
}

/** Los módulos que se pueden filtrar, en el orden del menú. El nombre llega igual desde el servidor en cada fila. */
export const MODULOS_FILTRABLES: ReadonlyArray<{ valor: string; nombre: string }> = [
  { valor: 'noticias', nombre: 'Noticias' },
  { valor: 'banners', nombre: 'Carrusel de la portada' },
  { valor: 'politicas', nombre: 'Políticas' },
  { valor: 'tramites', nombre: 'Trámites y servicios' },
  { valor: 'entidad', nombre: 'Entidad' },
  { valor: 'usuarios', nombre: 'Usuarios' },
  { valor: 'roles', nombre: 'Roles' },
  { valor: 'session', nombre: 'Sesión' },
]
