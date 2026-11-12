/**
 * Lógica pura de navegación: qué números de página se dibujan y qué migas de pan
 * se enlazan. Las dos tenían un defecto verificable: la tabla dibujaba un botón
 * por página (con 80 páginas, 80 botones) y la miga enlazaba segmentos de la URL
 * que no son rutas (`/contenidos`), que llevaban a «Página no encontrada».
 */
import { describe, expect, it } from 'vitest'

import { ventanaDePaginas } from '../src/components/base/paginas'
import { construirMigas } from '../src/layouts/migas'

describe('ventanaDePaginas', () => {
  it('pocas páginas: todas', () => {
    expect(ventanaDePaginas(1, 3)).toEqual([1, 2, 3])
    expect(ventanaDePaginas(1, 1)).toEqual([1])
  })

  it('sin páginas no dibuja ninguna', () => {
    expect(ventanaDePaginas(1, 0)).toEqual([])
  })

  it('muchas páginas: primera, última, vecinas de la actual y huecos marcados', () => {
    expect(ventanaDePaginas(1, 20)).toEqual([1, 2, 3, '…', 20])
    expect(ventanaDePaginas(10, 20)).toEqual([1, '…', 9, 10, 11, '…', 20])
    expect(ventanaDePaginas(20, 20)).toEqual([1, '…', 18, 19, 20])
  })

  it('no deja un hueco de una sola página: la dibuja', () => {
    expect(ventanaDePaginas(4, 20)).toEqual([1, 2, 3, 4, 5, '…', 20])
    expect(ventanaDePaginas(17, 20)).toEqual([1, '…', 16, 17, 18, 19, 20])
  })

  it('una página actual fuera de rango se acota', () => {
    expect(ventanaDePaginas(99, 3)).toEqual([1, 2, 3])
    expect(ventanaDePaginas(-4, 3)).toEqual([1, 2, 3])
  })
})

describe('construirMigas', () => {
  const rutasReales = new Set(['/contenidos/noticias', '/configuracion/politicas'])
  const existe = (ruta: string): boolean => rutasReales.has(ruta)

  it('sin segmentos no hay migas', () => {
    expect(construirMigas('/', undefined, existe)).toEqual([])
  })

  it('un segmento intermedio que no es ruta no se enlaza', () => {
    const migas = construirMigas('/contenidos/noticias', 'Noticias', existe)

    expect(migas).toEqual([
      { label: 'Contenidos', ruta: null, esActual: false },
      { label: 'Noticias', ruta: '/contenidos/noticias', esActual: true },
    ])
  })

  it('un segmento intermedio que sí es ruta se enlaza', () => {
    const migas = construirMigas('/contenidos/noticias/nueva', 'Nueva noticia', existe)

    expect(migas.map((m) => m.ruta)).toEqual([null, '/contenidos/noticias', '/contenidos/noticias/nueva'])
    expect(migas.at(-1)).toMatchObject({ label: 'Nueva noticia', esActual: true })
  })

  it('un uuid intermedio no se enlaza ni se muestra crudo', () => {
    const uuid = '3f2a9c1e-1b2d-4c3e-8f4a-5b6c7d8e9f01'
    const migas = construirMigas(`/contenidos/noticias/${uuid}`, 'Editar noticia', existe)

    expect(migas.at(-1)).toMatchObject({ label: 'Editar noticia', esActual: true })
    expect(migas.map((m) => m.label)).not.toContain(uuid)
  })

  it('el nombre se arma del segmento cuando no hay título', () => {
    expect(construirMigas('/gestion-documental', undefined, existe)[0]!.label).toBe('Gestion documental')
  })
})
