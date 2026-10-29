/**
 * Políticas del sitio en el panel (T-20): lógica del formulario y servicio.
 */
import { AxiosError, type AxiosResponse } from 'axios'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import type { PoliticaPanelItem } from '../src/types/api'
import {
  entradaDesde,
  formularioDesde,
  hayCambios,
  idDeApartado,
  motivosDeError,
  parrafosDesdeTexto,
  problemasLocales,
} from '../src/views/admin/politicas-logica'

const politica = (extra: Partial<PoliticaPanelItem> = {}): PoliticaPanelItem => ({
  id: 4,
  type: 'politica',
  slug: 'uso-de-cookies',
  nombre: 'Uso de cookies',
  version: 1,
  vigente_desde: null,
  acto: { tipo: null, numero: null, fecha: null },
  es_ejemplo: true,
  documento_oficial: null,
  actualizada_en: '2026-10-29',
  secciones: [
    { id: 'finalidad', titulo: 'Finalidad', parrafos: ['Uno.', 'Dos.'] },
    { id: 'extra', titulo: 'Extra', parrafos: ['Texto.'] },
  ],
  uuid: '11111111-1111-4111-8111-111111111111',
  componentes: { finalidad: 'Finalidad de cada una' },
  completa: true,
  problemas: [],
  ...extra,
})

describe('párrafos', () => {
  it('separa por línea en blanco y descarta lo vacío', () => {
    expect(parrafosDesdeTexto('Uno.\n\nDos.\r\n\r\n\n  \nTres.')).toEqual(['Uno.', 'Dos.', 'Tres.'])
    expect(parrafosDesdeTexto('  \n\n ')).toEqual([])
  })

  it('ida y vuelta: lo que se carga es lo que se envía', () => {
    const f = formularioDesde(politica())
    expect(f.secciones[0]?.texto).toBe('Uno.\n\nDos.')
    expect(parrafosDesdeTexto(f.secciones[0]?.texto ?? '')).toEqual(['Uno.', 'Dos.'])
  })
})

describe('formulario', () => {
  it('marca como obligatorios sólo los apartados mínimos', () => {
    const f = formularioDesde(politica())
    expect(f.secciones.map((s) => [s.id, s.obligatoria])).toEqual([
      ['finalidad', true],
      ['extra', false],
    ])
  })

  it('sin cambios no hay nada que guardar', () => {
    const f = formularioDesde(politica())
    expect(hayCambios(f, formularioDesde(politica()))).toBe(false)
    f.nombre = 'Otro'
    expect(hayCambios(f, formularioDesde(politica()))).toBe(true)
  })

  it('un cambio de datos sin tocar el texto no envía las secciones (no sube la versión)', () => {
    const original = formularioDesde(politica())
    const f = formularioDesde(politica())
    f.acto_tipo = 'Resolución'
    f.acto_numero = '12'
    f.acto_fecha = '2026-10-01'

    const entrada = entradaDesde(f, original)
    expect(entrada.secciones).toBeUndefined()
    expect(entrada).toMatchObject({ acto_tipo: 'Resolución', acto_numero: '12', acto_fecha: '2026-10-01', version: 1 })
  })

  it('un cambio de texto envía las secciones y deja que el servidor suba la versión', () => {
    const original = formularioDesde(politica())
    const f = formularioDesde(politica())
    f.secciones[0]!.texto = 'Nuevo.\n\nTexto.'

    const entrada = entradaDesde(f, original)
    expect(entrada.secciones?.[0]).toEqual({ id: 'finalidad', titulo: 'Finalidad', parrafos: ['Nuevo.', 'Texto.'] })
    expect('version' in entrada).toBe(false)
  })

  it('si además se cambió la versión a mano, se respeta', () => {
    const original = formularioDesde(politica())
    const f = formularioDesde(politica())
    f.secciones[0]!.texto = 'Nuevo.'
    f.version = 5

    expect(entradaDesde(f, original).version).toBe(5)
  })

  it('los campos vacíos viajan como null y no como cadena vacía', () => {
    const original = formularioDesde(politica())
    const f = formularioDesde(politica())
    f.vigente_desde = '  '
    f.acto_tipo = ''
    expect(entradaDesde(f, original)).toMatchObject({ vigente_desde: null, acto_tipo: null })
  })
})

describe('problemas antes de enviar', () => {
  it('no hay problemas en un formulario completo', () => {
    expect(problemasLocales(formularioDesde(politica()))).toEqual([])
  })

  it('detecta apartados sin texto o sin título, nombre vacío y versión inválida', () => {
    const f = formularioDesde(politica())
    f.nombre = ' '
    f.version = 0
    f.secciones[0]!.texto = '  \n\n'
    f.secciones[1]!.titulo = ''
    const problemas = problemasLocales(f)

    expect(problemas).toHaveLength(4)
    expect(problemas.join(' ')).toContain('«Finalidad» no tiene texto')
    expect(problemas.join(' ')).toContain('«extra» necesita un título')
  })
})

describe('identificador de un apartado nuevo', () => {
  it('sale del título, sin tildes ni símbolos', () => {
    expect(idDeApartado('Garantías y límites', [])).toBe('garantias-y-limites')
    expect(idDeApartado('¿Quién responde?', [])).toBe('quien-responde')
  })

  it('no repite uno existente', () => {
    expect(idDeApartado('Extra', ['extra'])).toBe('extra-2')
    expect(idDeApartado('Extra', ['extra', 'extra-2'])).toBe('extra-3')
  })

  it('un título sin letras da un identificador válido', () => {
    expect(idDeApartado('¿?', [])).toBe('apartado')
  })
})

describe('motivos de un rechazo', () => {
  const rechazo = (data: unknown): AxiosError => {
    const respuesta = { status: 422, data, headers: {}, statusText: '', config: {} } as AxiosResponse
    return new AxiosError('x', 'ERR_BAD_REQUEST', undefined, undefined, respuesta)
  }

  it('muestra los motivos del servidor (los apartados que faltan)', () => {
    const e = rechazo({ message: 'Error de validación', errors: { politica: ['Falta «Finalidad»', 'Hay marcadores'] } })
    expect(motivosDeError(e, 'x')).toEqual(['Falta «Finalidad»', 'Hay marcadores'])
  })

  it('sin errores usa el mensaje; sin respuesta usa el de por defecto', () => {
    expect(motivosDeError(rechazo({ message: 'No autorizado.', errors: null }), 'x')).toEqual(['No autorizado.'])
    expect(motivosDeError(new Error('red'), 'Sin conexión')).toEqual(['Sin conexión'])
  })
})

describe('servicio', () => {
  const peticion = { get: vi.fn(), patch: vi.fn(), post: vi.fn(), delete: vi.fn() }

  beforeEach(() => {
    vi.resetModules()
    for (const f of Object.values(peticion)) f.mockReset()
    vi.doMock('../src/services/http', () => ({
      http: peticion,
      desenvolver: (s: { data: unknown }) => s.data,
    }))
  })

  it('lista, actualiza por slug y consulta los adjuntos de la colección', async () => {
    const servicio = await import('../src/services/politicas')
    peticion.get.mockResolvedValue({ data: { success: true, data: [] } })
    peticion.patch.mockResolvedValue({ data: { success: true, data: { slug: 'uso-de-cookies' } } })

    await servicio.listar()
    expect(peticion.get).toHaveBeenCalledWith('/panel/politicas')

    await servicio.actualizar('uso-de-cookies', { nombre: 'X' })
    expect(peticion.patch).toHaveBeenCalledWith('/panel/politicas/uso-de-cookies', { nombre: 'X' })

    await servicio.documentosAdjuntos('uuid-1')
    expect(peticion.get).toHaveBeenLastCalledWith('/panel/entidad/uuid-1/archivos', {
      params: { collection: 'documento-oficial' },
    })
  })

  it('adjunta con multipart a la colección documento-oficial y quita por uuid', async () => {
    const servicio = await import('../src/services/politicas')
    peticion.post.mockResolvedValue({ data: { success: true, data: { uuid: 'a' } } })
    peticion.delete.mockResolvedValue({ data: {} })

    await servicio.adjuntarDocumento('uuid-1', new File(['%PDF'], 'acto.pdf', { type: 'application/pdf' }))
    const [url, cuerpo, opciones] = peticion.post.mock.calls[0] as [string, FormData, { headers: Record<string, string> }]
    expect(url).toBe('/panel/entidad/uuid-1/archivos')
    expect(cuerpo.get('collection')).toBe('documento-oficial')
    expect((cuerpo.get('archivo') as File).name).toBe('acto.pdf')
    expect(opciones.headers['Content-Type']).toBe('multipart/form-data')

    await servicio.quitarArchivo('abc')
    expect(peticion.delete).toHaveBeenCalledWith('/panel/archivos/abc')
  })
})
