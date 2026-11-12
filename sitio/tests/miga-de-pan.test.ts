/**
 * Miga de pan (CAG-11), derivada de la ruta.
 *
 * Hallazgo medido en staging el 2026-11-11: la ficha de una noticia mostraba
 * «Inicio > Noticias > Noticias» y la de un trámite «Inicio > Trámites y servicios >
 * Trámites y servicios». El último nivel debe ser la página en la que se está, no la
 * sección repetida. Autorizado por José en el chat el 2026-11-11 (21:57): es el único
 * cambio de texto de la miga; el resto del recorrido no cambia.
 */
import { describe, expect, it } from 'vitest'

import { construirMiga, fichaDeRuta, tituloDeFicha } from '../app/utils/miga'

const destinos = [
  { ruta: '/noticias', etiqueta: 'Noticias' },
  { ruta: '/tramites', etiqueta: 'Trámites y servicios' },
  { ruta: '/participa', etiqueta: 'Participa' },
  { ruta: '/participa/consulta-ciudadana', etiqueta: 'Participación y consulta ciudadana' },
]
const fueraDelMenu = (ruta: string): string | undefined => (ruta === '/politicas' ? 'Políticas' : undefined)
const base = { destinos, fueraDelMenu }

describe('construirMiga', () => {
  it('la portada no tiene recorrido', () => {
    expect(construirMiga('/', base)).toEqual([])
  })

  it('una sección del menú: Inicio y la sección', () => {
    expect(construirMiga('/noticias', base)).toEqual([
      { etiqueta: 'Inicio', ruta: '/' },
      { etiqueta: 'Noticias', ruta: '/noticias' },
    ])
  })

  it('la ficha de una noticia termina en el título de la noticia, no en la sección repetida', () => {
    const titulo = { ruta: '/noticias/mi-noticia', texto: 'Mi noticia de prueba' }
    expect(construirMiga('/noticias/mi-noticia', { ...base, titulo })).toEqual([
      { etiqueta: 'Inicio', ruta: '/' },
      { etiqueta: 'Noticias', ruta: '/noticias' },
      { etiqueta: 'Mi noticia de prueba', ruta: '/noticias/mi-noticia' },
    ])
  })

  it('la ficha de un trámite termina en el nombre del trámite', () => {
    const titulo = { ruta: '/tramites/certificado', texto: 'Certificado de residencia' }
    expect(construirMiga('/tramites/certificado', { ...base, titulo }).map((n) => n.etiqueta)).toEqual([
      'Inicio',
      'Trámites y servicios',
      'Certificado de residencia',
    ])
  })

  it('un título que pertenece a otra ruta no se usa (no hay migas de una página anterior)', () => {
    const titulo = { ruta: '/noticias/otra', texto: 'Otra noticia' }
    expect(construirMiga('/noticias/mi-noticia', { ...base, titulo }).map((n) => n.etiqueta)).toEqual([
      'Inicio',
      'Noticias',
      'Noticias',
    ])
  })

  it('sin título, el recorrido queda como estaba (no se inventa un nombre)', () => {
    expect(construirMiga('/noticias/mi-noticia', base).map((n) => n.etiqueta)).toEqual(['Inicio', 'Noticias', 'Noticias'])
  })

  it('un destino que está en el menú conserva su nombre aunque haya un título', () => {
    const titulo = { ruta: '/participa/consulta-ciudadana', texto: 'Otro nombre' }
    expect(construirMiga('/participa/consulta-ciudadana', { ...base, titulo }).map((n) => n.etiqueta)).toEqual([
      'Inicio',
      'Participa',
      'Participación y consulta ciudadana',
    ])
  })

  it('una página fuera del menú usa su nombre registrado', () => {
    expect(construirMiga('/politicas', base).map((n) => n.etiqueta)).toEqual(['Inicio', 'Políticas'])
  })

  it('un segmento que nadie conoce se deriva del propio segmento', () => {
    expect(construirMiga('/algo-nuevo', base).map((n) => n.etiqueta)).toEqual(['Inicio', 'Algo nuevo'])
  })

  it('el título se escribe como texto: no interpreta etiquetas', () => {
    const titulo = { ruta: '/noticias/x', texto: '<b>x</b>' }
    const miga = construirMiga('/noticias/x', { ...base, titulo })
    expect(miga[miga.length - 1]?.etiqueta).toBe('<b>x</b>')
  })
})

describe('fichaDeRuta', () => {
  it('reconoce la ficha de una noticia y la de un trámite', () => {
    expect(fichaDeRuta('/noticias/mi-noticia')).toEqual({
      seccion: 'noticias',
      slug: 'mi-noticia',
      clave: 'noticia-mi-noticia',
      endpoint: '/noticias/mi-noticia',
    })
    expect(fichaDeRuta('/tramites/certificado')).toEqual({
      seccion: 'tramites',
      slug: 'certificado',
      clave: 'tramite-certificado',
      endpoint: '/tramites/certificado',
    })
  })

  it('la clave es la que ya usan las páginas, para que una sola lectura sirva a las dos', () => {
    expect(fichaDeRuta('/noticias/x')?.clave).toBe('noticia-x')
    expect(fichaDeRuta('/tramites/x')?.clave).toBe('tramite-x')
  })

  it('no reconoce los listados, la portada ni otras secciones', () => {
    for (const ruta of ['/', '/noticias', '/tramites', '/noticias/', '/participa/consulta-ciudadana', '/politicas', '/noticias/a/b']) {
      expect(fichaDeRuta(ruta), ruta).toBeNull()
    }
  })

  it('decodifica el slug como lo hace el enrutador, y no revienta con uno mal codificado', () => {
    expect(fichaDeRuta('/tramites/a%20b')?.slug).toBe('a b')
    expect(fichaDeRuta('/tramites/%E0%A4%A')?.slug).toBe('%E0%A4%A')
  })

  it('el slug que va a la API queda codificado: no puede colar otra ruta', () => {
    expect(fichaDeRuta('/noticias/a%2Fb')?.endpoint).toBe('/noticias/a%2Fb')
  })
})

describe('tituloDeFicha', () => {
  it('la noticia usa su título y el trámite su nombre', () => {
    expect(tituloDeFicha('noticias', { data: { titulo: 'Una noticia' } })).toBe('Una noticia')
    expect(tituloDeFicha('tramites', { data: { nombre: 'Certificado' } })).toBe('Certificado')
  })

  it('sin dato válido no hay título (no se inventa)', () => {
    for (const r of [null, undefined, {}, { data: null }, { data: { titulo: '' } }, { data: { titulo: '   ' } }, { data: { titulo: 5 } }]) {
      expect(tituloDeFicha('noticias', r), JSON.stringify(r)).toBeNull()
    }
  })
})
