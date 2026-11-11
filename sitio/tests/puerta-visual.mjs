/**
 * Puerta visual automática (decisión de José, 2026-11-10: que se evalúe sola, sin su
 * intervención, y que alcance también a las páginas que se creen de nuevo).
 *
 * Qué hace, en este orden:
 *  1. Levanta el sitio compilado con una API simulada local (hermético, sin Internet).
 *  2. DESCUBRE las rutas publicadas leyendo el `sitemap.xml` del propio sitio —que sale
 *     de `config/sitemap.ts`—: una página que se publica entra sola, sin tocar listas.
 *     Añade los estados que no están en el mapa (buscador con y sin resultados, 404).
 *  3. A cada ruta, en 4 anchos (1300, 768, 390, 320): mide propiedades objetivas
 *     (PV-01…PV-08: reflujo, imágenes, fuentes, salto de maquetación, controles de
 *     tamaño mínimo, errores) y pasa `axe` (WCAG 2.1 A/AA; fallan crítico y serio).
 *  4. PV-09: la cabecera y el pie son los mismos en todas las páginas.
 *  5. Guarda una captura por ruta y ancho en `SALIDA_VISUAL` (por defecto
 *     `salida-visual/`), para quien quiera mirar.
 *  6. Con `--contra=<rama>` compila esa rama en un árbol temporal, captura lo mismo y
 *     INFORMA qué vistas cambiaron de aspecto, más los archivos de diseño que toca el
 *     cambio. Es un informe, no una puerta: juzgar si un cambio de aspecto es deseado
 *     es de quien aprueba el diseño, y ninguna propiedad medible lo decide.
 *
 * Falla (código 1) si alguna propiedad objetiva o axe se incumple; 2 si no pudo medir.
 *
 * Uso:  npm run build && npm run test:puerta-visual
 *       npm run test:puerta-visual -- --contra=origin/develop
 */
import { execFileSync, spawn } from 'node:child_process'
import { appendFileSync, existsSync, readFileSync } from 'node:fs'
import { mkdir, mkdtemp, rm, symlink, writeFile } from 'node:fs/promises'
import { tmpdir } from 'node:os'
import { dirname, join, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

import AxeBuilder from '@axe-core/playwright'

import { compararJuegos, resumirComparacion } from '../scripts/lib/captura-ab.mjs'
import { ANCHOS, archivosDeDiseno, juzgarCromo, juzgarMedidas, rutasDelSitemap } from '../scripts/lib/puerta-visual.mjs'
import { iniciarApiSimulada } from './ayudas/api-simulada.mjs'
import { lanzar } from './ayudas/motor.mjs'

const raiz = resolve(dirname(fileURLToPath(import.meta.url)), '..')
const AUTOPRUEBA = process.argv.includes('--autoprueba')
const ESCRIBIR_BASE = process.argv.includes('--escribir-base')
const ARCHIVO_BASE = resolve(raiz, 'tests/puerta-visual-base.json')
const CONTRA = process.argv.find((a) => a.startsWith('--contra='))?.slice('--contra='.length)
const SALIDA = resolve(raiz, process.env.SALIDA_VISUAL ?? 'salida-visual')
const PUERTO = Number(process.env.PUERTO_VISUAL ?? 3014)
const PUERTO_BASE = PUERTO + 1

/** Estados que el mapa del sitio no lista pero que también son diseño. */
const EXTRAS = [
  { ruta: '/buscar?q=salud', estadoEsperado: 200 },
  { ruta: '/buscar?q=zzzz', estadoEsperado: 200 },
  { ruta: '/ruta-que-no-existe', estadoEsperado: 404 },
]

/** Los anchos en los que se compara el aspecto contra la rama base (los extremos). */
const ANCHOS_AB = [1300, 390]
const ETIQUETAS_AXE = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']
const BLOQUEANTES = new Set(['critical', 'serious'])
const esperar = (ms) => new Promise((r) => setTimeout(r, ms))

async function esperarServidor(origen, intentos = 80) {
  let ultimo = 0
  for (let i = 0; i < intentos; i++) {
    try {
      ultimo = (await fetch(`${origen}/`, { redirect: 'manual' })).status
      if (ultimo < 500) return
    } catch {
      /* todavía no escucha */
    }
    await esperar(500)
  }
  throw new Error(ultimo >= 500 ? `${origen} responde ${ultimo}` : `${origen} no respondió tras 40 s`)
}

function servir(directorio, puerto, apiInterna) {
  const proceso = spawn('node', ['.output/server/index.mjs'], {
    cwd: directorio,
    env: {
      ...process.env,
      NODE_ENV: 'production',
      HOST: '127.0.0.1',
      PORT: String(puerto),
      NITRO_PORT: String(puerto),
      SEDE_DOMINIO: 'www.santamarta.gov.co',
      NUXT_PUBLIC_API_URL: '/api/v1',
      NUXT_API_INTERNA: apiInterna,
    },
    stdio: 'ignore',
  })
  return proceso
}

/** Cierra el aviso de cookies y el aviso emergente que deja: tapan la parte alta. */
async function cerrarAvisos(pagina) {
  for (const boton of await pagina.locator('button').all()) {
    const texto = ((await boton.textContent()) || '').toLowerCase()
    if (texto.includes('aceptar') || texto.includes('rechazar')) {
      await boton.click().catch(() => {})
      await esperar(300)
      await pagina.getByRole('button', { name: 'Cerrar notificación' }).first().click({ timeout: 1000 }).catch(() => {})
      await esperar(200)
      return
    }
  }
}

/** Lo que se mide DENTRO de la página. Devuelve datos crudos; el juicio es de `juzgarMedidas`. */
function medirEnPagina() {
  const vw = document.documentElement.clientWidth
  const selector = (el) => {
    const clases = [...el.classList].slice(0, 2).join('.')
    return el.tagName.toLowerCase() + (el.id ? `#${el.id}` : '') + (clases ? `.${clases}` : '')
  }
  const visible = (el) => {
    const r = el.getBoundingClientRect()
    const cs = getComputedStyle(el)
    return r.width > 0 && r.height > 0 && cs.visibility !== 'hidden' && cs.display !== 'none'
  }
  const recortado = (el) => {
    for (let p = el.parentElement; p && p !== document.body; p = p.parentElement) {
      if (/(auto|scroll|hidden|clip)/.test(getComputedStyle(p).overflowX)) return true
    }
    return false
  }
  const firma = (el) => {
    if (!el) return 'ausente'
    const clases = [...el.classList].filter((c) => !/^(active|current|show|collapsed|is-)/.test(c)).sort().join('.')
    return `${el.tagName}.${clases}[${[...el.children].map(firma).join(',')}]`
  }

  const fuera = []
  for (const el of document.querySelectorAll('main *')) {
    if (!visible(el)) continue
    const r = el.getBoundingClientRect()
    if (r.right > vw + 1 && getComputedStyle(el).position !== 'fixed' && !recortado(el)) {
      fuera.push({ selector: selector(el), derecha: Math.round(r.right) })
    }
  }

  const pequenos = []
  const controles = 'a[href], button, input:not([type=hidden]), select, textarea, [role=button]'
  for (const el of document.querySelectorAll(controles)) {
    if (!visible(el)) continue
    const cs = getComputedStyle(el)
    const r = el.getBoundingClientRect()
    // Exentos por WCAG 2.5.8: enlaces en línea dentro de un texto. Y lo que sólo aparece al enfocar (saltar al contenido).
    if (cs.display === 'inline' && el.tagName === 'A') continue
    if (cs.clip !== 'auto' || /visually-hidden|sr-only|skip/.test(el.className)) continue
    if (r.width < 24 || r.height < 24) pequenos.push({ selector: selector(el), ancho: Math.round(r.width), alto: Math.round(r.height) })
  }

  return {
    titulo: document.title,
    lang: document.documentElement.lang,
    h1: document.querySelectorAll('h1').length,
    tieneMain: !!document.querySelector('main'),
    tieneCabecera: !!document.querySelector('header'),
    tienePie: !!document.querySelector('footer'),
    desborde: Math.max(0, document.documentElement.scrollWidth - vw),
    elementosFuera: fuera,
    imagenesRotas: [...document.images].filter((i) => i.complete && i.naturalWidth === 0 && i.currentSrc).map((i) => i.getAttribute('src') ?? ''),
    imagenesSinAlt: [...document.images].filter((i) => !i.hasAttribute('alt')).length,
    fuentesConError: [...document.fonts].filter((f) => f.status === 'error').map((f) => f.family),
    cls: window.__cls ?? 0,
    objetivosPequenos: pequenos,
    firmaCabecera: firma(document.querySelector('header')),
    firmaPie: firma(document.querySelector('footer')),
  }
}

/** Abre una ruta a un ancho, la mide, la captura y (opcional) pasa axe. */
async function visitar(navegador, origen, { ruta, estadoEsperado }, ancho, { axe, capturar, mutar }) {
  const contexto = await navegador.newContext({ viewport: { width: ancho, height: 900 }, deviceScaleFactor: 1, reducedMotion: 'reduce' })
  const pagina = await contexto.newPage()
  const erroresConsola = []
  const recursosFallidos = []

  pagina.on('pageerror', (e) => erroresConsola.push(String(e.message).slice(0, 160)))
  pagina.on('console', (m) => {
    // «Failed to load resource» ya se cuenta como recurso fallido con su estado.
    if (m.type() === 'error' && !/Failed to load resource/.test(m.text())) erroresConsola.push(m.text().slice(0, 160))
  })
  pagina.on('response', (r) => {
    const url = new URL(r.url())
    // Sólo lecturas: la API simulada responde 405 a cualquier escritura (consentimiento, formularios) por diseño.
    if (r.status() >= 400 && r.request().method() === 'GET' && r.request().resourceType() !== 'document') recursosFallidos.push({ url: url.pathname, estado: r.status() })
  })
  await pagina.addInitScript(() => {
    window.__cls = 0
    new PerformanceObserver((lista) => {
      for (const e of lista.getEntries()) if (!e.hadRecentInput) window.__cls += e.value
    }).observe({ type: 'layout-shift', buffered: true })
  })

  if (mutar) {
    // Sólo en la autoprueba: se estropea la respuesta DENTRO del navegador; el sitio no se toca.
    await pagina.route((url) => url.pathname === '/', async (route) => {
      const original = await route.fetch()
      await route.fulfill({ response: original, body: mutar(await original.text()) })
    })
  }
  const respuesta = await pagina.goto(origen + ruta, { waitUntil: 'networkidle' })
  await cerrarAvisos(pagina)
  await pagina.evaluate(() => document.fonts.ready)
  await esperar(300)

  const medidas = await pagina.evaluate(medirEnPagina)
  const resultado = { ruta, ancho, estado: respuesta?.status() ?? 0, estadoEsperado, erroresConsola, recursosFallidos, ...medidas }

  let violacionesAxe = []
  if (axe) {
    const r = await new AxeBuilder({ page: pagina }).withTags(ETIQUETAS_AXE).analyze()
    violacionesAxe = r.violations
      .filter((v) => BLOQUEANTES.has(v.impact ?? ''))
      .map((v) => ({ regla: 'AXE', ruta, ancho, mensaje: `[${v.impact}] ${v.id}: ${v.help} (${v.nodes.length} nodo(s))` }))
  }

  await pagina.evaluate(() => window.scrollTo(0, 0))
  const imagen = capturar ? await pagina.screenshot({ fullPage: true }) : null
  await contexto.close()
  return { resultado, violacionesAxe, imagen }
}

/** Lo que el diseño aprobado ya tiene (ver `tests/puerta-visual-base.json`): puede bajar, no subir. */
function leerBase() {
  if (ESCRIBIR_BASE || !existsSync(ARCHIVO_BASE)) return {}
  return JSON.parse(readFileSync(ARCHIVO_BASE, 'utf8'))
}

const nombreDeArchivo = (ruta, ancho) => `${ruta === '/' ? 'inicio' : ruta.replace(/^\//, '').replace(/[^a-z0-9]+/gi, '_')}@${ancho}.png`

async function rutasADescubrir(origen) {
  const respuesta = await fetch(`${origen}/sitemap.xml`)
  if (!respuesta.ok) throw new Error(`/sitemap.xml responde ${respuesta.status}: no se pueden descubrir las rutas`)
  const rutas = rutasDelSitemap(await respuesta.text())
  if (rutas.length === 0) throw new Error('El sitemap.xml no lista ninguna ruta: no hay nada que medir')
  return [...rutas.map((ruta) => ({ ruta, estadoEsperado: 200 })), ...EXTRAS]
}

async function contraLaRama(navegador, ref, rutas, apiInterna, capturasActual) {
  const toplevel = execFileSync('git', ['rev-parse', '--show-toplevel'], { encoding: 'utf8' }).trim()
  const arbol = await mkdtemp(join(tmpdir(), 'visual-base-'))
  let servidor = null
  try {
    execFileSync('git', ['worktree', 'add', '--detach', '-q', arbol, ref], { cwd: toplevel, stdio: 'inherit' })
    await symlink(join(raiz, 'node_modules'), join(arbol, 'sitio/node_modules'))
    execFileSync('npx', ['nuxt', 'build'], { cwd: join(arbol, 'sitio'), stdio: 'ignore' })
    servidor = servir(join(arbol, 'sitio'), PUERTO_BASE, apiInterna)
    await esperarServidor(`http://127.0.0.1:${PUERTO_BASE}`)

    const base = new Map()
    for (const ruta of rutas) {
      for (const ancho of ANCHOS_AB) {
        const { imagen } = await visitar(navegador, `http://127.0.0.1:${PUERTO_BASE}`, ruta, ancho, { axe: false, capturar: true })
        base.set(nombreDeArchivo(ruta.ruta, ancho), imagen)
      }
    }
    const archivos = execFileSync('git', ['diff', '--name-only', `${ref}...HEAD`], { cwd: toplevel, encoding: 'utf8' }).split('\n').filter(Boolean)
    return { vistas: compararJuegos(base, capturasActual), diseno: archivosDeDiseno(archivos) }
  } finally {
    if (servidor) servidor.kill()
    try {
      execFileSync('git', ['worktree', 'remove', '--force', arbol], { cwd: toplevel, stdio: 'ignore' })
    } catch {
      /* ya no está registrado */
    }
    await rm(arbol, { recursive: true, force: true }).catch(() => {})
  }
}

/**
 * Defectos conocidos, uno por regla, aplicados DESPUÉS de que Vue hidrata (lo que se
 * inyecta en el HTML del servidor, la hidratación lo borra). Si la puerta deja de verlos,
 * la puerta está rota.
 */
const enPagina = (js) => (html) =>
  html.replace(
    '</body>',
    `<script>(function(){var t=setInterval(function(){var n=document.querySelector('#__nuxt');if(n&&n.__vue_app__){clearInterval(t);setTimeout(function(){var m=document.querySelector('main');${js}},250)}},25)})()</script></body>`,
  )

const DEFECTOS = {
  'PV-02': enPagina("document.documentElement.lang='en'"),
  'PV-03': enPagina("var d=document.createElement('div');d.style.cssText='width:2400px;height:4px;background:#000';m.appendChild(d)"),
  'PV-04': enPagina("var i=document.createElement('img');i.src='/__roto.png';i.alt='roto';m.appendChild(i);var j=document.createElement('img');j.src='data:image/gif;base64,R0lGODlhAQABAAAAACw=';m.appendChild(j)"),
  'PV-05': enPagina("var s=document.createElement('style');s.textContent='@font-face{font-family:Roto;src:url(/__roto.woff2)}';document.head.appendChild(s);var p=document.createElement('p');p.style.fontFamily='Roto';p.textContent='x';m.appendChild(p);document.fonts.load('16px Roto').catch(function(){})"),
  'PV-06': enPagina("var d=document.createElement('div');d.style.height='900px';m.prepend(d)"),
  'PV-07': enPagina("for(var k=0;k<7;k++){var b=document.createElement('button');b.type='button';b.textContent='x';b.style.cssText='width:10px;height:10px;padding:0';m.appendChild(b)}"),
  'PV-08': enPagina("console.error('autoprueba');setTimeout(function(){throw new Error('autoprueba')},10)"),
  AXE: enPagina("var p=document.createElement('p');p.style.cssText='color:#ccc;background:#fff;font-size:14px';p.textContent='contraste insuficiente';m.appendChild(p)"),
}

async function autoprueba(navegador, origen) {
  const base = leerBase()
  let ciega = 0
  for (const [regla, mutar] of Object.entries(DEFECTOS)) {
    const vistos = new Set()
    for (const ancho of [1300, 390]) {
      const { resultado, violacionesAxe } = await visitar(navegador, origen, { ruta: '/', estadoEsperado: 200 }, ancho, { axe: regla === 'AXE', capturar: false, mutar })
      for (const x of [...juzgarMedidas(resultado, base), ...violacionesAxe]) vistos.add(x.regla)
    }
    const detectada = vistos.has(regla)
    if (!detectada) ciega++
    console.log(`  ${detectada ? 'DETECTA' : 'CIEGA  '} ${regla}`)
  }
  // Y sin defectos, la portada pasa: la autoprueba no vale si todo falla siempre.
  const { resultado } = await visitar(navegador, origen, { ruta: '/', estadoEsperado: 200 }, 390, { axe: false, capturar: false })
  const limpia = juzgarMedidas(resultado, base)
  console.log(`  ${limpia.length === 0 ? 'LIMPIA ' : 'FALLA  '} portada sin defectos`)
  return ciega + limpia.length
}

if (!existsSync(resolve(raiz, '.output/server/index.mjs'))) {
  console.error('No hay compilación del sitio. Ejecute `npm run build` antes.')
  process.exit(2)
}

const simulada = await iniciarApiSimulada()
const servidor = servir(raiz, PUERTO, simulada.url)
let navegador
let codigo = 0

async function terminar(salida) {
  await navegador?.close().catch(() => {})
  servidor.kill()
  await simulada.cerrar?.()
  process.exit(salida)
}

try {
  const origen = `http://127.0.0.1:${PUERTO}`
  await esperarServidor(origen)
  navegador = await lanzar()
  await mkdir(SALIDA, { recursive: true })

  if (AUTOPRUEBA) {
    console.log('\n  Autoprueba: cada defecto inyectado debe ser detectado por su regla\n')
    const fallos = await autoprueba(navegador, origen)
    console.log(fallos === 0 ? '\n  La puerta detecta todos los defectos conocidos.' : `\n  ${fallos} defecto(s) sin detectar o portada que falla: la puerta no es fiable.`)
    await terminar(fallos === 0 ? 0 : 1)
  }

  const rutas = await rutasADescubrir(origen)
  console.log(`\n  ${rutas.length} rutas (${rutas.length - EXTRAS.length} del sitemap + ${EXTRAS.length} estados) × ${ANCHOS.length} anchos\n`)

  const base = leerBase()
  const medidas = []
  const violaciones = []
  const capturas = new Map()

  for (const ruta of rutas) {
    for (const ancho of ANCHOS) {
      const axe = ancho === 1300 || ancho === 390
      const { resultado, violacionesAxe, imagen } = await visitar(navegador, origen, ruta, ancho, { axe, capturar: true })
      medidas.push(resultado)
      const propias = [...juzgarMedidas(resultado, base), ...violacionesAxe]
      violaciones.push(...propias)
      const nombre = nombreDeArchivo(ruta.ruta, ancho)
      await writeFile(join(SALIDA, nombre), imagen)
      if (ANCHOS_AB.includes(ancho)) capturas.set(nombre, imagen)
      console.log(`  ${propias.length === 0 ? 'OK     ' : 'FALLA  '} ${ruta.ruta.padEnd(46)} ${String(ancho).padStart(4)} px${propias.length ? `  ${propias.length} violación(es)` : ''}`)
    }
  }

  // PV-09 se juzga sobre las rutas normales: la página 404 tiene su propia maqueta mínima por diseño.
  violaciones.push(...juzgarCromo(medidas.filter((m) => m.estadoEsperado === 200)))

  if (ESCRIBIR_BASE) {
    const observado = { objetivosPequenos: {}, cls: {} }
    for (const m of medidas) {
      if (m.objetivosPequenos.length > 0) observado.objetivosPequenos[m.ruta] = Math.max(observado.objetivosPequenos[m.ruta] ?? 0, m.objetivosPequenos.length)
      if (m.cls > 0.1) observado.cls[m.ruta] = Math.max(observado.cls[m.ruta] ?? 0, Math.ceil(m.cls * 100) / 100)
    }
    console.log(`\n  Observado (para la línea base):\n${JSON.stringify(observado, null, 2)}`)
    await writeFile(join(SALIDA, 'base-observada.json'), JSON.stringify(observado, null, 2) + '\n')
  }

  let ab = null
  const avisos = []
  if (CONTRA) {
    console.log(`\n  Comparando el aspecto con ${CONTRA} (informativo)…`)
    try {
      ab = await contraLaRama(navegador, CONTRA, rutas, simulada.url, capturas)
    } catch (error) {
      // La comparación es informativa: que no se pueda hacer no debe tapar ni cambiar el veredicto de la puerta.
      console.log(`  La comparación con ${CONTRA} no se pudo hacer: ${String(error.message).slice(0, 200)}`)
      avisos.push(`La comparación de aspecto con \`${CONTRA}\` no se pudo hacer: ${String(error.message).slice(0, 200)}`)
    }
  }

  // ── Informe ────────────────────────────────────────────────────────────────
  console.log('')
  if (violaciones.length === 0) console.log('  Todas las propiedades objetivas se cumplen en todas las rutas y anchos.')
  for (const v of violaciones) console.log(`  [${v.regla}] ${v.ruta} @${v.ancho}px — ${v.mensaje}`)

  const lineas = ['## Puerta visual automática', '', `${rutas.length} rutas × ${ANCHOS.length} anchos, descubiertas desde \`/sitemap.xml\`.`, '']
  lineas.push(violaciones.length === 0 ? '✅ **Todas las propiedades objetivas se cumplen.**' : `❌ **${violaciones.length} violación(es):**`, '')
  for (const v of violaciones) lineas.push(`- \`${v.regla}\` \`${v.ruta}\` @${v.ancho}px — ${v.mensaje}`)
  for (const a of avisos) lineas.push('', `⚠️ ${a}`)
  if (ab) {
    const cambiadas = ab.vistas.filter((v) => v.estado !== 'igual')
    lineas.push('', `### Aspecto frente a \`${CONTRA}\` (informativo, no bloquea)`, '')
    lineas.push(cambiadas.length === 0 ? '✅ Ninguna vista cambió de aspecto.' : `⚠️ ${cambiadas.length} de ${ab.vistas.length} vistas cambiaron:`, '')
    for (const v of cambiadas) lineas.push(`- \`${v.nombre}\`: ${v.alturaBase ?? '—'} → ${v.alturaActual ?? '—'} px (${v.estado})`)
    lineas.push('', ab.diseno.length === 0 ? 'Este cambio **no toca archivos de diseño**.' : `Este cambio **toca ${ab.diseno.length} archivo(s) de diseño**:`, '')
    for (const a of ab.diseno) lineas.push(`- \`${a}\``)
    console.log(`\n  Aspecto frente a ${CONTRA}: ${resumirComparacion(ab.vistas).distintas} de ${ab.vistas.length} vistas cambiaron; archivos de diseño tocados: ${ab.diseno.length}`)
  }
  if (process.env.GITHUB_STEP_SUMMARY) appendFileSync(process.env.GITHUB_STEP_SUMMARY, lineas.join('\n') + '\n')
  await writeFile(join(SALIDA, 'informe.md'), lineas.join('\n') + '\n')

  const desconocidas = simulada.rutasDesconocidas()
  if (desconocidas.length > 0) console.log(`\n  ℹ la API simulada no conoce: ${desconocidas.join(', ')}`)

  codigo = violaciones.length > 0 ? 1 : 0
} catch (error) {
  console.error(`\n  La puerta visual no pudo ejecutarse: ${error.message}`)
  codigo = 2
}
await terminar(codigo)
