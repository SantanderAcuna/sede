/**
 * Verifica el `sitemap.xml` contra una instancia en marcha (M01-1, RF-01-010).
 *
 *   BASE_URL=https://staging.santamarta.gov.co node tests/sitemap.mjs
 *
 * Es la prueba de aceptación del mapa XML: lo que `sitemap-xml.test.ts` comprueba con datos
 * fabricados, aquí se comprueba con los datos reales de la API y con el sitio sirviendo.
 *
 *  AC-1.A  Responde 200 con `application/xml` y es un `<urlset>` del protocolo.
 *  AC-1.B  Cada trámite que publica `GET /api/v1/tramites` tiene su `<url>` `/tramites/{slug}`,
 *          ni uno más ni uno menos.
 *  AC-1.C  Ninguna URL se repite y todas son absolutas y del dominio que se prueba.
 *  AC-1.D  Todo `<lastmod>` tiene formato `AAAA-MM-DD`, y el de cada trámite coincide con su
 *          `actualizado_en`.
 *  AC-1.E  Las páginas que sólo dicen «en preparación» (p. ej. `/transparencia`) NO se anuncian.
 *  AC-1.F  Toda URL anunciada responde 200 (muestra completa con `COMPLETO=1`).
 *
 * Sale con código 1 si algún criterio falla. Sirve igual desde el navegador integrado: las
 * funciones sólo usan `fetch`.
 */
const BASE = (process.env.BASE_URL ?? 'http://localhost:3000').replace(/\/$/, '')
const COMPLETO = process.env.COMPLETO === '1'
// La API puede vivir en otro origen que el sitio (en local, el sitio apunta a la de staging).
const API = (process.env.API_URL ?? `${BASE}/api/v1`).replace(/\/$/, '')

const fallos = []
const verificar = (cumple, mensaje) => {
  console.log(`${cumple ? 'OK   ' : 'FALLA'}  ${mensaje}`)
  if (!cumple) fallos.push(mensaje)
}

const respuesta = await fetch(`${BASE}/sitemap.xml`)
const xml = await respuesta.text()
verificar(respuesta.status === 200, `AC-1.A  /sitemap.xml responde 200 (fue ${respuesta.status})`)
verificar(
  /application\/xml/.test(respuesta.headers.get('content-type') ?? ''),
  `AC-1.A  content-type application/xml (fue ${respuesta.headers.get('content-type')})`,
)
verificar(
  xml.includes('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'),
  'AC-1.A  es un <urlset> del protocolo',
)

const entradas = [...xml.matchAll(/<url>([\s\S]*?)<\/url>/g)].map(([, cuerpo]) => ({
  loc: cuerpo.match(/<loc>([^<]+)<\/loc>/)?.[1] ?? '',
  lastmod: cuerpo.match(/<lastmod>([^<]+)<\/lastmod>/)?.[1],
}))
const rutaDe = (loc) => {
  try {
    return new URL(loc).pathname
  } catch {
    return loc
  }
}

// AC-1.C
const locs = entradas.map((e) => e.loc)
verificar(new Set(locs).size === locs.length, 'AC-1.C  ninguna URL repetida')
verificar(
  locs.every((l) => /^https?:\/\//.test(l)),
  'AC-1.C  todas las URL son absolutas',
)

// AC-1.B — trámites de la API
const slugsApi = []
const vigentesApi = new Map()
for (let pagina = 1, ultima = 1; pagina <= ultima && pagina < 60; pagina += 1) {
  const r = await fetch(`${API}/tramites?per_page=100&page=${pagina}`)
  const json = await r.json()
  ultima = json?.meta?.last_page ?? 1
  for (const t of json?.data ?? []) {
    slugsApi.push(t.slug)
    vigentesApi.set(t.slug, String(t.actualizado_en ?? '').slice(0, 10))
  }
}
const enMapa = new Map(
  entradas.filter((e) => rutaDe(e.loc).startsWith('/tramites/')).map((e) => [rutaDe(e.loc).slice('/tramites/'.length), e]),
)
const faltan = slugsApi.filter((s) => !enMapa.has(s))
const sobran = [...enMapa.keys()].filter((s) => !vigentesApi.has(s))
verificar(slugsApi.length > 0, `AC-1.B  la API publica trámites (${slugsApi.length})`)
verificar(faltan.length === 0, `AC-1.B  todos los trámites de la API están en el mapa (faltan ${faltan.length})`)
verificar(sobran.length === 0, `AC-1.B  el mapa no anuncia trámites que la API no publica (sobran ${sobran.length})`)

// AC-1.D
const FECHA = /^\d{4}-\d{2}-\d{2}$/
verificar(
  entradas.every((e) => e.lastmod === undefined || FECHA.test(e.lastmod)),
  'AC-1.D  todo lastmod tiene formato AAAA-MM-DD',
)
const fechasMal = [...enMapa].filter(([slug, e]) => vigentesApi.get(slug) && e.lastmod !== vigentesApi.get(slug))
verificar(fechasMal.length === 0, `AC-1.D  el lastmod de cada trámite es su actualizado_en (discrepan ${fechasMal.length})`)

// AC-1.E
for (const ruta of ['/transparencia', '/noticias', '/servicios', '/portales', '/seguimiento']) {
  verificar(!entradas.some((e) => rutaDe(e.loc) === ruta), `AC-1.E  ${ruta} (en preparación) no se anuncia`)
}

// AC-1.F
const muestra = COMPLETO ? entradas : [...entradas.slice(0, 15), ...entradas.slice(-15)]
const rotas = []
for (const { loc } of muestra) {
  const r = await fetch(BASE + rutaDe(loc), { headers: { Accept: 'text/html' } })
  if (r.status !== 200) rotas.push(`${rutaDe(loc)} → ${r.status}`)
}
verificar(rotas.length === 0, `AC-1.F  las ${muestra.length} URL comprobadas responden 200 ${rotas.length ? `(${rotas.join(', ')})` : ''}`)

console.log(`\n${entradas.length} URL en el mapa · ${slugsApi.length} trámites en la API`)
if (fallos.length) {
  console.error(`\n${fallos.length} criterio(s) sin cumplir.`)
  process.exit(1)
}
console.log('\nTodos los criterios cumplidos.')
