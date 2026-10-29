/**
 * Motor de navegador de las pruebas con Playwright (T-29, RNF-B1-033).
 *
 * El requisito pide ≥ 3 navegadores sin diferencias funcionales (Chrome, Firefox,
 * Safari, Edge). Playwright los representa con sus propios motores:
 *
 *   NAVEGADOR=chromium   Chrome (el motor de Chrome, no el instalado en la máquina)
 *   NAVEGADOR=firefox    Firefox
 *   NAVEGADOR=webkit     Safari (el motor WebKit; NO es Safari de Apple: sin las
 *                        particularidades de iOS ni de macOS)
 *   NAVEGADOR=msedge     Edge, con el canal `msedge` (requiere Edge instalado)
 *
 * Sin la variable, `chromium`: el comportamiento de siempre.
 *
 * `PLAYWRIGHT_MODULE=playwright` usa otra instalación de Playwright (por ejemplo,
 * la global); por defecto, la del proyecto.
 */
import { createRequire } from 'node:module'

export const MOTORES = ['chromium', 'firefox', 'webkit', 'msedge']
export const MOTOR = process.env.NAVEGADOR ?? 'chromium'

if (!MOTORES.includes(MOTOR)) {
  console.error(`NAVEGADOR=${MOTOR} no es válido; use uno de: ${MOTORES.join(', ')}.`)
  process.exit(2)
}

/**
 * Firefox no admite `isMobile` (Playwright lo rechaza): ahí la «emulación móvil»
 * es ancho estrecho + táctil, sin el comportamiento de ventana de diseño de los
 * navegadores móviles. Es una diferencia real del motor, no de la prueba.
 */
export const ADMITE_IS_MOBILE = MOTOR !== 'firefox'

/** Opciones de contexto para emular un teléfono en el motor en uso. */
export const opcionesMovil = (movil = true) => ({
  isMobile: movil && ADMITE_IS_MOBILE,
  hasTouch: movil,
})

export async function cargarPlaywright() {
  return process.env.PLAYWRIGHT_MODULE
    ? createRequire(import.meta.url)(process.env.PLAYWRIGHT_MODULE)
    : await import('@playwright/test')
}

/** Lanza el motor elegido. */
export async function lanzar() {
  const pw = await cargarPlaywright()
  console.log(`Navegador: ${MOTOR}`)
  return MOTOR === 'msedge' ? pw.chromium.launch({ channel: 'msedge' }) : pw[MOTOR].launch()
}
