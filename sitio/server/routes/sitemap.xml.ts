/**
 * Mapa del sitio en XML.
 *
 * No es un adorno: el Anexo 1 §4.3.1(b) lo exige —«el mapa del sitio debe estar
 * en formato XML para que sea visible a los motores de búsqueda, de forma que se
 * facilite la accesibilidad»—. El sitio de la Alcaldía que sustituimos tiene un
 * mapa HTML de 366 enlaces, útil para una persona y **invisible** para un motor
 * de búsqueda: no publica `sitemap.xml` ni lo declara en `robots.txt`.
 *
 * Se construye como ruta de servidor y no como archivo estático porque las
 * páginas dependen de datos: cuando la sección de normativa publique normas
 * reales, cada una tendrá su propia dirección y este archivo debe incluirla sin
 * que nadie se acuerde de tocar una lista a mano.
 *
 * **Sólo se anuncian las páginas que existen de verdad.** Anunciar una que
 * devuelve 404 es peor que no anunciarla: el buscador la indexa, el ciudadano
 * llega desde ahí y encuentra un error. Por eso la lista es explícita y no se
 * genera recorriendo el disco.
 */

/** Una dirección publicada y, si se conoce, la fecha de su último cambio real. */
interface Direccion {
  ruta: string
  /** Prioridad relativa dentro del sitio, de 0 a 1. */
  prioridad: number
  /** Cada cuánto cambia de verdad, no cada cuánto nos gustaría. */
  frecuencia: 'daily' | 'weekly' | 'monthly' | 'yearly'
}

/**
 * Las páginas de primer nivel, las de servicio y las secciones del menú.
 * Se mantienen en el mismo orden en que aparecen en el menú principal para que
 * sea fácil comparar esta lista con él cuando alguien añada una sección.
 */
const paginas: Direccion[] = [
  { ruta: '/', prioridad: 1.0, frecuencia: 'daily' },
  { ruta: '/transparencia', prioridad: 0.9, frecuencia: 'weekly' },
  { ruta: '/tramites', prioridad: 0.9, frecuencia: 'weekly' },
  { ruta: '/atencion', prioridad: 0.8, frecuencia: 'monthly' },
  { ruta: '/participa', prioridad: 0.8, frecuencia: 'monthly' },
  { ruta: '/normativa', prioridad: 0.7, frecuencia: 'weekly' },
  { ruta: '/noticias', prioridad: 0.7, frecuencia: 'daily' },
  { ruta: '/pqrsd', prioridad: 0.8, frecuencia: 'monthly' },
  { ruta: '/realizar-una-peticion', prioridad: 0.8, frecuencia: 'monthly' },
  { ruta: '/seguimiento', prioridad: 0.7, frecuencia: 'monthly' },
  { ruta: '/portales', prioridad: 0.6, frecuencia: 'monthly' },
  { ruta: '/servicios', prioridad: 0.6, frecuencia: 'monthly' },
  { ruta: '/buscar', prioridad: 0.3, frecuencia: 'monthly' },
  { ruta: '/mapa-del-sitio', prioridad: 0.4, frecuencia: 'monthly' },
  { ruta: '/accesibilidad', prioridad: 0.5, frecuencia: 'yearly' },
]

/** Las seis subcategorías de Participa que fija el Anexo 2 §4.1.2.3. */
const subcategoriasParticipa = [
  'identificacion-de-problemas',
  'presupuesto-participativo',
  'consulta-ciudadana',
  'innovacion-abierta',
  'rendicion-de-cuentas',
  'control-ciudadano',
]

/** Las cinco políticas del criterio de Seguridad nº 6. */
const politicas = [
  'terminos-y-condiciones-de-uso',
  'seguridad-y-privacidad',
  'proteccion-y-tratamiento-de-datos-personales',
  'uso-de-cookies',
  'derechos-de-autor-y-uso-sobre-contenidos',
]

/** Escapa lo que va dentro de un nodo XML. Las rutas son nuestras, pero un día dejarán de serlo. */
const escapar = (texto: string): string =>
  texto.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

export default defineEventHandler((evento): string => {
  const config = useRuntimeConfig(evento)

  /*
   * El dominio sale de la configuración y no de la cabecera `Host`: un mapa del
   * sitio debe declarar el dominio canónico. Si se tomara de la petición, el
   * mismo archivo anunciaría `staging.santamarta.gov.co` o una IP de balanceador,
   * y el buscador indexaría direcciones que no son la sede.
   */
  const configurado = String(config.public.dominio ?? 'www.santamarta.gov.co').replace(/\/$/, '')
  /*
   * El `<loc>` de un mapa del sitio **exige una URL absoluta con esquema**, y la
   * configuración guarda sólo el dominio. Sin esto saldría
   * `staging.santamarta.gov.co/` como dirección, que ningún buscador acepta.
   */
  const base = /^https?:\/\//.test(configurado) ? configurado : `https://${configurado}`

  const todas: Direccion[] = [
    ...paginas,
    ...subcategoriasParticipa.map((slug) => ({
      ruta: `/participa/${slug}`,
      prioridad: 0.6,
      frecuencia: 'monthly' as const,
    })),
    ...politicas.map((slug) => ({
      ruta: `/politicas/${slug}`,
      prioridad: 0.4,
      frecuencia: 'yearly' as const,
    })),
  ]

  const entradas = todas
    .map(
      (p) =>
        `  <url>\n` +
        `    <loc>${escapar(base + p.ruta)}</loc>\n` +
        `    <changefreq>${p.frecuencia}</changefreq>\n` +
        `    <priority>${p.prioridad.toFixed(1)}</priority>\n` +
        `  </url>`,
    )
    .join('\n')

  setHeader(evento, 'content-type', 'application/xml; charset=utf-8')
  // Una hora: el contenido de la sede no cambia al segundo, y así el archivo se
  // sirve desde la caché sin castigar al servidor en cada rastreo.
  setHeader(evento, 'cache-control', 'public, max-age=3600')

  return `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${entradas}\n</urlset>\n`
})
