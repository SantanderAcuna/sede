import { describe, expect, it } from 'vitest'
import { enlaceTramite } from '../app/utils/enlace-tramite'

describe('enlaceTramite', () => {
  it('manda a la ficha de GOV.CO cuando el trámite la tiene', () => {
    const e = enlaceTramite({ slug: 'licencia', url_ficha_gov_co: 'https://www.gov.co/ficha-tramites-y-servicios/T17743' })
    expect(e).toEqual({ href: 'https://www.gov.co/ficha-tramites-y-servicios/T17743', externo: true })
  })

  it('acepta subdominios de gov.co', () => {
    expect(enlaceTramite({ slug: 'a', url_ficha_gov_co: 'https://tramites.gov.co/x' }).externo).toBe(true)
  })

  it('sin URL cae a la ficha propia', () => {
    expect(enlaceTramite({ slug: 'consulta-x', url_ficha_gov_co: null })).toEqual({ href: '/tramites/consulta-x', externo: false })
    expect(enlaceTramite({ slug: 'consulta-x' })).toEqual({ href: '/tramites/consulta-x', externo: false })
    expect(enlaceTramite({ slug: 'consulta-x', url_ficha_gov_co: '   ' }).externo).toBe(false)
  })

  it.each([
    'http://www.gov.co/ficha/T1',
    'https://evil.com/gov.co',
    'https://gov.co.evil.com/x',
    'https://notgov.co/x',
    'javascript:alert(1)',
    '//www.gov.co/x',
    'www.gov.co/x',
    'no es una url',
  ])('rechaza %s y cae a la ficha propia', (url) => {
    expect(enlaceTramite({ slug: 's', url_ficha_gov_co: url })).toEqual({ href: '/tramites/s', externo: false })
  })
})
