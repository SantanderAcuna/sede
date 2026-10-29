/**
 * Puerta de las políticas contra un despliegue real (T-20, AC-20.1, AC-20.5, AC-20.7).
 *
 * Uso:  BASE_URL=https://… node tests/politicas.mjs
 *
 * Comprueba, para cada una de las cinco políticas:
 *   - la página responde 200, trae el texto de la API y NO dice «en preparación»;
 *   - si el texto es de ejemplo, lo avisa y lleva `noindex`;
 *   - se descarga en ODT (zip válido, tipo de documento OpenDocument) y en PDF/A
 *     (cabecera %PDF y marca `pdfaid`), sin poner cookies;
 *   - si la API dice que tiene documento oficial, su enlace responde 200.
 * Y que una política inexistente responde 404. Sale con código 1 si algo falla.
 * No necesita navegador: son peticiones HTTP.
 */
const BASE = (process.env.BASE_URL ?? '').replace(/\/+$/, '')
if (!BASE) {
  console.error('Falta BASE_URL (por ejemplo, BASE_URL=https://staging.example.org).')
  process.exit(2)
}

const SLUGS = [
  'terminos-y-condiciones-de-uso',
  'seguridad-y-privacidad',
  'proteccion-y-tratamiento-de-datos-personales',
  'uso-de-cookies',
  'derechos-de-autor-y-uso-sobre-contenidos',
]

let fallos = 0
const falla = (mensaje) => {
  fallos += 1
  console.error(`  ✗ ${mensaje}`)
}
const ok = (mensaje) => console.log(`  ✓ ${mensaje}`)

const pedir = async (ruta, opciones = {}) => fetch(`${BASE}${ruta}`, { redirect: 'manual', ...opciones })

for (const slug of SLUGS) {
  console.log(slug)

  const api = await pedir(`/api/v1/politicas/${slug}`, { headers: { Accept: 'application/json' } })
  const politica = api.ok ? (await api.json()).data : null
  if (politica) ok(`API: versión ${politica.version}, ${politica.secciones.length} apartados, ejemplo=${politica.es_ejemplo}`)
  else falla(`la API respondió ${api.status}`)

  const pagina = await pedir(`/politicas/${slug}`)
  const html = await pagina.text()
  if (pagina.status === 200) ok('página 200')
  else falla(`la página respondió ${pagina.status}`)
  if (/en preparaci[oó]n/i.test(html)) falla('la página dice «en preparación» (AC-20.7)')
  else ok('no dice «en preparación»')
  if (politica?.secciones?.[0] && !html.includes(politica.secciones[0].titulo)) falla('la página no muestra el texto de la API')
  if (politica?.es_ejemplo) {
    if (!html.includes('Texto de ejemplo')) falla('es de ejemplo pero la página no lo avisa')
    if (!/<meta[^>]+name="robots"[^>]+content="noindex"/.test(html)) falla('es de ejemplo pero no lleva noindex')
  }

  const odt = await pedir(`/politicas/${slug}.odt`)
  const bytesOdt = Buffer.from(await odt.arrayBuffer())
  if (odt.status !== 200) falla(`ODT respondió ${odt.status}`)
  else if (!odt.headers.get('content-type')?.startsWith('application/vnd.oasis.opendocument.text')) falla(`ODT con tipo ${odt.headers.get('content-type')}`)
  else if (bytesOdt.subarray(0, 2).toString() !== 'PK' || !bytesOdt.includes('application/vnd.oasis.opendocument.text')) falla('el ODT no es un OpenDocument válido')
  else ok(`ODT ${bytesOdt.length} bytes`)
  if (odt.headers.get('set-cookie')) falla('la descarga ODT pone cookies')

  const pdf = await pedir(`/politicas/${slug}.pdf`)
  const bytesPdf = Buffer.from(await pdf.arrayBuffer())
  if (pdf.status !== 200) falla(`PDF respondió ${pdf.status}`)
  else if (bytesPdf.subarray(0, 4).toString() !== '%PDF') falla('el PDF no empieza por %PDF')
  else if (!bytesPdf.includes('pdfaid:part')) falla('el PDF no declara PDF/A (pdfaid)')
  else ok(`PDF/A ${bytesPdf.length} bytes`)
  if (pdf.headers.get('set-cookie')) falla('la descarga PDF pone cookies')

  const oficial = politica?.documento_oficial
  if (oficial) {
    const doc = await pedir(oficial.url)
    if (doc.status === 200) ok(`documento oficial ${oficial.nombre}`)
    else falla(`el documento oficial respondió ${doc.status}`)
  } else {
    const doc = await pedir(`/politicas/${slug}/documento`)
    if (doc.status === 404) ok('sin documento oficial: 404')
    else falla(`sin documento oficial debería ser 404 y fue ${doc.status}`)
  }
}

console.log('inexistentes')
for (const ruta of ['/politicas/no-existe', '/politicas/no-existe.pdf', '/politicas/no-existe.odt', '/politicas/no-existe/documento']) {
  const r = await pedir(ruta)
  if (r.status === 404) ok(`${ruta} → 404`)
  else falla(`${ruta} respondió ${r.status}`)
}

if (fallos > 0) {
  console.error(`\n${fallos} comprobación(es) fallaron.`)
  process.exit(1)
}
console.log('\nLas cinco políticas cumplen.')
