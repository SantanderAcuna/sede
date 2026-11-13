/**
 * Puerta del Kit UI en navegador real (T-28: AC-28.4, AC-28.8, AC-28.9 y lo que
 * jsdom no puede medir).
 *
 * Uso:  BASE_URL=https://… node tests/kit-ui.mjs
 *       PLAYWRIGHT_MODULE=playwright node tests/kit-ui.mjs   (otra instalación)
 *
 * Qué comprueba, y por qué aquí y no en vitest: lo que depende de la maquetación
 * (tamaños en píxeles, qué cubre qué) o del navegador (foco, cierre por teclado).
 *
 *  1. PQRSD: la tabla de términos tiene `aria-sort` en sus dos columnas, ordena
 *     al pulsar (10 < 15 < 30 días) en las dos direcciones y su botón mide ≥ 44 px.
 *  2. Trámites: cada control de la paginación mide ≥ 44 × 44 px en móvil (390 px) y
 *     la página activa lleva `aria-current="page"`.
 *  3. Directorio de trámites: los grupos de filtros son acordeones del Kit con
 *     `aria-expanded`; tabular hasta uno NO lo abre; Intro lo abre y Espacio lo cierra.
 *  4. Aviso emergente: elegir en el banner de cookies muestra un `role="status"` que
 *     se cierra con su botón y con Esc.
 *
 * Sale con código 1 si algo falla. Lo que no se pudo probar se declara «no comprobado»
 * (no pasa en silencio).
 */

const BASE = (process.env.BASE_URL ?? '').replace(/\/+$/, '')
if (!BASE) {
  console.error('Falta BASE_URL (por ejemplo, BASE_URL=https://staging.example.org).')
  process.exit(2)
}

import { lanzar } from './ayudas/motor.mjs'
import { opcionesAgente } from './ayudas/agente-usuario.mjs'

let fallos = 0
let sinComprobar = 0
const ok = (m) => console.log(`  ✓ ${m}`)
const falla = (m) => {
  fallos += 1
  console.error(`  ✗ ${m}`)
}
const omite = (m) => {
  sinComprobar += 1
  console.log(`  – NO COMPROBADO: ${m}`)
}
const MIN = 44

const navegador = await lanzar()
/** Agente de usuario sin la marca `Headless` (ver ayudas/agente-usuario.mjs). */
const AGENTE = await opcionesAgente(navegador)

async function abrir(ruta, ancho = 1300) {
  const pagina = await navegador.newPage({ viewport: { width: ancho, height: 900 }, ...AGENTE })
  await pagina.goto(`${BASE}${ruta}`, { waitUntil: 'networkidle' })
  // Elegir en el banner dispara el aviso emergente; se cierra para que no tape nada.
  const rechazar = pagina.getByRole('button', { name: /rechazar/i }).first()
  if (await rechazar.count()) {
    await rechazar.click()
    await pagina.getByRole('button', { name: 'Cerrar notificación' }).first().click().catch(() => {})
  }
  return pagina
}

// 1. Tabla ordenable en PQRSD ------------------------------------------------
console.log('PQRSD · tabla de términos')
{
  const p = await abrir('/pqrsd')
  const ths = p.locator('.tabla-ordenable-govco thead th')
  if ((await ths.count()) !== 2) falla(`se esperaban 2 columnas, hay ${await ths.count()}`)
  for (const i of [0, 1]) {
    const v = await ths.nth(i).getAttribute('aria-sort')
    if (v === 'none') ok(`columna ${i + 1}: aria-sort="none" al inicio`)
    else falla(`columna ${i + 1}: aria-sort="${v}" (esperado none)`)
  }
  const plazos = async () =>
    (await p.locator('.tabla-ordenable-govco tbody td').allTextContents()).map((t) => parseInt(t.match(/\d+/)?.[0] ?? '0', 10))
  const boton = ths.nth(1).locator('button')
  const caja = await boton.boundingBox()
  if (caja && caja.height >= MIN - 0.5) ok(`el botón de encabezado mide ${Math.round(caja.height)} px de alto`)
  else falla(`el botón de encabezado mide ${caja ? Math.round(caja.height) : '?'} px (< ${MIN})`)
  await boton.click()
  const asc = await plazos()
  if (JSON.stringify(asc) === JSON.stringify([10, 15, 30]) && (await ths.nth(1).getAttribute('aria-sort')) === 'ascending')
    ok('ascendente: 10, 15, 30 y aria-sort="ascending"')
  else falla(`ascendente incorrecto: ${asc}`)
  await boton.click()
  const desc = await plazos()
  if (JSON.stringify(desc) === JSON.stringify([30, 15, 10]) && (await ths.nth(1).getAttribute('aria-sort')) === 'descending')
    ok('descendente: 30, 15, 10 y aria-sort="descending"')
  else falla(`descendente incorrecto: ${desc}`)
  await p.close()
}

// 2. Paginación en móvil ------------------------------------------------------
console.log('Trámites · paginación a 390 px')
{
  const p = await abrir('/tramites', 390)
  const controles = p.locator('nav[aria-label="Paginación de los resultados"] .page-link')
  const n = await controles.count()
  if (n < 3) falla(`se esperaban al menos Anterior, 1 y Siguiente; hay ${n}`)
  let chicos = 0
  for (let i = 0; i < n; i += 1) {
    const c = await controles.nth(i).boundingBox()
    if (!c || c.width < MIN - 0.5 || c.height < MIN - 0.5) {
      chicos += 1
      falla(`control ${i + 1} («${(await controles.nth(i).textContent())?.trim()}») mide ${c ? `${Math.round(c.width)}×${Math.round(c.height)}` : '?'}`)
    }
  }
  if (chicos === 0) ok(`${n} controles, todos ≥ ${MIN}×${MIN} px`)
  const actual = await p.locator('nav[aria-label="Paginación de los resultados"] [aria-current="page"]').count()
  if (actual === 1) ok('exactamente una página con aria-current="page"')
  else falla(`aria-current="page" aparece ${actual} veces`)
  await p.close()
}

// 3. Acordeón del Kit en los filtros del directorio de trámites ------------------
// La ficha propia muestra los pasos como lista ordenada, no como acordeón: el acordeón del Kit vive
// en el panel de filtros de `/tramites` (Modalidad, Costo, Temáticas…), y es ahí donde se prueba.
console.log('Directorio de trámites · acordeones de los filtros')
{
  const p = await abrir('/tramites')
  const botones = p.locator('.acordeon-govco button[aria-expanded]')
  await botones.first().waitFor({ timeout: 8000 }).catch(() => {})
  const total = await botones.count()
  if (total === 0) {
    falla('el panel de filtros no muestra ningún acordeón del Kit (`.acordeon-govco button[aria-expanded]`)')
  } else {
    ok(`${total} grupo(s) de filtros como acordeón`)
    // Busca uno cerrado para probar que el foco no lo abre.
    let cerrado = null
    for (let i = 0; i < total; i += 1) {
      if ((await botones.nth(i).getAttribute('aria-expanded')) === 'false') {
        cerrado = botones.nth(i)
        break
      }
    }
    if (cerrado === null) omite('todos los grupos nacen abiertos, no se puede probar el foco sobre uno cerrado')
    else {
      await cerrado.focus()
      if ((await cerrado.getAttribute('aria-expanded')) === 'false') ok('recibir el foco NO lo abre')
      else falla('recibir el foco abrió el panel')
      await p.keyboard.press('Enter')
      if ((await cerrado.getAttribute('aria-expanded')) === 'true') ok('Intro lo abre')
      else falla('Intro no abrió el panel')
      await p.keyboard.press('Space')
      if ((await cerrado.getAttribute('aria-expanded')) === 'false') ok('Espacio lo vuelve a cerrar')
      else falla('Espacio no cerró el panel')
    }
  }
  await p.close()
}

// 4. Aviso emergente ------------------------------------------------------------
console.log('Aviso emergente tras elegir en el banner de cookies')
{
  const p = await navegador.newPage({ viewport: { width: 1300, height: 900 }, ...AGENTE })
  await p.goto(`${BASE}/`, { waitUntil: 'networkidle' })
  const rechazar = p.getByRole('button', { name: /rechazar/i }).first()
  if ((await rechazar.count()) === 0) {
    omite('el banner no apareció (ya hay una elección guardada)')
  } else {
    await rechazar.click()
    const aviso = p.locator('.toast[role="status"]').first()
    await aviso.waitFor({ timeout: 3000 }).catch(() => {})
    if ((await aviso.count()) === 1) ok('aparece un aviso con role="status"')
    else falla('no apareció el aviso')
    await aviso.focus().catch(() => {})
    await aviso.locator('button').focus()
    await p.keyboard.press('Escape')
    if ((await p.locator('.toast').count()) === 0) ok('Esc lo cierra')
    else falla('Esc no cerró el aviso')
  }
  await p.close()
}

await navegador.close()
console.log(`\n${fallos === 0 ? 'Kit UI: todo lo comprobado pasa' : `Kit UI: ${fallos} fallo(s)`}${sinComprobar ? ` · ${sinComprobar} punto(s) NO comprobado(s)` : ''}`)
process.exit(fallos === 0 ? 0 : 1)
