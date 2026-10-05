/**
 * Tests de tipos para la entidad (Vertical Slice 1 — Entidad).
 *
 * Solo cubre los tipos TypeScript — no requiere mocks HTTP.
 */
import { beforeEach, describe, expect, it } from 'vitest'

import type { EntidadItem, EntidadInput } from '../src/types/api'

beforeEach(() => {
  // Sin limpieza necesaria para tests de tipos puros.
})

// ---------------------------------------------------------------------------
// EntidadItem
// ---------------------------------------------------------------------------
describe('EntidadItem — tipo y campos', () => {
  const entidad: EntidadItem = {
    id: 1,
    type: 'entidad',
    nombre: 'Alcaldía Distrital de Santa Marta',
    sigla: 'D.T.C.H.',
    nit: '891.780.009-4',
    direccion: 'Calle 14 No. 2-49, Palacio Municipal',
    telefono: 'PBX 4201234',
    correo: 'contacto@santamarta.gov.co',
    web: 'https://www.santamarta.gov.co',
    facebook: null,
    twitter: null,
    instagram: null,
    youtube: null,
    linkedin: null,
    tiktok: null,
    latitud: 11.245,
    longitud: -74.2113,
    horarios_atencion: 'Lunes a viernes 7:30 am a 5:30 pm',
    politicas_privacidad: 'https://www.santamarta.gov.co/politicas',
    terminos_condiciones: 'https://www.santamarta.gov.co/terminos',
    mapa_url: null,
  }

  it('tiene los campos mínimos de identificación', () => {
    expect(entidad.id).toBe(1)
    expect(entidad.type).toBe('entidad')
    expect(entidad.nombre).toBe('Alcaldía Distrital de Santa Marta')
    expect(entidad.nit).toBe('891.780.009-4')
  })

  it('campos de contacto son strings', () => {
    expect(typeof entidad.telefono).toBe('string')
    expect(typeof entidad.correo).toBe('string')
    expect(typeof entidad.web).toBe('string')
  })

  it('redes sociales pueden ser null', () => {
    expect(entidad.facebook).toBeNull()
    expect(entidad.twitter).toBeNull()
    expect(entidad.instagram).toBeNull()
  })

  it('coordenadas son números', () => {
    expect(typeof entidad.latitud).toBe('number')
    expect(typeof entidad.longitud).toBe('number')
    expect(entidad.latitud).toBeCloseTo(11.245)
    expect(entidad.longitud).toBeCloseTo(-74.2113)
  })

  it('políticas y términos son URLs válidas', () => {
    expect(entidad.politicas_privacidad).toMatch(/^https?:\/\//)
    expect(entidad.terminos_condiciones).toMatch(/^https?:\/\//)
  })
})

// ---------------------------------------------------------------------------
// EntidadInput — actualización parcial
// ---------------------------------------------------------------------------
describe('EntidadInput — actualización parcial', () => {
  it('acepta actualización de un solo campo', () => {
    const actualizacion: EntidadInput = { telefono: 'PBX 4209999' }
    expect(actualizacion.telefono).toBe('PBX 4209999')
  })

  it('acepta actualización de varios campos', () => {
    const actualizacion: EntidadInput = {
      telefono: 'PBX 4209999',
      correo: 'nuevo@santamarta.gov.co',
      facebook: 'https://facebook.com/alcaldia',
    }
    expect(actualizacion.telefono).toBe('PBX 4209999')
    expect(actualizacion.correo).toBe('nuevo@santamarta.gov.co')
    expect(actualizacion.facebook).toBe('https://facebook.com/alcaldia')
  })

  it('todos los campos son opcionales', () => {
    const vacia: EntidadInput = {}
    expect(vacia.nombre).toBeUndefined()
    expect(vacia.telefono).toBeUndefined()
    expect(vacia.direccion).toBeUndefined()
  })

  it('redes sociales se actualizan de forma independiente', () => {
    const redes: EntidadInput = {
      facebook: 'https://facebook.com/santamarta',
      twitter: 'https://x.com/alcaldiadecol',
      instagram: 'https://instagram.com/alcaldiadecol',
      youtube: 'https://youtube.com/@alcaldia',
      linkedin: 'https://linkedin.com/company/alcaldia',
      tiktok: 'https://tiktok.com/@alcaldia',
    }
    expect(redes.facebook).toBe('https://facebook.com/santamarta')
    expect(redes.tiktok).toBe('https://tiktok.com/@alcaldia')
    expect(redes.youtube).toBe('https://youtube.com/@alcaldia')
  })

  it('coordenadas aceptan números decimales', () => {
    const coords: EntidadInput = { latitud: 4.123456, longitud: -72.987654 }
    expect(coords.latitud).toBeCloseTo(4.123456)
    expect(coords.longitud).toBeCloseTo(-72.987654)
  })

  it('horarios de atención en texto libre', () => {
    const horarios: EntidadInput = {
      horarios_atencion: 'Lunes a viernes 8:00 am - 12:00 pm y 2:00 pm - 6:00 pm',
    }
    expect(horarios.horarios_atencion).toContain('Lunes')
    expect(horarios.horarios_atencion).toContain('6:00 pm')
  })
})
