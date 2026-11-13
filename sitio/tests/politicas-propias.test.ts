/**
 * Políticas propias de la Entidad en el sitio público.
 *
 * Además de las cinco obligatorias (catálogo cerrado, `POLITICAS`), la API publica las que la Entidad
 * agregó y adoptó. El sitio las lee de `GET /politicas` y las suma al pie, al índice, al mapa del sitio y
 * al `sitemap.xml`. Una respuesta rara no rompe ni inventa nada.
 */
import { describe, expect, it } from 'vitest'

import { politicasPropiasDesdeApi } from '../app/config/politicas-contenido'
import { POLITICAS } from '../app/config/sitemap'
import { fichaDeRuta, tituloDeFicha } from '../app/utils/miga'

const CONOCIDAS = POLITICAS.map((p) => p.slug)
const respuesta = (data: unknown) => ({ success: true, data })

describe('politicasPropiasDesdeApi', () => {
  it('devuelve solo las que no son de las cinco obligatorias, con su nombre', () => {
    const lista = politicasPropiasDesdeApi(
      respuesta([
        { slug: 'uso-de-cookies', nombre: 'Uso de cookies' },
        { slug: 'politica-de-accesibilidad', nombre: 'Política de accesibilidad' },
      ]),
      CONOCIDAS,
    )
    expect(lista).toEqual([{ slug: 'politica-de-accesibilidad', nombre: 'Política de accesibilidad' }])
  })

  it('descarta lo que no tiene slug válido o nombre', () => {
    const lista = politicasPropiasDesdeApi(
      respuesta([
        { slug: 'Mal Slug', nombre: 'X' },
        { slug: 'sin-nombre', nombre: '  ' },
        { slug: '../raro', nombre: 'Y' },
        { slug: 'buena', nombre: 'Buena' },
        null,
        'texto',
      ]),
      CONOCIDAS,
    )
    expect(lista).toEqual([{ slug: 'buena', nombre: 'Buena' }])
  })

  it.each([[null], [undefined], [{}], [{ data: 'x' }], [[]], ['x']])('ante %j no anuncia nada', (crudo) => {
    expect(politicasPropiasDesdeApi(crudo, CONOCIDAS)).toEqual([])
  })

  it('no repite un slug', () => {
    const lista = politicasPropiasDesdeApi(
      respuesta([{ slug: 'a', nombre: 'A' }, { slug: 'a', nombre: 'A otra vez' }]),
      CONOCIDAS,
    )
    expect(lista).toHaveLength(1)
  })

  it('lee también la forma del pie (lista de enlaces dentro de data.politicas)', () => {
    const lista = politicasPropiasDesdeApi(respuesta({ politicas: [{ slug: 'b', nombre: 'B' }] }), CONOCIDAS)
    expect(lista).toEqual([{ slug: 'b', nombre: 'B' }])
  })
})

describe('la miga de una política propia', () => {
  it('reconoce /politicas/{slug} como ficha y lee su nombre', () => {
    expect(fichaDeRuta('/politicas/mi-politica')).toEqual({
      seccion: 'politicas', slug: 'mi-politica', clave: 'politica-mi-politica', endpoint: '/politicas/mi-politica',
    })
    expect(tituloDeFicha('politicas', { data: { nombre: 'Política de accesibilidad' } })).toBe('Política de accesibilidad')
  })

  it('el índice /politicas sigue sin ser una ficha', () => {
    expect(fichaDeRuta('/politicas')).toBeNull()
  })
})
