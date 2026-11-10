/**
 * Construcción del `robots.txt` por entorno — lógica pura, sin Nitro ni red.
 *
 * Vive aparte de la ruta (`server/routes/robots.txt.ts`) para poder probarla sin levantar el
 * servidor: `tests/robots-txt.test.ts` fija los criterios AC-1.R1 a AC-1.R4 y
 * `tests/robots.mjs` los comprueba contra una instancia real.
 *
 * Se genera en el servidor en lugar de servirse como archivo estático por un motivo concreto:
 * el `Sitemap:` tiene que declarar **el dominio desde el que se sirve la sede**, y el archivo
 * estático lo llevaba escrito a mano con `www.santamarta.gov.co` mientras la configuración del
 * sitio apunta a `staging.santamarta.gov.co`. Un mapa del sitio que anuncia un dominio que no
 * es el suyo manda a los rastreadores a otro sitio; y en un entorno de pruebas, además, los
 * indexa.
 */

/** Lo que decide el contenido: el dominio público y si el entorno se indexa. */
export interface EntornoRobots {
  /** Dominio desde el que se sirve la sede; con o sin protocolo, con o sin barra final. */
  dominio: string | null | undefined
  /** `runtimeConfig.public.indexable`: la misma variable que gobierna `X-Robots-Tag`. */
  indexable: boolean
}

/**
 * Normaliza el dominio a una base absoluta sin barra final.
 * Devuelve `undefined` si no hay dominio: con él vacío quedaría `https:///sitemap.xml`.
 */
function baseDe(dominio: string | null | undefined): string | undefined {
  const limpio = String(dominio ?? '').trim().replace(/\/+$/, '')
  if (limpio === '') return undefined
  return /^https?:\/\//.test(limpio) ? limpio : `https://${limpio}`
}

export function construirRobots({ dominio, indexable }: EntornoRobots): string {
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
  const base = baseDe(dominio)

  return [
    '# La sede no tiene nada que ocultar: todo su contenido es público y debe poder',
    '# rastrearse. El sitio de la Alcaldía que sustituimos publicaba la plantilla por',
    '# defecto de su gestor, que además listaba las rutas internas del servidor.',
    'User-Agent: *',
    'Disallow:',
    '',
    ...(base
      ? [
          '# El mapa del sitio, en XML y declarado aquí. Un mapa que no se declara sólo lo',
          '# encuentran quienes ya saben que existe.',
          `Sitemap: ${base}/sitemap.xml`,
          '',
        ]
      : []),
  ].join('\n')
}
