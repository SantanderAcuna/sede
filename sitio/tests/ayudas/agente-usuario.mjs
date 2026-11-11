/**
 * Agente de usuario de los scripts de navegador (`tests/*.mjs`).
 *
 * Chromium y Edge de Playwright se anuncian como `HeadlessChrome/…`. Medido en el
 * despliegue 42959455809 (GitHub Actions → staging, detrás de Cloudflare): con
 * esa marca el sitio responde «Just a moment…» (verificación de seguridad) en
 * lugar de la página; con el agente de `devices['Desktop Chrome']` —el que usan
 * los specs de `playwright.config.mjs`— la misma ejecución pasa. Firefox y WebKit
 * no llevan la marca y pasan.
 *
 * Aquí se quita **sólo** la marca `Headless`: se conserva la versión real del
 * navegador instalado (no se inventa otra) y el resto del agente no cambia.
 * Es la misma identidad que ya usan los specs; el script deja de ser distinguible
 * de ellos por ese rasgo. No se toca ninguna otra señal del navegador.
 */

/** Quita la marca `Headless` del agente de usuario. Idempotente. */
export function sinMarcaHeadless(agente) {
  return agente.replace(/\bHeadlessChrome\//, 'Chrome/')
}

/**
 * Opciones de contexto con el agente de usuario del navegador lanzado sin la
 * marca `Headless`. Devuelve `{}` si el navegador no la llevaba (Firefox, WebKit).
 */
export async function opcionesAgente(navegador) {
  const contexto = await navegador.newContext()
  try {
    const pagina = await contexto.newPage()
    const agente = await pagina.evaluate(() => navigator.userAgent)
    const limpio = sinMarcaHeadless(agente)
    return limpio === agente ? {} : { userAgent: limpio }
  } finally {
    await contexto.close()
  }
}
