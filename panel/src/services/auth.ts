/**
 * Servicio de autenticación del panel (cookie-based SPA con Sanctum).
 *
 * Flujo según documentación Laravel 13.x Sanctum SPA:
 *   1. GET /sanctum/csrf-cookie  → establece cookie XSRF-TOKEN (HttpOnly=false)
 *   2. POST /panel/login (con header X-XSRF-TOKEN) → establece cookie de sesión
 *   3. GET /panel/perfil → verifica sesión (cookie de sesión se envía automáticamente)
 *
 * No se usan tokens Bearer ni localStorage.
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
 * Obtiene la cookie CSRF de Sanctum.
 *
 * Se usa fetch en lugar de Axios porque esta ruta vive fuera de /api/v1/ y
 * el path se reescribe en el proxy de Vite. Axios con withXSRFToken:true
 * leerá automáticamente el token de la cookie XSRF-TOKEN establecida aquí.
 */
async function csrf(): Promise<void> {
  await fetch('/sanctum/csrf-cookie', { credentials: 'include' })
}

/**
 * Inicia sesión con credenciales.
 *
 * El flujo CSRF es obligatorio antes del login en SPAs con Sanctum cookie-auth.
 */
export async function login(credenciales: Credenciales): Promise<RespuestaLogin> {
  await csrf()
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
