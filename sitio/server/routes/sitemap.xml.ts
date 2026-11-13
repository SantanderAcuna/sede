/**
 * Mapa del sitio en XML.
 *
 * No es un adorno: el Anexo 1 §4.3.1(b) lo exige —«el mapa del sitio debe estar
 * en formato XML para que sea visible a los motores de búsqueda, de forma que se
 * facilite la accesibilidad»—. El sitio de la Alcaldía que sustituimos tiene un
 * mapa HTML de 366 enlaces, útil para una persona y **invisible** para un motor
 * de búsqueda: no publica `sitemap.xml` ni lo declara en `robots.txt`.
 *
 * Se construye como ruta de servidor y consume `app/config/sitemap.ts` como
 * fuente centralizada de rutas. Cualquier cambio en la navegación se refleja
 * automáticamente aquí y en el menú de la sede (RF-B1-010).
 */
import { politicasPropiasDesdeApi, slugsAdoptados } from '../../app/config/politicas-contenido'
import { POLITICAS, todasLasRutas } from '../../app/config/sitemap'
import {
  construirSitemapXml,
  fechasDePoliticas,
  leerTramites,
  tramitesConCache,
  cacheDelMapa,
  type EntradaSitemap,
} from '../utils/sitemap-xml'

/**
 * Las fichas de trámite viven en la API, no en la configuración de rutas. La API devuelve cada
 * ficha completa, así que la lectura se guarda diez minutos: un rastreador no debe costarle al
 * backend el catálogo entero en cada visita.
 */
const tramites = new Map<string, () => Promise<EntradaSitemap[]>>()
const tramitesDe = (urlApi: string): (() => Promise<EntradaSitemap[]>) => {
  let lector = tramites.get(urlApi)
  if (!lector) {
    lector = tramitesConCache(
      () =>
        leerTramites((pagina) =>
          $fetch<unknown>(`${urlApi}/tramites`, { query: { per_page: 100, page: pagina }, timeout: 5000 }),
        ),
      { vigenciaMs: 10 * 60 * 1000 },
    )
    tramites.set(urlApi, lector)
  }
  return lector
}

export default defineEventHandler(async (evento): Promise<string> => {
  const config = useRuntimeConfig(evento)

  /*
   * El dominio sale de la configuración y no de la cabecera `Host`: un mapa del
   * sitio debe declarar el dominio canónico.
   */
  const configurado = String(config.public.dominio ?? 'www.santamarta.gov.co').replace(/\/$/, '')
  const base = /^https?:\/\//.test(configurado) ? configurado : `https://${configurado}`

  /*
   * Generar las entradas del sitemap desde la configuración centralizada.
   * Las rutas se filtran a las que ya publican contenido, para no anunciar
   * páginas que devuelven 404.
   */
  /*
   * Las políticas se anuncian cuando la API dice que su texto ya no es de ejemplo.
   * Si la API no responde se omiten: es preferible no anunciarlas un rato a
   * anunciar un texto que no se pudo comprobar.
   */
  const urlApi = String(config.public.apiUrl ?? '').replace(/\/+$/, '')
  let adoptadas: string[] = []
  let propias: { slug: string; nombre: string }[] = []
  let fechasPoliticas = new Map<string, string>()
  try {
    const respuesta = await $fetch<unknown>(`${urlApi}/politicas`, { timeout: 3000 })
    adoptadas = slugsAdoptados(
      respuesta,
      POLITICAS.map((politica) => politica.slug),
    )
    fechasPoliticas = fechasDePoliticas(respuesta)
    // Una política propia solo llega publicada, y solo se publica adoptada: se anuncia sin más.
    propias = politicasPropiasDesdeApi(respuesta, POLITICAS.map((politica) => politica.slug))
  } catch {
    adoptadas = []
    propias = []
  }

  const fijas: EntradaSitemap[] = todasLasRutas()
    .filter((ruta) => ruta.publicada || adoptadas.some((slug) => ruta.ruta === `/politicas/${slug}`))
    .map((ruta) => ({
      ruta: ruta.ruta,
      frecuencia: ruta.frecuenciaSitemap ?? 'weekly',
      prioridad: ruta.prioridadSitemap ?? 0.5,
      // Sólo las políticas tienen una fecha real de modificación; las páginas fijas no.
      modificado: fechasPoliticas.get(ruta.ruta.replace(/^\/politicas\//, '')),
    }))

  for (const politica of propias) {
    fijas.push({
      ruta: `/politicas/${politica.slug}`,
      frecuencia: 'yearly',
      prioridad: 0.4,
      modificado: fechasPoliticas.get(politica.slug),
    })
  }

  // Las fichas de trámite se leen de la API. Si falla, el mapa sale con las rutas fijas.
  const fichas = await tramitesDe(urlApi)()

  setHeader(evento, 'content-type', 'application/xml; charset=utf-8')
  // Una hora si el mapa trae fichas; nada si salió sin ellas (ver `cacheDelMapa`).
  setHeader(evento, 'cache-control', cacheDelMapa(fichas.length))

  return construirSitemapXml(base, [...fijas, ...fichas])
})
