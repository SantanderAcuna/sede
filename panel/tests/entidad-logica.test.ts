/**
 * Lógica pura del formulario de la Entidad: de la API al formulario, qué se envía,
 * qué se comprueba antes de enviar.
 */
import { describe, expect, it } from 'vitest'
import type { EntidadItem } from '../src/types/api'
import {
  entradaDesde,
  formularioDesde,
  hayCambios,
  problemasPorCampo,
} from '../src/views/admin/entidad-logica'

const entidad = (extra: Partial<EntidadItem> = {}): EntidadItem =>
  ({
    id: 1,
    uuid: 'u',
    type: 'entidad',
    nombre: 'Alcaldía Distrital de Santa Marta',
    sigla: 'D.T.C.H.',
    nit: '891.780.009-4',
    direccion: 'Calle 14',
    municipio: 'Santa Marta',
    departamento: 'Magdalena',
    pais: 'Colombia',
    telefono: '(+57) 605 420 9600',
    linea_atencion: null,
    linea_gratuita: '018000 955 532',
    linea_anticorrupcion: null,
    correo_atencion: 'a@santamarta.gov.co',
    correo_notificaciones_judiciales: null,
    horario: 'Lunes a viernes',
    codigo_postal: '470004',
    dominio: 'https://santamarta.gov.co',
    logo: '/logo-entidad.png',
    redes: [{ red: 'facebook', url: 'https://facebook.com/x' }],
    politicas: [{ slug: 'uso-de-cookies', nombre: 'Uso de cookies' }],
    datos_por_confirmar: [],
    ...extra,
  }) as EntidadItem

describe('formularioDesde', () => {
  it('convierte los vacíos en cadena vacía y copia las listas', () => {
    const f = formularioDesde(entidad())
    expect(f.linea_atencion).toBe('')
    expect(f.nombre).toBe('Alcaldía Distrital de Santa Marta')
    f.redes.push({ red: 'x', url: 'https://x.com/a' })
    expect(entidad().redes).toHaveLength(1)
  })
})

describe('entradaDesde — solo lo que cambió', () => {
  it('sin cambios no envía nada', () => {
    const o = formularioDesde(entidad())
    expect(entradaDesde(formularioDesde(entidad()), o)).toEqual({})
    expect(hayCambios(formularioDesde(entidad()), o)).toBe(false)
  })

  it('envía solo el campo cambiado, recortado', () => {
    const o = formularioDesde(entidad())
    const f = formularioDesde(entidad())
    f.telefono = '  (+57) 605 111  '
    expect(entradaDesde(f, o)).toEqual({ telefono: '(+57) 605 111' })
    expect(hayCambios(f, o)).toBe(true)
  })

  it('vaciar un campo opcional envía null: se puede borrar un dato', () => {
    const o = formularioDesde(entidad())
    const f = formularioDesde(entidad())
    f.linea_gratuita = '   '
    expect(entradaDesde(f, o)).toEqual({ linea_gratuita: null })
  })

  it('quitar todas las redes envía lista vacía (antes era imposible)', () => {
    const o = formularioDesde(entidad())
    const f = formularioDesde(entidad())
    f.redes = []
    expect(entradaDesde(f, o)).toEqual({ redes: [] })
  })

  it('cambiar una red envía la lista completa de redes', () => {
    const o = formularioDesde(entidad())
    const f = formularioDesde(entidad())
    f.redes[0]!.url = 'https://facebook.com/otra'
    expect(entradaDesde(f, o)).toEqual({ redes: [{ red: 'facebook', url: 'https://facebook.com/otra' }] })
  })

  it('no envía el logo: no se edita desde aquí', () => {
    const o = formularioDesde(entidad())
    const f = formularioDesde(entidad())
    f.nombre = 'Otro'
    expect(entradaDesde(f, o)).not.toHaveProperty('logo')
  })
})

describe('problemasPorCampo', () => {
  const vacio = () => formularioDesde(entidad())

  it('un formulario correcto no tiene problemas', () => {
    expect(problemasPorCampo(vacio())).toEqual([])
  })

  it('el nombre es obligatorio', () => {
    const f = vacio()
    f.nombre = '  '
    expect(problemasPorCampo(f)).toEqual([{ campo: 'nombre', mensaje: expect.stringContaining('nombre') }])
  })

  it('los correos deben tener forma de correo', () => {
    const f = vacio()
    f.correo_atencion = 'sin-arroba'
    f.correo_notificaciones_judiciales = 'a@b'
    const campos = problemasPorCampo(f).map((p) => p.campo)
    expect(campos).toContain('correo_atencion')
    expect(campos).toContain('correo_notificaciones_judiciales')
  })

  it('el dominio exige http:// o https://', () => {
    const f = vacio()
    f.dominio = 'santamarta.gov.co'
    expect(problemasPorCampo(f).map((p) => p.campo)).toContain('dominio')
  })

  it('cada red necesita una dirección web válida, sin espacios', () => {
    const f = vacio()
    f.redes = [
      { red: 'facebook', url: '' },
      { red: 'x', url: 'https://x.com/a b' },
      { red: 'youtube', url: 'ftp://youtube.com/x' },
    ]
    const campos = problemasPorCampo(f).map((p) => p.campo)
    expect(campos).toEqual(['redes.0', 'redes.1', 'redes.2'])
  })

  it('las políticas del pie necesitan nombre y no se repiten', () => {
    const f = vacio()
    f.politicas = [
      { slug: 'uso-de-cookies', nombre: '' },
      { slug: 'uso-de-cookies', nombre: 'Cookies' },
    ]
    const campos = problemasPorCampo(f).map((p) => p.campo)
    expect(campos).toContain('politicas.0')
    expect(campos).toContain('politicas.1')
  })

  it('los límites del servidor se avisan antes', () => {
    const f = vacio()
    f.sigla = 'x'.repeat(21)
    expect(problemasPorCampo(f).map((p) => p.campo)).toContain('sigla')
  })
})
