/**
 * Carrusel de la portada alimentado por la API (Fase 6).
 *
 * Regla de fondo: lo que publica la Entidad sustituye a las diapositivas por defecto sólo si
 * llega completo y seguro; si la API falla, está vacía o devuelve algo inválido, la portada
 * sigue mostrando las de siempre. Nunca queda el carrusel en blanco ni con una imagen sin alt.
 */
import { describe, expect, it } from 'vitest'

import { diapositivasDePortada } from '../app/utils/banners'

const porDefecto = [{ id: 'x', imagen: '/govco/x.jpg', alt: 'Por defecto', titulo: 'Defecto' }]
const uuid = '11111111-1111-4111-8111-111111111111'
const b = (extra: Record<string, unknown> = {}) => ({
  uuid,
  titulo: 'Trámites',
  descripcion: 'Sin filas',
  enlace: '/tramites',
  imagen: { url: `/api/v1/banners/${uuid}/imagen`, alt: 'Ciudadana en un computador' },
  ...extra,
})

describe('diapositivasDePortada', () => {
  it('sin respuesta (la API falló) usa las de por defecto', () => {
    expect(diapositivasDePortada(null, porDefecto)).toEqual(porDefecto)
  })
  it('lista vacía usa las de por defecto', () => {
    expect(diapositivasDePortada({ data: [] }, porDefecto)).toEqual(porDefecto)
  })
  it('convierte las publicadas, en el orden recibido', () => {
    const r = diapositivasDePortada({ data: [b(), b({ uuid: '22222222-2222-4222-8222-222222222222', titulo: 'Otra', imagen: { url: '/api/v1/banners/22222222-2222-4222-8222-222222222222/imagen', alt: 'Otra foto' } })] }, porDefecto)
    expect(r.map((d) => d.titulo)).toEqual(['Trámites', 'Otra'])
    expect(r[0]).toMatchObject({ id: uuid, imagen: `/api/v1/banners/${uuid}/imagen`, alt: 'Ciudadana en un computador', enlace: '/tramites', descripcion: 'Sin filas' })
  })
  it('descarta una sin texto alternativo, no la muestra sin alt', () => {
    const r = diapositivasDePortada({ data: [b({ imagen: { url: `/api/v1/banners/${uuid}/imagen`, alt: '  ' } })] }, porDefecto)
    expect(r).toEqual(porDefecto)
  })
  it('descarta una cuya imagen no sea de la API de banners', () => {
    expect(diapositivasDePortada({ data: [b({ imagen: { url: 'https://evil.com/x.png', alt: 'a' } })] }, porDefecto)).toEqual(porDefecto)
  })
  it('un enlace peligroso se quita pero la diapositiva se conserva', () => {
    const r = diapositivasDePortada({ data: [b({ enlace: 'javascript:alert(1)' })] }, porDefecto)
    expect(r).toHaveLength(1)
    expect(r[0]!.enlace).toBeUndefined()
  })
  it('acepta enlace https', () => {
    expect(diapositivasDePortada({ data: [b({ enlace: 'https://santamarta.gov.co/x' })] }, porDefecto)[0]!.enlace).toBe('https://santamarta.gov.co/x')
  })
  it('basura en la respuesta no rompe', () => {
    expect(diapositivasDePortada({ data: 'x' } as never, porDefecto)).toEqual(porDefecto)
    expect(diapositivasDePortada({ data: [null, 3] } as never, porDefecto)).toEqual(porDefecto)
  })
})
