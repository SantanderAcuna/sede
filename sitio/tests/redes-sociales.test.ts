import { describe, expect, it } from 'vitest'

import { REDES_RESPALDO, redesDesdeApi } from '../app/config/redes-sociales'

describe('redes sociales del pie (AC-21.4)', () => {
  it('el respaldo trae las cuatro redes', () => {
    expect(REDES_RESPALDO.map((r) => r.icono)).toEqual([
      'govco-facebook-f',
      'govco-instagram',
      'govco-twitter',
      'govco-youtube',
    ])
  })

  it('traduce lo que entrega la API y muestra el usuario sin «@» ni codificación', () => {
    const redes = redesDesdeApi([
      { red: 'youtube', url: 'https://www.youtube.com/@Alcald%C3%ADadeSantaMartaDTCH' },
      { red: 'facebook', url: 'https://www.facebook.com/SantaMartaDTCH' },
    ])
    expect(redes.map((r) => r.usuario)).toEqual(['AlcaldíadeSantaMartaDTCH', 'SantaMartaDTCH'])
    expect(redes[0]!.red).toBe('YouTube')
  })

  it('descarta redes desconocidas, URL que no son https y URL inválidas', () => {
    const redes = redesDesdeApi([
      { red: 'myspace', url: 'https://myspace.com/x' },
      { red: 'facebook', url: 'http://www.facebook.com/x' },
      { red: 'instagram', url: 'javascript:alert(1)' },
      { red: 'x', url: 'https://x.com/ok con espacio' },
      { red: 'youtube', url: 'no es una url' },
    ])
    // «ok con espacio» lo normaliza `URL` a %20 y es válido; el resto se descarta.
    expect(redes.map((r) => r.icono)).toEqual(['govco-twitter'])
  })

  it('no repite una red: gana la primera', () => {
    const redes = redesDesdeApi([
      { red: 'facebook', url: 'https://www.facebook.com/primera' },
      { red: 'Facebook', url: 'https://www.facebook.com/segunda' },
    ])
    expect(redes).toHaveLength(1)
    expect(redes[0]!.usuario).toBe('primera')
  })

  it('con null o vacío no falla', () => {
    expect(redesDesdeApi(null)).toEqual([])
    expect(redesDesdeApi(undefined)).toEqual([])
    expect(redesDesdeApi([])).toEqual([])
  })
})
