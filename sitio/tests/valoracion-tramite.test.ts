/**
 * Valoración de una ficha (RF-B2-033/034, RF-B1-094): la lógica que decide si hay algo que enviar y
 * cómo se arma el cuerpo de la petición. El render y la accesibilidad los cubre la puerta visual y
 * el barrido axe sobre el sitio construido.
 */
import { describe, expect, it } from 'vitest'

import { cuerpoValoracion, hayValoracion } from '../app/utils/valoracion'

describe('valoración de una ficha', () => {
  it('no hay nada que enviar sin experiencia ni estrellas', () => {
    expect(hayValoracion('', 0)).toBe(false)
  })

  it('basta el FÁCIL/DIFÍCIL o al menos una estrella', () => {
    expect(hayValoracion('facil', 0)).toBe(true)
    expect(hayValoracion('', 3)).toBe(true)
  })

  it('arma el cuerpo con lo elegido y pone null —no cero ni vacío— en lo que no', () => {
    expect(cuerpoValoracion('dificil', 2, '  El formulario era confuso.  ')).toEqual({
      valor: 'dificil',
      estrellas: 2,
      comentario: 'El formulario era confuso.',
    })
  })

  it('sólo estrellas: el valor viaja como null', () => {
    expect(cuerpoValoracion('', 5, '')).toEqual({ valor: null, estrellas: 5, comentario: null })
  })

  it('sólo FÁCIL/DIFÍCIL: las estrellas viajan como null', () => {
    expect(cuerpoValoracion('facil', 0, '')).toEqual({ valor: 'facil', estrellas: null, comentario: null })
  })
})
