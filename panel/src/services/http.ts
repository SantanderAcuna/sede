/**
 * Cliente HTTP del panel.
 *
 * Habla el sobre plano del contrato y lo desempaqueta en un solo sitio: ninguna
 * vista debe saber que existe un campo `success`. Si una vista tuviera que
 * comprobarlo, cada una lo haría a su manera.
 */
import axios, { type AxiosInstance, type AxiosError } from 'axios'
import type { ApiEnvelope, PaginatedEnvelope, PageMeta, CollectionLinks } from '@/types/api'
import { useSesionStore } from '@/stores/sesion'

/**
 * Error con la forma del contrato, para que la interfaz pueda explicarlo.
 *
 * Los campos se declaran y se asignan a mano en lugar de usar propiedades del
 * constructor: la configuración de TypeScript del proyecto prohíbe la sintaxis
 * que no se puede borrar sin cambiar el significado, y esa construcción lo es.
 */
export class ErrorApi extends Error {
  readonly estado: number
  readonly errores: Record<string, string[]> | null

  constructor(message: string, estado: number, errores: Record<string, string[]> | null = null) {
    super(message)
    this.name = 'ErrorApi'
    this.estado = estado
    this.errores = errores
  }
}

export const http: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? '/api/v1',
  headers: {
    // El contrato exige `application/json`. El sobre plano no usa
    // `application/vnd.api+json`.
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  // La sesión viaja en una cookie `HttpOnly`, nunca en el almacenamiento del
  // navegador: es lo que la protege de un script inyectado.
  withCredentials: true,
  timeout: 15_000,
})

/**
 * Interceptor que añade el token de Sanctum a cada petición.
 *
 * El token se obtiene tras el login y se guarda en el store de sesión.
 * Sanctum acepta el token en el header `Authorization: Bearer <token>`.
 */
http.interceptors.request.use((config) => {
  // Se importa aquí para evitar circularidad con el store.
  const sesion = useSesionStore()
  if (sesion.token) {
    config.headers.Authorization = `Bearer ${sesion.token}`
  }
  return config
})

/**
 * Interceptor que maneja errores 401 redirigiendo al login.
 */
http.interceptors.response.use(
  (response) => response,
  (error: AxiosError<ApiEnvelope<never>>) => {
    if (axios.isAxiosError(error) && error.response?.status === 401) {
      const sesion = useSesionStore()
      sesion.cerrarSesion()
      window.location.href = '/admin/acceso'
    }
    return Promise.reject(error)
  }
)

/** Desempaqueta el sobre y devuelve sólo los datos. */
export function desenvolver<T>(sobre: ApiEnvelope<T>): T {
  if (!sobre.success || sobre.data === null) {
    throw new ErrorApi(sobre.message ?? 'La operación no se pudo completar.', 200, sobre.errors)
  }
  return sobre.data
}

/** Colección paginada, con su `meta` y sus `links`. */
export interface Pagina<T> {
  datos: T[]
  meta: PageMeta
  enlaces: CollectionLinks
}

export function desenvolverPagina<T>(sobre: PaginatedEnvelope<T>): Pagina<T> {
  if (!sobre.success) {
    throw new ErrorApi(sobre.message ?? 'La operación no se pudo completar.', 200, sobre.errors)
  }
  return { datos: sobre.data, meta: sobre.meta, enlaces: sobre.links }
}

export function esErrorApi(error: unknown): error is AxiosError<ApiEnvelope<never>> {
  return axios.isAxiosError(error)
}
