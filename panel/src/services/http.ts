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
  // Sanctum SPA: Axios lee el token CSRF de la cookie `XSRF-TOKEN` y lo envía
  // automáticamente en el header `X-XSRF-TOKEN` en cada petición.
  // Disponible desde axios v1.6.2.
  withXSRFToken: true,
  timeout: 15_000,
})

/**
 * Interceptor que maneja errores 401 y 429.
 *
 * 401 — Si la sesión estaba activa (el usuario ya pasó el login), cierra la
 * sesión en el store y deja que el guardia del routerRedirija al login en la
 * siguiente navegación. NO llama a window.location aquí porque interferiría
 * con la navegación del router y causaría loops cuando cerrarSesion se llama
 * dos veces (interceptor + botón).
 *
 * 429 — Redirige directamente porque es un estado irrecuperable sin acción
 * del usuario (rate limit). El parámetro ?rate_limited=1 informa al login.
 */
http.interceptors.response.use(
  (response) => response,
  (error: AxiosError<ApiEnvelope<never>>) => {
    if (axios.isAxiosError(error) && error.response?.status === 401) {
      const sesion = useSesionStore()
      // Solo actúa si la sesión ya estaba iniciada. Durante init() la sesión
      // aún no se había restaurado (inicializado=false) y el guardia se
      // encarga de redirigir.
      if (sesion.inicializado && sesion.iniciada) {
        sesion.cerrarSesion()
        // No se redirige aquí: el guardia del router detectará sesion.iniciada=false
        // en la siguiente navegación y redirigirá correctamente.
      }
    }
    if (axios.isAxiosError(error) && error.response?.status === 429) {
      window.location.href = '/admin/acceso?rate_limited=1'
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
