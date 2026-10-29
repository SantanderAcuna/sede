/**
 * Estados que sólo existen en CSS (RF-B3-066 y el «hover» del acordeón).
 *
 * jsdom no aplica hojas de estilo, así que «el botón tiene estado hover» no se
 * puede medir con el DOM. Lo que sí se puede —y es lo que evita que alguien
 * borre una regla sin que nada se entere— es comprobar que **la regla existe en
 * la hoja que se sirve**. Que esa regla se vea bien lo comprueba el navegador
 * real (T-29); aquí se declara con honestidad el alcance: presencia de la regla,
 * no su aspecto.
 */
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

import { describe, expect, it } from 'vitest'

const kit = readFileSync(resolve(__dirname, '../public/govco/all.css'), 'utf8')
const sitio = readFileSync(resolve(__dirname, '../app/assets/css/sitio.css'), 'utf8')

/** Los selectores de todas las reglas de una hoja, ya separados por coma. */
function selectores(css: string): string[] {
  const limpio = css.replace(/\/\*[\s\S]*?\*\//g, '')
  return [...limpio.matchAll(/([^{}]+)\{/g)].flatMap((m) =>
    (m[1] ?? '').split(',').map((s) => s.trim()),
  )
}

const selectoresKit = selectores(kit)

function tieneEstado(clase: string, estado: RegExp): boolean {
  return selectoresKit.some((s) => s.includes(`.${clase}`) && estado.test(s))
}

describe('botones: 4 variantes × 4 estados (RF-B3-066)', () => {
  const variantes = [
    'fill-btn-govco',
    'outline-btn-govco',
    'fill-secundary-btn-govco',
    'outline-secundary-btn-govco',
  ]
  const estados: Array<[string, RegExp]> = [
    ['hover', /:hover/],
    ['foco', /:focus/],
    ['deshabilitado', /:disabled|\.disabled|\[disabled\]/],
  ]

  for (const variante of variantes) {
    it(`${variante} define normal, hover, foco y deshabilitado`, () => {
      expect(selectoresKit.some((s) => s.includes(`.${variante}`))).toBe(true)
      for (const [nombre, patron] of estados) {
        expect(tieneEstado(variante, patron), `${variante} sin estado ${nombre}`).toBe(true)
      }
    })
  }

  it('el estado normal de cada variante existe (la regla base)', () => {
    for (const variante of variantes) {
      expect(
        selectoresKit.some((s) => s.includes(`.${variante}`) && !s.includes(':')),
        `${variante} sin regla base`,
      ).toBe(true)
    }
  })
})

describe('acordeón (RF-B3-062)', () => {
  it('foco y deshabilitado vienen del Kit', () => {
    expect(selectoresKit).toContain('.button-accordion-govco:focus-visible')
    expect(selectoresKit.some((s) => /\.button-accordion-govco:disabled$/.test(s))).toBe(true)
  })

  it('el hover, que el Kit no define, lo añade sitio.css', () => {
    expect(selectoresKit.some((s) => s.includes('button-accordion-govco') && s.includes(':hover'))).toBe(false)
    expect(selectores(sitio)).toContain('.acordeon-govco .button-accordion-govco:hover:not(:disabled)')
  })
})

describe('toast (RF-B3-064): las tres variantes existen en el Kit', () => {
  for (const variante of ['info', 'success', 'error']) {
    it(`.container-toast-govco.${variante} y su cabecera`, () => {
      expect(selectoresKit).toContain(`.container-toast-govco.${variante}`)
      expect(selectoresKit).toContain(`.container-toast-govco .toast-header-${variante}-govco`)
    })
  }
})

describe('iconos que usan los componentes nuevos', () => {
  it.each(['govco-spinner', 'govco-sort', 'govco-sort-up', 'govco-sort-down', 'govco-times'])(
    '%s existe en el Kit',
    (icono) => {
      expect(selectoresKit.some((s) => s === `.govco-svg.${icono}`)).toBe(true)
    },
  )
})
