/**
 * `sinMarcaHeadless` (tests/ayudas/agente-usuario.mjs).
 *
 * Los scripts de `tests/*.mjs` abren staging con el agente de usuario por defecto
 * de Playwright, que en Chromium/Edge dice `HeadlessChrome/…`. Medido en el
 * despliegue 42959455809: con esa marca, Cloudflare sirve «Just a moment…» en
 * lugar del sitio (los specs de Playwright, que usan el agente de `devices[...]`,
 * no la reciben). La función quita **sólo** la marca `Headless`, conserva la
 * versión real del navegador y no toca a Firefox ni WebKit.
 */
import { describe, expect, it } from 'vitest'
import { sinMarcaHeadless } from './agente-usuario.mjs'

const CHROMIUM_HEADLESS =
  'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/158.0.8127.18 Safari/537.36'
const EDGE_HEADLESS =
  'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0'
const FIREFOX =
  'Mozilla/5.0 (X11; Linux x86_64; rv:150.0) Gecko/20100101 Firefox/150.0'
const WEBKIT =
  'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Safari/605.1.15'

describe('sinMarcaHeadless', () => {
  it('Chromium: «HeadlessChrome/158…» pasa a «Chrome/158…» con la misma versión', () => {
    expect(sinMarcaHeadless(CHROMIUM_HEADLESS)).toBe(
      'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/158.0.8127.18 Safari/537.36',
    )
  })

  it('Edge: conserva el sufijo «Edg/…» y la versión instalada', () => {
    const ua = sinMarcaHeadless(EDGE_HEADLESS)
    expect(ua).toContain('Chrome/157.0.0.0')
    expect(ua).toContain('Edg/157.0.0.0')
    expect(ua).not.toMatch(/headless/i)
  })

  it('no toca a Firefox ni a WebKit', () => {
    expect(sinMarcaHeadless(FIREFOX)).toBe(FIREFOX)
    expect(sinMarcaHeadless(WEBKIT)).toBe(WEBKIT)
  })

  it('es idempotente y no altera un Chrome normal', () => {
    const una = sinMarcaHeadless(CHROMIUM_HEADLESS)
    expect(sinMarcaHeadless(una)).toBe(una)
  })

  it('cambia únicamente la marca: nada más del agente se modifica', () => {
    const antes = CHROMIUM_HEADLESS.split(' ')
    const despues = sinMarcaHeadless(CHROMIUM_HEADLESS).split(' ')
    expect(despues).toHaveLength(antes.length)
    const distintos = antes.filter((parte, i) => parte !== despues[i])
    expect(distintos).toEqual(['HeadlessChrome/158.0.8127.18'])
  })
})
