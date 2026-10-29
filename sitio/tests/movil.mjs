/**
 * Puerta móvil en navegador real: lo que sólo se ve midiendo una pantalla de
 * teléfono (revisión de las 20 rutas a 320, 360 y 390 px).
 *
 * Uso:  BASE_URL=https://… node tests/movil.mjs
 *       PLAYWRIGHT_MODULE=playwright node tests/movil.mjs   (otra instalación)
 *
 * Qué comprueba, y por qué aquí y no en vitest: todo depende de la maquetación.
 *
 *  1. Banner de cookies. En 390×844, 360×740, 320×568 y 667×375 (horizontal) no
 *     pasa del 72 % de la pantalla, las dos salidas de igual peso se ven sin
 *     desplazar y las categorías sólo aparecen al pedirlas (con «Guardar mi
 *     elección» alcanzable). En escritorio las categorías se ven y el botón de
 *     desplegar no existe.
 *  2. Botón de accesibilidad (CAG-07, Kit UI 9.2, RNF-07-D01, HU-07-D02). Por
 *     debajo de 992 px el único disparador visible es el de la cabecera (no flota,
 *     ≥ 44 px, cabe en la pantalla) y el círculo flotante no se dibuja; desde 992
 *     px es un círculo fijo, centrado a la derecha, sin solaparse con «Volver
 *     arriba». Además, desde 992 px, medido de forma analítica en 17 rutas y 5
 *     anchos: ningún texto, imagen ni control entra en la columna del círculo.
 *  3. Panel de accesibilidad. Cabe en la pantalla, el aviso va dentro de la zona
 *     que se desplaza y el botón de cerrar mide ≥ 44 px.
 *  4. Ninguna ruta pública se sale del ancho a 320 ni a 390 px.
 *  5. Áreas táctiles de ≥ 44 px: botón del menú GOV.CO, indicadores del carrusel
 *     (comprobado con `elementFromPoint` en las esquinas del área), «Reproducir»,
 *     enlaces de ficha del listado de trámites y enlaces de la página 404.
 *  6. Miga de pan alineada con el contenido (sangría ≤ 16 px a 390 px) y «Inicio»
 *     en una sola línea (se partía en «Inici / o»).
 *  7. Buscador (RF-B1-005). La lupa se dibuja (lleva el filtro de color del Kit;
 *     con el del Kit por defecto —blanco— desaparece sobre el fondo blanco); hay
 *     **un solo** buscador en la página (antes, cabecera y menú: dos regiones
 *     «search» con el mismo nombre); el campo vacío muestra 27 caracteres a 320,
 *     360 y 390 px, con texto y botón de borrar caben a partir de 360 px, y al
 *     borrar el foco vuelve al campo.
 *  9. Carrusel. La imagen mide ≤ 144 px de alto a 320 y 390 px (el Kit deja 216).
 *  8. Nombre accesible (WCAG 2.5.3, RF-B3-066): axe `label-content-name-mismatch`
 *     sin violaciones en la portada, en el panel de accesibilidad abierto y en
 *     la declaración de accesibilidad. El rótulo que se ve tiene que estar
 *     dentro del nombre que anuncia el lector de pantalla.
 *
 * Sale con código 1 si algo falla.
 */
import { createRequire } from 'node:module'

const BASE = (process.env.BASE_URL ?? '').replace(/\/+$/, '')
if (!BASE) {
  console.error('Falta BASE_URL (por ejemplo, BASE_URL=https://staging.example.org).')
  process.exit(2)
}

import { lanzar, opcionesMovil } from './ayudas/motor.mjs'

let fallos = 0
const ok = (m) => console.log(`  ✓ ${m}`)
const falla = (m) => {
  fallos += 1
  console.error(`  ✗ ${m}`)
}
const comprobar = (condicion, bien, mal) => (condicion ? ok(bien) : falla(mal))
const MIN = 44

const navegador = await lanzar()

async function contexto(ancho, alto, movil = true) {
  return navegador.newContext({
    viewport: { width: ancho, height: alto },
    ...opcionesMovil(movil),
    deviceScaleFactor: 1,
  })
}

/** Abre una ruta con el banner ya resuelto (para medir el resto de la página). */
async function abrirResuelta(ctx, ruta) {
  const p = await ctx.newPage()
  // En CI se prueba contra un entorno remoto: 10 s no alcanzan con `networkidle`.
  p.setDefaultTimeout(Number(process.env.TIEMPO_ESPERA_MS) || 10000)
  await p.goto(`${BASE}${ruta}`, { waitUntil: 'networkidle' })
  const rechazar = p.getByRole('button', { name: /rechazar/i }).first()
  if (await rechazar.count()) {
    await rechazar.click()
    await p.getByRole('button', { name: 'Cerrar notificación' }).first().click().catch(() => {})
  }
  return p
}

const dentro = (caja, ancho, alto) =>
  caja !== null && caja.y >= -1 && caja.y + caja.height <= alto + 1 && caja.x >= -1 && caja.x + caja.width <= ancho + 1

// 1. Banner de cookies -------------------------------------------------------
console.log('Banner de cookies')
for (const [ancho, alto] of [[390, 844], [360, 740], [320, 568], [667, 375]]) {
  const ctx = await contexto(ancho, alto)
  const p = await ctx.newPage()
  await p.goto(`${BASE}/`, { waitUntil: 'networkidle' })
  const etiqueta = `${ancho}×${alto}`
  const banner = p.locator('.banner-cookies')
  const caja = await banner.boundingBox()
  comprobar(
    caja !== null && caja.height <= alto * 0.72,
    `${etiqueta}: el banner ocupa ${Math.round((100 * caja.height) / alto)} % de la pantalla`,
    `${etiqueta}: el banner ocupa ${caja ? Math.round((100 * caja.height) / alto) : '?'} % (máx. 72 %)`,
  )
  const aceptar = await p.getByRole('button', { name: 'Aceptar todas' }).boundingBox()
  const rechazar = await p.getByRole('button', { name: 'Rechazar opcionales' }).boundingBox()
  comprobar(dentro(aceptar, ancho, alto) && dentro(rechazar, ancho, alto), `${etiqueta}: «Aceptar» y «Rechazar» se ven sin desplazar`, `${etiqueta}: «Aceptar» o «Rechazar» quedan fuera de la pantalla`)
  comprobar(aceptar && rechazar && aceptar.height >= MIN - 0.5 && rechazar.height >= MIN - 0.5, `${etiqueta}: los dos botones miden ≥ 44 px de alto`, `${etiqueta}: algún botón del banner mide menos de 44 px`)
  const categorias = p.locator('.categorias-cookies')
  comprobar(!(await categorias.isVisible()), `${etiqueta}: las categorías empiezan plegadas`, `${etiqueta}: las categorías se ven sin pedirlas`)
  const alternar = p.getByRole('button', { name: 'Elegir por categorías' })
  await alternar.click()
  const abierto = await p.getByRole('button', { name: 'Ocultar categorías' }).getAttribute('aria-expanded')
  comprobar(abierto === 'true', `${etiqueta}: al desplegar, aria-expanded="true"`, `${etiqueta}: aria-expanded no cambió (${abierto})`)
  comprobar(await categorias.isVisible(), `${etiqueta}: las categorías se ven al pedirlas`, `${etiqueta}: las categorías siguen ocultas`)
  const guardar = p.getByRole('button', { name: 'Guardar mi elección' })
  await guardar.scrollIntoViewIfNeeded()
  comprobar(dentro(await guardar.boundingBox(), ancho, alto), `${etiqueta}: «Guardar mi elección» alcanzable`, `${etiqueta}: «Guardar mi elección» fuera de la pantalla`)
  await guardar.click()
  comprobar(!(await banner.isVisible()), `${etiqueta}: guardar cierra el banner`, `${etiqueta}: el banner sigue tras guardar`)
  await ctx.close()
}
{
  const ctx = await contexto(1300, 900, false)
  const p = await ctx.newPage()
  await p.goto(`${BASE}/`, { waitUntil: 'networkidle' })
  comprobar(await p.locator('.categorias-cookies').isVisible(), 'escritorio: las categorías se ven', 'escritorio: las categorías no se ven')
  comprobar(!(await p.getByRole('button', { name: /categorías/ }).isVisible()), 'escritorio: no hay botón de desplegar', 'escritorio: aparece el botón de desplegar')
  await ctx.close()
}

// 2. Botón de accesibilidad y volver arriba ---------------------------------
// Criterios: CAG-07 y Kit UI 9.2 (la barra no aparece flotando por debajo de 992 px
// porque «puede llegar a ocultar contenido»), RF-B1-044, RNF-07-D01 (disponible y
// operable desde 320 px), HU-07-D02 (sin solape con el contenido), RNF-B3-005
// (≥ 44 × 44 px).
//   · < 992 px: el único disparador visible es el de la cabecera. No es `fixed`,
//     no reserva carril y queda por encima de todo (es parte de la cabecera).
//   · ≥ 992 px: el círculo flotante, fijo y centrado a la derecha.
// La primera revisión móvil lo anclaba abajo y la segunda reservaba un carril de
// 56 px: las dos tapaban o estrechaban el contenido. Esta lo resuelve de raíz.
console.log('Botón de accesibilidad')
for (const [ancho, alto] of [[390, 844], [320, 568], [667, 375], [800, 1000], [991, 800]]) {
  const ctx = await contexto(ancho, alto)
  const p = await abrirResuelta(ctx, '/tramites')
  const etiqueta = `${ancho}×${alto}`
  const visibles = p.locator('.boton-accesibilidad:visible')
  comprobar((await visibles.count()) === 1, `${etiqueta}: hay un solo disparador visible`, `${etiqueta}: hay ${await visibles.count()} disparadores visibles`)
  const m = await visibles.first().evaluate((b) => {
    const c = b.getBoundingClientRect()
    return { fijo: getComputedStyle(b).position === 'fixed', cabecera: Boolean(b.closest('.cabecera-govco')), x: c.x, y: c.y, w: c.width, h: c.height }
  })
  comprobar(!m.fijo && m.cabecera, `${etiqueta}: va en la cabecera y no flota`, `${etiqueta}: el disparador no está en la cabecera o flota (fijo=${m.fijo}, cabecera=${m.cabecera})`)
  comprobar(m.w >= MIN && m.h >= MIN, `${etiqueta}: mide ${Math.round(m.w)}×${Math.round(m.h)} px`, `${etiqueta}: mide menos de 44 px`)
  comprobar(m.x >= 0 && m.x + m.w <= ancho + 1 && m.y >= 0, `${etiqueta}: cabe en la pantalla`, `${etiqueta}: se sale de la pantalla`)
  await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2))
  await p.waitForTimeout(400)
  // Tras desplazar, lo único que flota es «Volver arriba»: el círculo no existe.
  const flotantes = await p.evaluate(() =>
    [...document.querySelectorAll('body *')]
      .filter((e) => getComputedStyle(e).position === 'fixed' && e.getBoundingClientRect().width > 0 && !e.closest('dialog, .banner-cookies'))
      .map((e) => e.className?.baseVal ?? String(e.className)),
  )
  comprobar(!flotantes.some((c) => c.includes('boton-accesibilidad')), `${etiqueta}: el círculo flotante no se dibuja`, `${etiqueta}: hay un círculo flotante visible (${flotantes.join(' | ')})`)
  await ctx.close()
}
for (const [ancho, alto] of [[1300, 900], [992, 800], [1536, 864]]) {
  const ctx = await contexto(ancho, alto, false)
  const p = await abrirResuelta(ctx, '/')
  const etiqueta = `${ancho}×${alto}`
  const visibles = p.locator('.boton-accesibilidad:visible')
  comprobar((await visibles.count()) === 1, `escritorio ${etiqueta}: un solo disparador visible`, `escritorio ${etiqueta}: hay ${await visibles.count()} disparadores visibles`)
  const fab = await visibles.first().boundingBox()
  comprobar(fab !== null && Math.abs(fab.y + fab.height / 2 - alto / 2) <= 2, `escritorio ${etiqueta}: centrado verticalmente`, `escritorio ${etiqueta}: no está centrado`)
  comprobar(fab !== null && ancho - (fab.x + fab.width) <= 16, `escritorio ${etiqueta}: pegado a la derecha`, `escritorio ${etiqueta}: se aleja del borde derecho`)
  comprobar(fab !== null && fab.width >= MIN && fab.height >= MIN, `escritorio ${etiqueta}: mide ${Math.round(fab.width)} px`, `escritorio ${etiqueta}: mide menos de 44 px`)
  await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2))
  await p.waitForTimeout(400)
  const subir = await p.locator('.volver-arriba-govco').boundingBox()
  if (subir !== null && fab !== null) {
    const solapa = !(subir.y + subir.height <= fab.y || subir.y >= fab.y + fab.height || subir.x + subir.width <= fab.x || subir.x >= fab.x + fab.width)
    comprobar(!solapa, `escritorio ${etiqueta}: «Volver arriba» no se solapa con el círculo`, `escritorio ${etiqueta}: «Volver arriba» se solapa con el círculo`)
  } else {
    falla(`escritorio ${etiqueta}: no apareció «Volver arriba» tras desplazar`)
  }
  await ctx.close()
}

// 2b. El círculo no tapa contenido (HU-07-D02: «no se solapa con el contenido») -
// Desde 992 px el círculo flota; para que no cubra nada, el contenido deja libre la
// columna de su ancho (`--acc-flotante-columna`). Se mide **analíticamente**: el
// círculo, al desplazarse, puede cruzar cualquier altura de la página, así que no
// puede haber texto, imagen ni control cuya caja entre en esa columna. No se
// cuenta lo que no es contenido: textos sólo para lectores de pantalla y las
// imágenes o enlaces que ocupan el ancho entero (el cartel del carrusel).
console.log('Columna libre del círculo flotante (≥ 992 px)')
const RUTAS_TEXTO = ['/', '/transparencia', '/atencion', '/tramites', '/pqrsd', '/realizar-una-peticion', '/seguimiento', '/noticias', '/portales', '/servicios', '/normativa', '/buscar?q=salud', '/mapa-del-sitio', '/accesibilidad', '/politicas', '/participa', '/ruta-que-no-existe']
/** Elementos de contenido cuya caja entra en la columna del círculo flotante. */
async function invadenLaColumna(p) {
  return p.evaluate(() => {
    const fab = document.querySelector('.boton-accesibilidad--flotante')
    if (!fab || getComputedStyle(fab).display === 'none') return { sin: true }
    const f = fab.getBoundingClientRect()
    const alto = document.documentElement.scrollHeight
    const sy = window.scrollY
    // El círculo queda a mitad de ventana: con el desplazamiento recorre de vh/2 a alto − vh/2.
    const alcanza = (r) => r.bottom + sy > window.innerHeight / 2 - f.height / 2 && r.top + sy < alto - window.innerHeight / 2 + f.height / 2
    const cruza = (r) => r.width >= 1 && r.height >= 1 && r.left < f.right && r.right > f.left && alcanza(r)
    const ignorado = (el) =>
      el.closest('.etiqueta-buscador-govco, .boton-accesibilidad, dialog, .banner-cookies, .volver-arriba-govco, .visually-hidden, .sr-only, script, style') ||
      getComputedStyle(el).visibility === 'hidden' ||
      getComputedStyle(el).display === 'none'
    const hallados = []
    const it = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT)
    for (let n = it.nextNode(); n; n = it.nextNode()) {
      if (!n.textContent.trim()) continue
      const el = n.parentElement
      if (!el || ignorado(el)) continue
      const g = document.createRange()
      g.selectNodeContents(n)
      for (const r of g.getClientRects()) {
        if (cruza(r)) { hallados.push(`texto «${n.textContent.trim().slice(0, 22)}»`); break }
      }
    }
    for (const el of document.querySelectorAll('img, svg, input, select, textarea, button, a[href], table, hr, summary, video')) {
      if (ignorado(el)) continue
      const r = el.getBoundingClientRect()
      if (r.width > window.innerWidth * 0.9) continue
      if (cruza(r)) hallados.push(`${el.tagName.toLowerCase()}.${String(el.className?.baseVal ?? el.className).split(' ')[0]}`)
    }
    return { sin: false, hallados: [...new Set(hallados)] }
  })
}
for (const ancho of [992, 1100, 1280, 1536, 1920]) {
  const ctx = await contexto(ancho, 800, false)
  const p = await abrirResuelta(ctx, '/')
  const con = []
  for (const ruta of RUTAS_TEXTO) {
    await p.goto(`${BASE}${ruta}`, { waitUntil: 'networkidle' })
    const r = await invadenLaColumna(p)
    if (r.sin) con.push(`${ruta} (sin círculo)`)
    else if (r.hallados.length) con.push(`${ruta} → ${r.hallados.slice(0, 3).join(', ')}`)
  }
  comprobar(con.length === 0, `${ancho} px: el círculo no cubre contenido en ${RUTAS_TEXTO.length} rutas`, `${ancho} px: contenido bajo el círculo en ${con.length} ruta(s): ${con.slice(0, 4).join(' ; ')}`)
  await ctx.close()
}

// 3. Panel de accesibilidad --------------------------------------------------
console.log('Panel de accesibilidad')
for (const [ancho, alto] of [[390, 844], [320, 568], [667, 375]]) {
  const ctx = await contexto(ancho, alto)
  const p = await abrirResuelta(ctx, '/')
  await p.locator('.boton-accesibilidad:visible').click()
  const dialogo = p.locator('dialog[open]')
  const caja = await dialogo.boundingBox()
  const etiqueta = `${ancho}×${alto}`
  comprobar(dentro(caja, ancho, alto), `${etiqueta}: el panel cabe en la pantalla`, `${etiqueta}: el panel se sale de la pantalla`)
  comprobar((await dialogo.locator('.panel-contenido .panel-aviso').count()) === 1, `${etiqueta}: el aviso va en la zona que se desplaza`, `${etiqueta}: el aviso quedó fuera de la zona que se desplaza`)
  const cerrar = await dialogo.locator('.panel-cerrar').boundingBox()
  comprobar(cerrar !== null && cerrar.width >= MIN - 0.5 && cerrar.height >= MIN - 0.5, `${etiqueta}: cerrar mide ≥ 44 px`, `${etiqueta}: cerrar mide menos de 44 px`)
  const restablecer = dialogo.getByRole('button', { name: 'Restablecer todo' })
  await restablecer.scrollIntoViewIfNeeded()
  comprobar(dentro(await restablecer.boundingBox(), ancho, alto), `${etiqueta}: «Restablecer todo» se alcanza desplazando`, `${etiqueta}: «Restablecer todo» no se alcanza`)
  await ctx.close()
}

// 4. Sin desbordamiento horizontal ------------------------------------------
console.log('Desbordamiento horizontal')
const RUTAS = ['/', '/transparencia', '/atencion', '/tramites', '/pqrsd', '/realizar-una-peticion', '/seguimiento', '/noticias', '/portales', '/servicios', '/normativa', '/buscar', '/buscar?q=salud', '/mapa-del-sitio', '/accesibilidad', '/politicas', '/participa', '/verificar/ABCD-1234', '/ruta-que-no-existe']
// 800, 992 y 1000 px: los anchos de tableta y el umbral donde aparece el círculo
// flotante. A 992–1008 px el pie desbordaba 17 px (cuatro redes sociales en una
// fila sin `wrap`) y esta puerta sólo miraba teléfonos.
for (const ancho of [320, 390, 800, 992, 1000]) {
  const ctx = await contexto(ancho, 740, ancho < 800)
  const p = await abrirResuelta(ctx, '/')
  const malas = []
  for (const ruta of RUTAS) {
    await p.goto(`${BASE}${ruta}`, { waitUntil: 'networkidle' })
    // Se compara con el ancho que se pidió, no con `innerWidth`: ante un desborde el
    // navegador móvil ensancha la ventana de diseño y `innerWidth` crece con él.
    const exceso = await p.evaluate((ancho) => document.documentElement.scrollWidth - ancho, ancho)
    if (exceso > 0) malas.push(`${ruta} (+${exceso} px)`)
  }
  comprobar(malas.length === 0, `${ancho} px: ${RUTAS.length} rutas sin desbordamiento`, `${ancho} px: desbordan ${malas.join(', ')}`)
  await ctx.close()
}

// 5. Áreas táctiles ----------------------------------------------------------
console.log('Áreas táctiles (390 px)')
{
  const ctx = await contexto(390, 844)
  const p = await abrirResuelta(ctx, '/')
  const menu = await p.locator('.button-rounded-menu-govco').first().boundingBox()
  comprobar(menu !== null && menu.width >= MIN && menu.height >= MIN, `botón del menú GOV.CO: ${Math.round(menu.width)}×${Math.round(menu.height)}`, 'botón del menú GOV.CO por debajo de 44 px')

  const puntos = p.locator('.carrusel-govco .carousel-indicators button')
  const n = await puntos.count()
  let mal = 0
  for (let i = 0; i < n; i++) {
    const c = await puntos.nth(i).boundingBox()
    // Las cuatro esquinas del área de 44 px, a 1 px del borde, tienen que
    // pertenecer al propio indicador (no a un vecino ni a la barra).
    const cx = c.x + c.width / 2
    const cy = c.y + c.height / 2
    for (const [dx, dy] of [[-21, -21], [21, -21], [-21, 21], [21, 21]]) {
      const pertenece = await p.evaluate(([x, y, idx]) => {
        const el = document.elementFromPoint(x, y)
        const btn = document.querySelectorAll('.carrusel-govco .carousel-indicators button')[idx]
        return el === btn
      }, [cx + dx, cy + dy, i])
      if (!pertenece) mal += 1
    }
  }
  comprobar(n > 1 && mal === 0, `indicadores del carrusel: ${n} áreas de 44 × 44 px`, `indicadores del carrusel: ${mal} esquinas del área de 44 px no responden`)
  if (n > 1) {
    const a = await puntos.nth(0).boundingBox()
    const b = await puntos.nth(1).boundingBox()
    comprobar(b.x - a.x >= MIN - 0.5, `paso entre indicadores ${Math.round(b.x - a.x)} px`, `paso entre indicadores ${Math.round(b.x - a.x)} px (< 44)`)
  }
  const play = await p.locator('.control-start-pause button').boundingBox()
  comprobar(play !== null && play.height >= MIN - 0.5, `«Reproducir»: ${Math.round(play.height)} px de alto`, '«Reproducir» mide menos de 44 px de alto')
  await ctx.close()
}
{
  const ctx = await contexto(390, 844)
  const p = await abrirResuelta(ctx, '/tramites')
  const enlaces = p.locator('main .enlace-ficha-tramite')
  const n = await enlaces.count()
  let corto = 0
  for (let i = 0; i < n; i++) if ((await enlaces.nth(i).boundingBox()).height < MIN - 0.5) corto += 1
  comprobar(n > 0 && corto === 0, `«Ver la ficha completa»: ${n} enlaces de ≥ 44 px`, n === 0 ? 'no hay enlaces de ficha (¿catálogo vacío?)' : `«Ver la ficha completa»: ${corto} de ${n} bajo 44 px`)

  // 6. Miga de pan
  const miga = await p.locator('.breadcrumb-govco .breadcrumb-item-govco').first().boundingBox()
  comprobar(miga !== null && miga.x <= 16, `miga de pan: empieza a ${Math.round(miga.x)} px del borde`, `miga de pan: empieza a ${Math.round(miga?.x)} px del borde (sangría del Kit)`)
  // «Inicio» en una sola línea: el enlace mide como mucho una línea de texto más su
  // relleno (22 + 24 = 46 px); partido en «Inici / o» medía 69 px.
  const inicio = await p.locator('.breadcrumb-govco .breadcrumb-item-govco:first-child > a').first().boundingBox()
  comprobar(inicio !== null && inicio.height <= 50, `miga de pan: «Inicio» en una línea (${Math.round(inicio?.height ?? 0)} px de alto)`, `miga de pan: «Inicio» se parte en varias líneas (${Math.round(inicio?.height ?? 0)} px de alto)`)
  await ctx.close()
}
{
  const ctx = await contexto(390, 844)
  const p = await abrirResuelta(ctx, '/ruta-que-no-existe')
  const enlaces = p.locator('.enlace-seccion')
  const n = await enlaces.count()
  let corto = 0
  for (let i = 0; i < n; i++) if ((await enlaces.nth(i).boundingBox()).height < MIN - 0.5) corto += 1
  comprobar(n > 0 && corto === 0, `página 404: ${n} enlaces de ≥ 44 px`, `página 404: ${corto} de ${n} enlaces bajo 44 px`)
  await ctx.close()
}

// 7. Lupa del buscador -------------------------------------------------------
console.log('Buscador')
{
  const ctx = await contexto(390, 844)
  const p = await abrirResuelta(ctx, '/')
  const filtro = await p.evaluate(() => {
    const icono = document.querySelector('.btn-search-basic-govco .govco-svg')
    return icono ? getComputedStyle(icono).filter : null
  })
  comprobar(filtro !== null && filtro !== 'none', 'la lupa lleva filtro de color (se dibuja)', `la lupa no lleva filtro de color (${filtro})`)
  const formularios = await p.locator('form[role="search"]').count()
  comprobar(formularios === 1, 'un solo buscador en la página', `${formularios} buscadores en la página (se espera 1)`)
  await ctx.close()
}

// Muestra de 27 caracteres con letras de ancho medio (no «iiii…» ni «WWWW…»).
const MUESTRA = 'licencia de construcción ab'
for (const ancho of [320, 360, 390]) {
  const ctx = await contexto(ancho, 740)
  const p = await abrirResuelta(ctx, '/')
  const campo = p.locator('form[role="search"] input')
  const vacio = await campo.evaluate((i, texto) => {
    const cs = getComputedStyle(i)
    const lienzo = document.createElement('canvas').getContext('2d')
    lienzo.font = `${cs.fontSize} ${cs.fontFamily}`
    const area = i.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight)
    let n = 0
    while (n < texto.length && lienzo.measureText(texto.slice(0, n + 1)).width <= area) n += 1
    return n
  }, MUESTRA)
  comprobar(vacio === MUESTRA.length, `${ancho} px: el campo vacío muestra ${MUESTRA.length} caracteres`, `${ancho} px: el campo vacío sólo muestra ${vacio} de ${MUESTRA.length} caracteres`)
  await campo.fill(MUESTRA)
  const cabe = await campo.evaluate((i) => i.scrollWidth <= i.clientWidth)
  if (ancho >= 360) comprobar(cabe, `${ancho} px: con texto y botón de borrar caben los ${MUESTRA.length} caracteres`, `${ancho} px: con texto, los ${MUESTRA.length} caracteres no caben (el campo se desplaza)`)
  const tamLetra = await campo.evaluate((i) => parseFloat(getComputedStyle(i).fontSize))
  comprobar(tamLetra >= 16, `${ancho} px: letra del campo ${tamLetra} px (≥ 16: iOS no amplía la página al enfocar)`, `${ancho} px: letra del campo ${tamLetra} px (< 16: iOS amplía la página al enfocar)`)
  await campo.focus()
  await p.getByRole('button', { name: 'Limpiar la búsqueda' }).click()
  const foco = await p.evaluate(() => document.activeElement?.tagName)
  comprobar(foco === 'INPUT', `${ancho} px: al borrar, el foco vuelve al campo`, `${ancho} px: al borrar, el foco quedó en ${foco}`)
  await ctx.close()
}

// 9. Imagen del carrusel en un teléfono --------------------------------------
console.log('Carrusel')
for (const ancho of [320, 390]) {
  const ctx = await contexto(ancho, 844)
  const p = await abrirResuelta(ctx, '/')
  const alto = await p.locator('.carousel-item img').first().evaluate((i) => Math.round(i.getBoundingClientRect().height))
  comprobar(alto <= 144, `${ancho} px: imagen del carrusel de ${alto} px (≤ 144; el Kit deja 216)`, `${ancho} px: imagen del carrusel de ${alto} px (> 144: empuja el texto bajo la primera pantalla)`)
  await ctx.close()
}

// 8. Nombre accesible que contiene el texto visible --------------------------
console.log('Nombre accesible (2.5.3)')
{
  const axe = (await import('node:fs')).readFileSync(createRequire(import.meta.url).resolve('axe-core/axe.min.js'), 'utf8')
  const revisar = async (p, etiqueta) => {
    await p.evaluate(axe)
    const nodos = await p.evaluate(async () => {
      const r = await window.axe.run(document, { runOnly: ['label-content-name-mismatch'] })
      return r.violations.flatMap((v) => v.nodes.map((n) => n.target.join(' ')))
    })
    comprobar(nodos.length === 0, `${etiqueta}: sin desajustes entre rótulo visible y nombre`, `${etiqueta}: ${nodos.length} desajuste(s): ${nodos.slice(0, 3).join(' | ')}`)
  }
  const ctx = await contexto(390, 844)
  const p = await abrirResuelta(ctx, '/')
  await revisar(p, 'portada')
  await p.locator('.boton-accesibilidad:visible').click()
  await p.waitForTimeout(400)
  await revisar(p, 'panel de accesibilidad abierto')
  await p.goto(`${BASE}/accesibilidad`, { waitUntil: 'networkidle' })
  await revisar(p, '/accesibilidad')
  await ctx.close()
}

await navegador.close()
if (fallos > 0) {
  console.error(`\n${fallos} comprobación(es) fallida(s).`)
  process.exit(1)
}
console.log('\nTodo en orden.')
