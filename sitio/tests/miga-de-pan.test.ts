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

import { construirMiga } from '../app/utils/miga'

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
