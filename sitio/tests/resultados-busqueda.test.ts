/**
 * Lógica de la página de resultados (T-22, E3-3 · `docs/busqueda-interaccion.md` §5).
 *
 * La página en sí —foco, anuncios, enlaces sin JavaScript— se comprueba en
 * navegador (E3-4); aquí, lo que se puede decidir sin DOM.
 */
import { describe, expect, it } from 'vitest'

import {
  analizarResultados,
  estadoDelTermino,
  etiquetaDeFecha,
  EXTRACTO_MAXIMO,
  leerFiltro,
  leerPagina,
  limpiarTermino,
  recortarExtracto,
  RESULTADOS_POR_PAGINA,
  textoResumen,
} from '../app/composables/resultadosBusqueda'

function item(id: number, extra: Record<string, unknown> = {}) {
  return {
    id,
    type: 'resultados-busqueda',
    titulo: `Trámite ${id}`,
    categoria: 'Trámite',
    tipo_recurso: 'tramite',
    extracto: 'Autorización para construir, ampliar o modificar una edificación.',
    fecha: '2026-03-14',
    autor: null,
    miniatura: null,
    url: `/tramites/tramite-${id}`,
    score: 0.9,
    ...extra,
  }
}

function cuerpo(data: unknown[], meta: Record<string, unknown> = {}) {
  return { success: true, data, meta: { total: data.length, last_page: 1, ...meta } }
}

describe('el término de la URL', () => {
  it('colapsa espacios y recorta, y no corta en 120 para poder avisar', () => {
    expect(limpiarTermino('  licencia   de\tconstrucción ')).toBe('licencia de construcción')
    expect(limpiarTermino('a'.repeat(300))).toHaveLength(300)
  })

  it.each([undefined, null, 5, {}, []])('un valor que no es texto (%j) es «sin término»', (valor) => {
    expect(limpiarTermino(valor)).toBe('')
  })

  it('con varios `q` en la URL toma el primero', () => {
    expect(limpiarTermino(['salud', 'otra'])).toBe('salud')
  })

  it('estados: sin término, corta, larga y válida (3 a 120)', () => {
    expect(estadoDelTermino('')).toBe('sin-termino')
    expect(estadoDelTermino('ab')).toBe('corta')
    expect(estadoDelTermino('abc')).toBe('valida')
    expect(estadoDelTermino('a'.repeat(120))).toBe('valida')
    expect(estadoDelTermino('a'.repeat(121))).toBe('larga')
  })

  it('el filtro admite sólo «tramite» y «politica»; lo demás es «Todo»', () => {
    expect(leerFiltro('tramite')).toBe('tramite')
    expect(leerFiltro('politica')).toBe('politica')
    expect(leerFiltro(['politica', 'tramite'])).toBe('politica')
    for (const raro of ['', 'otro', 'TRAMITE', undefined, 3, null]) expect(leerFiltro(raro)).toBe('')
  })

  it('la página es un entero desde 1; lo demás es la primera', () => {
    expect(leerPagina('3')).toBe(3)
    expect(leerPagina('1')).toBe(1)
    expect(leerPagina(['2', '5'])).toBe(2)
    for (const raro of ['0', '-1', '2.5', 'abc', '', '1234567', undefined, null, 4]) {
      expect(leerPagina(raro), String(raro)).toBe(1)
    }
  })
})

describe('lo que llega de la API', () => {
  it('lee los resultados con su página y su total', () => {
    const pagina = analizarResultados(cuerpo([item(1), item(2)], { total: 12, last_page: 2 }))
    expect(pagina?.total).toBe(12)
    expect(pagina?.ultimaPagina).toBe(2)
    expect(pagina?.resultados[0]).toEqual({
      id: 1,
      titulo: 'Trámite 1',
      categoria: 'Trámite',
      tipoRecurso: 'tramite',
      extracto: 'Autorización para construir, ampliar o modificar una edificación.',
      fecha: '2026-03-14',
      url: '/tramites/tramite-1',
    })
  })

  it('ignora autor y miniatura: no se dibuja nada por ellos', () => {
    const [resultado] = analizarResultados(cuerpo([item(1, { autor: 'Alguien', miniatura: '/x.png' })]))?.resultados ?? []
    expect(resultado).not.toHaveProperty('autor')
    expect(resultado).not.toHaveProperty('miniatura')
  })

  it('una lista vacía es válida: «sin resultados», no un fallo', () => {
    expect(analizarResultados(cuerpo([]))).toEqual({ resultados: [], total: 0, ultimaPagina: 1 })
  })

  it('descarta los resultados malformados o con una dirección que sale del sitio', () => {
    const pagina = analizarResultados(
      cuerpo([
        item(1),
        item(2, { url: '//otro.sitio/x' }),
        item(3, { url: 'https://otro.sitio/x' }),
        item(4, { url: 'javascript:alert(1)' }),
        item(5, { titulo: '  ' }),
        item(6, { id: '6' }),
        item(7, { categoria: null }),
        item(8, { tipo_recurso: 'noticia' }),
        null,
        'texto',
        item(9, { tipo_recurso: 'politica', url: '/politicas/uso-de-cookies' }),
      ]),
    )
    expect(pagina?.resultados.map((r) => r.id)).toEqual([1, 9])
  })

  it('un extracto o una fecha ausentes o inválidos quedan en null', () => {
    const [a, b, c] =
      analizarResultados(cuerpo([item(1, { extracto: null, fecha: null }), item(2, { extracto: '  ', fecha: 'ayer' }), item(3, { extracto: 5, fecha: 20260314 })]))
        ?.resultados ?? []
    for (const r of [a, b, c]) {
      expect(r?.extracto).toBeNull()
      expect(r?.fecha).toBeNull()
    }
  })

  it('una fecha con hora se queda con el día', () => {
    expect(analizarResultados(cuerpo([item(1, { fecha: '2026-03-14T05:00:00Z' })]))?.resultados[0]?.fecha).toBe('2026-03-14')
  })

  it.each([
    ['null', null],
    ['texto', 'x'],
    ['sin data', { meta: { total: 0, last_page: 1 } }],
    ['data que no es lista', { data: {}, meta: { total: 0, last_page: 1 } }],
    ['sin meta', { data: [] }],
    ['meta sin total', { data: [], meta: { last_page: 1 } }],
    ['total negativo', { data: [], meta: { total: -1, last_page: 1 } }],
    ['total no entero', { data: [], meta: { total: 1.5, last_page: 1 } }],
    ['sin last_page', { data: [], meta: { total: 0 } }],
    ['last_page 0', { data: [], meta: { total: 0, last_page: 0 } }],
  ])('un cuerpo sin la forma del contrato (%s) no se lee: es un fallo del servicio', (_nombre, malo) => {
    expect(analizarResultados(malo)).toBeNull()
  })

  it('se piden 10 por página', () => {
    expect(RESULTADOS_POR_PAGINA).toBe(10)
  })
})

describe('fechas (§5.1)', () => {
  it('trámite: «Publicado el …»', () => {
    expect(etiquetaDeFecha('tramite', '2026-03-14')).toBe('Publicado el 14 de marzo de 2026')
  })

  it('política: «Vigente desde el …»', () => {
    expect(etiquetaDeFecha('politica', '2026-01-01')).toBe('Vigente desde el 1 de enero de 2026')
  })

  it('es una fecha de calendario: el último día del año no pasa al siguiente', () => {
    expect(etiquetaDeFecha('tramite', '2026-12-31')).toBe('Publicado el 31 de diciembre de 2026')
    expect(etiquetaDeFecha('politica', '2026-01-01')).toBe('Vigente desde el 1 de enero de 2026')
  })

  it('sin fecha, o con algo que no es una fecha, no se escribe nada', () => {
    expect(etiquetaDeFecha('tramite', null)).toBeNull()
    expect(etiquetaDeFecha('politica', '2026-13-45')).toBeNull()
  })
})

describe('extracto y resumen', () => {
  it('un extracto corto queda igual', () => {
    expect(recortarExtracto('Texto breve.')).toBe('Texto breve.')
    expect(recortarExtracto('a'.repeat(EXTRACTO_MAXIMO))).toHaveLength(EXTRACTO_MAXIMO)
  })

  it('uno largo se corta en un espacio, sin partir palabras, y termina en «…»', () => {
    const largo = `${'palabra '.repeat(40)}final`
    const corto = recortarExtracto(largo)
    expect(corto.length).toBeLessThanOrEqual(EXTRACTO_MAXIMO + 1)
    expect(corto.endsWith('…')).toBe(true)
    expect(corto.slice(0, -1).endsWith('palabra')).toBe(true)
  })

  it('sin ningún espacio, corta en el máximo', () => {
    const corto = recortarExtracto('x'.repeat(500))
    expect(corto).toBe(`${'x'.repeat(EXTRACTO_MAXIMO)}…`)
  })

  it('«N resultados para «q».» y, en singular, «1 resultado para «q».»', () => {
    expect(textoResumen(12, 'licencia')).toBe('12 resultados para «licencia».')
    expect(textoResumen(1, 'licencia')).toBe('1 resultado para «licencia».')
  })
})
