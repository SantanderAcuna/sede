/**
 * `robots.txt` por entorno.
 *
 * Se genera en el servidor en lugar de servirse como archivo estático por un
 * motivo concreto: el `Sitemap:` tiene que declarar **el dominio desde el que se
 * sirve la sede**, y el archivo estático lo llevaba escrito a mano con
 * `www.santamarta.gov.co` mientras la configuración del sitio apunta a
 * `staging.santamarta.gov.co`. Un mapa del sitio que anuncia un dominio que no es
 * el suyo manda a los rastreadores a otro sitio; y en un entorno de pruebas,
 * además, los indexa.
 *
 * La decisión de indexar sale de `runtimeConfig.public.indexable`, la misma
 * variable que gobierna la cabecera `X-Robots-Tag` de `nuxt.config.ts`: una sola
 * decisión, dos mecanismos, y no pueden discrepar.
 */
export default defineEventHandler((evento): string => {
  const config = useRuntimeConfig(evento)
  const dominio = String(config.public.dominio ?? '').replace(/\/$/, '')
  const base = /^https?:\/\//.test(dominio) ? dominio : `https://${dominio}`
  const indexable = config.public.indexable === true

  setHeader(evento, 'content-type', 'text/plain; charset=utf-8')
  // Cinco minutos: si se cambia el entorno, el archivo deja de mentir enseguida.
  setHeader(evento, 'cache-control', 'public, max-age=300')

  /*
   * Entorno no indexable: se cierra el rastreo entero y NO se declara el mapa
   * del sitio. Declararlo con `Disallow: /` sería contradictorio, y un buscador
   * que ya lo tuviera cacheado podría seguir entrando.
   */
  if (!indexable) {
    return [
      '# Entorno no productivo: la sede no se indexa.',
      '#',
      '# Un despliegue de pruebas que se indexe compite en los buscadores con la',
      '# sede oficial y publica como definitivas las secciones que aún están en',
      '# preparación. La decisión se toma con SEDE_INDEXABLE (ver nuxt.config.ts).',
      'User-Agent: *',
      'Disallow: /',
      '',
    ].join('\n')
  }

  /*
   * Producción: la sede no tiene nada que ocultar —todo su contenido es público
   * y debe poder rastrearse—, así que se permite el rastreo completo y se declara
   * el mapa en XML, que es lo que exige el Anexo 1 §4.3.1(b).
   */
  return [
    '# La sede no tiene nada que ocultar: todo su contenido es público y debe poder',
    '# rastrearse. El sitio de la Alcaldía que sustituimos publicaba la plantilla por',
    '# defecto de su gestor, que además listaba las rutas internas del servidor.',
    'User-Agent: *',
    'Disallow:',
    '',
    '# El mapa del sitio, en XML y declarado aquí. Un mapa que no se declara sólo lo',
    '# encuentran quienes ya saben que existe.',
    `Sitemap: ${base}/sitemap.xml`,
    '',
  ].join('\n')
})
