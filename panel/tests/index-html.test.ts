/**
 * El `index.html` del panel: los recursos de la cabecera (íconos y manifiesto) deben
 * resolver igual desde cualquier ruta del panel.
 *
 * Con rutas relativas (`./site.webmanifest`), en `/admin/contenidos/noticias` se pedía
 * `/admin/contenidos/site.webmanifest`, que el panel contesta con HTML: el navegador
 * avisaba «Manifest: Line: 1, column: 1, Syntax error» y los íconos no cargaban.
 * `%BASE_URL%` lo reemplaza Vite por la base (`/admin/`) y no depende de la ruta.
 */
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

import { describe, expect, it } from 'vitest'

const html = readFileSync(resolve(__dirname, '../index.html'), 'utf8')
const enlaces = [...html.matchAll(/<link\b[^>]*\bhref="([^"]+)"[^>]*>/g)].map((m) => m[1] as string)

describe('index.html del panel', () => {
  it('declara el manifiesto y los íconos', () => {
    expect(enlaces.some((h) => h.endsWith('site.webmanifest'))).toBe(true)
    expect(enlaces.filter((h) => /\.(ico|png)$/.test(h)).length).toBeGreaterThanOrEqual(3)
  })

  it('ninguno de esos recursos usa una ruta relativa al documento', () => {
    const relativos = enlaces.filter((h) => /\.(ico|png|webmanifest)$/.test(h) && !h.startsWith('%BASE_URL%'))
    expect(relativos).toEqual([])
  })
})
