/**
 * Humo funcional de la Sede, igual en cada navegador (T-29; AC-29.2, AC-29.3).
 *
 * Lo que se mide es el comportamiento, no la estética: que lo que funciona en un
 * motor funcione en los demás. La estética la vigilan `tests/diseno.mjs` y
 * `tests/movil.mjs` (que aceptan `NAVEGADOR=firefox|webkit|msedge`).
 *
 * Uso: BASE_URL=https://… npx playwright test   (ver `playwright.config.mjs`)
 */
import { expect, test } from '@playwright/test'

const MIN = 44

const esMovil = (info) => info.project.name.endsWith('-movil')

/** Entra en una ruta y anota lo que el navegador reporta como fallo de ejecución. */
async function abrir(page, ruta = '/') {
  const errores = []
  const respuestas5xx = []
  page.on('pageerror', (e) => errores.push(String(e)))
  page.on('response', (r) => {
    if (r.status() >= 500) respuestas5xx.push(`${r.status()} ${r.url()}`)
  })
  const respuesta = await page.goto(ruta, { waitUntil: 'networkidle' })
  return { respuesta, errores, respuestas5xx }
}

/** Resuelve el banner de cookies para que no tape nada. */
async function resolverBanner(page) {
  const rechazar = page.getByRole('button', { name: /rechazar/i }).first()
  if (await rechazar.count()) {
    await rechazar.click()
    await page.getByRole('button', { name: 'Cerrar notificación' }).first().click().catch(() => {})
  }
}

test('la portada carga, con un único h1 y sin errores de ejecución', async ({ page }) => {
  const { respuesta, errores, respuestas5xx } = await abrir(page)
  expect(respuesta?.status()).toBe(200)
  await expect(page.locator('h1')).toHaveCount(1)
  expect(errores, 'errores de ejecución del navegador').toEqual([])
  expect(respuestas5xx, 'respuestas 5xx').toEqual([])
})

test('el menú lleva a Normativa', async ({ page }, info) => {
  await abrir(page)
  await resolverBanner(page)
  if (esMovil(info)) await page.getByRole('button', { name: 'Abrir el menú de navegación' }).click()
  await page
    .getByRole('navigation', { name: /Menú principal/ })
    .getByRole('link', { name: 'Normativa', exact: true })
    .click()
  await expect(page).toHaveURL(/\/normativa/)
  await expect(page.locator('h1')).toHaveCount(1)
})

test('el buscador envía el término y abre los resultados', async ({ page }) => {
  await abrir(page)
  await resolverBanner(page)
  await page.locator('form[role="search"] input').fill('licencia')
  await page.locator('form[role="search"] input').press('Enter')
  await expect(page).toHaveURL(/\/buscar\?q=licencia/)
})

test('cookies: rechazar cierra el banner y la elección sobrevive a recargar', async ({ page }) => {
  await abrir(page)
  const rechazar = page.getByRole('button', { name: /rechazar/i }).first()
  await expect(rechazar).toBeVisible()
  await rechazar.click()
  await expect(rechazar).toBeHidden()
  await page.reload({ waitUntil: 'networkidle' })
  await expect(page.getByRole('button', { name: /rechazar/i })).toHaveCount(0)
})

test('carrusel: pausado por defecto, Reproducir/Pausar y flechas', async ({ page }) => {
  await abrir(page)
  await resolverBanner(page)
  const control = page.locator('.control-start-pause button')
  await expect(control).toContainText('Reproducir')
  const activo = () => page.locator('.carousel-indicators button.active').getAttribute('aria-label')
  const antes = await activo()
  await page.getByRole('button', { name: 'Diapositiva siguiente' }).click()
  await expect.poll(activo).not.toBe(antes)
  await control.click()
  await expect(control).toContainText('Pausar')
  await control.click()
  await expect(control).toContainText('Reproducir')
})

test('panel de accesibilidad: abre, agranda el texto, atrapa el foco y Esc lo cierra', async ({ page }) => {
  await abrir(page)
  await resolverBanner(page)
  const abrirPanel = page.locator('.boton-accesibilidad')
  await abrirPanel.click()
  const dialogo = page.locator('dialog[open]')
  await expect(dialogo).toBeVisible()

  // Texto más grande: el panel lo declara en su indicador («100 %» → «110 %») y la
  // escala de verdad: el sitio usa `zoom` en la raíz (no toca el `font-size`), y
  // `zoom` es una propiedad que cada motor llegó a admitir en fechas distintas, de
  // ahí que valga la pena comprobarla en los tres.
  const porcentaje = () => dialogo.locator('.panel-tamano-valor').innerText().then((t) => parseInt(t, 10))
  const letraDelContenido = () => page.evaluate(() => parseFloat(document.documentElement.style.zoom || '1'))
  const antesPorcentaje = await porcentaje()
  const antesLetra = await letraDelContenido()
  await dialogo.getByRole('button', { name: 'Aumentar el tamaño del texto' }).click()
  await expect.poll(porcentaje).toBeGreaterThan(antesPorcentaje)
  await expect.poll(letraDelContenido).toBeGreaterThan(antesLetra)

  // Ningún elemento de la página de detrás recibe el foco aunque se tabule más
  // veces que controles tiene el diálogo. Un `<dialog>` modal nativo deja salir el
  // foco hacia la interfaz del navegador (la barra de direcciones) tras el último
  // control —en Chromium y WebKit `activeElement` pasa a ser `body`—, y eso es el
  // comportamiento esperado, no una fuga: lo que no puede ocurrir es que el foco
  // caiga en un enlace o botón de la página inerte.
  const n = await dialogo.locator('button, a[href], input, select, textarea, [tabindex]:not([tabindex="-1"])').count()
  let sinFoco = 0
  for (let i = 0; i < n * 2 + 3; i++) {
    await page.keyboard.press('Tab')
    const estado = await page.evaluate(() => {
      const el = document.activeElement
      if (!el || el === document.body || el === document.documentElement) return 'navegador'
      return el.closest('dialog[open]') ? 'dialogo' : `página: ${el.tagName.toLowerCase()}.${String(el.className).split(' ')[0]}`
    })
    expect(['dialogo', 'navegador'], `Tab #${i + 1}: el foco cayó en ${estado}`).toContain(estado)
    if (estado === 'navegador') sinFoco += 1
  }
  // Tras salir hacia el navegador el foco vuelve al diálogo (la tabulación cicla).
  expect(sinFoco, 'el foco no puede quedarse fuera del diálogo').toBeLessThan(n)

  // Esc cierra y devuelve el foco a quien lo abrió.
  await page.keyboard.press('Escape')
  await expect(dialogo).toHaveCount(0)
  await expect(abrirPanel).toBeFocused()
})

test('una ruta inexistente responde 404 con salidas hacia la sede', async ({ page }) => {
  const { respuesta } = await abrir(page, '/ruta-que-no-existe')
  expect(respuesta?.status()).toBe(404)
  await expect(page.locator('.enlace-seccion').first()).toBeVisible()
})

test('teléfono: todos los controles de la cabecera, el carrusel y el pie miden ≥ 44 × 44 px (AC-29.3)', async ({ page }, info) => {
  test.skip(!esMovil(info), 'sólo en los proyectos de teléfono')
  await abrir(page)
  await resolverBanner(page)
  const pequenos = await page.evaluate((min) => {
    const zonas = ['.cabecera-govco', '.menu-govco', '.carrusel-govco', 'footer', '.boton-accesibilidad', '.volver-arriba-govco']
    const vistos = new Set()
    const malos = []
    for (const zona of zonas) {
      for (const raiz of document.querySelectorAll(zona)) {
        for (const el of raiz.querySelectorAll('a[href], button, input, select, textarea, [role="button"]')) {
          if (vistos.has(el)) continue
          vistos.add(el)
          const cs = getComputedStyle(el)
          const r = el.getBoundingClientRect()
          // Fuera de pantalla o recortado (enlace de salto, textos para lector de pantalla).
          if (cs.visibility === 'hidden' || cs.display === 'none' || r.width < 2 || r.height < 2) continue
          // Un enlace dentro de un párrafo es texto en línea: WCAG 2.5.8 lo exceptúa.
          if (el.tagName === 'A' && el.closest('p')) continue
          if (r.width < min - 0.5 || r.height < min - 0.5) {
            // El área táctil puede ser mayor que la caja visible (pseudoelemento que la
            // amplía: los indicadores del carrusel miden 16 px y su área, 44). Se
            // comprueba como lo hace el dedo: las cuatro esquinas de un área de
            // 44 × 44 px centrada en el control, a 1 px del borde, le tienen que
            // pertenecer.
            el.scrollIntoView({ block: 'center', inline: 'center' })
            const r2 = el.getBoundingClientRect()
            const cx = r2.left + r2.width / 2
            const cy = r2.top + r2.height / 2
            const mitad = min / 2 - 1
            const esquinas = [[-mitad, -mitad], [mitad, -mitad], [-mitad, mitad], [mitad, mitad]]
            const fueraDePantalla = cx - mitad < 0 || cy - mitad < 0 || cx + mitad > window.innerWidth || cy + mitad > window.innerHeight
            if (!fueraDePantalla && esquinas.every(([dx, dy]) => {
              const golpe = document.elementFromPoint(cx + dx, cy + dy)
              return golpe !== null && (golpe === el || el.contains(golpe))
            })) continue
            malos.push(`${el.tagName.toLowerCase()}${el.className ? '.' + String(el.className).trim().split(/\s+/)[0] : ''} «${(el.getAttribute('aria-label') || el.textContent || '').trim().slice(0, 30)}» ${Math.round(r.width)}×${Math.round(r.height)}`)
          }
        }
      }
    }
    return malos
  }, MIN)
  expect(pequenos, 'controles por debajo de 44 × 44 px').toEqual([])
})
