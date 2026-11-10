/**
 * `robots.txt` por entorno (M01-1b, RF-01-010 «declarado en robots.txt»).
 *
 * El comportamiento existía en `server/routes/robots.txt.ts` pero **no tenía ninguna prueba**:
 * quien tocara la ruta podía romper la declaración del mapa del sitio sin que nada fallara.
 * Estas pruebas fijan los criterios; `tests/robots.mjs` los comprueba después contra una
 * instancia real.
 *
 *  - AC-1.R1  Un entorno NO indexable cierra el rastreo (`Disallow: /`) y NO declara el mapa:
 *             declararlo junto a un `Disallow: /` es contradictorio.
 *  - AC-1.R2  Un entorno indexable permite el rastreo (`Disallow:` vacío) y declara el mapa
 *             **una sola vez**, con URL absoluta `https://{dominio}/sitemap.xml`.
 *  - AC-1.R3  El dominio se normaliza: sin protocolo se asume `https://`; una barra final no
 *             produce `//sitemap.xml`; el host del mapa es siempre el del dominio configurado.
 *  - AC-1.R4  Con un dominio vacío no se escribe una línea `Sitemap:` mal formada
 *             (`https:///sitemap.xml`): una declaración inválida es peor que ninguna.
 */
import { describe, expect, it } from 'vitest'

import { construirRobots } from '../server/utils/robots-txt'

const lineas = (texto: string): string[] => texto.split('\n')
const sitemaps = (texto: string): string[] => lineas(texto).filter((l) => /^sitemap:/i.test(l))

describe('construirRobots — entorno no indexable (AC-1.R1)', () => {
  const texto = construirRobots({ dominio: 'staging.santamarta.gov.co', indexable: false })

  it('cierra el rastreo entero', () => {
    expect(lineas(texto)).toContain('User-Agent: *')
    expect(lineas(texto)).toContain('Disallow: /')
  })

  it('no declara el mapa del sitio ni permite nada', () => {
    expect(sitemaps(texto)).toEqual([])
    expect(lineas(texto).some((l) => /^allow:/i.test(l))).toBe(false)
  })
})

describe('construirRobots — entorno indexable (AC-1.R2)', () => {
  const texto = construirRobots({ dominio: 'www.santamarta.gov.co', indexable: true })

  it('permite el rastreo completo', () => {
    expect(lineas(texto)).toContain('User-Agent: *')
    expect(lineas(texto)).toContain('Disallow:')
    expect(lineas(texto)).not.toContain('Disallow: /')
  })

  it('declara el mapa del sitio una sola vez, en absoluto', () => {
    expect(sitemaps(texto)).toEqual(['Sitemap: https://www.santamarta.gov.co/sitemap.xml'])
  })
})

describe('construirRobots — normalización del dominio (AC-1.R3, AC-1.R4)', () => {
  it.each([
    ['www.santamarta.gov.co', 'https://www.santamarta.gov.co/sitemap.xml'],
    ['www.santamarta.gov.co/', 'https://www.santamarta.gov.co/sitemap.xml'],
    ['https://www.santamarta.gov.co', 'https://www.santamarta.gov.co/sitemap.xml'],
    ['https://www.santamarta.gov.co/', 'https://www.santamarta.gov.co/sitemap.xml'],
    ['http://localhost:3000', 'http://localhost:3000/sitemap.xml'],
  ])('dominio %s → %s', (dominio, esperado) => {
    const texto = construirRobots({ dominio, indexable: true })
    expect(sitemaps(texto)).toEqual([`Sitemap: ${esperado}`])
  })

  it.each([[''], ['   '], [undefined], [null]])('con dominio %j no escribe un Sitemap inválido', (dominio) => {
    const texto = construirRobots({ dominio, indexable: true })
    expect(sitemaps(texto)).toEqual([])
    expect(texto).not.toContain('https:///')
  })
})
