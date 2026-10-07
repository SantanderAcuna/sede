/**
 * Sesión del panel.
 *
 * La sesión se gestiona con cookie HttpOnly via Sanctum (cookie-based SPA).
 * No se usa localStorage ni tokens Bearer. La cookie de sesión se envía
 * automáticamente en cada petición (withCredentials: true).
 *
 * La verificación de sesión al arrancar se hace llamando a /perfil:
 * si devuelve 401, la cookie no es válida y se redirige al login.
 */
import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { login as loginApi, logout as logoutApi, perfil as perfilApi, type Credenciales, type UsuarioItem } from '@/services/auth'

export interface UsuarioSesion {
  id: number
  email: string
  nombre: string
  permisos: string[]
}

export const useSesionStore = defineStore('sesion', () => {
  // Estado en memoria — la sesión real vive en la cookie HttpOnly del navegador.
  const usuario = ref<UsuarioSesion | null>(null)
  const inicializado = ref(false)
  /** Promise de la inicialización en curso. Permite que el guardia espere. */
  let initPromise: Promise<void> | null = null
  /** Bandera que impide llamadas Concurrentes a cerrarSesion. */
  let isLoggingOut = false

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
   *
   * El flujo internally ya hace el handshake CSRF y establece la cookie
   * de sesión HttpOnly. Solo se guarda el usuario en memoria.
   */
  async function iniciarSesion(credenciales: Credenciales): Promise<void> {
    const respuesta = await loginApi(credenciales)

    if (respuesta.user) {
      usuario.value = {
        id: respuesta.user.id,
        email: respuesta.user.email,
        nombre: respuesta.user.email.split('@')[0] ?? 'usuario',
        permisos: extraerPermisos(respuesta.user.roles),
      }
    }
  }

  /**
   * Cierra la sesión actual.
   *
   * Es idempotente: llamadas Concurrentesdevuelven la misma promesa.
   * NO redirige — el chiamante decide qué hacer tras el cierre.
   * Resetea initPromise para que el guardia no restaure la sesión tras el logout.
   */
  async function cerrarSesion(): Promise<void> {
    if (isLoggingOut) return initPromise ?? Promise.resolve()

    isLoggingOut = true
    try {
      await logoutApi()
    } catch {
      // Si el servidor rechaza o hay red, la sesión queda invalidate de todas
      // formas. No interesa propagar el error — el estado local se limpia.
    } finally {
      usuario.value = null
      inicializado.value = false
      initPromise = null
      isLoggingOut = false
    }
  }

  /**
   * Verifica la sesión existente llamando a /perfil.
   *
   * Se llama al arrancar la aplicación (en main.ts) antes de pintar nada.
   * La cookie de sesión se envía automáticamente con withCredentials.
   * Si /perfil devuelve 401, la sesión no es válida.
   */
  async function init(): Promise<void> {
    if (inicializado.value) return

    // Si ya hay una inicialización en curso, devolver esa misma promesa
    // para que el guardia pueda esperar.
    if (initPromise) return initPromise

    initPromise = (async () => {
      try {
        const perfil = await perfilApi()
        usuario.value = {
          id: perfil.id,
          email: perfil.email,
          nombre: perfil.email.split('@')[0] ?? 'usuario',
          permisos: extraerPermisos(perfil.roles),
        }
      } catch {
        // Sin sesión válida — el guardia del router redirigirá al login.
        usuario.value = null
      } finally {
        inicializado.value = true
        initPromise = null
      }
    })()

    return initPromise
  }

  return {
    usuario,
    inicializado,
    iniciada,
    permisos,
    tienePermiso,
    iniciarSesion,
    cerrarSesion,
    init,
  }
})
