import { crearClienteApi, type ClienteApi, type Traer } from '~/utils/cliente-api'

/**
 * El cliente de la API pública para páginas, layouts, middleware y plugins (T9.1).
 *
 * Resuelve la base una sola vez desde `public.apiUrl` y usa por defecto `useRequestFetch()`: en
 * el servidor la URL relativa de la API se resuelve contra la petición en curso y se reenvían sus
 * cabeceras, igual que antes. Un plugin del navegador que necesite el `$fetch` desnudo (porque
 * pasa `keepalive` o `credentials`) lo indica con `traer`.
 *
 * Se llama en el cuerpo de `setup`, del middleware o del plugin, **antes** de cualquier `await`:
 * `useRequestFetch()` necesita el contexto de Nuxt.
 */
export function useClienteApi(traer?: Traer): ClienteApi {
  return crearClienteApi(traer ?? (useRequestFetch() as unknown as Traer), useRuntimeConfig().public.apiUrl)
}
