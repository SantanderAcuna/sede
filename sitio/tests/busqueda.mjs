/**
 * Búsqueda en navegador real (T-22, E3-4 · `docs/busqueda-interaccion.md` §8).
 *
 * Cubre los casos que no se pueden decidir sin un navegador:
 *
 *   TC-BUS-26  axe-core sobre `/buscar` y el encabezado con la lista abierta, cerrada,
 *              sin resultados y con error: 0 violaciones críticas o serias.
 *   TC-BUS-27  Atributos ARIA exactos de §4.2 y teclado de §4.3 (↑ ↓ Alt+↓ Intro Esc
 *              Tab, el recorrido por el campo); el foco visible no se pierde.
 *   TC-BUS-28  Los siete estados de §4.4 y los ocho de §5.2 con su texto; objetivos
 *              ≥ 44 px (filas ≥ 48 px) medidos por su caja; el círculo flotante no
 *              tapa la lista; capturas a 360 y 1280 px.
 *   TC-BUS-32  `/buscar?q=`: filtro por tipo, paginación, «sin resultados», grupo
 *              «Secciones de la sede» y resultados **sin JavaScript**.
 *   TC-BUS-33  Reflujo a 320 px y con la letra al 200 %: sin desplazamiento horizontal.
 *   TC-BUS-34  Alto contraste y letra grande: la lista y la fila activa se distinguen.
 *   TC-BUS-35  Tras varias búsquedas, `localStorage`, `sessionStorage`, cookies e
 *              IndexedDB no cambian; la consulta no sale a ningún otro sitio ni a la consola.
 *   TC-BUS-37  Secciones de la sede (M01-3): en el desplegable y en `/buscar`, sólo las
 *              publicadas, con tolerancia de una letra; «sin resultados» una sola vez.
 *   TC-BUS-36  Escritura lenta (500 ms entre teclas, 20 caracteres): el número de
 *              peticiones coincide con el modelo y ninguna respuesta vieja se muestra.
 *
 * TC-BUS-29 (lector de pantalla real) es manual y no está aquí.
 *
 * Levanta la API simulada (`tests/ayudas/api-simulada.mjs`, contenido «de prueba») y
 * el sitio ya compilado; para forzar el desorden, la lentitud y los errores que la
 * API simulada no produce intercepta las peticiones **desde el navegador** (las de
 * la página; las del servidor, que son las de la primera carga, no se tocan).
 *
 * Uso:
 *   npm run build && npm run test:busqueda
 *   PUERTO_BUSQUEDA=3013 CAPTURAS_BUSQUEDA=/ruta npm run test:busqueda
 *
 * Las capturas se guardan fuera del repositorio (por defecto en el directorio
 * temporal) para que las mire una persona: no son referencias que se comparen.
 *
 * Sale con código 1 si algo falla.
 */
import { spawn } from 'node:child_process'
import { existsSync, mkdirSync } from 'node:fs'
import { homedir, tmpdir } from 'node:os'
import { dirname, join, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const RUTA_NAVEGADORES = process.env.PLAYWRIGHT_BROWSERS_PATH ?? join(homedir(), '.cache', 'ms-playwright')
if (!process.env.PLAYWRIGHT_BROWSERS_PATH && existsSync(RUTA_NAVEGADORES)) {
  process.env.PLAYWRIGHT_BROWSERS_PATH = RUTA_NAVEGADORES
}

// Después de fijar la variable: Playwright resuelve la ruta de sus navegadores al cargarse.
const { lanzar } = await import('./ayudas/motor.mjs')
const { iniciarApiSimulada } = await import('./ayudas/api-simulada.mjs')

const raiz = resolve(dirname(fileURLToPath(import.meta.url)), '..')
const puerto = Number(process.env.PUERTO_BUSQUEDA ?? 3013)
const BASE = `http://127.0.0.1:${puerto}`
const rutaAxe = resolve(raiz, 'node_modules/axe-core/axe.min.js')
const CAPTURAS = process.env.CAPTURAS_BUSQUEDA ?? join(tmpdir(), 'busqueda-capturas')
mkdirSync(CAPTURAS, { recursive: true })

const ETIQUETAS_AXE = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']
const BLOQUEANTES = new Set(['critical', 'serious'])

/** Lo que el modelo de §4.5 dice: pausa antes de preguntar, y el mínimo de caracteres. */
const PAUSA_MS = 350
const MINIMO = 3

let fallos = 0
const ok = (m) => console.log(`  ✓ ${m}`)
const falla = (m) => {
  fallos += 1
  console.error(`  ✗ ${m}`)
}
const comprobar = (condicion, bien, mal) => (condicion ? ok(bien) : falla(mal))
const igual = (real, esperado, etiqueta) =>
  comprobar(
    JSON.stringify(real) === JSON.stringify(esperado),
    `${etiqueta}: ${JSON.stringify(esperado)}`,
    `${etiqueta}: esperaba ${JSON.stringify(esperado)} y hay ${JSON.stringify(real)}`,
  )
const pausa = (ms) => new Promise((seguir) => setTimeout(seguir, ms))

// ---------------------------------------------------------------------------
// Arranque: API simulada + sitio compilado
// ---------------------------------------------------------------------------

if (!existsSync(resolve(raiz, '.output/server/index.mjs'))) {
  console.error('No hay compilación del sitio. Ejecute `npm run build` antes.')
  process.exit(2)
}

const simulada = await iniciarApiSimulada()
const servidor = spawn('node', ['.output/server/index.mjs'], {
  cwd: raiz,
  env: {
    ...process.env,
    NODE_ENV: 'production',
    HOST: '127.0.0.1',
    PORT: String(puerto),
    NITRO_PORT: String(puerto),
    SEDE_DOMINIO: 'www.santamarta.gov.co',
    NUXT_PUBLIC_API_URL: '/api/v1',
    NUXT_API_INTERNA: simulada.url,
  },
  stdio: ['ignore', 'pipe', 'pipe'],
})
let salidaServidor = ''
servidor.stdout?.on('data', (t) => (salidaServidor += String(t)))
servidor.stderr?.on('data', (t) => (salidaServidor += String(t)))

async function esperarServidor(intentos = 60) {
  for (let i = 0; i < intentos; i += 1) {
    try {
      const r = await fetch(`${BASE}/`, { redirect: 'manual' })
      if (r.status < 500) return
    } catch {
      // Todavía no escucha.
    }
    await pausa(500)
  }
  throw new Error(`El sitio no respondió en ${BASE} tras 30 s.\n${salidaServidor}`)
}

// ---------------------------------------------------------------------------
// Utilidades de página
// ---------------------------------------------------------------------------

let navegador

/** Contexto con las preferencias de accesibilidad sembradas (como las deja el composable). */
async function contexto(ancho, alto, { prefs, js = true } = {}) {
  const ctx = await navegador.newContext({ viewport: { width: ancho, height: alto }, javaScriptEnabled: js, deviceScaleFactor: 1 })
  if (prefs) {
    const guardadas = {
      v: 2, contraste: 'normal', letra: 0, espaciado: false, dislexia: false,
      resaltarEnlaces: false, guiaLectura: false, detenerAnimaciones: false, ...prefs,
    }
    await ctx.addInitScript((p) => {
      try { localStorage.setItem('sede-accesibilidad', JSON.stringify(p)) } catch { /* sin almacenamiento */ }
    }, guardadas)
  }
  return ctx
}

/** Abre una ruta y oculta el banner de cookies, que no es objeto de estas pruebas. */
async function abrir(ctx, ruta, { esperar = 'networkidle' } = {}) {
  const p = await ctx.newPage()
  p.setDefaultTimeout(10_000)
  await p.goto(`${BASE}${ruta}`, { waitUntil: esperar })
  await p.addStyleTag({ content: '.banner-cookies{display:none !important}' })
  return p
}

const CAMPO = 'input[role="combobox"]'
const LISTA = 'ul[role="listbox"]'
const OPCION = '[role="option"]'
const ESTADO = 'form:has(input[role="combobox"]) [role="status"]'
const MENSAJE = '.mensaje-sugerencias-govco'

/** Escribe en el campo del encabezado con una pausa entre teclas (sin pausa = rápido). */
async function escribir(p, texto, retardo = 25) {
  await p.locator(CAMPO).click()
  await p.keyboard.type(texto, { delay: retardo })
}

const opciones = (p) => p.locator(`${LISTA} ${OPCION}`)
const abierta = (p) => p.locator(CAMPO).getAttribute('aria-expanded')
const textoEstado = async (p) => ((await p.locator(ESTADO).first().textContent()) ?? '').trim()

/** Espera a que haya `n` opciones y la lista esté abierta. */
async function esperarOpciones(p, n, tiempo = 6000) {
  await p.waitForFunction(
    ([sel, cuantas]) => document.querySelectorAll(sel).length === cuantas && document.querySelector('input[role="combobox"]')?.getAttribute('aria-expanded') === 'true',
    [`${LISTA} ${OPCION}`, n],
    { timeout: tiempo },
  )
}

/** Espera a que aparezca la fila de mensaje (sin sugerencias, error, 429). */
const esperarMensaje = (p, tiempo = 6000) => p.locator(MENSAJE).waitFor({ state: 'visible', timeout: tiempo })

/** Qué opción está activa (índice) según `aria-activedescendant`, o -1. */
async function indiceActivo(p) {
  return p.evaluate(() => {
    const campo = document.querySelector('input[role="combobox"]')
    const id = campo?.getAttribute('aria-activedescendant')
    if (!id) return -1
    return [...document.querySelectorAll('ul[role="listbox"] [role="option"]')].findIndex((o) => o.id === id)
  })
}

const foco = (p) =>
  p.evaluate(() => {
    const a = document.activeElement
    return a ? `${a.tagName.toLowerCase()}${a.getAttribute('aria-label') ? `[${a.getAttribute('aria-label')}]` : ''}${a.id ? `#${a.id}` : ''}` : null
  })

const sinViolaciones = async (p, etiqueta) => {
  if (!(await p.evaluate(() => typeof window.axe !== 'undefined'))) await p.addScriptTag({ path: rutaAxe })
  const r = await p.evaluate((tags) => window.axe.run(document, { runOnly: { type: 'tag', values: tags } }), ETIQUETAS_AXE)
  const graves = r.violations.filter((v) => BLOQUEANTES.has(v.impact))
  comprobar(
    graves.length === 0,
    `${etiqueta}: axe sin violaciones críticas ni serias (${r.violations.length} menores)`,
    `${etiqueta}: ${graves.map((v) => `${v.id} (${v.impact}) en ${v.nodes.length} nodo(s): ${v.nodes[0]?.target?.join(' ')}`).join(' | ')}`,
  )
}

const captura = (p, nombre, ancho) => p.screenshot({ path: join(CAPTURAS, `${nombre}-${ancho}.png`) })

/** Respuesta 200 de sugerencias con los títulos dados (el contrato de la API real). */
async function cuerpoDeSugerencias(titulos) {
  const plantilla = await (await fetch(`${simulada.url}/api/v1/busqueda/sugerencias?q=prueba&limite=1`)).json()
  return {
    ...plantilla,
    data: titulos.map((titulo, i) => ({
      id: 900 + i, type: 'sugerencias-busqueda', titulo, categoria: 'Trámite', tipo_recurso: 'tramite',
      url: `/tramites/${encodeURIComponent(titulo.toLowerCase().replace(/\s+/g, '-'))}`, score: 1,
    })),
  }
}

const json = (cuerpo, estado = 200, cabeceras = {}) => ({
  status: estado, contentType: 'application/json; charset=utf-8', headers: cabeceras, body: JSON.stringify(cuerpo),
})

/** Cuenta las peticiones de sugerencias y de resultados que hace la página. */
function contarPeticiones(p) {
  const lista = []
  p.on('request', (r) => {
    const u = new URL(r.url())
    if (u.pathname === '/api/v1/busqueda/sugerencias') lista.push({ tipo: 'sugerencias', q: u.searchParams.get('q') })
    if (u.pathname === '/api/v1/buscar') lista.push({ tipo: 'buscar', q: u.searchParams.get('q') })
  })
  return lista
}

/** Rectángulo de un localizador, o null. */
const caja = (loc) => loc.boundingBox()

// ---------------------------------------------------------------------------
// Ejecución
// ---------------------------------------------------------------------------

try {
  await esperarServidor()
  navegador = await lanzar()

  // ── TC-BUS-27 · ARIA y teclado ───────────────────────────────────────────
  console.log('\nTC-BUS-27 · atributos ARIA y teclado (§4.2, §4.3)')
  {
    const ctx = await contexto(1280, 900)
    const p = await abrir(ctx, '/')
    const campo = p.locator(CAMPO)

    const a = await campo.evaluate((el) => ({
      tipo: el.type, nombre: el.name, autocompletar: el.getAttribute('autocomplete'), tecla: el.getAttribute('enterkeyhint'),
      largo: el.getAttribute('maxlength'), rol: el.getAttribute('role'), autocompletado: el.getAttribute('aria-autocomplete'),
      expandido: el.getAttribute('aria-expanded'), controla: el.getAttribute('aria-controls'),
      activo: el.getAttribute('aria-activedescendant'), descrito: el.getAttribute('aria-describedby'),
      etiqueta: document.querySelector(`label[for="${el.id}"]`)?.textContent?.trim() ?? null,
      ayuda: document.getElementById(el.getAttribute('aria-describedby') ?? '')?.textContent?.trim() ?? null,
      lista: (() => { const l = document.getElementById(el.getAttribute('aria-controls') ?? ''); return l ? { rol: l.getAttribute('role'), nombre: l.getAttribute('aria-label'), oculta: l.hidden, ocupado: l.getAttribute('aria-busy') } : null })(),
      formulario: (() => { const f = el.closest('form'); return { rol: f.getAttribute('role'), nombre: f.getAttribute('aria-label'), accion: new URL(f.action).pathname, metodo: f.method } })(),
      limpiar: el.closest('form').querySelector('button[type="button"]')?.getAttribute('aria-label') ?? null,
      buscar: el.closest('form').querySelector('button[type="submit"]')?.getAttribute('aria-label') ?? null,
    }))
    igual(a.tipo, 'text', 'type del campo (no «search»: no duplica el botón de borrar)')
    igual(a.nombre, 'q', 'name del campo')
    igual(a.autocompletar, 'off', 'autocomplete')
    igual(a.tecla, 'search', 'enterkeyhint')
    igual(a.largo, '120', 'maxlength')
    igual(a.rol, 'combobox', 'role')
    igual(a.autocompletado, 'list', 'aria-autocomplete')
    igual(a.expandido, 'false', 'aria-expanded con la lista cerrada')
    comprobar(a.activo === null || a.activo === '', 'sin aria-activedescendant al empezar', `aria-activedescendant al empezar: ${a.activo}`)
    igual(a.etiqueta, 'Buscar en el sitio', 'etiqueta <label for>')
    igual(a.ayuda, 'Escriba al menos 3 letras para ver sugerencias.', 'aria-describedby apunta a la ayuda')
    igual(a.lista, { rol: 'listbox', nombre: 'Sugerencias de búsqueda', oculta: true, ocupado: 'false' }, 'la lista (aria-controls) y su nombre; oculta y sin aria-busy')
    igual(a.formulario, { rol: 'search', nombre: 'Buscar en el sitio', accion: '/buscar', metodo: 'get' }, 'formulario: role=search, action y method')
    igual(a.limpiar, 'Limpiar la búsqueda', 'botón limpiar')
    igual(a.buscar, 'Buscar', 'botón buscar')

    // Con la lista abierta.
    await escribir(p, 'prueba')
    await esperarOpciones(p, 6)
    igual(await abierta(p), 'true', 'aria-expanded con la lista abierta')
    comprobar((await opciones(p).count()) === 6, 'hasta 6 filas (D-I2)', 'la lista no tiene 6 filas')
    const roles = await opciones(p).evaluateAll((os) => os.map((o) => [o.getAttribute('role'), o.getAttribute('aria-selected')]))
    comprobar(roles.every(([r, s]) => r === 'option' && s === 'false'), 'cada fila es role=option con aria-selected=false', `filas: ${JSON.stringify(roles)}`)
    const nombre = await opciones(p).first().evaluate((o) => o.textContent.replace(/\s+/g, ' ').trim())
    igual(nombre, 'Trámite de prueba 01, Trámite', 'nombre accesible de una opción: «título, categoría»')
    igual(await textoEstado(p), '6 sugerencias disponibles.', 'anuncio con la lista abierta')

    // ↓ y ↑: pasan por el campo entre la última y la primera opción.
    await p.keyboard.press('ArrowDown')
    igual(await indiceActivo(p), 0, '↓ activa la primera opción')
    comprobar(await opciones(p).first().evaluate((o) => o.getAttribute('aria-selected') === 'true'), 'la activa lleva aria-selected=true', 'la activa no lleva aria-selected=true')
    comprobar((await foco(p)).startsWith('input'), 'el foco sigue en el campo (aria-activedescendant)', `el foco está en ${await foco(p)}`)
    await p.keyboard.press('ArrowDown', { delay: 0 })
    igual(await indiceActivo(p), 1, '↓ pasa a la siguiente')
    for (let i = 0; i < 4; i += 1) await p.keyboard.press('ArrowDown')
    igual(await indiceActivo(p), 5, '↓ llega a la última')
    await p.keyboard.press('ArrowDown')
    igual(await indiceActivo(p), -1, '↓ desde la última vuelve al campo (sin activa)')
    await p.keyboard.press('ArrowUp')
    igual(await indiceActivo(p), 5, '↑ desde el campo va a la última (decisión, §4.3)')
    for (let i = 0; i < 5; i += 1) await p.keyboard.press('ArrowUp')
    igual(await indiceActivo(p), 0, '↑ llega a la primera')
    await p.keyboard.press('ArrowUp')
    igual(await indiceActivo(p), -1, '↑ desde la primera vuelve al campo')

    // ←, →, Inicio, Fin borran la activa y mueven el cursor.
    for (const tecla of ['ArrowRight', 'ArrowLeft', 'Home', 'End']) {
      await p.keyboard.press('ArrowDown')
      await p.keyboard.press(tecla)
      igual(await indiceActivo(p), -1, `${tecla} borra la opción activa`)
    }

    // Esc: cierra y deja el texto; un segundo Esc lo borra.
    await p.keyboard.press('ArrowDown')
    await p.keyboard.press('Escape')
    igual(await abierta(p), 'false', 'Esc cierra la lista')
    igual(await campo.inputValue(), 'prueba', 'Esc deja el texto')
    igual(await indiceActivo(p), -1, 'Esc borra la opción activa')
    await p.keyboard.press('Escape')
    igual(await campo.inputValue(), '', 'un segundo Esc borra el texto')

    // Alt+↓ abre sin activar nada; ↓ con la lista cerrada también abre.
    await p.keyboard.type('prueba', { delay: 20 })
    await esperarOpciones(p, 6)
    await p.keyboard.press('Escape')
    igual(await abierta(p), 'false', '(preparación) lista cerrada con texto')
    await p.keyboard.press('ArrowUp')
    igual(await abierta(p), 'false', '↑ con la lista cerrada no hace nada (abrir es cosa de ↓ y Alt+↓)')
    await p.keyboard.press('Alt+ArrowDown')
    igual(await abierta(p), 'true', 'Alt+↓ abre la lista')
    igual(await indiceActivo(p), -1, 'Alt+↓ no activa ninguna opción')
    await p.keyboard.press('Escape')
    await p.keyboard.press('ArrowDown')
    igual(await abierta(p), 'true', '↓ con la lista cerrada la abre')

    // Tab cierra y sigue el orden normal: campo → limpiar → buscar.
    await p.keyboard.press('Tab')
    igual(await abierta(p), 'false', 'Tab cierra la lista')
    igual(await foco(p), 'button[Limpiar la búsqueda]', 'Tab lleva al botón de limpiar (hay texto)')
    await p.keyboard.press('Tab')
    igual(await foco(p), 'button[Buscar]', 'otro Tab lleva a «Buscar»')
    await p.keyboard.press('Shift+Tab')
    await p.keyboard.press('Shift+Tab')
    comprobar((await foco(p)).startsWith('input'), 'Mayús+Tab devuelve el foco al campo', `el foco está en ${await foco(p)}`)

    // El foco visible no se pierde.
    const anillo = await campo.evaluate((el) => {
      const verlo = (e) => { const c = getComputedStyle(e); return { contorno: c.outlineStyle !== 'none' && parseFloat(c.outlineWidth) >= 2, sombra: c.boxShadow !== 'none', borde: parseFloat(c.borderTopWidth) >= 2 }; }
      return { campo: verlo(el), contenedor: verlo(el.closest('.container-govco')) }
    })
    const hayAnillo = Object.values(anillo.campo).some(Boolean) || Object.values(anillo.contenedor).some(Boolean)
    comprobar(hayAnillo, `el campo enfocado por teclado muestra un indicador (${JSON.stringify(anillo)})`, `el campo enfocado no muestra indicador de foco: ${JSON.stringify(anillo)}`)
    for (const [nombreBoton, selector] of [['Limpiar', 'button[aria-label="Limpiar la búsqueda"]'], ['Buscar', 'button[aria-label="Buscar"]']]) {
      await p.locator(selector).first().focus()
      const visible = await p.locator(selector).first().evaluate((el) => {
        const c = getComputedStyle(el)
        return (c.outlineStyle !== 'none' && parseFloat(c.outlineWidth) >= 2) || c.boxShadow !== 'none'
      })
      comprobar(visible, `«${nombreBoton}» enfocado muestra indicador de foco`, `«${nombreBoton}» enfocado no muestra indicador de foco`)
    }

    // Intro: con opción activa va a su URL; sin ella envía el formulario.
    await campo.focus()
    await p.keyboard.press('ArrowDown') // abre la lista (cerrada)
    await esperarOpciones(p, 6)
    await p.keyboard.press('ArrowDown')
    await p.keyboard.press('ArrowDown')
    const activo = await indiceActivo(p)
    comprobar(activo >= 0, '(preparación) hay una opción activa', 'no hay opción activa')
    const tituloActivo = await opciones(p).nth(activo).locator('.titulo-sugerencia-govco').textContent()
    const destino = `/tramites/${tituloActivo.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/\s+/g, '-')}`
    await Promise.all([p.waitForURL(new RegExp(`${destino}$`)), p.keyboard.press('Enter')])
    ok(`Intro con una opción activa va a su URL (${destino})`)
    await p.goto(`${BASE}/`, { waitUntil: 'networkidle' })
    await p.addStyleTag({ content: '.banner-cookies{display:none !important}' })
    await escribir(p, 'prueba')
    await esperarOpciones(p, 6)
    await Promise.all([p.waitForURL(/\/buscar\?q=prueba$/), p.keyboard.press('Enter')])
    ok('Intro sin opción activa envía el formulario: /buscar?q=prueba')
    await ctx.close()
  }

  // ── TC-BUS-28 · estados del encabezado (§4.4) y geometría ────────────────
  console.log('\nTC-BUS-28 · estados de la lista (§4.4), objetivos y capas')
  for (const [ancho, alto] of [[1280, 900], [360, 740]]) {
    console.log(`  — ${ancho} px`)
    const ctx = await contexto(ancho, alto)
    const p = await abrir(ctx, '/')
    const campo = p.locator(CAMPO)
    const peticiones = contarPeticiones(p)

    // 1. Vacío / menos de 3 caracteres.
    await campo.click()
    await p.keyboard.type('pr', { delay: 30 })
    await pausa(PAUSA_MS + 350)
    igual(await abierta(p), 'false', `${ancho}: con 2 caracteres no hay lista`)
    igual(peticiones.filter((x) => x.tipo === 'sugerencias').length, 0, `${ancho}: con 2 caracteres no se pide nada`)
    igual(await textoEstado(p), '', `${ancho}: no se anuncia nada al teclear`)
    comprobar((await p.locator(MENSAJE).count()) === 0 || !(await p.locator(MENSAJE).isVisible()), `${ancho}: sin fila de mensaje`, `${ancho}: hay una fila de mensaje con 2 caracteres`)

    // 2. Con resultados + geometría.
    await p.keyboard.type('ueba', { delay: 20 })
    await esperarOpciones(p, 6)
    await captura(p, 'lista-abierta', ancho)
    const alturas = await opciones(p).evaluateAll((os) => os.map((o) => Math.round(o.getBoundingClientRect().height)))
    comprobar(alturas.every((h) => h >= 48), `${ancho}: todas las filas miden ≥ 48 px (${Math.min(...alturas)}–${Math.max(...alturas)})`, `${ancho}: hay filas de menos de 48 px: ${alturas}`)
    for (const [n, sel] of [['limpiar', 'button[aria-label="Limpiar la búsqueda"]'], ['buscar', 'button[aria-label="Buscar"]']]) {
      const c = await caja(p.locator(sel).first())
      comprobar(c && c.width >= 43.5 && c.height >= 43.5, `${ancho}: botón ${n} de ${Math.round(c?.width)}×${Math.round(c?.height)} px (≥ 44)`, `${ancho}: botón ${n} de ${c ? `${Math.round(c.width)}×${Math.round(c.height)}` : '?'} px (< 44)`)
    }
    // El círculo flotante no tapa la lista: el elemento bajo el centro de cada fila es la fila.
    const tapadas = await p.evaluate(() => {
      const malas = []
      for (const o of document.querySelectorAll('ul[role="listbox"] [role="option"]')) {
        const r = o.getBoundingClientRect()
        // Tres puntos por fila: el centro y los extremos derecho e izquierdo (donde cae el círculo).
        for (const x of [r.left + r.width / 2, r.right - 6, r.left + 6]) {
          const y = r.top + r.height / 2
          const e = document.elementFromPoint(x, y)
          if (!e || !o.contains(e)) malas.push(`${o.textContent.trim().slice(0, 20)} @${Math.round(x)},${Math.round(y)} → ${e?.className || e?.tagName}`)
        }
      }
      const f = document.querySelector('.boton-accesibilidad').getBoundingClientRect()
      const l = document.querySelector('ul[role="listbox"]').getBoundingClientRect()
      return { malas, solapan: !(f.right < l.left || f.left > l.right || f.bottom < l.top || f.top > l.bottom) }
    })
    comprobar(tapadas.malas.length === 0, `${ancho}: ningún punto de la lista queda bajo otro elemento${tapadas.solapan ? ' (el círculo SÍ cae sobre la lista y queda debajo)' : ''}`, `${ancho}: algo tapa la lista: ${tapadas.malas.join(' | ')}`)
    const z = await p.evaluate(() => ({
      lista: Number(getComputedStyle(document.querySelector('.sugerencias-busqueda-govco')).zIndex),
      circulo: Number(getComputedStyle(document.querySelector('.boton-accesibilidad')).zIndex),
    }))
    comprobar(z.lista > z.circulo, `${ancho}: z-index de la lista (${z.lista}) por encima del círculo (${z.circulo})`, `${ancho}: la lista (${z.lista}) no está por encima del círculo (${z.circulo})`)
    const area = await p.evaluate(() => { const l = document.querySelector('.sugerencias-busqueda-govco').getBoundingClientRect(); return { fondo: Math.round(l.bottom), alto: innerHeight, derecha: Math.round(l.right), ancho: innerWidth } })
    comprobar(area.fondo <= area.alto && area.derecha <= area.ancho, `${ancho}: la lista cabe en la pantalla`, `${ancho}: la lista se sale de la pantalla: ${JSON.stringify(area)}`)

    // 3. Cargando: la lista previa se conserva; a los 200 ms, el indicador y el anuncio.
    let soltar
    const retenida = new Promise((r) => (soltar = r))
    await p.route('**/api/v1/busqueda/sugerencias*', async (ruta) => {
      await retenida
      await ruta.continue().catch(() => {}) // la app cancela la petición anterior: ya no hay nada que seguir
    })
    await campo.press('End')
    await p.keyboard.type(' 0', { delay: 20 })
    await pausa(PAUSA_MS + 450)
    comprobar(await p.locator('.indicador-carga-buscador').isVisible(), `${ancho}: pasados 200 ms aparece el indicador de carga`, `${ancho}: no aparece el indicador de carga`)
    igual(await textoEstado(p), 'Buscando sugerencias.', `${ancho}: se anuncia «Buscando sugerencias.»`)
    igual(await p.locator(LISTA).getAttribute('aria-busy'), 'true', `${ancho}: aria-busy en la lista`)
    comprobar((await opciones(p).count()) === 6, `${ancho}: la lista previa se conserva (nunca un vacío intermedio)`, `${ancho}: la lista previa se perdió`)
    await captura(p, 'lista-cargando', ancho)
    soltar()
    await p.unroute('**/api/v1/busqueda/sugerencias*')
    await p.locator('.indicador-carga-buscador').waitFor({ state: 'detached' }).catch(() => {})

    // 4. Sin resultados.
    await campo.fill('')
    await p.keyboard.type('zzzz', { delay: 20 })
    await esperarMensaje(p)
    const sin = await p.evaluate(() => ({
      texto: document.querySelector('.mensaje-sugerencias-govco')?.textContent.trim(),
      opciones: document.querySelectorAll('ul[role="listbox"] [role="option"]').length,
      abierto: document.querySelector('input[role="combobox"]').getAttribute('aria-expanded'),
      esOpcion: document.querySelector('.mensaje-sugerencias-govco')?.getAttribute('role'),
    }))
    igual(sin.texto, 'No hay sugerencias. Pulse Intro para buscar en toda la sede.', `${ancho}: sin resultados, texto`)
    igual(sin.opciones, 0, `${ancho}: sin resultados, la fila no es una opción`)
    comprobar(sin.esOpcion !== 'option', `${ancho}: el mensaje no lleva role=option`, `${ancho}: el mensaje lleva role=option`)
    igual(sin.abierto, 'true', `${ancho}: sin resultados, aria-expanded=true`)
    igual(await textoEstado(p), 'No hay sugerencias. Pulse Intro para buscar en toda la sede.', `${ancho}: sin resultados, anuncio`)
    await captura(p, 'lista-sin-resultados', ancho)
    const filaMensaje = await caja(p.locator(MENSAJE))
    comprobar(filaMensaje && filaMensaje.height >= 48 - 0.5, `${ancho}: la fila de mensaje mide ≥ 48 px (${Math.round(filaMensaje?.height)})`, `${ancho}: la fila de mensaje mide ${Math.round(filaMensaje?.height)} px`)

    // 5. 422 de la API: se trata como «sin resultados».
    await p.route('**/api/v1/busqueda/sugerencias*', (ruta) => ruta.fulfill(json({ success: false, message: 'Datos no válidos.', data: null, errors: { q: ['No válido.'] } }, 422)))
    await campo.fill('')
    await p.keyboard.type('prueba 422', { delay: 20 })
    await esperarMensaje(p)
    igual(((await p.locator(MENSAJE).textContent()) ?? '').trim(), 'No hay sugerencias. Pulse Intro para buscar en toda la sede.', `${ancho}: un 422 de la API se muestra como «sin resultados»`)
    await p.unroute('**/api/v1/busqueda/sugerencias*')

    // 6. Error: texto, anuncio y sin reintento antes de 2 s.
    await campo.fill('')
    peticiones.length = 0
    await p.keyboard.type('error-servidor', { delay: 15 })
    await esperarMensaje(p)
    const instanteFallo = Date.now()
    igual(((await p.locator(MENSAJE).textContent()) ?? '').trim(), 'No pudimos consultar las sugerencias. Pulse Intro para buscar.', `${ancho}: error, texto`)
    igual(await textoEstado(p), 'No pudimos consultar las sugerencias. Pulse Intro para buscar.', `${ancho}: error, anuncio`)
    await captura(p, 'lista-error', ancho)
    const tras = peticiones.filter((x) => x.tipo === 'sugerencias').length
    await pausa(PAUSA_MS + 400)
    igual(peticiones.filter((x) => x.tipo === 'sugerencias').length, tras, `${ancho}: tras el error no se reintenta solo`)
    // Una pulsación antes de 2 s del fallo no pregunta; pasados los 2 s, sí.
    await p.keyboard.type('x', { delay: 0 })
    await pausa(PAUSA_MS + 200)
    const dentroDeLosDos = Date.now() - instanteFallo < 2000
    if (dentroDeLosDos) igual(peticiones.filter((x) => x.tipo === 'sugerencias').length, tras, `${ancho}: una pulsación a menos de 2 s del fallo no pregunta`)
    else console.log('  ℹ la pulsación llegó pasados 2 s del fallo; no se comprueba la espera')
    await pausa(Math.max(0, 2100 - (Date.now() - instanteFallo)))
    await p.keyboard.type('y', { delay: 0 })
    await pausa(PAUSA_MS + 400)
    comprobar(peticiones.filter((x) => x.tipo === 'sugerencias').length > tras, `${ancho}: pasados 2 s, la siguiente pulsación vuelve a intentar`, `${ancho}: pasados 2 s no vuelve a intentar`)

    // 7. 429: texto con Retry-After, anuncio una vez y sugerencias pausadas.
    await campo.fill('')
    peticiones.length = 0
    await p.keyboard.type('limite-peticiones', { delay: 15 })
    await esperarMensaje(p)
    const textoLimite = 'Hizo muchas búsquedas seguidas. Espere 7 segundos para ver sugerencias; aun así puede pulsar Intro.'
    igual(((await p.locator(MENSAJE).textContent()) ?? '').trim(), textoLimite, `${ancho}: 429, texto con N de Retry-After`)
    igual(await textoEstado(p), textoLimite, `${ancho}: 429, anuncio`)
    await captura(p, 'lista-429', ancho)
    const antes429 = peticiones.filter((x) => x.tipo === 'sugerencias').length
    await p.keyboard.type('abc', { delay: 80 })
    await pausa(PAUSA_MS + 300)
    igual(peticiones.filter((x) => x.tipo === 'sugerencias').length, antes429, `${ancho}: con el 429 vigente no se pregunta`)
    igual(await textoEstado(p), textoLimite, `${ancho}: el 429 no vuelve a anunciarse con cada tecla (mismo texto, sin cuenta atrás)`)
    await ctx.close()
  }

  // ── TC-BUS-36 · ritmo de escritura y respuestas fuera de orden ───────────
  console.log('\nTC-BUS-36 · escritura lenta, respuestas en desorden y composición (§4.5)')
  {
    const ctx = await contexto(1280, 900)
    const p = await abrir(ctx, '/')
    const peticiones = contarPeticiones(p)
    const campo = p.locator(CAMPO)

    // Rápido: una sola petición por pausa.
    await campo.click()
    await p.keyboard.type('tramite', { delay: 20 })
    await pausa(PAUSA_MS + 600)
    igual(peticiones.filter((x) => x.tipo === 'sugerencias').map((x) => x.q), ['tramite'], 'escritura rápida: una sola petición, con el texto completo')

    // 500 ms entre teclas, 20 caracteres: una por tecla desde la 3.ª (cada pausa supera 350 ms).
    await campo.fill('')
    peticiones.length = 0
    const texto = `prueba${'x'.repeat(14)}`
    comprobar(texto.length === 20, '(preparación) el texto mide 20 caracteres', `el texto mide ${texto.length}`)
    await campo.click()
    await p.keyboard.type(texto, { delay: 500 })
    await pausa(PAUSA_MS + 800)
    const pedidas = peticiones.filter((x) => x.tipo === 'sugerencias').map((x) => x.q)
    igual(pedidas.length, 18, `500 ms entre teclas: ${texto.length - MINIMO + 1} peticiones (una por tecla desde la 3.ª)`)
    igual(pedidas[0], 'pru', 'la primera petición es la de 3 caracteres')
    igual(pedidas.at(-1), texto, 'la última es la del texto completo')
    igual(new Set(pedidas).size, pedidas.length, 'ninguna consulta se repite')
    // «pru», «prue», «prueb» y «prueba» sí encuentran; lo demás no: la lista final tiene que ser el mensaje de «sin sugerencias».
    igual(await opciones(p).count(), 0, 'la lista final corresponde al último texto: ninguna respuesta vieja deja filas')
    igual(((await p.locator(MENSAJE).textContent()) ?? '').trim(), 'No hay sugerencias. Pulse Intro para buscar en toda la sede.', 'y muestra el mensaje de «sin sugerencias»')

    // Desorden: la respuesta de la consulta vieja llega la última y no se muestra.
    await campo.fill('')
    peticiones.length = 0
    let soltarVieja
    const vieja = new Promise((r) => (soltarVieja = r))
    const cuerpoViejo = await cuerpoDeSugerencias(['RESPUESTA VIEJA'])
    await p.route('**/api/v1/busqueda/sugerencias*', async (ruta) => {
      if (new URL(ruta.request().url()).searchParams.get('q') === 'pru') {
        await vieja
        await ruta.fulfill(json(cuerpoViejo)).catch(() => {})
      } else {
        await ruta.continue().catch(() => {})
      }
    })
    await campo.click()
    await p.keyboard.type('pru', { delay: 20 })
    await pausa(PAUSA_MS + 300) // sale la petición de «pru» y queda retenida
    await p.keyboard.type('eba', { delay: 20 })
    await esperarOpciones(p, 6)
    soltarVieja()
    await pausa(500)
    const titulos = await opciones(p).evaluateAll((os) => os.map((o) => o.textContent))
    comprobar(titulos.length === 6 && !titulos.some((t) => t.includes('VIEJA')), 'la respuesta de «pru», que llega la última, no reemplaza a la de «prueba»', `se mostró la respuesta vieja: ${titulos.join(' | ')}`)
    await p.unroute('**/api/v1/busqueda/sugerencias*')

    // Sin repetir la petición al borrar y reescribir (caché de 60 s).
    peticiones.length = 0
    await campo.fill('')
    await p.keyboard.type('prueba', { delay: 20 })
    await pausa(PAUSA_MS + 400)
    igual(peticiones.filter((x) => x.tipo === 'sugerencias').length, 0, 'borrar y volver a escribir un texto ya consultado no repite la petición (caché)')

    // Composición: sin peticiones hasta terminar.
    peticiones.length = 0
    await campo.fill('')
    await campo.evaluate((el) => {
      el.focus()
      el.dispatchEvent(new CompositionEvent('compositionstart', { bubbles: true }))
      el.value = 'composicio'
      el.dispatchEvent(new InputEvent('input', { bubbles: true, data: 't', inputType: 'insertCompositionText', isComposing: true }))
    })
    await pausa(PAUSA_MS + 400)
    igual(peticiones.filter((x) => x.tipo === 'sugerencias').length, 0, 'durante una composición no se pide nada')
    await campo.evaluate((el) => {
      el.value = 'composición'
      el.dispatchEvent(new CompositionEvent('compositionend', { bubbles: true, data: 'á' }))
      el.dispatchEvent(new InputEvent('input', { bubbles: true, inputType: 'insertText', data: 'á' }))
    })
    await pausa(PAUSA_MS + 600)
    comprobar(peticiones.some((x) => x.tipo === 'sugerencias' && x.q === 'composición'), 'al terminar la composición se pide el texto completo', `peticiones tras la composición: ${JSON.stringify(peticiones)}`)
    await ctx.close()
  }

  // ── TC-BUS-35 · privacidad ───────────────────────────────────────────────
  console.log('\nTC-BUS-35 · nada se guarda ni sale (§4.5, §6)')
  {
    const ctx = await contexto(1280, 900)
    const p = await abrir(ctx, '/')
    const consulta = 'consultaprivadaxyz'
    const foraneas = []
    const conLaConsulta = []
    const consola = []
    p.on('console', (m) => consola.push(m.text()))
    p.on('request', (r) => {
      const u = new URL(r.url())
      if (u.origin !== BASE) foraneas.push(r.url())
      if (r.url().toLowerCase().includes(consulta) || (r.postData() ?? '').toLowerCase().includes(consulta)) conLaConsulta.push(`${r.method()} ${u.pathname}`)
    })
    const instantanea = () =>
      p.evaluate(async () => ({
        local: JSON.stringify(Object.entries(localStorage).sort()),
        sesion: JSON.stringify(Object.entries(sessionStorage).sort()),
        cookies: document.cookie.split('; ').sort().join('; '),
        indexed: JSON.stringify(((await indexedDB.databases?.()) ?? []).map((d) => d.name).sort()),
      }))
    const antes = await instantanea()
    for (const t of [consulta, 'prueba', 'zzzz', `${consulta} dos`]) {
      await p.locator(CAMPO).fill('')
      await p.locator(CAMPO).click()
      await p.keyboard.type(t, { delay: 15 })
      await pausa(PAUSA_MS + 500)
    }
    await p.locator(CAMPO).press('Enter')
    await p.waitForURL(/\/buscar\?q=/)
    await p.waitForLoadState('networkidle')
    const despues = await instantanea()
    for (const k of ['local', 'sesion', 'cookies', 'indexed']) igual(despues[k], antes[k], `«${k}» no cambia tras varias búsquedas`)
    comprobar(foraneas.length === 0, 'ninguna petición sale del origen del sitio (sin analítica de terceros)', `peticiones a otros orígenes: ${foraneas.join(', ')}`)
    const salenDelContrato = conLaConsulta.filter((x) => !/\/api\/v1\/(busqueda\/sugerencias|buscar)$|\/buscar$/.test(x))
    comprobar(salenDelContrato.length === 0, `la consulta solo viaja a la búsqueda (${[...new Set(conLaConsulta)].join(', ')})`, `la consulta viaja a: ${salenDelContrato.join(', ')}`)
    comprobar(!consola.some((m) => m.toLowerCase().includes(consulta)), 'la consulta no se escribe en la consola', 'la consulta aparece en la consola')
    await ctx.close()
  }

  // ── TC-BUS-32 · /buscar ──────────────────────────────────────────────────
  console.log('\nTC-BUS-32 · la página /buscar (§5)')
  {
    const ctx = await contexto(1280, 900)
    const leer = (p) =>
      p.evaluate(() => ({
        titulo: document.title,
        robots: document.querySelector('meta[name="robots"]')?.content,
        h1: document.querySelector('h1')?.textContent.trim(),
        resumen: document.querySelector('p[role="status"]')?.textContent.trim(),
        items: document.querySelectorAll('ul[aria-label="Resultados de la búsqueda"] > li').length,
        primero: document.querySelector('ul[aria-label="Resultados de la búsqueda"] > li')?.innerText.replace(/\s+/g, ' ').trim(),
        filtros: [...document.querySelectorAll('nav[aria-label="Filtrar por tipo"] a')].map((a) => ({ t: a.textContent.trim(), actual: a.getAttribute('aria-current'), href: a.getAttribute('href') })),
        pag: [...document.querySelectorAll('.paginacion-govco a, .paginacion-govco span.page-link')].map((a) => ({ t: a.textContent.trim(), tag: a.tagName, actual: a.getAttribute('aria-current'), href: a.getAttribute('href') })),
        secciones: [...document.querySelectorAll('#titulo-secciones ~ ul a')].map((a) => a.textContent.trim()),
        h2: [...document.querySelectorAll('h2')].map((h) => h.textContent.trim()),
      }))

    const p = await abrir(ctx, '/buscar?q=prueba')
    const a = await leer(p)
    igual(a.titulo, 'Resultados para «prueba» · Sede Electrónica', 'título de la pestaña')
    comprobar(/noindex/.test(a.robots ?? ''), `meta robots: «${a.robots}» (una búsqueda no se indexa)`, `meta robots: «${a.robots}»`)
    igual(a.h1, 'Resultados de la búsqueda', 'h1')
    igual(a.resumen, '25 resultados para «prueba».', 'resumen (role=status)')
    igual(a.items, 10, '10 resultados por página')
    comprobar(/^Trámite Trámite de prueba 01 Texto de la puerta de pruebas\. No es contenido de la Entidad\. Publicado el 14 de marzo de 2026$/i.test(a.primero), 'el resultado lleva categoría, título, extracto y «Publicado el …»', `primer resultado: «${a.primero}»`)
    igual(a.filtros.map((f) => [f.t, f.actual]), [['Todo', 'true'], ['Trámites', null], ['Políticas', null]], 'filtro por tipo: el actual lleva aria-current')
    igual(a.filtros.map((f) => f.href), ['/buscar?q=prueba', '/buscar?q=prueba&tipo=tramite', '/buscar?q=prueba&tipo=politica'], 'los filtros son enlaces con la URL compartible')
    comprobar(a.pag.some((x) => x.t === 'Siguiente' && x.tag === 'A' && x.href === '/buscar?q=prueba&page=2'), 'la paginación son enlaces (Siguiente → ?page=2)', `paginación: ${JSON.stringify(a.pag)}`)
    comprobar(a.pag.some((x) => x.actual === 'page' && x.t === '1'), 'la página actual lleva aria-current="page"', `paginación: ${JSON.stringify(a.pag)}`)
    igual(a.secciones, [], 'sin sección que coincida, no hay grupo «Secciones de la sede»')
    comprobar(!(await p.locator('ul[aria-label="Resultados de la búsqueda"] li img').count()), 'no se dibuja miniatura ni hueco (autor y miniatura valen null)', 'se dibujó una imagen')

    const p3 = await abrir(ctx, '/buscar?q=prueba&page=3')
    const c = await leer(p3)
    igual(c.items, 5, 'la página 3 tiene los 5 restantes')
    igual(c.resumen, '25 resultados para «prueba».', 'el recuento no cambia al paginar')
    const pFuera = await abrir(ctx, '/buscar?q=prueba&page=99')
    comprobar(/page=3$/.test(pFuera.url()) || pFuera.url().includes('page=3'), 'una página fuera de rango se corrige a la última', `URL tras page=99: ${pFuera.url()}`)

    const pol = await leer(await abrir(ctx, '/buscar?q=prueba&tipo=politica'))
    igual(pol.resumen, '2 resultados para «prueba».', 'filtro «Políticas»: 2 resultados')
    igual(pol.filtros.map((f) => f.actual), [null, null, 'true'], 'filtro «Políticas»: aria-current en «Políticas»')
    comprobar(/Vigente desde el 1 de enero de 2026/.test(pol.primero ?? ''), 'una política lleva «Vigente desde el …»', `primer resultado: «${pol.primero}»`)
    const tra = await leer(await abrir(ctx, '/buscar?q=prueba&tipo=tramite'))
    igual(tra.resumen, '23 resultados para «prueba».', 'filtro «Trámites»: 23 resultados')

    // Secciones de la sede: solo con «Todo» y en la primera página.
    const sec = await leer(await abrir(ctx, '/buscar?q=normativa'))
    comprobar(sec.secciones.length >= 1 && sec.h2.includes('Secciones de la sede'), `con «Todo» aparece el grupo «Secciones de la sede» (${sec.secciones.join(', ')})`, `sin grupo de secciones: ${JSON.stringify(sec.h2)}`)
    const secTramites = await leer(await abrir(ctx, '/buscar?q=normativa&tipo=tramite'))
    igual(secTramites.secciones, [], 'con «Trámites» el grupo no aparece')

    // Sin resultados (M01-3, CA-01-009): el mensaje se dice UNA vez y se ofrecen las secciones principales.
    const pSin = await abrir(ctx, '/buscar?q=zzzz')
    const sin = await pSin.evaluate(() => {
      const estado = document.querySelector('p[role="status"]')
      const caja = estado?.getBoundingClientRect()
      const nav = document.querySelector('nav[aria-label="Secciones principales de la sede"]')
      return {
        estado: estado?.textContent.trim(),
        visible: !!caja && caja.width > 0 && caja.height > 0 && getComputedStyle(estado).visibility !== 'hidden',
        veces: (document.body.innerText.match(/No encontramos resultados/g) ?? []).length,
        h2Repetido: [...document.querySelectorAll('h2')].some((h) => /No encontramos/.test(h.textContent)),
        ayuda: nav?.previousElementSibling?.textContent.replace(/\s+/g, ' ').trim(),
        enlaces: [...(nav?.querySelectorAll('a') ?? [])].map((a) => ({ t: a.textContent.trim(), href: a.getAttribute('href') })),
      }
    })
    igual(sin.estado, 'No encontramos resultados para «zzzz».', 'sin resultados: el mensaje (role=status)')
    comprobar(sin.visible, 'sin resultados: el mensaje es visible y no sólo para lectores de pantalla', 'sin resultados: el aviso de estado no se ve')
    igual(sin.veces, 1, 'sin resultados: «No encontramos resultados» aparece UNA vez en la página')
    igual(sin.h2Repetido, false, 'sin resultados: ya no se repite en un h2')
    igual(sin.ayuda, 'Revise la ortografía o pruebe con otras palabras. También puede ir a una de estas secciones:', 'sin resultados: ayuda')
    comprobar(sin.enlaces.length >= 3 && sin.enlaces.every((e) => e.t !== '' && e.href?.startsWith('/') && !e.href.startsWith('//')), `sin resultados: ${sin.enlaces.length} enlaces a secciones principales (${sin.enlaces.map((e) => e.href).join(', ')})`, `sin resultados: enlaces a secciones: ${JSON.stringify(sin.enlaces)}`)
    comprobar(['/tramites', '/pqrsd', '/normativa'].every((h) => sin.enlaces.some((e) => e.href === h)), 'sin resultados: incluye trámites, PQRSD y normativa, que hoy publican contenido', `sin resultados: faltan secciones en ${JSON.stringify(sin.enlaces.map((e) => e.href))}`)
    for (const enlace of sin.enlaces) {
      const r = await ctx.request.get(`${BASE}${enlace.href}`)
      comprobar(r.status() === 200, `sin resultados: ${enlace.href} responde 200`, `sin resultados: ${enlace.href} responde ${r.status()}`)
    }
    // El eco del término es texto, no HTML.
    const pHtml = await abrir(ctx, `/buscar?q=${encodeURIComponent('<b>negrita</b>zz')}`)
    igual(await pHtml.locator('p[role="status"] b').count(), 0, 'el eco de lo buscado se escapa (no se interpreta como HTML)')

    // Foco: al cambiar de filtro o de página pasa al h1.
    const pf = await abrir(ctx, '/buscar?q=prueba')
    await pf.locator('nav[aria-label="Filtrar por tipo"] a', { hasText: 'Políticas' }).click()
    await pf.waitForURL(/tipo=politica/)
    await pf.waitForFunction(() => document.activeElement?.tagName === 'H1')
    ok('al cambiar de filtro el foco pasa al <h1>')
    await pf.locator('nav[aria-label="Filtrar por tipo"] a', { hasText: 'Todo' }).click()
    await pf.waitForFunction(() => document.activeElement?.tagName === 'H1' && /25 resultados/.test(document.querySelector('p[role="status"]')?.textContent ?? ''))
    await pf.locator('.paginacion-govco a', { hasText: 'Siguiente' }).click()
    await pf.waitForURL(/page=2/)
    await pf.waitForFunction(() => document.activeElement?.tagName === 'H1')
    ok('al cambiar de página el foco pasa al <h1>')

    // Sin JavaScript.
    const ctxSinJs = await contexto(1280, 900, { js: false })
    const sj = await ctxSinJs.newPage()
    await sj.goto(`${BASE}/buscar?q=prueba&tipo=tramite`, { waitUntil: 'load' })
    igual(await sj.locator('ul[aria-label="Resultados de la búsqueda"] > li').count(), 10, 'sin JavaScript: los resultados vienen en la página')
    igual(await sj.locator('p[role="status"]').textContent(), '23 resultados para «prueba».', 'sin JavaScript: el resumen')
    await sj.locator('.paginacion-govco a', { hasText: 'Siguiente' }).click()
    await sj.waitForURL(/tipo=tramite&page=2$/)
    ok('sin JavaScript: la paginación es navegable (enlaces)')
    await sj.goto(`${BASE}/buscar`, { waitUntil: 'load' })
    await sj.locator('main input[name="q"], #contenido input[name="q"], form[action="/buscar"] input[name="q"]').last().fill('politica')
    await Promise.all([sj.waitForURL(/\/buscar\?q=politica$/), sj.locator('form[action="/buscar"] button[type="submit"]').last().click()])
    igual(await sj.locator('p[role="status"]').textContent(), '2 resultados para «politica».', 'sin JavaScript: el formulario envía por GET y la página responde')
    await ctxSinJs.close()
    await ctx.close()
  }

  // ── TC-BUS-37 · secciones de la sede en las sugerencias y en /buscar (M01-3) ──
  console.log('\nTC-BUS-37 · secciones de la sede en el desplegable y en /buscar (M01-3, CA-01-009)')
  {
    const ctx = await contexto(1280, 900)
    const p = await abrir(ctx, '/')
    const peticiones = contarPeticiones(p)
    const sugeridas = () => peticiones.filter((x) => x.tipo === 'sugerencias').length
    const filas = () => opciones(p).evaluateAll((os) => os.map((o) => o.textContent.replace(/\s+/g, ' ').trim()))

    // Una página publicada sale con su nombre y la categoría «Sección», aunque la API no halle nada.
    await escribir(p, 'normativa')
    await esperarOpciones(p, 1)
    const exacta = await filas()
    comprobar(/^Normativa,? Sección$/.test(exacta[0] ?? ''), '«normativa»: la sección sale con su nombre y la categoría escrita «Sección»', `«normativa»: ${JSON.stringify(exacta)}`)
    igual(sugeridas(), 1, '«normativa»: una sola petición a la API (las secciones no cuestan peticiones)')
    igual(await textoEstado(p), '1 sugerencia disponible.', '«normativa»: se anuncia una sugerencia')

    // Con una letra de más o de menos, y con la palabra a medias.
    const campo = p.locator(CAMPO)
    for (const escrito of ['normatva', 'normativ', 'NORMATIVA']) {
      await campo.fill('')
      await p.keyboard.type(escrito, { delay: 20 })
      await esperarOpciones(p, 1)
      comprobar(/^Normativa/.test((await filas())[0] ?? ''), `«${escrito}» también encuentra «Normativa»`, `«${escrito}»: ${JSON.stringify(await filas())}`)
    }

    // Una página que aún no publica contenido no se ofrece (decisión de José, 2026-11-10).
    await campo.fill('')
    await p.keyboard.type('transparencia', { delay: 20 })
    await esperarMensaje(p)
    igual(await opciones(p).count(), 0, '«transparencia» (en preparación): no se ofrece como sugerencia')

    // Con trámites y secciones a la vez, las secciones van primero y el total no pasa de 6.
    await campo.fill('')
    await p.keyboard.type('prueba', { delay: 20 })
    await esperarOpciones(p, 6)
    comprobar(!(await filas()).some((f) => /Sección/.test(f)), '«prueba»: sin sección que calce, la lista es la de la API (6)', `«prueba»: ${JSON.stringify(await filas())}`)

    // Elegir una sección con el teclado lleva a su página.
    await campo.fill('')
    await p.keyboard.type('pqrsd', { delay: 20 })
    await esperarOpciones(p, 1)
    await p.keyboard.press('ArrowDown')
    await Promise.all([p.waitForURL(/\/pqrsd$/), p.keyboard.press('Enter')])
    ok('↓ e Intro sobre «PQRSD» llevan a /pqrsd')

    // La página de resultados usa la misma regla: tolera una letra.
    const pr = await abrir(ctx, '/buscar?q=normatva')
    const grupo = await pr.evaluate(() => ({
      titulo: document.querySelector('#titulo-secciones')?.textContent.trim(),
      enlaces: [...document.querySelectorAll('#titulo-secciones ~ ul a')].map((a) => ({ t: a.textContent.trim(), href: a.getAttribute('href') })),
      resumen: document.querySelector('p[role="status"]')?.textContent.trim(),
    }))
    igual(grupo.titulo, 'Secciones de la sede', '/buscar?q=normatva: grupo «Secciones de la sede»')
    igual(grupo.enlaces, [{ t: 'Normativa', href: '/normativa' }], '/buscar?q=normatva: encuentra «Normativa» (una letra de diferencia)')
    igual(grupo.resumen, '1 resultado para «normatva».', '/buscar?q=normatva: el recuento cuenta la sección')

    const prT = await abrir(ctx, '/buscar?q=transparencia')
    igual(await prT.locator('#titulo-secciones').count(), 0, '/buscar?q=transparencia: la página en preparación no se ofrece')
    await ctx.close()
  }

  // ── TC-BUS-28 · estados de la página (§5.2) ──────────────────────────────
  console.log('\nTC-BUS-28 · estados de la página /buscar (§5.2), objetivos y capturas')
  for (const [ancho, alto] of [[1280, 900], [360, 740]]) {
    console.log(`  — ${ancho} px`)
    const ctx = await contexto(ancho, alto)

    // Sin término: el texto y el campo enfocado.
    let p = await abrir(ctx, '/buscar')
    igual(((await p.locator('main p, .container p').first().textContent()) ?? '').trim(), 'Escriba lo que busca: un trámite, una política o un servicio.', `${ancho}: sin término, texto`)
    comprobar(await p.evaluate(() => document.activeElement?.tagName === 'INPUT' && document.activeElement.getAttribute('role') !== 'combobox'), `${ancho}: sin término, el campo de la página queda enfocado`, `${ancho}: sin término, el foco no está en el campo de la página`)
    await captura(p, 'pagina-sin-termino', ancho)

    // Con resultados + objetivos táctiles.
    p = await abrir(ctx, '/buscar?q=prueba')
    await captura(p, 'pagina-resultados', ancho)
    const objetivos = await p.evaluate(() => {
      const medir = (sel, etiqueta) => [...document.querySelectorAll(sel)].map((e) => { const r = e.getBoundingClientRect(); return { etiqueta, texto: (e.textContent || e.getAttribute('aria-label') || '').trim().slice(0, 18), w: Math.round(r.width), h: Math.round(r.height) } })
      return [
        ...medir('form[action="/buscar"] button', 'botón del campo').filter((x) => x.w > 0),
        ...medir('nav[aria-label="Filtrar por tipo"] a', 'filtro'),
        ...medir('.paginacion-govco a, .paginacion-govco span.page-link', 'paginación'),
      ]
    })
    for (const o of objetivos) {
      comprobar(o.w >= 43.5 && o.h >= 43.5, `${ancho}: ${o.etiqueta} «${o.texto}» ${o.w}×${o.h} px`, `${ancho}: ${o.etiqueta} «${o.texto}» mide ${o.w}×${o.h} px (< 44)`)
    }

    // Demasiado corta, demasiado larga, sin resultados, error, 429.
    const casos = [
      ['corta', '/buscar?q=ab', 'alert', 'Escriba al menos 3 caracteres para buscar.'],
      ['larga', `/buscar?q=${'a'.repeat(121)}`, 'alert', 'Use menos de 120 caracteres.'],
      ['sin-resultados', '/buscar?q=zzzz', 'estado', 'No encontramos resultados para «zzzz».'],
      ['error', '/buscar?q=error-servidor', 'alert', 'No pudimos realizar la búsqueda en este momento. Intente de nuevo en unos minutos.'],
      ['limite', '/buscar?q=limite-peticiones', 'alert', 'Hizo muchas búsquedas seguidas. Espere 7 segundos e intente de nuevo.'],
    ]
    for (const [nombre, ruta, tipo, texto] of casos) {
      p = await abrir(ctx, ruta)
      const visto = await p.evaluate((t) => (t === 'alert' ? [...document.querySelectorAll('[role="alert"]')] : [...document.querySelectorAll('p[role="status"]')]).map((e) => e.textContent.replace(/\s+/g, ' ').trim()), tipo)
      comprobar(visto.some((v) => v.includes(texto)), `${ancho}: estado «${nombre}»: «${texto}» (${tipo === 'alert' ? 'role=alert' : 'role=status'})`, `${ancho}: estado «${nombre}»: esperaba «${texto}» y hay ${JSON.stringify(visto)}`)
      await captura(p, `pagina-${nombre}`, ancho)
      if (nombre === 'error') {
        const reintentar = await caja(p.getByRole('button', { name: 'Reintentar' }))
        comprobar(reintentar && reintentar.height >= 43.5, `${ancho}: botón «Reintentar» de ${Math.round(reintentar?.width)}×${Math.round(reintentar?.height)} px`, `${ancho}: no hay botón «Reintentar» de ≥ 44 px`)
      }
    }

    // Cargando (cambio de filtro en el navegador): contenido atenuado, aria-busy y «Buscando…».
    p = await abrir(ctx, '/buscar?q=prueba')
    let soltar
    const retenida = new Promise((r) => (soltar = r))
    await p.route('**/api/v1/buscar*', async (ruta) => {
      await retenida
      await ruta.continue().catch(() => {}) // la app cancela la petición anterior: ya no hay nada que seguir
    })
    await p.locator('nav[aria-label="Filtrar por tipo"] a', { hasText: 'Políticas' }).click()
    await p.waitForFunction(() => document.querySelector('[aria-busy="true"]') !== null)
    const cargando = await p.evaluate(() => ({
      busy: document.querySelector('[aria-busy="true"]')?.className,
      texto: /Buscando…/.test(document.body.innerText),
      opacidad: Number(getComputedStyle(document.querySelector('[aria-busy="true"]')).opacity),
      items: document.querySelectorAll('ul[aria-label="Resultados de la búsqueda"] > li').length,
    }))
    comprobar(cargando.opacidad < 1, `${ancho}: cargando, el contenido anterior está atenuado (opacidad ${cargando.opacidad})`, `${ancho}: cargando, el contenido no está atenuado`)
    comprobar(cargando.texto, `${ancho}: cargando, se lee «Buscando…»`, `${ancho}: cargando, no se lee «Buscando…»`)
    comprobar(cargando.items > 0, `${ancho}: cargando, el contenido anterior se conserva`, `${ancho}: cargando, la lista se vació`)
    await captura(p, 'pagina-cargando', ancho)
    soltar()
    await p.waitForFunction(() => document.querySelector('[aria-busy="true"]') === null)
    ok(`${ancho}: al llegar la respuesta se quita aria-busy`)
    await p.unroute('**/api/v1/buscar*')

    // Error con secciones encontradas + «Reintentar» (la petición del navegador falla una vez).
    p = await abrir(ctx, '/')
    let fallar = true
    await p.route('**/api/v1/buscar*', (ruta) => (fallar ? ruta.fulfill(json({ success: false, message: 'Error simulado.', data: null, errors: null }, 500)) : ruta.continue()))
    await p.locator('form[role="search"] input').first().fill('normativa')
    await p.locator('form[role="search"] button[type="submit"]').first().click()
    await p.waitForURL(/\/buscar\?q=normativa$/)
    await p.locator('[role="alert"]').first().waitFor()
    const conSecciones = await p.evaluate(() => ({ alertas: [...document.querySelectorAll('[role="alert"]')].map((e) => e.textContent.replace(/\s+/g, ' ').trim()), secciones: document.querySelectorAll('#titulo-secciones ~ ul a').length }))
    comprobar(conSecciones.alertas.join(' ').includes('El catálogo de trámites y políticas no respondió, así que solo se muestran las secciones de la sede que coinciden.'), `${ancho}: error con secciones: el aviso aclara que el catálogo no respondió`, `${ancho}: error con secciones: ${JSON.stringify(conSecciones.alertas)}`)
    comprobar(conSecciones.secciones >= 1, `${ancho}: error con secciones: las secciones encontradas se muestran igualmente`, `${ancho}: error con secciones: no se muestran las secciones`)
    await captura(p, 'pagina-error-con-secciones', ancho)
    fallar = false
    await p.getByRole('button', { name: 'Reintentar' }).click()
    await p.waitForFunction(() => document.querySelectorAll('[role="alert"]').length === 0)
    ok(`${ancho}: «Reintentar» vuelve a pedir y quita el aviso`)
    await ctx.close()
  }

  // ── TC-BUS-26 · axe-core ─────────────────────────────────────────────────
  console.log('\nTC-BUS-26 · axe-core en /buscar y en el encabezado (§4.2, §4.6)')
  {
    const ctx = await contexto(1280, 900)
    for (const [etiqueta, ruta] of [
      ['/buscar sin término', '/buscar'],
      ['/buscar con resultados', '/buscar?q=prueba'],
      ['/buscar con filtro y página', '/buscar?q=prueba&tipo=tramite&page=2'],
      ['/buscar sin resultados', '/buscar?q=zzzz'],
      ['/buscar consulta corta', '/buscar?q=ab'],
      ['/buscar con error', '/buscar?q=error-servidor'],
      ['/buscar con 429', '/buscar?q=limite-peticiones'],
    ]) {
      await sinViolaciones(await abrir(ctx, ruta), etiqueta)
    }
    const p = await abrir(ctx, '/')
    await sinViolaciones(p, 'encabezado con la lista cerrada')
    await escribir(p, 'prueba')
    await esperarOpciones(p, 6)
    await sinViolaciones(p, 'encabezado con la lista abierta')
    await p.keyboard.press('ArrowDown')
    await sinViolaciones(p, 'encabezado con una opción activa')
    await p.locator(CAMPO).fill('')
    await p.keyboard.type('zzzz', { delay: 20 })
    await esperarMensaje(p)
    await sinViolaciones(p, 'encabezado con «sin resultados»')
    await p.locator(CAMPO).fill('')
    await p.keyboard.type('error-servidor', { delay: 15 })
    await esperarMensaje(p)
    await sinViolaciones(p, 'encabezado con error')
    await ctx.close()
  }

  // ── TC-BUS-33 · reflujo ──────────────────────────────────────────────────
  console.log('\nTC-BUS-33 · reflujo a 320 px y con la letra al 200 % (§4.6)')
  for (const [etiqueta, ancho, prefs] of [['320 px', 320, undefined], ['640 px con la letra al 200 %', 640, { letra: 4 }]]) {
    const ctx = await contexto(ancho, 700, { prefs })
    for (const ruta of ['/buscar?q=prueba', '/buscar?q=error-servidor', '/buscar?q=zzzz']) {
      const p = await abrir(ctx, ruta)
      const m = await p.evaluate(() => ({ desborde: document.documentElement.scrollWidth - document.documentElement.clientWidth, ancho: innerWidth }))
      comprobar(m.desborde <= 1, `${etiqueta}: ${ruta} no se desplaza en horizontal`, `${etiqueta}: ${ruta} se desborda ${m.desborde} px`)
    }
    const p = await abrir(ctx, '/')
    await escribir(p, 'prueba')
    await esperarOpciones(p, 6)
    const m = await p.evaluate(() => {
      const l = document.querySelector('.sugerencias-busqueda-govco').getBoundingClientRect()
      const cortado = [...document.querySelectorAll('ul[role="listbox"] [role="option"]')].filter((o) => o.scrollWidth > o.clientWidth + 1).length
      return { dentro: l.left >= -1 && l.right <= innerWidth + 1, desborde: document.documentElement.scrollWidth - document.documentElement.clientWidth, cortado }
    })
    comprobar(m.dentro && m.desborde <= 1 && m.cortado === 0, `${etiqueta}: la lista abierta cabe y no corta texto`, `${etiqueta}: la lista abierta se sale o corta texto: ${JSON.stringify(m)}`)
    await captura(p, 'reflujo-lista', ancho)
    await ctx.close()
  }

  // ── TC-BUS-34 · alto contraste y letra grande ────────────────────────────
  console.log('\nTC-BUS-34 · alto contraste y letra grande (§4.6)')
  for (const [etiqueta, ancho, prefs] of [
    ['alto contraste, 1280 px', 1280, { contraste: 'alto' }],
    ['alto contraste, 360 px', 360, { contraste: 'alto' }],
    ['letra al 150 %, 1280 px', 1280, { letra: 2 }],
    ['alto contraste y letra al 150 %, 360 px', 360, { contraste: 'alto', letra: 2 }],
  ]) {
    const ctx = await contexto(ancho, ancho === 360 ? 740 : 900, { prefs })
    const p = await abrir(ctx, '/')
    await escribir(p, 'prueba')
    await esperarOpciones(p, 6)
    await p.keyboard.press('ArrowDown')
    await p.keyboard.press('ArrowDown')
    const m = await p.evaluate(() => {
      const lum = (c) => { const [r, g, b] = c.match(/\d+(\.\d+)?/g).slice(0, 3).map(Number).map((v) => { const s = v / 255; return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4 }); return 0.2126 * r + 0.7152 * g + 0.0722 * b }
      const ratio = (a, b) => { const [x, y] = [lum(a), lum(b)].sort((u, v) => v - u); return (x + 0.05) / (y + 0.05) }
      const lista = document.querySelector('.sugerencias-busqueda-govco')
      const ls = getComputedStyle(lista)
      const o = [...document.querySelectorAll('ul[role="listbox"] [role="option"]')]
      const activa = o[1], normal = o[3]
      const sa = getComputedStyle(activa), sn = getComputedStyle(normal)
      const titulo = (el) => getComputedStyle(el.querySelector('.titulo-sugerencia-govco'))
      const categoria = (el) => getComputedStyle(el.querySelector('.categoria-sugerencia-govco'))
      return {
        fondoLista: ls.backgroundColor, bordeLista: ls.borderTopColor, anchoBorde: ls.borderTopWidth,
        activa: { fondo: sa.backgroundColor, borde: sa.borderInlineStartColor, anchoBorde: sa.borderInlineStartWidth, contorno: sa.outlineStyle },
        normal: { fondo: sn.backgroundColor, anchoBorde: sn.borderInlineStartWidth },
        contrasteTitulo: ratio(titulo(normal).color, sn.backgroundColor === 'rgba(0, 0, 0, 0)' ? ls.backgroundColor : sn.backgroundColor),
        contrasteTituloActiva: ratio(titulo(activa).color, sa.backgroundColor === 'rgba(0, 0, 0, 0)' ? ls.backgroundColor : sa.backgroundColor),
        contrasteCategoria: ratio(categoria(normal).color, sn.backgroundColor === 'rgba(0, 0, 0, 0)' ? ls.backgroundColor : sn.backgroundColor),
        altoFilas: o.map((x) => Math.round(x.getBoundingClientRect().height)),
        desborde: Math.max(0, ...[...document.querySelectorAll('body *')].filter(() => true).map((e) => Math.round(e.getBoundingClientRect().right) - document.documentElement.clientWidth)),
        desbordePie: document.documentElement.scrollWidth - document.documentElement.clientWidth,
      }
    })
    const distingue = parseFloat(m.activa.anchoBorde) > parseFloat(m.normal.anchoBorde) || m.activa.fondo !== m.normal.fondo
    comprobar(distingue, `${etiqueta}: la fila activa se distingue (borde ${m.activa.anchoBorde} ${m.activa.borde}, fondo ${m.activa.fondo})`, `${etiqueta}: la fila activa no se distingue de las demás: ${JSON.stringify(m)}`)
    comprobar(parseFloat(m.anchoBorde) > 0 && m.bordeLista !== m.fondoLista, `${etiqueta}: la lista tiene borde propio (${m.anchoBorde} ${m.bordeLista})`, `${etiqueta}: la lista no tiene borde propio`)
    comprobar(m.contrasteTitulo >= 4.5 && m.contrasteTituloActiva >= 4.5 && m.contrasteCategoria >= 4.5, `${etiqueta}: contraste del texto de la lista: título ${m.contrasteTitulo.toFixed(1)}:1, activa ${m.contrasteTituloActiva.toFixed(1)}:1, categoría ${m.contrasteCategoria.toFixed(1)}:1`, `${etiqueta}: contraste insuficiente: título ${m.contrasteTitulo.toFixed(2)}, activa ${m.contrasteTituloActiva.toFixed(2)}, categoría ${m.contrasteCategoria.toFixed(2)}`)
    comprobar(m.altoFilas.every((h) => h >= 48), `${etiqueta}: las filas siguen midiendo ≥ 48 px`, `${etiqueta}: filas de menos de 48 px: ${m.altoFilas}`)
    comprobar(m.desborde <= 1 && m.desbordePie <= 1, `${etiqueta}: sin desplazamiento horizontal`, `${etiqueta}: se desborda ${Math.max(m.desborde, m.desbordePie)} px`)
    if (prefs.contraste === 'alto') {
      comprobar(m.fondoLista === 'rgb(0, 0, 0)' && m.bordeLista === 'rgb(255, 255, 255)', `${etiqueta}: la lista es negra con borde blanco`, `${etiqueta}: la lista no es negra con borde blanco: ${m.fondoLista} / ${m.bordeLista}`)
      comprobar(m.activa.borde === 'rgb(255, 255, 0)', `${etiqueta}: la fila activa lleva borde amarillo`, `${etiqueta}: la fila activa no lleva borde amarillo: ${m.activa.borde}`)
      // Iconos del campo (lupa y X): `axe` no mide íconos; aquí se mira el filtro que los lleva a blanco.
      const iconos = await p.evaluate(() =>
        [...document.querySelectorAll('form:has(input[role="combobox"]) .govco-svg')].map((e) => getComputedStyle(e).filter),
      )
      comprobar(iconos.length >= 2 && iconos.every((f) => f === 'brightness(0) invert(1)'), `${etiqueta}: la lupa y la X se dibujan en blanco sobre negro`, `${etiqueta}: iconos con filtro ${JSON.stringify(iconos)}`)
      // Anillo de foco: del contenedor (con su radio), no del <input> rectangular.
      await p.locator(CAMPO).press('Escape')
      await p.locator('button[aria-label="Buscar"]').first().focus()
      await p.keyboard.press('Shift+Tab')
      await p.keyboard.press('Shift+Tab')
      const anilloAlto = await p.evaluate(() => {
        const campo = document.querySelector('input[role="combobox"]')
        const cont = campo.closest('.container-govco')
        const cc = getComputedStyle(cont)
        return { enCampo: document.activeElement === campo, campo: getComputedStyle(campo).outlineStyle, contenedor: `${cc.outlineStyle} ${cc.outlineColor}`, ancho: parseFloat(cc.outlineWidth) }
      })
      comprobar(anilloAlto.enCampo && anilloAlto.campo === 'none' && anilloAlto.contenedor === 'solid rgb(255, 255, 0)' && anilloAlto.ancho >= 2, `${etiqueta}: el anillo de foco amarillo (≥ 2 px) rodea el campo entero`, `${etiqueta}: anillo de foco: ${JSON.stringify(anilloAlto)}`)
    }
    await captura(p, `contraste-${etiqueta.replace(/[^a-z0-9]+/gi, '-').toLowerCase()}`, ancho)
    await sinViolaciones(p, `${etiqueta}: axe con la lista abierta`)
    await ctx.close()
  }
} finally {
  await navegador?.close().catch(() => {})
  servidor.kill()
  await simulada.cerrar?.().catch?.(() => {})
}

console.log(`\nCapturas en ${CAPTURAS}`)
if (fallos > 0) {
  console.error(`\n${fallos} comprobación(es) fallida(s).`)
  process.exit(1)
}
console.log('\nTodas las comprobaciones pasan.')
process.exit(0)
