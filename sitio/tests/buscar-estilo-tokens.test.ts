/**
 * El estado «sin resultados» de /buscar (M01-3, #501) se escribió con un color en crudo
 * (`#1a1a1a`) en un componente, contra la regla de `AGENTS.md` §3: un color no se escribe
 * fuera de `tokens.css`, se consume por `var()`. El stylelint del CI sólo revisa
 * `app/assets/css/*.css`, así que un bloque `<style>` de un componente no lo detecta.
 * Esta prueba fija ese caso; no pretende ser un linter general.
 */
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'

const fuente = readFileSync(resolve(__dirname, '../app/pages/buscar.vue'), 'utf8')

function reglaDe(selector: string): string {
  const inicio = fuente.indexOf(`${selector} {`)
  expect(inicio, `no se encontró la regla ${selector}`).toBeGreaterThan(-1)
  return fuente.slice(inicio, fuente.indexOf('}', inicio) + 1)
}

describe('/buscar · estilo del estado «sin resultados»', () => {
  const regla = reglaDe('.resultado-resumen--vacio')

  it('no escribe ningún color en crudo', () => {
    expect(regla).not.toMatch(/#[0-9a-f]{3,8}\b/i)
    expect(regla).not.toMatch(/\brgba?\(|\bhsla?\(/i)
  })

  it('usa el token de texto de máximo contraste (el del titular)', () => {
    expect(regla).toMatch(/color:\s*var\(--color-texto-fuerte\)/)
  })
})
