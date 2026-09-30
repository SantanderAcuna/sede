import axios, { type AxiosError, type InternalAxiosRequestConfig } from 'axios';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
import router from '@/router';

/** Instancia única de Axios para todo el frontend (src/api/client.ts). */
export const client = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  timeout: 20_000,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
});

client.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const auth = useAuthStore();
  if (auth.token) config.headers.set('Authorization', `Bearer ${auth.token}`);
  config.headers.set('X-Request-Id', crypto.randomUUID());
  return config;
});

client.interceptors.response.use(
  (resp) => resp,
  async (error: AxiosError<{ message?: string }>) => {
    const toast = useToast();
    const auth = useAuthStore();
    const status = error.response?.status;
    if (status === 401) {
      auth.logout();
      router.push({ name: 'login', query: { reason: 'expired' } });
    } else if (status === 403) {
      toast.error('No tienes permisos para esta acción');
    } else if (status && status >= 500) {
      toast.error('Error del servidor. Intenta nuevamente.');
    } else if (error.response?.data?.message) {
      toast.error(error.response.data.message);
    }
    return Promise.reject(error);
  }
);

export interface Page<T> {
  items: T[];
  total: number;
  page: number;
  pageSize: number;
}
