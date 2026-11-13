/**
 * Enlace a la ficha oficial de un trámite en GOV.CO. Las normas de la ficha enlazan aquí y no a la
 * descarga de SUIT (`tramites1.suit.gov.co`), que a veces responde 502.
 */
import { describe, expect, it } from 'vitest'

import { urlFichaGovCo } from '../app/utils/url-ficha-govco'

describe('urlFichaGovCo', () => {
  it('usa la URL de la ficha cuando el trámite la trae', () => {
    expect(urlFichaGovCo({ url_ficha_gov_co: 'https://www.gov.co/ficha-tramites-y-servicios/T41040', codigo: 'T41040' }))
      .toBe('https://www.gov.co/ficha-tramites-y-servicios/T41040')
  })

  it('la construye con el código cuando falta la URL', () => {
    expect(urlFichaGovCo({ url_ficha_gov_co: null, codigo: 'T2636' })).toBe('https://www.gov.co/ficha-tramites-y-servicios/T2636')
    expect(urlFichaGovCo({ codigo: 'T41040' })).toBe('https://www.gov.co/ficha-tramites-y-servicios/T41040')
  })

  it('no inventa nada si el código no tiene la forma T + dígitos', () => {
    expect(urlFichaGovCo({ codigo: 'abc' })).toBeNull()
    expect(urlFichaGovCo({ codigo: '' })).toBeNull()
    expect(urlFichaGovCo({ codigo: '../x' })).toBeNull()
    expect(urlFichaGovCo({})).toBeNull()
  })

  it('descarta una URL que no sea https de gov.co y cae al código', () => {
    expect(urlFichaGovCo({ url_ficha_gov_co: 'javascript:alert(1)', codigo: 'T7' })).toBe('https://www.gov.co/ficha-tramites-y-servicios/T7')
    expect(urlFichaGovCo({ url_ficha_gov_co: 'https://malo.example/x', codigo: null })).toBeNull()
  })
})
