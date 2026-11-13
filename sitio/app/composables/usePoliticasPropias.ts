import { politicasPropiasDesdeApi, type PoliticaPropia } from '~/config/politicas-contenido'
import { POLITICAS } from '~/config/sitemap'

/**
 * Las políticas propias de la Entidad que la API ya publicó (además de las cinco obligatorias).
 *
 * Una sola lectura de `GET /politicas` por visita, compartida por el índice, la ficha y el mapa del
 * sitio (misma clave de `useAsyncData`). Si la API no responde devuelve una lista vacía: el sitio
 * sigue mostrando las cinco obligatorias, que son un catálogo cerrado y no dependen de esta lectura.
 */
export async function usePoliticasPropias() {
  const urlApi = useRuntimeConfig().public.apiUrl.replace(/\/+$/, '')
  const traer = useRequestFetch()
  const { data } = await useAsyncData<PoliticaPropia[]>('politicas-propias', async () => {
    try {
      const respuesta = await traer<unknown>(`${urlApi}/politicas`)
      return politicasPropiasDesdeApi(respuesta, POLITICAS.map((p) => p.slug))
    } catch {
      return []
    }
  })
  return computed<PoliticaPropia[]>(() => data.value ?? [])
}
