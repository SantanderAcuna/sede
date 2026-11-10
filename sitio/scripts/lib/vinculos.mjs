/**
 * Lógica pura del verificador de vínculos (M01-2, RF-01-012) — sin red propia ni procesos.
 *
 * La usan `tests/vinculos.mjs` (el rastreador, que sí hace red) y `tests/vinculos-lib.test.ts`
 * (que fija los criterios AC-2.1 a AC-2.7 con respuestas inyectadas). Vive en `scripts/lib` y
 * es `.mjs` para que Node la ejecute tal cual, sin compilar, desde el rastreador y desde la
 * canalización.
 */

/**
 * ¿Un vínculo INTERNO está roto? Responde ≥ 400 o no responde (estado 0).
 * 2xx y 3xx no: el rastreador sigue las redirecciones y juzga el destino final.
 */
export function esRoto(estado) {
  return estado === 0 || estado >= 400
}

/**
 * ¿El fallo es pasajero? Sin respuesta, 429 (límite de peticiones) o 502/503/504 (la sede o la
 * API no contestaron a tiempo). La sede responde 503 a propósito cuando la API no contesta, y
 * un rastreo seguido desde una IP agota su límite de peticiones: eso no es un vínculo roto.
 * Un 404 o un 500 sí lo son y no se reintentan.
 */
export function esTransitorio(estado) {
  return estado === 0 || estado === 429 || estado === 502 || estado === 503 || estado === 504
}

/**
 * Pide una página y, si falla por algo pasajero, la reintenta tras cada pausa de `esperasMs`
 * (por defecto 5 s y 15 s: un intento y dos reintentos). Devuelve el último resultado.
 *
 * @template {{ estado: number }} R
 * @param {() => Promise<R>} pedir
 * @param {{ esperasMs?: number[] }} [opciones]
 * @returns {Promise<R>}
 */
export async function comprobarConReintentos(pedir, { esperasMs = [5000, 15000] } = {}) {
  let resultado = await pedir()
  for (const espera of esperasMs) {
    if (!esTransitorio(resultado.estado)) break
    await sleep(espera)
    resultado = await pedir()
  }
  return resultado
}

/** Las descargas no se siguen como páginas: no son HTML y no tienen enlaces que rastrear. */
const DESCARGA = /\.(pdf|odt|png|jpe?g|svg|webp|zip|css|js|ico|xml|txt)(\?|$)/i

/**
 * Separa los enlaces de un HTML en internos (rutas del dominio) y externos (URL absolutas).
 *
 * Ignora lo que no es navegación (`mailto:`, `tel:`, `javascript:`, anclas), lo que está en
 * comentarios HTML (en desarrollo se sirven enlaces de ejemplo) y `/cdn-cgi/` (lo inyecta
 * Cloudflare, no es de la Sede). Quita ancla y, en los internos, también la consulta y la barra
 * final, para no contar dos veces la misma página.
 *
 * @param {string} html
 * @param {string} base  Origen de la sede, sin barra final.
 * @returns {{ internos: Set<string>, externos: Set<string> }}
 */
export function extraerEnlaces(html, base) {
  const internos = new Set()
  const externos = new Set()
  const sinComentarios = html.replace(/<!--[\s\S]*?-->/g, '')

  for (const [, href] of sinComentarios.matchAll(/<a\b[^>]*?\shref="([^"#]*)(?:#[^"]*)?"/g)) {
    if (!href || /^(mailto:|tel:|javascript:)/i.test(href)) continue

    if (href.startsWith('//')) {
      externos.add(`https:${href}`)
      continue
    }

    let ruta = href
    if (/^https?:\/\//i.test(href)) {
      if (!href.startsWith(base)) {
        externos.add(href)
        continue
      }
      ruta = href.slice(base.length) || '/'
    }

    if (!ruta.startsWith('/') || ruta.startsWith('/cdn-cgi/')) continue
    if (DESCARGA.test(ruta)) continue
    internos.add(ruta.split('?')[0].replace(/(.)\/$/, '$1'))
  }

  return { internos, externos }
}

const sleep = (ms) => new Promise((resolver) => setTimeout(resolver, ms))

/**
 * Comprueba un externo: HEAD primero (barato) y, si no basta, GET; un fallo pasajero se
 * reintenta una vez. Devuelve `undefined` si responde bien o `{ url, estado, error? }`.
 */
async function verificarUno(url, pedir, esperaMs) {
  const intentar = async (metodo) => {
    try {
      return { estado: await pedir(url, metodo) }
    } catch (error) {
      return { estado: 0, error: error instanceof Error ? error.message : String(error) }
    }
  }

  // Algunos servidores no admiten HEAD (405/501) o lo bloquean: GET es la prueba definitiva.
  let resultado = await intentar('HEAD')
  if (esRoto(resultado.estado)) resultado = await intentar('GET')

  // Sólo lo pasajero (5xx o sin respuesta) merece un segundo intento; un 404 no se arregla solo.
  if (resultado.estado === 0 || resultado.estado >= 500) {
    await sleep(esperaMs)
    resultado = await intentar('GET')
  }

  if (!esRoto(resultado.estado)) return undefined
  return resultado.error
    ? { url, estado: resultado.estado, error: resultado.error }
    : { url, estado: resultado.estado }
}

/**
 * Verifica los vínculos externos y devuelve SÓLO los que tienen problema. Nunca lanza: un
 * tercero caído es un hallazgo, no un fallo del verificador (AC-2.2).
 *
 * @param {string[]} urls
 * @param {{ pedir: (url: string, metodo: 'HEAD'|'GET') => Promise<number>, concurrencia?: number, esperaMs?: number }} opciones
 */
export async function verificarExternos(urls, { pedir, concurrencia = 4, esperaMs = 1500 }) {
  const problemas = []
  let siguiente = 0

  const trabajador = async () => {
    while (siguiente < urls.length) {
      const url = urls[siguiente]
      siguiente += 1
      const problema = await verificarUno(url, pedir, esperaMs)
      if (problema) problemas.push(problema)
    }
  }

  await Promise.all(Array.from({ length: Math.max(1, Math.min(concurrencia, urls.length)) }, trabajador))
  return problemas.sort((a, b) => a.url.localeCompare(b.url))
}

/** El código de salida depende sólo de los internos rotos (AC-2.5). */
export function codigoDeSalida(internosRotos) {
  return internosRotos.length > 0 ? 1 : 0
}

/**
 * Informe en Markdown, el mismo para la consola, el resumen del trabajo y el aviso al
 * administrador.
 *
 * @param {{ base: string, paginas: number,
 *           internosRotos: Array<{ ruta: string, estado: number, error?: string, origen?: string }>,
 *           externos?: { comprobados: number, problemas: Array<{ url: string, estado: number, error?: string }> } }} datos
 */
export function construirInforme({ base, paginas, internosRotos, externos }) {
  const lineas = [`# Verificación de vínculos — ${base}`, '']
  lineas.push(`Se comprobaron ${paginas} páginas internas.`, '')

  const n = internosRotos.length
  lineas.push(n === 1 ? '**1 vínculo interno roto.**' : `**${n} vínculos internos rotos.**`)
  for (const roto of internosRotos) {
    const motivo = roto.error ? ` — ${roto.error}` : ''
    const origen = roto.origen ? `  (enlazada desde ${roto.origen})` : ''
    lineas.push(`- \`${roto.estado || 'sin respuesta'}\`  \`${roto.ruta}\`${motivo}${origen}`)
  }
  lineas.push('')

  if (!externos) {
    lineas.push('Vínculos externos: no se verificaron en esta ejecución.')
  } else {
    const m = externos.problemas.length
    lineas.push(
      `Vínculos externos: ${externos.comprobados} comprobados, ${m} con problema ` +
        '(se reportan y no bloquean la verificación: su disponibilidad no depende de la Sede).',
    )
    for (const p of externos.problemas) {
      lineas.push(`- \`${p.estado || 'sin respuesta'}\`  ${p.url}${p.error ? ` — ${p.error}` : ''}`)
    }
  }

  return `${lineas.join('\n')}\n`
}
