/**
 * Sesión del panel.
 *
 * La sesión se persiste en localStorage para sobrevivir a F5. El token de
 * Sanctum viaja como Bearer en el header Authorization (no en cookie), y
 * localStorage permite recuperarlo tras una recarga sin pedir credenciales.
 *
 * El riesgo de que un token caducado persista se mitiga verificando con
 * `/perfil` al arrancar: si el servidor rechaza el token, se descarta y
 * se redirige al login.
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

const LLAVE_SESION = 'sede.panel.sesion'

/**
 * Lee la sesión persistida de localStorage.
 * Devuelve null si no hay nada válido.
 */
function leerSesionPersistida(): { token: string; usuario: UsuarioSesion } | null {
  try {
    const raw = localStorage.getItem(LLAVE_SESION)
    if (!raw) return null
    const parsed = JSON.parse(raw) as { token: string; usuario: UsuarioSesion }
    if (!parsed.token || !parsed.usuario) return null
    return parsed
  } catch {
    return null
  }
}

/**
 * Persiste la sesión en localStorage.
 */
function persistirSesion(token: string, usuario: UsuarioSesion): void {
  try {
    localStorage.setItem(LLAVE_SESION, JSON.stringify({ token, usuario }))
  } catch {
    // Si localStorage falla (cuota, privado), no es crítico.
  }
}

/**
 * Borra la sesión persistida de localStorage.
 */
function borrarSesionPersistida(): void {
  try {
    localStorage.removeItem(LLAVE_SESION)
  } catch {
    // Ignorar errores de localStorage.
  }
}

export const useSesionStore = defineStore('sesion', () => {
  // Estado en memoria.
  const usuario = ref<UsuarioSesion | null>(null)
  const token = ref<string | null>(null)
  const inicializado = ref(false)

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
    for (const rol of roles) {
      if (rol.nombre === 'super-admin') {
        return ['*']
      }
    }

    const permisosLista = new Set<string>()
    for (const rol of roles) {
      if (rol.permisos.includes('*')) {
        permisosLista.add('*')
      }
      for (const p of rol.permisos) {
        permisosLista.add(p)
      }
    }
    return Array.from(permisosLista)
  }

  /**
   * Inicia sesión con credenciales.
   */
  async function iniciarSesion(credenciales: Credenciales): Promise<void> {
    const respuesta: RespuestaLogin = await loginApi(credenciales)
    token.value = respuesta.csrf_token

    if (respuesta.user) {
      const usuarioSesion: UsuarioSesion = {
        id: respuesta.user.id,
        email: respuesta.user.email,
        nombre: respuesta.user.email.split('@')[0],
        permisos: extraerPermisos(respuesta.user.roles),
      }
      usuario.value = usuarioSesion
      persistirSesion(respuesta.csrf_token, usuarioSesion)
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
      inicializado.value = false
      borrarSesionPersistida()
    }
  }

  /**
   * Carga el perfil del usuario autenticado desde el servidor.
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

  /**
   * Restaura la sesión desde localStorage y verifica con el servidor.
   *
   * Se llama al arrancar la aplicación (en main.ts) antes de pintar nada.
   * Primero intenta restaurar desde localStorage de forma síncrona para que
   * la UI no parpadee. Después verifica con el servidor en segundo plano.
   *
   * Es idempotente: si ya se intentó antes, no vuelve a intentar.
   */
  async function init(): Promise<void> {
    if (inicializado.value) return

    // 1. Restaurar desde localStorage de forma síncrona (evita parpadeo).
    const persistida = leerSesionPersistida()
    if (persistida) {
      token.value = persistida.token
      usuario.value = persistida.usuario
      inicializado.value = true // Marcar antes de la llamada async.
    }

    // 2. Verificar con el servidor en segundo plano.
    try {
      await cargarPerfil()
      // El servidor valida el token. Si llega aquí, la sesión es válida.
      // Actualizar con datos del servidor por si cambiaron.
      if (usuario.value) {
        persistirSesion(token.value!, usuario.value)
      }
    } catch {
      // Token inválido o expirado. Limpiar todo y dejar que el guardia
      // del router redirija al login.
      usuario.value = null
      token.value = null
      inicializado.value = true
      borrarSesionPersistida()
      return
    }

    inicializado.value = true
  }

  return {
    usuario,
    token,
    inicializado,
    iniciada,
    permisos,
    tienePermiso,
    iniciarSesion,
    cerrarSesion,
    cargarPerfil,
    init,
  }
})
