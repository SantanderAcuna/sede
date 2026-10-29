/**
 * Sanidad de la ayuda de axe: una ayuda que siempre devuelve «sin violaciones»
 * haría pasar cualquier componente. Se comprueba que SÍ detecta un botón sin
 * nombre y una imagen sin texto alternativo.
 */
import { describe, expect, it } from 'vitest'

import { violacionesAxe } from './axe'

describe('la ayuda de axe detecta fallos reales', () => {
  it('botón sin nombre accesible', async () => {
    document.body.innerHTML = '<div id="x"><button type="button"></button></div>'
    const v = await violacionesAxe(document.getElementById('x') as Element)
    expect(v.some((m) => m.startsWith('button-name'))).toBe(true)
  })

  it('imagen sin alternativa', async () => {
    document.body.innerHTML = '<div id="x"><img src="a.png"></div>'
    const v = await violacionesAxe(document.getElementById('x') as Element)
    expect(v.some((m) => m.startsWith('image-alt'))).toBe(true)
  })

  it('marcado correcto: sin violaciones', async () => {
    document.body.innerHTML = '<div id="x"><button type="button">Aceptar</button></div>'
    expect(await violacionesAxe(document.getElementById('x') as Element)).toEqual([])
  })
})
