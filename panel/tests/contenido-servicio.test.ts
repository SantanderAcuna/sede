/**
 * Servicio de imágenes del contenido: sube con el alt y devuelve la URL pública.
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'

const post = vi.fn()
vi.mock('../src/services/http', () => ({ http: { post: (...a: unknown[]) => post(...a) }, desenvolver: (r: { data: unknown }) => r.data }))

import { subirImagenContenido } from '../src/services/contenido'

describe('subirImagenContenido', () => {
  beforeEach(() => post.mockReset())

  it('envía imagen y alt a la ruta del contenido y devuelve la imagen', async () => {
    post.mockResolvedValue({ data: { success: true, data: { uuid: 'u1', url: '/api/v1/contenido/imagenes/u1', alt: 'Alcalde' } } })
    const archivo = new File(['x'], 'foto.png', { type: 'image/png' })

    const r = await subirImagenContenido(archivo, 'Alcalde')

    expect(r).toEqual({ uuid: 'u1', url: '/api/v1/contenido/imagenes/u1', alt: 'Alcalde' })
    const [ruta, cuerpo] = post.mock.calls[0] as [string, FormData]
    expect(ruta).toBe('/panel/contenido/imagenes')
    expect(cuerpo.get('alt')).toBe('Alcalde')
    expect(cuerpo.get('imagen')).toBe(archivo)
  })
})
