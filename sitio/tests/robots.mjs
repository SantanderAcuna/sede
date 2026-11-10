/**
 * Verifica el `robots.txt` contra una instancia en marcha (M01-1b, RF-01-010).
 *
 *   BASE_URL=https://staging.santamarta.gov.co node tests/robots.mjs
 *   BASE_URL=https://www.santamarta.gov.co INDEXABLE=1 node tests/robots.mjs
 *
 * Lo que `robots-txt.test.ts` comprueba con entradas fabricadas, aquí se comprueba con el
 * archivo que sirve el despliegue real —incluido lo que antepone el borde (Cloudflare añade un
 * bloque «Managed content» antes del contenido del origen)—.
 *
 *  AC-1.R0  Responde 200 con `text/plain`.
 *  Con INDEXABLE ausente (staging, pruebas):
 *    AC-1.R1  Existe un grupo `User-agent: *` con `Disallow: /`, no hay línea `Sitemap:`
 *             y la cabecera `X-Robots-Tag` lleva `noindex`.
 *  Con INDEXABLE=1 (producción):
 *    AC-1.R2  Hay EXACTAMENTE una línea `Sitemap:`, igual a `{BASE_URL}/sitemap.xml`, y esa URL
 *             responde 200 con `application/xml`. La cabecera `X-Robots-Tag` NO lleva `noindex`.
 *
 * Sale con código 1 si algún criterio falla.
 */
const BASE = (process.env.BASE_URL ?? 'http://localhost:3000').replace(/\/$/, '')
const INDEXABLE = process.env.INDEXABLE === '1'

const fallos = []
const verificar = (cumple, mensaje) => {
  console.log(`${cumple ? 'OK   ' : 'FALLA'}  ${mensaje}`)
  if (!cumple) fallos.push(mensaje)
}

const respuesta = await fetch(`${BASE}/robots.txt`)
const texto = await respuesta.text()
verificar(respuesta.status === 200, `AC-1.R0  /robots.txt responde 200 (fue ${respuesta.status})`)
verificar(
  /text\/plain/.test(respuesta.headers.get('content-type') ?? ''),
  `AC-1.R0  content-type text/plain (fue ${respuesta.headers.get('content-type')})`,
)

const lineas = texto.split('\n').map((l) => l.replace(/\r$/, '').trim())
const directivas = lineas.filter((l) => l !== '' && !l.startsWith('#'))
const sitemaps = directivas.filter((l) => /^sitemap:/i.test(l))

/** ¿Hay un grupo `User-agent: *` cuya primera regla sea `Disallow: /`? */
const cierraTodo = directivas.some(
  (l, i) => /^user-agent:\s*\*\s*$/i.test(l) && /^disallow:\s*\/\s*$/i.test(directivas[i + 1] ?? ''),
)
const robotsTag = respuesta.headers.get('x-robots-tag') ?? ''

if (!INDEXABLE) {
  verificar(cierraTodo, 'AC-1.R1  un grupo `User-agent: *` cierra el rastreo con `Disallow: /`')
  verificar(sitemaps.length === 0, `AC-1.R1  no declara el mapa del sitio (líneas Sitemap: ${sitemaps.length})`)
  verificar(/noindex/i.test(robotsTag), `AC-1.R1  X-Robots-Tag incluye noindex (fue «${robotsTag}»)`)
} else {
  const esperado = `${BASE}/sitemap.xml`
  verificar(sitemaps.length === 1, `AC-1.R2  declara el mapa exactamente una vez (fueron ${sitemaps.length})`)
  verificar(
    sitemaps[0]?.replace(/^sitemap:\s*/i, '') === esperado,
    `AC-1.R2  la URL declarada es ${esperado} (fue «${sitemaps[0] ?? ''}»)`,
  )
  const mapa = await fetch(esperado)
  verificar(
    mapa.status === 200 && /application\/xml/.test(mapa.headers.get('content-type') ?? ''),
    `AC-1.R2  la URL declarada responde 200 application/xml (fue ${mapa.status})`,
  )
  verificar(!/noindex/i.test(robotsTag), `AC-1.R2  X-Robots-Tag no incluye noindex (fue «${robotsTag}»)`)
}

if (fallos.length) {
  console.error(`\n${fallos.length} criterio(s) sin cumplir.`)
  process.exit(1)
}
console.log('\nTodos los criterios cumplidos.')
