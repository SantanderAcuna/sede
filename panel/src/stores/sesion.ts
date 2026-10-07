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
  /**
   * Flag persistente para coordinar inicialización entre recargas de página.
   * Pinia se re-inicializa en cada F5 (módulo re-evaluado), pero sessionStorage
   * persiste en la misma pestaña. Sin esto, múltiples F5 crean promesas concurrentes
   * de init() y la última en completarse (aunque sea un 401 de una petición older)
   * determina el estado final, perdiendo la sesión válida.
   */
  const INIT_KEY = 'sesion:initInProgress'

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
   * Es idempotente: llamadas Concurrentes devuelven la misma promesa.
   * NO redirige — el chiamante decide qué hacer tras el cierre.
   * NO resetea initPromise: si hay un init() en curso (perfilApi pendiente),
   * la promesa sigue viva y las siguientes llamadas esperan su resultado.
   * Una vez que init() termina (con 401 por la sesión invalidada),
   * el guardia redirigirá correctamente al login.
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
      // NO nullificar initPromise: mantener la promesa en curso para que
      // las siguientes llamadas a init() esperen en su lugar (y no creen
      // una nueva con una sesión ya invalidada).
      isLoggingOut = false
    }
  }

  /**
   * Verifica la sesión existente llamando a /perfil.
   *
   * Se llama al arrancar la aplicación (en main.ts) antes de pintar nada.
   * La cookie de sesión se envía automáticamente con withCredentials.
   * Si /perfil devuelve 401, la sesión no es válida.
   *
   * Para evitar race conditions con F5 múltiples, usa sessionStorage como
   * coordinator: si otra recarga de página ya está inicializando (flag en
   * sessionStorage), esta llamada espera la misma promesa en lugar de crear una nueva.
   */
  async function init(): Promise<void> {
    if (inicializado.value) return

    // Si sessionStorage indica que otra recarga ya está inicializando,
    // esperar la promesa existente (initPromise) en lugar de crear una nueva.
    // Esto evita que múltiples F5 creen promesas concurrentes.
    if (sessionStorage.getItem(INIT_KEY)) {
      // Hay otra recarga en curso. Esperar a que termine.
      // Poll hasta que inicializado=true o se alcance timeout.
      const inicio = Date.now()
      while (!inicializado.value && Date.now() - inicio < 5000) {
        await new Promise((r) => setTimeout(r, 50))
      }
      // Si aún no terminó, crear nueva promesa (evita deadlock).
      if (!inicializado.value) return init()
    }

    // Marcar inicio INMEDIATAMENTE para coordinar con otras recargas.
    sessionStorage.setItem(INIT_KEY, '1')

    // Si ya hay una inicialización en curso (del mismo load), devolver esa promesa.
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
        // NO nullificar initPromise aquí: así llamadasConcurrentes
        // devuelven la misma promesa y esperan el mismo resultado.
        sessionStorage.removeItem(INIT_KEY)
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
