import { fichaDeRuta } from '~/utils/miga'
import { leerFicha, useFichaPrecargada } from '~/composables/useFichaPrecargada'

/**
 * Lee la ficha de una noticia o de un trámite **antes** de dibujar la disposición, para
 * que la miga de pan pueda terminar en su título (CAG-11). La página reutiliza esta
 * misma lectura: ver `utils/miga.ts` para el porqué.
 *
 * Un fallo que no sea 404 se deja pasar sin guardar nada: la página hace entonces su
 * propia lectura y es ella quien muestra el error al ciudadano. La miga, mientras tanto,
 * conserva el nombre de la sección.
 */
export default defineNuxtRouteMiddleware(async (to) => {
  const ficha = fichaDeRuta(to.path)
  if (!ficha) return

  const nuxtApp = useNuxtApp()
  const precargada = useFichaPrecargada()

  // Al hidratar, el servidor ya la leyó y la trajo en el HTML: no se repite.
  if (import.meta.client && nuxtApp.isHydrating && precargada.value?.ruta === to.path) return

  const urlApi = useRuntimeConfig().public.apiUrl.replace(/\/+$/, '')
  const traer = useRequestFetch()

  try {
    const respuesta = await leerFicha<unknown>(traer as never, urlApi, ficha)
    precargada.value = { ruta: to.path, clave: ficha.clave, seccion: ficha.seccion, respuesta }
  } catch {
    precargada.value = null
  }
})
