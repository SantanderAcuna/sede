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
import { slugsAdoptados } from '../../app/config/politicas-contenido'
import { POLITICAS, todasLasRutas } from '../../app/config/sitemap'

/** Escapa lo que va dentro de un nodo XML. */
const escapar = (texto: string): string =>
  texto.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

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
  try {
    const respuesta = await $fetch<unknown>(`${urlApi}/politicas`, { timeout: 3000 })
    adoptadas = slugsAdoptados(
      respuesta,
      POLITICAS.map((politica) => politica.slug),
    )
  } catch {
    adoptadas = []
  }

  const entradas = todasLasRutas()
    .filter((ruta) => ruta.publicada || adoptadas.some((slug) => ruta.ruta === `/politicas/${slug}`))
    .map(
      (p) =>
        `  <url>\n` +
        `    <loc>${escapar(base + p.ruta)}</loc>\n` +
        `    <changefreq>${p.frecuenciaSitemap ?? 'weekly'}</changefreq>\n` +
        `    <priority>${(p.prioridadSitemap ?? 0.5).toFixed(1)}</priority>\n` +
        `  </url>`,
    )
    .join('\n')

  setHeader(evento, 'content-type', 'application/xml; charset=utf-8')
  // Una hora: el contenido de la sede no cambia al segundo.
  setHeader(evento, 'cache-control', 'public, max-age=3600')

  return `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${entradas}\n</urlset>\n`
})
