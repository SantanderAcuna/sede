/**
 * Verifica que no haya vínculos internos rotos (T-21, AC-21.1).
 *
 *   BASE_URL=https://staging.santamarta.gov.co node tests/vinculos.mjs
 *
 * Parte del `sitemap.xml` y de la portada, y sigue los enlaces internos de cada
 * página encontrada (menú, pie, miga de pan, contenido). Para cada miga de pan
 * comprueba también cada nivel intermedio, que es donde estaba el 404 de
 * `/politicas`. Sale con código 1 si alguna dirección interna no responde 200.
 *
 * No sigue enlaces externos: su disponibilidad no depende de la Sede, y un
 * tercero caído haría fallar el CI por algo que no se puede corregir aquí.
 */
const BASE = (process.env.BASE_URL ?? 'http://localhost:3000').replace(/\/$/, '')
const LIMITE = Number(process.env.LIMITE_PAGINAS ?? 400)

async function pedir(ruta) {
  try {
    const respuesta = await fetch(BASE + ruta, { redirect: 'follow', headers: { Accept: 'text/html' } })
    return { estado: respuesta.status, cuerpo: respuesta.ok ? await respuesta.text() : '' }
  } catch (error) {
    return { estado: 0, cuerpo: '', error: String(error) }
  }
}

function enlacesInternos(html) {
  const salida = new Set()
  // Los comentarios HTML pueden citar enlaces de ejemplo (en desarrollo se sirven).
  html = html.replace(/<!--[\s\S]*?-->/g, '')
  for (const [, href] of html.matchAll(/<a\b[^>]*?\shref="([^"#]*)(?:#[^"]*)?"/g)) {
    if (!href || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) continue
    if (href.startsWith('//')) continue
    let ruta = href
    if (/^https?:\/\//.test(href)) {
      if (!href.startsWith(BASE)) continue
      ruta = href.slice(BASE.length) || '/'
    }
    if (!ruta.startsWith('/') || ruta.startsWith('/cdn-cgi/')) continue // /cdn-cgi/: lo inyecta Cloudflare
    if (/\.(pdf|odt|png|jpe?g|svg|webp|zip|css|js|ico|xml|txt)(\?|$)/i.test(ruta)) continue
    salida.add(ruta.split('?')[0].replace(/(.)\/$/, '$1'))
  }
  return salida
}

const pendientes = ['/']
const sitemap = await pedir('/sitemap.xml')
for (const [, loc] of sitemap.cuerpo.matchAll(/<loc>([^<]+)<\/loc>/g)) {
  pendientes.push(loc.startsWith(BASE) ? loc.slice(BASE.length) || '/' : new URL(loc).pathname)
}

const vistos = new Map()
const origen = new Map() // dirección → primera página que la enlaza
const rotos = []
while (pendientes.length && vistos.size < LIMITE) {
  const ruta = pendientes.shift()
  if (vistos.has(ruta)) continue
  const { estado, cuerpo, error } = await pedir(ruta)
  vistos.set(ruta, estado)
  if (estado !== 200) {
    rotos.push({ ruta, estado, error })
    continue
  }
  // Cada nivel de la ruta es un destino posible de la miga de pan.
  const segmentos = ruta.split('/').filter(Boolean)
  for (let i = 1; i < segmentos.length; i++) pendientes.push('/' + segmentos.slice(0, i).join('/'))
  for (const enlace of enlacesInternos(cuerpo)) {
    if (!origen.has(enlace)) origen.set(enlace, ruta)
    if (!vistos.has(enlace)) pendientes.push(enlace)
  }
}

console.log(`Páginas internas comprobadas: ${vistos.size} (${BASE})`)
if (rotos.length) {
  console.error(`Vínculos rotos: ${rotos.length}`)
  for (const r of rotos) console.error(`  ${r.estado || 'sin respuesta'}  ${r.ruta}${r.error ? '  ' + r.error : ''}${origen.has(r.ruta) ? `  (enlazada desde ${origen.get(r.ruta)})` : ''}`)
  process.exit(1)
}
console.log('0 errores.')
