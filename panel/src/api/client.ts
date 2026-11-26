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

declare module 'axios' {
  interface InternalAxiosRequestConfig {
    /** Generación de la sesión con la que salió la petición (ver `generacionActual`). */
    generacionSesion?: number
  }
}

/**
 * Generación de la sesión del panel: un número que sube cada vez que se abre una sesión nueva
 * (acceso, o nuevo acceso desde el aviso de sesión vencida). Cada petición sale con la que había.
 * Una respuesta 401 o 419 de una petición ANTERIOR al nuevo acceso describe una sesión que ya no
 * existe y no debe reabrir el aviso: sin esto, una lectura lenta que salió antes de volver a
 * entrar llegaba después y volvía a tapar la pantalla con el aviso recién cerrado.
 *
 * Vive aquí, y no en un archivo propio, porque `client.ts` es quien sella las peticiones y
 * `estructura-api.test.ts` fija los archivos de `src/api` (un archivo por recurso).
 */
let generacion = 0

export function generacionActual(): number {
  return generacion
}

export function avanzarGeneracion(): void {
  generacion += 1
}

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

/** Cada petición anota con qué generación de sesión salió (ver `generacionActual`). */
http.interceptors.request.use((config) => {
  config.generacionSesion = generacionActual()
  return config
})

/**
 * Rutas de acceso. Su 401 es el RESULTADO de entrar o salir (credenciales malas, sesión ya
 * muerta al cerrar), no la pérdida de una sesión abierta. Tratarlo como pérdida cerraba la
 * sesión local cuando una contraseña mal escrita dentro del aviso de sesión vencida.
 */
const RUTAS_DE_ACCESO = /\/panel\/(login|logout)$/

/** Explicación genérica del servidor para un 401: no aporta nada a quien la lee. */
const MENSAJE_GENERICO_401 = 'No autenticado.'

/**
 * Interceptor de errores: 401, 419 y 429.
 *
 * 401 y 419 — con una sesión abierta en el panel significan lo mismo: el servidor ya no la
 * reconoce. Medido en staging (2026-11-26): una lectura sin sesión da 401; una escritura con
 * la sesión vencida da 419 (el token CSRF se comprueba antes que la autenticación); una
 * escritura con la sesión viva pero la cuenta bloqueada o la contraseña cambiada da 401
 * (`SesionDelPanelVigente`, que corre después del CSRF). Como una persona recibe uno u otro
 * según lo que hiciera primero, los dos hacen lo mismo (T9.8, D-0C.8): se levanta el aviso de
 * `SesionVencidaModal` y NO se cierra la sesión local ni se navega, para que lo escrito se
 * conserve. Antes un 401 de lectura borraba la sesión local y el 419 del guardado siguiente
 * no encontraba sesión que avisar: la persona se quedaba sin salida.
 *
 *   - Sin sesión (pantalla de acceso) no se hace nada: allí es un error del formulario.
 *   - `/panel/login` y `/panel/logout` quedan fuera (ver `RUTAS_DE_ACCESO`).
 *   - Una respuesta de una petición anterior al último acceso se ignora.
 *   - Si el 401 trae su explicación (cuenta inactiva, contraseña cambiada) se muestra en el aviso.
 *
 * 429 — Límite de peticiones. NO se redirige: el error llega a quien hizo la petición (el
 * formulario de acceso, una tabla) con la respuesta intacta, y `mensajeDeError()` construye el
 * aviso con el `Retry-After`. Antes cualquier 429, también de una consulta cualquiera, sacaba a
 * la persona del panel hacia el login y borraba lo que estaba haciendo (H-14).
 */
http.interceptors.response.use(
  (response) => response,
  (error: AxiosError<ApiEnvelope<never>>) => {
    if (axios.isAxiosError(error)) {
      const estado = error.response?.status
      const perdioLaSesion = estado === 401 || estado === 419
      const esDeAcceso = RUTAS_DE_ACCESO.test(error.config?.url ?? '')
      const esDeUnaSesionAnterior =
        error.config?.generacionSesion !== undefined && error.config.generacionSesion < generacionActual()

      if (perdioLaSesion && !esDeAcceso && !esDeUnaSesionAnterior) {
        const mensaje = error.response?.data?.message
        const motivo =
          estado === 401 && typeof mensaje === 'string' && mensaje !== '' && mensaje !== MENSAJE_GENERICO_401
            ? mensaje
            : undefined
        useSesionStore().marcarSesionVencida(motivo)
      }
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

/**
 * El texto que se le muestra a la persona ante un error de la API.
 *
 * Prefiere el mensaje del servidor (`success:false, message`), que ya viene en
 * español y sin detalles internos. Ante un 429 añade cuándo puede volver a
 * intentarlo, leyéndolo de `Retry-After`. Sin respuesta (red caída), usa `porDefecto`.
 */
export function mensajeDeError(error: unknown, porDefecto = 'No se pudo conectar con el servidor.'): string {
  if (!axios.isAxiosError(error) || !error.response) {
    return error instanceof ErrorApi ? error.message : porDefecto
  }

  const { status, data, headers } = error.response
  const delServidor = (data as Partial<ApiEnvelope<never>> | undefined)?.message

  // El servidor dice «CSRF token mismatch.»: exacto para quien depura, inútil para quien guarda
  // una noticia. La causa real para la persona es que la sesión venció.
  if (status === 419) {
    return 'Tu sesión venció. Vuelve a entrar y repite la acción.'
  }

  if (status === 429) {
    const segundos = Number.parseInt(String(headers?.['retry-after'] ?? ''), 10)
    const espera = Number.isFinite(segundos) && segundos > 0
      ? ` Vuelve a intentarlo en ${segundos < 60 ? `${segundos} s` : `${Math.ceil(segundos / 60)} min`}.`
      : ' Espera un momento antes de volver a intentarlo.'

    return 'Demasiados intentos.' + espera
  }

  return typeof delServidor === 'string' && delServidor !== '' ? delServidor : porDefecto
}

export function esErrorApi(error: unknown): error is AxiosError<ApiEnvelope<never>> {
  return axios.isAxiosError(error)
}
