/**
 * Sesión del panel.
 *
 * La sesión se gestiona con cookie HttpOnly via Sanctum (cookie-based SPA).
 * No se usan tokens Bearer ni localStorage. La cookie de sesión se envía
 * automáticamente en cada petición (withCredentials: true).
 *
 * La verificación de sesión al arrancar se hace llamando a /perfil:
 * si devuelve 401, la cookie no es válida y se redirige al login.
 *
 * El store expone tres signals síncronos para el guardia del router:
 *   - `inicializado`: true cuando init() ha terminado (éxito o fallo).
 *   - `iniciada`: true cuando hay un usuario cargado.
 *   - `usuario`: el usuario actual, o null.
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

  // Promesas en vuelo compartidas. Garantizar que init() y cerrarSesion()
  // son idempotentes: llamadas concurrentes esperan la misma operación.
  let initEnVuelo: Promise<void> | null = null
  let logoutEnVuelo: Promise<void> | null = null

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
   * El flujo internamente ya hace el handshake CSRF y establece la cookie
   * de sesión HttpOnly. Solo se guarda el usuario en memoria.
   *
   * Tras iniciar sesión correctamente, marca `inicializado=true` para que
   * el siguiente init() (ej. tras F5) reutilice el resultado si la cookie
   * sigue siendo válida.
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
      // Marcar como inicializado: la sesión está activa.
      inicializado.value = true
      // Cancelar cualquier init() en vuelo: ya no es necesario.
      initEnVuelo = null
    }
  }

  /**
   * Cierra la sesión actual.
   *
   * Es idempotente: llamadas concurrentes esperan la misma promesa de logout.
   * Limpia el estado local incluso si el backend falla (la cookie HttpOnly
   * se descarta en el navegador cuando se recarga la página).
   */
  async function cerrarSesion(): Promise<void> {
    // Si ya hay un logout en curso, devolver la misma promesa.
    if (logoutEnVuelo) return logoutEnVuelo

    logoutEnVuelo = (async () => {
      try {
        await logoutApi()
      } catch {
        // Si el servidor rechaza o hay red, la sesión queda invalidada
        // localmente. No interesa propagar el error.
      } finally {
        usuario.value = null
        inicializado.value = true // Login correcto: ya no hay sesión que verificar.
        initEnVuelo = null
        logoutEnVuelo = null
      }
    })()

    return logoutEnVuelo
  }

  /**
   * Verifica la sesión existente llamando a /perfil.
   *
   * Se llama al arrancar la aplicación (en main.ts) antes de pintar nada.
   * La cookie de sesión se envía automáticamente con withCredentials.
   * Si /perfil devuelve 401, la sesión no es válida.
   *
   * Es idempotente: múltiples llamadas concurrentes devuelven la misma
   * promesa, garantizando que solo se hace una petición HTTP al backend.
   *
   * Si se llama después de iniciarSesion() o cerrarSesion(), no hace nada
   * (porque `inicializado=true` indica que la sesión ya está determinada).
   */
  async function init(): Promise<void> {
    // Si ya se determinó el estado de la sesión, no llamar al backend.
    if (inicializado.value) return

    // Si hay una inicialización en curso, esperar la misma promesa.
    if (initEnVuelo) return initEnVuelo

    initEnVuelo = (async () => {
      try {
        const perfil = await perfilApi()
        usuario.value = {
          id: perfil.id,
          email: perfil.email,
          nombre: perfil.email.split('@')[0] ?? 'usuario',
          permisos: extraerPermisos(perfil.roles),
        }
      } catch {
        // 401 u otro error: usuario = null. El guardia redirigirá.
        usuario.value = null
      } finally {
        inicializado.value = true
        initEnVuelo = null
      }
    })()

    return initEnVuelo
  }

  /**
   * Resetea el estado a los valores iniciales. Usado por tests.
   * NO debe usarse en producción.
   */
  function $reset(): void {
    usuario.value = null
    inicializado.value = false
    initEnVuelo = null
    logoutEnVuelo = null
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
    $reset,
  }
})