/**
 * Sesión del panel.
 *
 * Conecta con el backend a través del servicio de autenticación.
 * La sesión vive **en memoria** y a propósito: un usuario o unos permisos
 * guardados en `localStorage` sobreviven al cierre de sesión del servidor y
 * siguen afirmando una identidad que ya caducó. El contrato ya transporta la
 * sesión en una cookie `HttpOnly` (`src/services/http.ts`), así que no hay nada
 * que persistir aquí.
 */
import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { login as loginApi, logout as logoutApi, perfil as perfilApi, type Credenciales, type UsuarioItem } from '@/services/auth'
import type { RespuestaLogin } from '@/services/auth'

export interface UsuarioSesion {
  id: number
  email: string
  nombre: string
  permisos: string[]
}

export const useSesionStore = defineStore('sesion', () => {
  const usuario = ref<UsuarioSesion | null>(null)
  const token = ref<string | null>(null)

  /** ¿Hay una sesión iniciada? */
  const iniciada = computed(() => usuario.value !== null)

  const permisos = computed(() => {
    const lista = usuario.value?.permisos ?? []
    return new Set(lista)
  })

  /**
   * Sin permiso declarado, la ruta se considera pública dentro del panel.
   */
  function tienePermiso(permiso?: string): boolean {
    if (!permiso) return true
    if (permisos.value.has('*')) return true
    return permisos.value.has(permiso)
  }

  /**
   * Extrae los permisos de los roles del usuario.
   */
  function extraerPermisos(roles: UsuarioItem['roles']): string[] {
    // El rol super-admin tiene acceso total, sin importar sus permisos declarados.
    for (const rol of roles) {
      if (rol.nombre === 'super-admin') {
        return ['*']
      }
    }

    const permisos = new Set<string>()
    for (const rol of roles) {
      if (rol.permisos.includes('*')) {
        permisos.add('*')
      }
      for (const p of rol.permisos) {
        permisos.add(p)
      }
    }
    return Array.from(permisos)
  }

  /**
   * Inicia sesión con credenciales.
   */
  async function iniciarSesion(credenciales: Credenciales): Promise<void> {
    const respuesta: RespuestaLogin = await loginApi(credenciales)
    token.value = respuesta.csrf_token

    if (respuesta.user) {
      usuario.value = {
        id: respuesta.user.id,
        email: respuesta.user.email,
        nombre: respuesta.user.email.split('@')[0],
        permisos: extraerPermisos(respuesta.user.roles),
      }
    }
  }

  /**
   * Cierra la sesión actual.
   */
  async function cerrarSesion(): Promise<void> {
    try {
      await logoutApi()
    } finally {
      usuario.value = null
      token.value = null
    }
  }

  /**
   * Carga el perfil del usuario autenticado.
   */
  async function cargarPerfil(): Promise<void> {
    const perfil = await perfilApi()
    usuario.value = {
      id: perfil.id,
      email: perfil.email,
      nombre: perfil.email.split('@')[0],
      permisos: extraerPermisos(perfil.roles),
    }
  }

  return {
    usuario,
    token,
    iniciada,
    permisos,
    tienePermiso,
    iniciarSesion,
    cerrarSesion,
    cargarPerfil,
  }
})
