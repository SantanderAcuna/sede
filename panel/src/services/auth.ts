/**
 * Servicio de autenticación del panel.
 *
 * Se comunica con el backend a través del contrato OpenAPI:
 * POST /api/v1/panel/login
 * POST /api/v1/panel/logout
 * GET  /api/v1/panel/perfil
 */
import { http, desenvolver } from './http'
import type { ApiEnvelope } from '@/types/api'

export interface Credenciales {
  email: string
  password: string
}

export interface UsuarioItem {
  id: number
  type: 'usuario'
  email: string
  mfa_habilitado: boolean
  estado: string
  roles: Array<{
    id: number
    type: 'rol'
    nombre: string
    permisos: string[]
  }>
}

export interface RespuestaLogin {
  require_mfa: boolean
  mfa_token: string | null
  csrf_token: string
  user: UsuarioItem | null
}

export interface RespuestaPerfil {
  id: number
  type: 'usuario'
  email: string
  mfa_habilitado: boolean
  estado: string
  roles: Array<{
    id: number
    type: 'rol'
    nombre: string
    permisos: string[]
  }>
}

/**
 * Inicia sesión con credenciales.
 */
export async function login(credenciales: Credenciales): Promise<RespuestaLogin> {
  const respuesta = await http.post<ApiEnvelope<RespuestaLogin>>(
    '/panel/login',
    credenciales
  )
  return desenvolver(respuesta.data)
}

/**
 * Cierra la sesión actual.
 */
export async function logout(): Promise<void> {
  await http.post('/panel/logout')
}

/**
 * Obtiene el perfil del usuario autenticado.
 */
export async function perfil(): Promise<RespuestaPerfil> {
  const respuesta = await http.get<ApiEnvelope<RespuestaPerfil>>('/panel/perfil')
  return desenvolver(respuesta.data)
}
