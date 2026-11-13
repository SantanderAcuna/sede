/**
 * Construcción del `sitemap.xml` (RF-01-010, RF-B3-044) — lógica pura, sin Nitro ni red.
 *
 * Vive aparte de la ruta (`server/routes/sitemap.xml.ts`) para poder probarla sin levantar el
 * servidor: `tests/sitemap-xml.test.ts` fija los criterios AC-1.1 a AC-1.7 y
 * `tests/sitemap.mjs` los comprueba después contra una instancia real.
 *
 * **Sobre `<lastmod>`.** Sólo se escribe cuando hay una fecha real de modificación (la
 * `actualizado_en` de cada trámite, la `actualizada_en` de cada política). Las páginas fijas
 * no tienen ninguna: ponerles la fecha del despliegue sería declarar un cambio que no ocurrió,
 * y un buscador que descubre que `lastmod` miente deja de usarlo en todo el mapa.
 */

/** Tope del protocolo sitemaps.org: más URL que esto, el buscador rechaza el archivo. */
export const MAXIMO_URL = 50_000

/** Tope de páginas que se leen de la API: protege de una respuesta que anuncie páginas sin fin. */
const MAXIMO_PAGINAS_API = 50

/** Un slug de trámite válido: el mismo patrón que usan las rutas de la sede. */
const SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/

/** Una URL del mapa, antes de escribirse como XML. */
export interface EntradaSitemap {
  /** Ruta absoluta de la sede, sin dominio (`/tramites/licencia`). */
  ruta: string
  frecuencia: 'daily' | 'weekly' | 'monthly' | 'yearly'
  /** Prioridad relativa, de 0 a 1. */
  prioridad: number
  /** Fecha real de última modificación, ya en formato `AAAA-MM-DD`. */
  modificado?: string
}

/** Escapa lo que va dentro de un nodo XML. */
const escapar = (texto: string): string =>
  texto.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

/**
 * Convierte una fecha de la API en la que pide el protocolo (`AAAA-MM-DD`).
 *
 * Toma los diez primeros caracteres de una cadena ISO —el día que escribió la API, sin
 * reinterpretarlo en otra zona horaria— y comprueba que sea un día real del calendario.
 * Devuelve `undefined` ante cualquier otra cosa: sin fecha fiable no hay `lastmod`.
 */
export function aFechaW3C(valor: unknown): string | undefined {
  if (typeof valor !== 'string') return undefined
  const dia = /^(\d{4})-(\d{2})-(\d{2})(?:$|[T\s])/.exec(valor)
  if (!dia) return undefined

  const [, anio, mes, d] = dia
  const fecha = new Date(Date.UTC(Number(anio), Number(mes) - 1, Number(d)))
  const coincide =
    fecha.getUTCFullYear() === Number(anio) &&
    fecha.getUTCMonth() === Number(mes) - 1 &&
    fecha.getUTCDate() === Number(d)

  return coincide ? `${anio}-${mes}-${d}` : undefined
}

/**
 * Escribe el XML del mapa.
 *
 * Una URL repetida se anuncia una sola vez (gana la primera, que es la declarada a mano en
 * la configuración de rutas) y el total se corta en `MAXIMO_URL`.
 */
export function construirSitemapXml(base: string, entradas: readonly EntradaSitemap[]): string {
  const vistas = new Set<string>()
  const nodos: string[] = []

  for (const entrada of entradas) {
    if (nodos.length >= MAXIMO_URL) break
    if (vistas.has(entrada.ruta)) continue
    vistas.add(entrada.ruta)

    const lastmod = entrada.modificado ? `    <lastmod>${escapar(entrada.modificado)}</lastmod>\n` : ''
    nodos.push(
      `  <url>\n` +
        `    <loc>${escapar(base + entrada.ruta)}</loc>\n` +
        lastmod +
        `    <changefreq>${entrada.frecuencia}</changefreq>\n` +
        `    <priority>${entrada.prioridad.toFixed(1)}</priority>\n` +
        `  </url>`,
    )
  }

  return (
    `<?xml version="1.0" encoding="UTF-8"?>\n` +
    `<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${nodos.join('\n')}\n</urlset>\n`
  )
}

/** Lee `data` de una respuesta de la API sin fiarse de su forma. */
function elementosDe(respuesta: unknown): unknown[] {
  if (typeof respuesta !== 'object' || respuesta === null) return []
  const lista = (respuesta as { data?: unknown }).data
  return Array.isArray(lista) ? lista : []
}

/**
 * Una entrada por trámite publicado.
 *
 * Los elementos sin `slug`, o con uno que no cumple el patrón, se descartan: el mapa no debe
 * anunciar una dirección que la sede no sabe servir ni romperse por un registro mal formado.
 */
export function entradasDeTramites(paginas: readonly unknown[]): EntradaSitemap[] {
  const entradas: EntradaSitemap[] = []

  for (const pagina of paginas) {
    for (const elemento of elementosDe(pagina)) {
      if (typeof elemento !== 'object' || elemento === null) continue
      const { slug, actualizado_en: actualizado } = elemento as Record<string, unknown>
      if (typeof slug !== 'string' || !SLUG.test(slug)) continue

      entradas.push({
        ruta: `/tramites/${slug}`,
        frecuencia: 'monthly',
        prioridad: 0.6,
        modificado: aFechaW3C(actualizado),
      })
    }
  }

  return entradas
}

/**
 * Lee todas las páginas del catálogo de trámites.
 *
 * `pedir(n)` devuelve la página `n` ya decodificada. La primera dice cuántas hay
 * (`meta.last_page`). Si una página falla se corta ahí y se conserva lo leído: el mapa con 100
 * trámites es mejor que un 500 que deje a los buscadores sin ningún mapa.
 */
export async function leerTramites(pedir: (pagina: number) => Promise<unknown>): Promise<EntradaSitemap[]> {
  const paginas: unknown[] = []
  let ultima = 1

  for (let n = 1; n <= ultima && n <= MAXIMO_PAGINAS_API; n += 1) {
    try {
      const pagina = await pedir(n)
      paginas.push(pagina)
      const anunciada = (pagina as { meta?: { last_page?: unknown } } | null)?.meta?.last_page
      if (n === 1 && typeof anunciada === 'number' && Number.isFinite(anunciada)) ultima = Math.floor(anunciada)
    } catch {
      break
    }
  }

  return entradasDeTramites(paginas)
}

/**
 * Cabecera `Cache-Control` del mapa del sitio.
 *
 * Con fichas de trámite se guarda una hora. Sin ninguna casi siempre es un fallo transitorio de la
 * API (un despliegue la reinicia): si el mapa incompleto se guardara una hora en el borde, los
 * buscadores y las pruebas verían una hora un catálogo sin trámites aunque la API ya hubiera vuelto.
 */
export function cacheDelMapa(fichas: number): string {
  return fichas > 0 ? 'public, max-age=3600' : 'no-store'
}

/** Opciones de la caché de lectura. */
export interface OpcionesCache {
  /** Cuánto vale una lectura, en milisegundos. */
  vigenciaMs: number
  /** Reloj inyectable para probar la caducidad. */
  reloj?: () => number
}

/**
 * Envuelve una lectura con una caché de memoria.
 *
 * La API devuelve cada trámite completo (decenas de KB por ficha), así que no se le pide el
 * catálogo entero cada vez que pasa un rastreador. Reglas:
 *  - una lectura **vacía no se guarda**: casi siempre es un fallo, y no debe durar la vigencia;
 *  - las visitas simultáneas comparten la misma lectura en curso.
 */
export function tramitesConCache(
  leer: () => Promise<EntradaSitemap[]>,
  { vigenciaMs, reloj = Date.now }: OpcionesCache,
): () => Promise<EntradaSitemap[]> {
  let guardada: { entradas: EntradaSitemap[]; hasta: number } | undefined
  let enCurso: Promise<EntradaSitemap[]> | undefined

  return async () => {
    if (guardada && reloj() < guardada.hasta) return guardada.entradas
    if (enCurso) return enCurso

    enCurso = leer()
      .then((entradas) => {
        if (entradas.length > 0) guardada = { entradas, hasta: reloj() + vigenciaMs }
        return entradas
      })
      .finally(() => {
        enCurso = undefined
      })

    return enCurso
  }
}

/**
 * Fecha de última modificación de cada política, por `slug`.
 *
 * Sólo entran las que traen una fecha válida; el resto no tendrá `lastmod` (ver la nota del
 * encabezado). Es lectura defensiva: la API es una frontera.
 */
export function fechasDePoliticas(crudo: unknown): Map<string, string> {
  const fechas = new Map<string, string>()

  for (const elemento of elementosDe(crudo)) {
    if (typeof elemento !== 'object' || elemento === null) continue
    const { slug, actualizada_en: actualizada } = elemento as Record<string, unknown>
    const fecha = aFechaW3C(actualizada)
    if (typeof slug === 'string' && fecha) fechas.set(slug, fecha)
  }

  return fechas
}
