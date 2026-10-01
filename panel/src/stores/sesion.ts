/**
 * Sesión del panel.
 *
 * **Hoy este almacén siempre está vacío.** El módulo de identidad no existe
 * todavía, así que no hay de dónde traer un usuario ni sus permisos: no se
 * inventa ninguno. Lo que sí existe ya es el punto único donde el enrutador
 * pregunta «¿hay sesión?» (`src/router/index.ts`) y el menú pregunta «¿tiene
 * este permiso?» (`src/layouts/AdminLayout.vue`). Conectar la autenticación
 * real será sustituir `iniciarSesion` por la llamada al backend, sin tocar las
 * guardias ni el filtrado del menú.
 *
 * La sesión vive **en memoria** y a propósito: un usuario o unos permisos
 * guardados en `localStorage` sobreviven al cierre de sesión del servidor y
 * siguen afirmando una identidad que ya caducó. El contrato ya transporta la
 * sesión en una cookie `HttpOnly` (`src/services/http.ts`), así que no hay nada
 * que persistir aquí.
 */
import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

/** Identidad y permisos que el backend entregará cuando exista el módulo. */
export interface UsuarioSesion {
  nombre: string
  permisos: string[]
}

export const useSesionStore = defineStore('sesion', () => {
  const usuario = ref<UsuarioSesion | null>(null)

  /** ¿Hay una sesión iniciada? Es la pregunta que responde `requiereSesion`. */
  const iniciada = computed(() => usuario.value !== null)

  const permisos = computed(() => new Set(usuario.value?.permisos ?? []))

  /**
   * Sin permiso declarado, la ruta se considera pública dentro del panel. Se
   * decide así para que un módulo nuevo que aún no tenga permiso asignado no
   * quede invisible por omisión.
   */
  function tienePermiso(permiso?: string): boolean {
    if (!permiso) return true
    return permisos.value.has(permiso)
  }

  /**
   * Único punto de entrada para la identidad real.
   *
   * Todavía **no valida credenciales**: existe para que la guardia y el menú
   * tengan un destino el día que el backend responda. Hoy nadie lo invoca, y
   * por eso la interfaz no puede afirmar que hay sesión.
   */
  function iniciarSesion(datos: UsuarioSesion): void {
    usuario.value = datos
  }

  function cerrarSesion(): void {
    usuario.value = null
  }

  return { usuario, iniciada, permisos, tienePermiso, iniciarSesion, cerrarSesion }
})
