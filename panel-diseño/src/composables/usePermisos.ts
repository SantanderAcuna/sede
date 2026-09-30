import type { Permiso } from '@/types/auth';
import { useAuthStore } from '@/stores/auth';
import { computed } from 'vue';

export function usePermisos() {
  const auth = useAuthStore();
  return {
    can: (p: Permiso) => auth.can(p),
    canAny: (perms: Permiso[]) => perms.some(p => auth.can(p)),
    canAll: (perms: Permiso[]) => perms.every(p => auth.can(p)),
    permisos: computed(() => auth.permisos),
  };
}
