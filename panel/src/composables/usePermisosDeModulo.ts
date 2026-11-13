import { computed, type ComputedRef } from 'vue'
import { permisoDe } from '@/config/permisos'
import { useSesionStore } from '@/stores/sesion'

export interface PermisosDeModulo {
  puedeCrear: ComputedRef<boolean>
  puedeEditar: ComputedRef<boolean>
  puedeEliminar: ComputedRef<boolean>
  puedePublicar: ComputedRef<boolean>
}

/**
 * Lo que la sesión puede hacer en un módulo, acción por acción.
 *
 * Abrir la pantalla pide «ver»; cada botón pide el suyo. Ocultarlos evita ofrecer lo que el
 * servidor va a rechazar, pero el control real sigue siendo el del servidor en cada petición.
 */
export function usePermisosDeModulo(modulo: string): PermisosDeModulo {
  const sesion = useSesionStore()
  return {
    puedeCrear: computed(() => sesion.tienePermiso(permisoDe(modulo, 'crear'))),
    puedeEditar: computed(() => sesion.tienePermiso(permisoDe(modulo, 'editar'))),
    puedeEliminar: computed(() => sesion.tienePermiso(permisoDe(modulo, 'eliminar'))),
    puedePublicar: computed(() => sesion.tienePermiso(permisoDe(modulo, 'publicar'))),
  }
}
