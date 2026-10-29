/**
 * Las cinco políticas leídas desde la API (T-20, AC-20.2 a AC-20.8).
 *
 * El texto ya no está en el repositorio: vive en base de datos y se edita desde el
 * panel. Aquí se prueba lo que sí es del sitio: que lee la respuesta con tolerancia
 * (una API con un campo roto no debe romper una página pública), que sólo enlaza
 * direcciones propias y que el `sitemap.xml` no anuncia texto de ejemplo (R-03).
 * Que el texto esté completo lo garantiza el servidor (`PoliticasTest`).
 */
import { describe, expect, it } from 'vitest'

import {
  actoEnUnaLinea,
  fechaLarga,
  politicaDesdeApi,
  slugsAdoptados,
  tamanoLegible,
} from '../app/config/politicas-contenido'
import { POLITICAS } from '../app/config/sitemap'

const SLUGS = POLITICAS.map((p) => p.slug)

const respuesta = (extra: Record<string, unknown> = {}): Record<string, unknown> => ({
  slug: 'uso-de-cookies',
  nombre: 'Uso de cookies',
  version: 2,
  vigente_desde: '2026-10-01',
  acto: { tipo: 'Resolución', numero: '123', fecha: '2026-09-20' },
  es_ejemplo: false,
  documento_oficial: null,
  secciones: [{ id: 'finalidad', titulo: 'Finalidad', parrafos: ['Texto.'] }],
  ...extra,
})

describe('politicaDesdeApi', () => {
  it('lee una política completa', () => {
    const p = politicaDesdeApi(respuesta())
    expect(p).toMatchObject({ slug: 'uso-de-cookies', version: 2, esEjemplo: false, vigenteDesde: '2026-10-01' })
    expect(p?.secciones).toEqual([{ id: 'finalidad', titulo: 'Finalidad', parrafos: ['Texto.'] }])
  })

  it('devuelve null si falta lo esencial o la respuesta no es un objeto', () => {
    expect(politicaDesdeApi(null)).toBeNull()
    expect(politicaDesdeApi('texto')).toBeNull()
    expect(politicaDesdeApi(respuesta({ slug: '' }))).toBeNull()
    expect(politicaDesdeApi(respuesta({ nombre: undefined }))).toBeNull()
    expect(politicaDesdeApi(respuesta({ version: 0 }))).toBeNull()
    expect(politicaDesdeApi(respuesta({ version: '2' }))).toBeNull()
  })

  it('descarta secciones y párrafos rotos sin romperse', () => {
    const p = politicaDesdeApi(
      respuesta({
        secciones: [
          null,
          { id: 'a', titulo: '', parrafos: ['x'] },
          { id: 'b', titulo: 'B', parrafos: ['uno', 7, '  ', 'dos'] },
          { id: 'c', titulo: 'C' },
        ],
      }),
    )
    expect(p?.secciones).toEqual([
      { id: 'b', titulo: 'B', parrafos: ['uno', 'dos'] },
      { id: 'c', titulo: 'C', parrafos: [] },
    ])
  })

  it('ante la duda trata el texto como de ejemplo (avisar de más es el error barato)', () => {
    expect(politicaDesdeApi(respuesta({ es_ejemplo: undefined }))?.esEjemplo).toBe(true)
    expect(politicaDesdeApi(respuesta({ es_ejemplo: 'false' }))?.esEjemplo).toBe(true)
    expect(politicaDesdeApi(respuesta({ es_ejemplo: false }))?.esEjemplo).toBe(false)
  })

  it('sólo enlaza el documento oficial si apunta a una dirección propia del sitio', () => {
    const bueno = politicaDesdeApi(
      respuesta({
        documento_oficial: { nombre: 'acto.pdf', tipo: 'application/pdf', tamano: 2048, url: '/politicas/uso-de-cookies/documento' },
      }),
    )
    expect(bueno?.documentoOficial).toEqual({
      nombre: 'acto.pdf',
      tipo: 'application/pdf',
      tamano: 2048,
      url: '/politicas/uso-de-cookies/documento',
    })

    for (const url of ['https://malo.example/x', '//malo.example/x', 'javascript:alert(1)', '/politicas/../x/documento', '/otra/cosa']) {
      expect(politicaDesdeApi(respuesta({ documento_oficial: { nombre: 'x', tipo: 'x', tamano: 1, url } }))?.documentoOficial, url).toBeNull()
    }
  })

  it('un acto con partes nulas no rompe', () => {
    const p = politicaDesdeApi(respuesta({ acto: { tipo: null, numero: null, fecha: null }, vigente_desde: null }))
    expect(p?.acto).toEqual({ tipo: null, numero: null, fecha: null })
    expect(p?.vigenteDesde).toBeNull()
  })
})

describe('presentación', () => {
  it('escribe el acto en una línea', () => {
    expect(actoEnUnaLinea({ tipo: 'Resolución', numero: '123', fecha: '2026-01-10' })).toBe('Resolución 123 del 10 de enero de 2026')
    expect(actoEnUnaLinea({ tipo: 'Decreto', numero: null, fecha: null })).toBe('Decreto')
    expect(actoEnUnaLinea({ tipo: null, numero: null, fecha: '2026-01-10' })).toBeNull()
  })

  it('la fecha larga no se corre un día por la zona horaria', () => {
    expect(fechaLarga('2026-01-01')).toBe('1 de enero de 2026')
    expect(fechaLarga('no-es-fecha')).toBe('no-es-fecha')
  })

  it('el tamaño se lee bien', () => {
    expect(tamanoLegible(500)).toBe('500 B')
    expect(tamanoLegible(2048)).toBe('2 KB')
    expect(tamanoLegible(3 * 1024 * 1024)).toBe('3 MB')
  })
})

describe('slugsAdoptados (lo que anuncia el sitemap.xml)', () => {
  const lista = (...elementos: Record<string, unknown>[]) => ({ data: elementos })

  it('anuncia sólo las que dejaron de ser de ejemplo', () => {
    expect(
      slugsAdoptados(
        lista(
          { slug: 'uso-de-cookies', es_ejemplo: false },
          { slug: 'seguridad-y-privacidad', es_ejemplo: true },
        ),
        SLUGS,
      ),
    ).toEqual(['uso-de-cookies'])
  })

  it('no anuncia nada ante una respuesta rara, vacía o sin el campo', () => {
    expect(slugsAdoptados(null, SLUGS)).toEqual([])
    expect(slugsAdoptados({}, SLUGS)).toEqual([])
    expect(slugsAdoptados({ data: 'x' }, SLUGS)).toEqual([])
    expect(slugsAdoptados(lista({ slug: 'uso-de-cookies' }), SLUGS)).toEqual([])
    expect(slugsAdoptados(lista({ slug: 'uso-de-cookies', es_ejemplo: 'false' }), SLUGS)).toEqual([])
  })

  it('ignora lo que no está en el catálogo cerrado', () => {
    expect(slugsAdoptados(lista({ slug: 'inventada', es_ejemplo: false }), SLUGS)).toEqual([])
  })
})
