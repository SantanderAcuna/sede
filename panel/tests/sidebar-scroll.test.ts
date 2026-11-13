/**
 * El menú lateral no se arrastra con la página: en escritorio queda fijo a la altura de la
 * pantalla y su lista de módulos se desplaza por dentro, con «Colapsar» siempre a la vista.
 */
import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'

const fuente = readFileSync('src/layouts/AdminLayout.vue', 'utf8')
const aside = fuente.match(/<aside[\s\S]*?aria-label="Navegación principal"/)?.[0] ?? ''

describe('AdminLayout — el menú lateral tiene su propio scroll', () => {
  it('en escritorio es sticky, de la altura de la pantalla y pegado arriba', () => {
    expect(aside).toContain('lg:sticky')
    expect(aside).toContain('lg:top-0')
    expect(aside).toContain('lg:h-screen')
    expect(aside).toContain('lg:self-start')
    expect(aside).not.toContain('lg:static')
  })

  it('la lista de módulos se desplaza por dentro y puede encogerse (min-h-0)', () => {
    const nav = fuente.match(/<nav class="([^"]*)">\s*<!--\s*Sin nada/)?.[1] ?? ''
    expect(nav).toContain('overflow-y-auto')
    expect(nav).toContain('min-h-0')
  })
})
