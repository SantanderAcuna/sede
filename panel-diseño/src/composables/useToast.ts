import { useToast as useVueToast } from 'vue-toastification';

/**
 * Wrapper tipado sobre vue-toastification.
 * Uso: const toast = useToast(); toast.success('Guardado');
 */
export function useToast() {
  const toast = useVueToast();
  return {
    success: (msg: string) => toast.success(msg),
    error: (msg: string) => toast.error(msg),
    warning: (msg: string) => toast.warning(msg),
    info: (msg: string) => toast.info(msg),
  };
}
