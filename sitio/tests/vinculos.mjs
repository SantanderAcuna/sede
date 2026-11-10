/**
 * Verifica los vínculos de la Sede (T-21 AC-21.1 · M01-2 RF-01-012).
 *
 *   BASE_URL=https://staging.santamarta.gov.co node tests/vinculos.mjs
 *   BASE_URL=... EXTERNOS=1 node tests/vinculos.mjs            # además, verifica los externos
 *   BASE_URL=... INFORME=informe.md node tests/vinculos.mjs     # escribe el informe en Markdown
 *   BASE_URL=... RUTAS_EXTRA=/a,/b node tests/vinculos.mjs      # rutas que se suman a las de partida
 *   BASE_URL=... PAUSA_MS=250 node tests/vinculos.mjs           # pausa entre páginas (por defecto 250)
 *
 * Parte del `sitemap.xml` y de la portada, y sigue los enlaces internos de cada página
 * encontrada (menú, pie, miga de pan, contenido). Para cada miga de pan comprueba también cada
 * nivel intermedio, que es donde estaba el 404 de `/politicas`.
 *
 * - **Internos:** salen con código 1 si alguno responde ≥ 400 o no responde (AC-2.1, AC-2.5).
 * - **Fallos pasajeros** (sin respuesta, 429, 502, 503, 504) se reintentan tras una pausa antes de
 *   darlos por rotos (AC-2.8): la sede responde 503 a propósito cuando la API no contesta, y un
 *   rastreo seguido desde una IP agota el límite de la API.
 * - **Externos** (sólo con `EXTERNOS=1`): se verifican y se REPORTAN aparte; nunca hacen fallar
 *   la verificación, porque su disponibilidad no depende de la Sede (AC-2.2, AC-2.3).
 *
 * La lógica pura vive en `scripts/lib/vinculos.mjs`, con sus pruebas en
 * `tests/vinculos-lib.test.ts`. La ejecuta cada semana `.github/workflows/vinculos.yml`.
 */
import { writeFileSync } from 'node:fs'

import {
  codigoDeSalida,
  comprobarConReintentos,
  construirInforme,
  esRoto,
  extraerEnlaces,
  pistaDeRespuesta,
  verificarExternos,
} from '../scripts/lib/vinculos.mjs'

const BASE = (process.env.BASE_URL ?? 'http://localhost:3000').replace(/\/$/, '')
const LIMITE = Number(process.env.LIMITE_PAGINAS ?? 400)
const VERIFICAR_EXTERNOS = process.env.EXTERNOS === '1'
// El verificador se identifica: quien administre el borde puede reconocerlo y permitirlo.
const AGENTE = 'SedeVerificadorVinculos/1.0 (+https://github.com/SantanderAcuna/sede)'
const PAUSA_MS = Number(process.env.PAUSA_MS ?? 250)
const RUTAS_EXTRA = (process.env.RUTAS_EXTRA ?? '').split(',').map((r) => r.trim()).filter(Boolean)

async function pedir(ruta) {
  try {
    const respuesta = await fetch(BASE + ruta, {
      redirect: 'follow',
      headers: { Accept: 'text/html', 'User-Agent': AGENTE },
    })
    const cuerpo = await respuesta.text()
    return {
      estado: respuesta.status,
      cuerpo: respuesta.ok ? cuerpo : '',
      pista: respuesta.ok ? '' : pistaDeRespuesta(respuesta.headers, cuerpo),
    }
  } catch (error) {
    return { estado: 0, cuerpo: '', error: String(error) }
  }
}

const pendientes = ['/', ...RUTAS_EXTRA]
const sitemap = await pedir('/sitemap.xml')
for (const [, loc] of sitemap.cuerpo.matchAll(/<loc>([^<]+)<\/loc>/g)) {
  pendientes.push(loc.startsWith(BASE) ? loc.slice(BASE.length) || '/' : new URL(loc).pathname)
}

const vistos = new Map()
const origen = new Map() // dirección → primera página que la enlaza
const externos = new Set()
const rotos = []
let inicio // si la portada falla, no se pudo iniciar: no es un vínculo roto de la Sede
while (pendientes.length && vistos.size < LIMITE) {
  const ruta = pendientes.shift()
  if (vistos.has(ruta)) continue
  const { estado, cuerpo, error, pista } = await comprobarConReintentos(() => pedir(ruta))
  vistos.set(ruta, estado)
  await new Promise((resolver) => setTimeout(resolver, PAUSA_MS))
  if (esRoto(estado)) {
    rotos.push({ ruta, estado, error: error ?? (pista || undefined), origen: origen.get(ruta) })
    if (ruta === '/') inicio = { ruta, estado, pista }
    continue
  }
  // Cada nivel de la ruta es un destino posible de la miga de pan.
  const segmentos = ruta.split('/').filter(Boolean)
  for (let i = 1; i < segmentos.length; i++) pendientes.push('/' + segmentos.slice(0, i).join('/'))
  const enlaces = extraerEnlaces(cuerpo, BASE)
  for (const externo of enlaces.externos) externos.add(externo)
  for (const enlace of enlaces.internos) {
    if (!origen.has(enlace)) origen.set(enlace, ruta)
    if (!vistos.has(enlace)) pendientes.push(enlace)
  }
}

let resultadoExternos
if (VERIFICAR_EXTERNOS) {
  const urls = [...externos].sort()
  const problemas = await verificarExternos(urls, {
    pedir: async (url, metodo) => {
      const controlador = new AbortController()
      const plazo = setTimeout(() => controlador.abort(), 15_000)
      try {
        const respuesta = await fetch(url, {
          method: metodo,
          redirect: 'follow',
          headers: { 'User-Agent': AGENTE },
          signal: controlador.signal,
        })
        return respuesta.status
      } finally {
        clearTimeout(plazo)
      }
    },
  })
  resultadoExternos = { comprobados: urls.length, problemas }
}

const informe = construirInforme({ base: BASE, paginas: vistos.size, internosRotos: rotos, externos: resultadoExternos, inicio })
console.log(informe)
if (process.env.INFORME) writeFileSync(process.env.INFORME, informe, 'utf-8')

process.exit(codigoDeSalida(rotos))
