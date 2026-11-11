/**
 * El carrusel de la portada no debe cambiar de forma al hidratar (hallazgo de la puerta
 * visual, 2026-11-10: CLS de 0,15 a 390 px y 0,21 a 320 px).
 *
 * Causa: el servidor no conoce el ancho del contenedor, así que pintaba el diseño ancho y
 * al montar el componente añadía `responsive-carrusel-govco`; entonces la leyenda pasaba de
 * flotar a fluir bajo la imagen y todo lo de debajo bajaba ~125 px.
 *
 * Esta prueba compara el PRIMER PINTADO (JavaScript apagado: lo que sale del servidor más
 * el CSS) con la página ya hidratada, a varios anchos. Cada elemento del carrusel debe medir
 * y estar colocado igual, dentro de 1 px, respecto de la sección. Es hermética: sitio
 * compilado + API simulada local.
 *
 * Uso: npm run build && npm run test:carrusel
 */
import { spawn } from 'node:child_process'
import { existsSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

import { iniciarApiSimulada } from './ayudas/api-simulada.mjs'
import { lanzar } from './ayudas/motor.mjs'

const raiz = resolve(dirname(fileURLToPath(import.meta.url)), '..')
const PUERTO = Number(process.env.PUERTO_CARRUSEL ?? 3013)
/** Incluye los bordes del corte de 652 px y el de 575 px de la barra de controles. */
const ANCHOS = [320, 360, 390, 575, 576, 640, 651, 652, 700, 1300]
const TOLERANCIA_PX = 1
const esperar = (ms) => new Promise((r) => setTimeout(r, ms))

if (!existsSync(resolve(raiz, '.output/server/index.mjs'))) {
  console.error('No hay compilación del sitio. Ejecute `npm run build` antes.')
  process.exit(2)
}

/** Posición y tamaño de cada elemento visible del carrusel, relativos a la sección. */
function medirCarrusel() {
  const seccion = document.querySelector('.carrusel-govco')
  if (!seccion) return null
  const base = seccion.getBoundingClientRect()
  // La clase `responsive-carrusel-govco` es justo lo que se añade al hidratar: no puede formar parte del nombre.
  const nombre = (e) => {
    const clases = typeof e.className === 'string' ? e.className.trim().split(/\s+/).filter((c) => c && c !== 'responsive-carrusel-govco').slice(0, 2) : []
    return e.tagName.toLowerCase() + (clases.length ? '.' + clases.join('.') : '')
  }
  const lista = []
  const contador = new Map()
  for (const e of [seccion, ...seccion.querySelectorAll('*')]) {
    const r = e.getBoundingClientRect()
    if (r.width === 0 && r.height === 0) continue
    const n = nombre(e)
    const i = (contador.get(n) ?? 0) + 1
    contador.set(n, i)
    lista.push({ clave: `${n}#${i}`, x: r.x - base.x, y: r.y - base.y, ancho: r.width, alto: r.height })
  }
  return { anchoSeccion: Math.round(base.width), elementos: lista }
}

async function medir(navegador, origen, ancho, conJs) {
  const contexto = await navegador.newContext({ viewport: { width: ancho, height: 900 }, javaScriptEnabled: conJs, reducedMotion: 'reduce' })
  const pagina = await contexto.newPage()
  await pagina.goto(origen + '/', { waitUntil: conJs ? 'networkidle' : 'load' })
  // Las fuentes cuentan en los dos casos: el ancho de un icono de fuente cambia al cargarla, y eso
  // no es lo que se mide aquí (el cambio de fuente tiene su propia regla en la puerta visual).
  await pagina.evaluate(() => document.fonts.ready)
  await esperar(conJs ? 600 : 300)
  const medidas = await pagina.evaluate(medirCarrusel)
  await contexto.close()
  return medidas
}

const simulada = await iniciarApiSimulada()
const servidor = spawn('node', ['.output/server/index.mjs'], {
  cwd: raiz,
  env: { ...process.env, NODE_ENV: 'production', HOST: '127.0.0.1', PORT: String(PUERTO), NITRO_PORT: String(PUERTO), SEDE_DOMINIO: 'www.santamarta.gov.co', NUXT_PUBLIC_API_URL: '/api/v1', NUXT_API_INTERNA: simulada.url },
  stdio: 'ignore',
})
let navegador
let codigo = 0
try {
  const origen = `http://127.0.0.1:${PUERTO}`
  for (let i = 0; i < 80; i++) {
    try { if ((await fetch(origen + '/')).status < 500) break } catch { /* aún no escucha */ }
    await esperar(500)
  }
  navegador = await lanzar()
  console.log(`\n  Carrusel: primer pintado (sin JS) frente a página hidratada, tolerancia ${TOLERANCIA_PX} px\n`)
  for (const ancho of ANCHOS) {
    const sinJs = await medir(navegador, origen, ancho, false)
    const conJs = await medir(navegador, origen, ancho, true)
    if (!sinJs || !conJs) { console.log(`  FALLA ${String(ancho).padStart(4)} px — no se encontró el carrusel`); codigo = 1; continue }
    const mapa = new Map(conJs.elementos.map((e) => [e.clave, e]))
    const diferencias = []
    for (const a of sinJs.elementos) {
      const b = mapa.get(a.clave)
      if (!b) { diferencias.push(`${a.clave} existe sin JS y no hidratado`); continue }
      for (const k of ['x', 'y', 'ancho', 'alto']) {
        if (Math.abs(a[k] - b[k]) > TOLERANCIA_PX) diferencias.push(`${a.clave} ${k}: ${a[k].toFixed(1)} → ${b[k].toFixed(1)}`)
      }
    }
    for (const b of conJs.elementos) if (!sinJs.elementos.some((a) => a.clave === b.clave)) diferencias.push(`${b.clave} aparece al hidratar`)
    console.log(`  ${diferencias.length === 0 ? 'OK    ' : 'FALLA '} ${String(ancho).padStart(4)} px (sección ${conJs.anchoSeccion} px)${diferencias.length ? `  ${diferencias.length} diferencia(s)` : ''}`)
    for (const d of diferencias.slice(0, 6)) console.log(`         ${d}`)
    if (diferencias.length > 0) codigo = 1
  }
  console.log(codigo === 0 ? '\n  El carrusel no cambia de forma al hidratar.' : '\n  El carrusel cambia de forma al hidratar: eso es un salto de maquetación.')
} catch (error) {
  console.error(`\n  No se pudo ejecutar: ${error.message}`)
  codigo = 2
} finally {
  await navegador?.close().catch(() => {})
  servidor.kill()
  await simulada.cerrar?.()
}
process.exit(codigo)
