/**
 * El botón flotante de accesibilidad (borde derecho, 44 px) no debe tapar contenido:
 * el área principal reserva un margen derecho igual a su ancho más aire.
 */
import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'

describe('AdminLayout — margen para el botón de accesibilidad', () => {
  const fuente = readFileSync('src/layouts/AdminLayout.vue', 'utf8')

  it('el <main> reserva un margen derecho de al menos 56 px (pr-14)', () => {
    const main = fuente.match(/<main[^>]*class="([^"]+)"/)?.[1] ?? ''
    expect(main).toMatch(/\bpr-14\b/)
  })
})
