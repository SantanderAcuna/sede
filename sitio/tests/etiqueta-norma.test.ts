import { describe, expect, it } from 'vitest'

import { etiquetaNorma, textoNorma } from '../app/utils/etiqueta-norma'

describe('etiquetaNorma', () => {
  it('une tipo, número y año con espacios', () => {
    expect(etiquetaNorma({ tipo: 'Decreto', numero: '2715', anio: 2009 })).toBe('Decreto 2715 de 2009')
  })
  it('omite lo que falta', () => {
    expect(etiquetaNorma({ tipo: 'Ley', numero: null, anio: 1581 })).toBe('Ley de 1581')
    expect(etiquetaNorma({ tipo: 'Resolución', numero: '10', anio: null })).toBe('Resolución 10')
    expect(etiquetaNorma({ tipo: 'Circular' })).toBe('Circular')
  })
})

describe('textoNorma', () => {
  it('añade los artículos tras un guion largo', () => {
    expect(textoNorma({ tipo: 'Decreto', numero: '0240', anio: 2012, articulos: 'Todos' })).toBe('Decreto 0240 de 2012 — Todos')
    expect(textoNorma({ tipo: 'Decreto', numero: '1', anio: 2002, articulos: '' })).toBe('Decreto 1 de 2002')
  })
})
