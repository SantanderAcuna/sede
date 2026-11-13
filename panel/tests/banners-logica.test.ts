/**
 * Carrusel de la portada en el panel (Fase 6): lógica del formulario y del orden.
 *
 * Reglas del servidor que aquí se anticipan: título ≤ 80, descripción ≤ 200, texto
 * alternativo ≤ 250 y obligatorio con imagen, enlace = ruta del sitio o https://,
 * fin posterior al inicio. El servidor valida siempre; aquí se explica antes y en palabras llanas.
 */
import { describe, expect, it } from 'vitest'

import type { BannerPanelItem } from '../src/types/api'
import {
  ALT_MAX,
  DESCRIPCION_MAX,
  TITULO_MAX,
  enlaceValido,
  entradaDesde,
  formularioDesde,
  formularioVacio,
  hayCambios,
  mover,
  problemasPorCampo,
  textoDeEstado,
} from '../src/views/admin/banners-logica'

const banner = (extra: Partial<BannerPanelItem> = {}): BannerPanelItem => ({
  uuid: 'b1',
  titulo: 'Trámites en línea',
  descripcion: 'Haga sus trámites sin filas.',
  enlace: '/tramites',
  orden: 1,
  activo: true,
  estado: 'vigente',
  vigente_desde: '2026-11-01T08:00:00-05:00',
  vigente_hasta: null,
  imagen: { url: '/api/v1/panel/banners/b1/imagen', alt: 'Ciudadana en un computador' },
  problemas: [],
  ...extra,
})

describe('enlaceValido', () => {
  it.each(['', '/transparencia', '/tramites/licencia', 'https://santamarta.gov.co/x'])('acepta %j', (v) => {
    expect(enlaceValido(v)).toBe(true)
  })
  it.each(['javascript:alert(1)', 'data:text/html,x', 'http://x.co', '//evil.com', 'tramites', '/con espacio'])('rechaza %j', (v) => {
    expect(enlaceValido(v)).toBe(false)
  })
})

describe('problemasPorCampo', () => {
  const ok = { ...formularioVacio(), titulo: 'Hola', imagen_alt: 'Una foto' }
  it('el formulario vacío sólo pide título', () => {
    expect(problemasPorCampo(formularioVacio(), { tieneImagen: false, imagenNueva: false }).map((p) => p.campo)).toEqual(['titulo'])
  })
  it('publicar sin imagen pide la imagen', () => {
    const p = problemasPorCampo({ ...ok, activo: true }, { tieneImagen: false, imagenNueva: false })
    expect(p.map((x) => x.campo)).toContain('imagen')
  })
  it('con imagen, el texto alternativo es obligatorio', () => {
    const p = problemasPorCampo({ ...ok, imagen_alt: ' ' }, { tieneImagen: false, imagenNueva: true })
    expect(p.find((x) => x.campo === 'imagen_alt')?.mensaje).toMatch(/descripción de la imagen/i)
  })
  it('límites de longitud', () => {
    const p = problemasPorCampo(
      { ...ok, titulo: 'x'.repeat(TITULO_MAX + 1), descripcion: 'x'.repeat(DESCRIPCION_MAX + 1), imagen_alt: 'x'.repeat(ALT_MAX + 1) },
      { tieneImagen: true, imagenNueva: false },
    )
    expect(p.map((x) => x.campo).sort()).toEqual(['descripcion', 'imagen_alt', 'titulo'])
  })
  it('enlace inválido se explica', () => {
    const p = problemasPorCampo({ ...ok, enlace: 'javascript:x' }, { tieneImagen: true, imagenNueva: false })
    expect(p.find((x) => x.campo === 'enlace')?.mensaje).toMatch(/https:\/\//)
  })
  it('el fin debe ser posterior al inicio', () => {
    const p = problemasPorCampo(
      { ...ok, vigente_desde: '2026-11-10T10:00', vigente_hasta: '2026-11-09T10:00' },
      { tieneImagen: true, imagenNueva: false },
    )
    expect(p.map((x) => x.campo)).toEqual(['vigente_hasta'])
  })
})

describe('formulario ↔ API', () => {
  it('formularioDesde pasa las fechas a hora local y los nulos a vacío', () => {
    const f = formularioDesde(banner({ descripcion: null, enlace: null, vigente_hasta: null }))
    expect(f.descripcion).toBe('')
    expect(f.enlace).toBe('')
    expect(f.vigente_hasta).toBe('')
    expect(f.vigente_desde).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/)
    expect(f.imagen_alt).toBe('Ciudadana en un computador')
  })
  it('entradaDesde envía sólo lo que cambió y vacíos como null', () => {
    const o = formularioDesde(banner())
    const f = { ...o, titulo: ' Nuevo ', enlace: '', vigente_desde: '' }
    expect(entradaDesde(f, o)).toEqual({ titulo: 'Nuevo', enlace: null, vigente_desde: null })
  })
  it('sin cambios no envía nada', () => {
    const o = formularioDesde(banner())
    expect(entradaDesde(o, o)).toEqual({})
    expect(hayCambios(o, o)).toBe(false)
  })
  it('al crear viaja el título aunque no haya original', () => {
    const f = { ...formularioVacio(), titulo: 'Hola', activo: true }
    expect(entradaDesde(f, formularioVacio(), { completa: true })).toMatchObject({ titulo: 'Hola', activo: true })
  })
  it('las fechas viajan en ISO con zona', () => {
    const o = formularioVacio()
    const e = entradaDesde({ ...o, vigente_desde: '2026-11-12T09:30' }, o)
    expect(e.vigente_desde).toMatch(/^2026-11-12T09:30:00[+-]\d{2}:\d{2}$/)
  })
})

describe('mover', () => {
  it('sube y baja un lugar', () => {
    expect(mover(['a', 'b', 'c'], 'b', -1)).toEqual(['b', 'a', 'c'])
    expect(mover(['a', 'b', 'c'], 'b', 1)).toEqual(['a', 'c', 'b'])
  })
  it('en los extremos no cambia', () => {
    expect(mover(['a', 'b'], 'a', -1)).toEqual(['a', 'b'])
    expect(mover(['a', 'b'], 'b', 1)).toEqual(['a', 'b'])
  })
  it('uuid desconocido no cambia nada', () => {
    expect(mover(['a', 'b'], 'z', 1)).toEqual(['a', 'b'])
  })
})

describe('textoDeEstado', () => {
  it('lo dice en palabras', () => {
    expect(textoDeEstado('borrador')).toBe('Borrador')
    expect(textoDeEstado('programado')).toBe('Programada')
    expect(textoDeEstado('vigente')).toBe('Publicada')
    expect(textoDeEstado('vencido')).toBe('Vencida')
  })
})
