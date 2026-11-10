/**
 * `sitemap.xml`: construcción del XML y lectura de las fichas de trámite (M01-1, RF-01-010).
 *
 * Criterios de aceptación que fijan estas pruebas:
 *
 *  - AC-1.1  Todo `<url>` es absoluto, único y sin escapes rotos.
 *  - AC-1.2  Cada trámite publicado por la API aparece como `/tramites/{slug}`, sea cual sea el
 *            número de páginas de la API (el catálogo pagina de 100 en 100).
 *  - AC-1.3  `<lastmod>` sólo aparece cuando hay una fecha real de modificación, en formato
 *            W3C (`AAAA-MM-DD`). Una página sin fecha NO lleva `lastmod`: inventarlo (la fecha
 *            del despliegue, por ejemplo) hace que el buscador deje de fiarse de todos.
 *  - AC-1.4  Un trámite sin `slug` o con `slug` fuera del patrón se descarta, no rompe el mapa.
 *  - AC-1.5  Si la API falla a mitad de la lectura, el mapa sale con lo que sí se pudo leer de
 *            las rutas fijas; nunca un 500.
 *  - AC-1.6  El mapa no supera las 50 000 URL del protocolo.
 *  - AC-1.8  La política adoptada lleva como `lastmod` su `actualizada_en`.
 *  - AC-1.7  La lectura se cachea (la API devuelve fichas completas; no se pide en cada visita
 *            de un rastreador) y la caché caduca.
 */
import { describe, expect, it, vi } from 'vitest'

import {
  MAXIMO_URL,
  aFechaW3C,
  construirSitemapXml,
  entradasDeTramites,
  fechasDePoliticas,
  leerTramites,
  tramitesConCache,
} from '../server/utils/sitemap-xml'

const BASE = 'https://www.santamarta.gov.co'

describe('aFechaW3C (AC-1.3)', () => {
  it.each([
    ['2026-11-10T19:21:46+00:00', '2026-11-10'],
    ['2026-11-10', '2026-11-10'],
    ['2026-01-05T23:59:59-05:00', '2026-01-05'],
  ])('acepta %s', (entrada, esperado) => {
    expect(aFechaW3C(entrada)).toBe(esperado)
  })

  it.each([[null], [undefined], [''], ['ayer'], ['2026-13-40'], [20261110], [{}]])(
    'descarta %j',
    (entrada) => {
      expect(aFechaW3C(entrada)).toBeUndefined()
    },
  )
})

describe('construirSitemapXml (AC-1.1, AC-1.3, AC-1.6)', () => {
  it('declara el espacio de nombres del protocolo y escapa los caracteres de XML', () => {
    const xml = construirSitemapXml(BASE, [{ ruta: '/a&b', frecuencia: 'weekly', prioridad: 0.5 }])
    expect(xml.startsWith('<?xml version="1.0" encoding="UTF-8"?>')).toBe(true)
    expect(xml).toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">')
    expect(xml).toContain('<loc>https://www.santamarta.gov.co/a&amp;b</loc>')
  })

  it('escribe lastmod sólo cuando la entrada trae fecha', () => {
    const xml = construirSitemapXml(BASE, [
      { ruta: '/con', frecuencia: 'weekly', prioridad: 0.5, modificado: '2026-11-10' },
      { ruta: '/sin', frecuencia: 'weekly', prioridad: 0.5 },
    ])
    expect(xml).toContain('<lastmod>2026-11-10</lastmod>')
    expect(xml.match(/<lastmod>/g)).toHaveLength(1)
  })

  it('no repite URL: gana la primera aparición', () => {
    const xml = construirSitemapXml(BASE, [
      { ruta: '/x', frecuencia: 'weekly', prioridad: 0.9 },
      { ruta: '/x', frecuencia: 'daily', prioridad: 0.1 },
    ])
    expect(xml.match(/<loc>/g)).toHaveLength(1)
    expect(xml).toContain('<priority>0.9</priority>')
  })

  it('no supera el máximo del protocolo', () => {
    const muchas = Array.from({ length: MAXIMO_URL + 10 }, (_, i) => ({
      ruta: `/t/${i}`,
      frecuencia: 'weekly' as const,
      prioridad: 0.5,
    }))
    const xml = construirSitemapXml(BASE, muchas)
    expect(xml.match(/<loc>/g)).toHaveLength(MAXIMO_URL)
  })

  it('con cero entradas sigue siendo un XML válido', () => {
    const xml = construirSitemapXml(BASE, [])
    expect(xml).toContain('<urlset')
    expect(xml).toContain('</urlset>')
  })
})

describe('entradasDeTramites (AC-1.2, AC-1.3, AC-1.4)', () => {
  const pagina = {
    data: [
      { slug: 'licencia-de-transito', actualizado_en: '2026-11-10T19:21:46+00:00' },
      { slug: 'sin-fecha' },
      { slug: 'Mal Slug' },
      { slug: '' },
      { nombre: 'sin slug' },
      null,
      'texto',
    ],
  }

  it('crea una entrada por trámite válido, con la fecha real de modificación', () => {
    const entradas = entradasDeTramites([pagina])
    expect(entradas.map((e) => e.ruta)).toEqual(['/tramites/licencia-de-transito', '/tramites/sin-fecha'])
    expect(entradas[0]?.modificado).toBe('2026-11-10')
    expect(entradas[1]?.modificado).toBeUndefined()
  })

  it('tolera respuestas sin forma de lista', () => {
    expect(entradasDeTramites([null, undefined, 5, {}, { data: 'x' }])).toEqual([])
  })
})

describe('leerTramites (AC-1.2, AC-1.5)', () => {
  const paginaDe = (desde: number, hasta: number, ultima: number, actual: number) => ({
    data: Array.from({ length: hasta - desde + 1 }, (_, i) => ({ slug: `t-${desde + i}` })),
    meta: { current_page: actual, last_page: ultima },
  })

  it('recorre todas las páginas que anuncia la API', async () => {
    const pedir = vi.fn(async (n: number) =>
      n === 1 ? paginaDe(1, 100, 2, 1) : paginaDe(101, 123, 2, 2),
    )
    const entradas = await leerTramites(pedir)
    expect(entradas).toHaveLength(123)
    expect(pedir).toHaveBeenCalledTimes(2)
    expect(entradas[122]?.ruta).toBe('/tramites/t-123')
  })

  it('si una página falla, conserva las anteriores y no lanza', async () => {
    const pedir = vi.fn(async (n: number) => {
      if (n === 2) throw new Error('timeout')
      return paginaDe(1, 100, 3, 1)
    })
    const entradas = await leerTramites(pedir)
    expect(entradas).toHaveLength(100)
  })

  it('si la primera página falla, devuelve una lista vacía', async () => {
    const entradas = await leerTramites(async () => {
      throw new Error('API caída')
    })
    expect(entradas).toEqual([])
  })

  it('no entra en bucle si la API anuncia páginas de más', async () => {
    const pedir = vi.fn(async () => paginaDe(1, 1, 1_000_000, 1))
    await leerTramites(pedir)
    expect(pedir.mock.calls.length).toBeLessThanOrEqual(60)
  })
})

describe('tramitesConCache (AC-1.7)', () => {
  it('reutiliza la lectura dentro del plazo y la repite después', async () => {
    let ahora = 1_000
    const leer = vi.fn(async () => [{ ruta: '/tramites/a', frecuencia: 'weekly' as const, prioridad: 0.6 }])
    const cache = tramitesConCache(leer, { vigenciaMs: 600_000, reloj: () => ahora })

    await cache()
    await cache()
    expect(leer).toHaveBeenCalledTimes(1)

    ahora += 600_001
    await cache()
    expect(leer).toHaveBeenCalledTimes(2)
  })

  it('no guarda en caché una lectura vacía (un fallo no debe durar el plazo entero)', async () => {
    const leer = vi.fn(async () => [])
    const cache = tramitesConCache(leer, { vigenciaMs: 600_000, reloj: () => 1 })
    await cache()
    await cache()
    expect(leer).toHaveBeenCalledTimes(2)
  })

  it('dos visitas simultáneas comparten una sola lectura', async () => {
    const leer = vi.fn(async () => [{ ruta: '/tramites/a', frecuencia: 'weekly' as const, prioridad: 0.6 }])
    const cache = tramitesConCache(leer, { vigenciaMs: 600_000, reloj: () => 1 })
    await Promise.all([cache(), cache(), cache()])
    expect(leer).toHaveBeenCalledTimes(1)
  })
})

describe('fechasDePoliticas (AC-1.8)', () => {
  it('asocia cada slug con su fecha de actualización', () => {
    const crudo = {
      data: [
        { slug: 'uso-de-cookies', actualizada_en: '2026-10-29' },
        { slug: 'sin-fecha' },
        { slug: 'fecha-rota', actualizada_en: 'ayer' },
        null,
      ],
    }
    expect(fechasDePoliticas(crudo)).toEqual(new Map([['uso-de-cookies', '2026-10-29']]))
  })

  it('tolera una respuesta sin forma de lista', () => {
    expect(fechasDePoliticas(null).size).toBe(0)
    expect(fechasDePoliticas({ data: 'x' }).size).toBe(0)
  })
})
