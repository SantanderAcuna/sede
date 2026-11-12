/**
 * Lógica pura de las vistas de usuarios y roles.
 *
 * La interfaz anticipa las reglas de seguridad solo para no ofrecer lo que el servidor va a
 * rechazar; quien las hace cumplir es el servidor (escalada de privilegios, uno mismo,
 * último súper administrador).
 */
import type { EstadoUsuarioPanel, RolPanelEntrada, RolPanelItem, UsuarioPanelEntrada, UsuarioPanelItem } from '@/types/api'

export const ROL_SUPER = 'super-admin'

export const ESTADOS: Array<{ valor: EstadoUsuarioPanel; etiqueta: string; ayuda: string }> = [
  { valor: 'activo', etiqueta: 'Activo', ayuda: 'Puede entrar al panel.' },
  { valor: 'inactivo', etiqueta: 'Inactivo', ayuda: 'No puede entrar; su cuenta se conserva.' },
  { valor: 'bloqueado', etiqueta: 'Bloqueado', ayuda: 'No puede entrar por una medida de seguridad.' },
  { valor: 'pendiente', etiqueta: 'Pendiente', ayuda: 'Aún no ha empezado a usar su cuenta.' },
]

export const etiquetaEstado = (estado: string): string => ESTADOS.find((e) => e.valor === estado)?.etiqueta ?? estado

export interface FormularioUsuario {
  name: string
  email: string
  password: string
  estado: EstadoUsuarioPanel
  roles: string[]
}

export const formularioVacioDeUsuario = (): FormularioUsuario => ({ name: '', email: '', password: '', estado: 'activo', roles: [] })

export const formularioDeUsuario = (u: UsuarioPanelItem): FormularioUsuario => ({
  name: u.name,
  email: u.email,
  password: '',
  estado: u.estado,
  roles: [...u.roles],
})

export interface RequisitoClave {
  texto: string
  cumple: boolean
}

/** Lo mismo que exige el servidor: 12 caracteres como mínimo, con letras y con números. */
export function requisitosDeClave(clave: string): RequisitoClave[] {
  return [
    { texto: 'Al menos 12 caracteres', cumple: clave.length >= 12 },
    { texto: 'Incluye letras', cumple: /\p{L}/u.test(clave) },
    { texto: 'Incluye al menos un número', cumple: /\p{N}/u.test(clave) },
  ]
}

const CORREO = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

export function problemasDeUsuario(f: FormularioUsuario, esNuevo: boolean): string[] {
  const problemas: string[] = []
  if (f.name.trim().length < 3) problemas.push('Escriba el nombre de la persona (al menos 3 caracteres).')
  if (!CORREO.test(f.email.trim())) problemas.push('Escriba un correo válido, por ejemplo nombre@santamarta.gov.co.')
  if (esNuevo || f.password !== '') {
    if (!requisitosDeClave(f.password).every((r) => r.cumple)) {
      problemas.push('La contraseña debe tener al menos 12 caracteres, con letras y números.')
    }
  }
  return problemas
}

const mismoConjunto = (a: string[], b: string[]): boolean => {
  const x = [...a].sort()
  const y = [...b].sort()
  return x.length === y.length && x.every((v, i) => v === y[i])
}

/** Solo viaja lo que cambió; la contraseña, únicamente si se escribió una. Sin original (alta), todo. */
export function cambiosDeUsuario(f: FormularioUsuario, original: FormularioUsuario | null): UsuarioPanelEntrada {
  if (original === null) {
    return { name: f.name.trim(), email: f.email.trim(), password: f.password, estado: f.estado, roles: [...f.roles] }
  }
  const cambios: UsuarioPanelEntrada = {}
  if (f.name.trim() !== original.name) cambios.name = f.name.trim()
  if (f.email.trim() !== original.email) cambios.email = f.email.trim()
  if (f.password !== '') cambios.password = f.password
  if (f.estado !== original.estado) cambios.estado = f.estado
  if (!mismoConjunto(f.roles, original.roles)) cambios.roles = [...f.roles]
  return cambios
}

export const hayCambiosDeUsuario = (f: FormularioUsuario, original: FormularioUsuario): boolean =>
  Object.keys(cambiosDeUsuario(f, original)).length > 0

export interface Administrador {
  esSuper: boolean
  permisos: string[]
}

/** Quien administra, a partir de los permisos de su sesión (`*` = súper administrador). */
export const administradorDe = (permisos: string[]): Administrador => ({ esSuper: permisos.includes('*'), permisos })

/** Los roles que esta persona puede asignar o quitar: dentro de sus permisos, y nunca el de súper administrador. */
export function rolesAsignables(roles: RolPanelItem[], quien: Administrador): RolPanelItem[] {
  if (quien.esSuper) return roles
  return roles.filter((r) => r.name !== ROL_SUPER && r.permisos.every((p) => quien.permisos.includes(p)))
}

export const puedeAdministrarA = (u: UsuarioPanelItem, quien: Administrador): boolean => !u.es_super_admin || quien.esSuper

export interface FormularioRol {
  name: string
  permisos: string[]
}

export function problemasDeRol(f: FormularioRol): string[] {
  return f.name.trim().length < 3 ? ['Escriba el nombre del rol (al menos 3 caracteres).'] : []
}

export function cambiosDeRol(f: FormularioRol, original: FormularioRol | null): RolPanelEntrada {
  if (original === null) return { name: f.name.trim(), permisos: [...f.permisos] }
  const cambios: RolPanelEntrada = {}
  if (f.name.trim() !== original.name) cambios.name = f.name.trim()
  if (!mismoConjunto(f.permisos, original.permisos)) cambios.permisos = [...f.permisos]
  return cambios
}

export const hayCambiosDeRol = (f: FormularioRol, original: FormularioRol): boolean =>
  Object.keys(cambiosDeRol(f, original)).length > 0
