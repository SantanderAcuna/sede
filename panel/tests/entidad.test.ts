/**
 * Tests del servicio de entidad y la vista EntidadView (Vertical Slice 1).
 *
 * Sección 1 — Servicio entidad.ts: mock de http con vi.mock (datos dentro del factory).
 * Sección 2 — Lógica de la vista: tabs, redes sociales, políticas, hayCambios.
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { reactive, ref } from 'vue'

// =============================================================================
// SECCIÓN 1 — Servicio entidad.ts
// =============================================================================

vi.mock('../src/services/http', () => {
  const mockEntidadData = {
    id: 1,
    type: 'entidad' as const,
    nombre: 'Alcaldía Distrital de Santa Marta',
    sigla: 'D.T.C.H.',
    nit: '891.780.009-4',
    direccion: 'Calle 14 No. 2-49, Palacio Municipal',
    municipio: 'Santa Marta',
    departamento: 'Magdalena',
    pais: 'Colombia',
    telefono: 'PBX 4201234',
    linea_atencion: null,
    linea_gratuita: null,
    linea_anticorrupcion: null,
    correo_atencion: 'contacto@santamarta.gov.co',
    correo_notificaciones_judiciales: null,
    horario: 'Lunes a viernes 8am-5pm',
    codigo_postal: '470004',
    dominio: 'https://www.santamarta.gov.co',
    logo: null,
    redes: [] as Array<{ red: string; url: string }>,
    politicas: [] as Array<{ slug: string; nombre: string }>,
    datos_por_confirmar: [] as string[],
    latitud: 11.245,
    longitud: -74.2113,
  }

  return {
    http: {
      get: vi.fn().mockResolvedValue({
        data: { success: true, message: null, data: mockEntidadData, errors: null },
      }),
      patch: vi.fn().mockResolvedValue({
        data: {
          success: true,
          message: 'Entidad actualizada correctamente',
          data: { ...mockEntidadData, nombre: 'Alcaldía Distrital Actualizada' },
          errors: null,
        },
      }),
    },
    desenvolver: vi.fn((sobre) => {
      if (!sobre.success || sobre.data === null) throw new Error(sobre.message ?? 'Error')
      return sobre.data
    }),
    ErrorApi: class ErrorApi extends Error {
      constructor(
        public message: string,
        public estado: number,
        public errores: Record<string, string[]> | null = null
      ) { super(message) }
    },
  }
})

import { obtener, actualizar } from '../src/services/entidad'
import { http } from '../src/services/http'

beforeEach(() => vi.clearAllMocks())

describe('obtener()', () => {
  it('llama a GET /entidad', async () => {
    await obtener()
    expect(http.get).toHaveBeenCalledWith('/entidad')
  })

  it('devuelve los datos de la entidad', async () => {
    const entidad = await obtener()
    expect(entidad.nombre).toBe('Alcaldía Distrital de Santa Marta')
    expect(entidad.nit).toBe('891.780.009-4')
    expect(entidad.sigla).toBe('D.T.C.H.')
  })

  it('incluye coordenadas y contacto', async () => {
    const entidad = await obtener()
    expect(entidad.latitud).toBeCloseTo(11.245)
    expect(entidad.longitud).toBeCloseTo(-74.2113)
    expect(entidad.correo_atencion).toBe('contacto@santamarta.gov.co')
  })

  it('redes y políticas pueden estar vacías', async () => {
    const entidad = await obtener()
    expect(entidad.redes).toEqual([])
    expect(entidad.politicas).toEqual([])
  })
})

describe('actualizar()', () => {
  it('llama a PATCH /panel/entidad con los datos', async () => {
    await actualizar({ nombre: 'Nuevo Nombre', telefono: '123' })
    expect(http.patch).toHaveBeenCalledWith('/panel/entidad', {
      nombre: 'Nuevo Nombre',
      telefono: '123',
    })
  })

  it('acepta actualización parcial', async () => {
    await actualizar({ sigla: 'ADM' })
    expect(http.patch).toHaveBeenCalledWith('/panel/entidad', { sigla: 'ADM' })
  })

  it('devuelve la entidad actualizada', async () => {
    const entidad = await actualizar({ nombre: 'Alcaldía Distrital Actualizada' })
    expect(entidad.nombre).toBe('Alcaldía Distrital Actualizada')
  })

  it('puede actualizar redes sociales', async () => {
    const redes = [{ red: 'facebook' as const, url: 'https://facebook.com/alcaldia' }]
    await actualizar({ redes })
    expect(http.patch).toHaveBeenCalledWith('/panel/entidad', { redes })
  })

  it('puede actualizar políticas', async () => {
    const politicas = [{ slug: 'privacidad', nombre: 'Política de privacidad' }]
    await actualizar({ politicas })
    expect(http.patch).toHaveBeenCalledWith('/panel/entidad', { politicas })
  })

  it('puede actualizar varios campos de contacto', async () => {
    await actualizar({
      telefono: 'PBX 4209999',
      correo_atencion: 'nuevo@santamarta.gov.co',
      linea_atencion: '605 420 9600',
    })
    const llamada = vi.mocked(http.patch).mock.calls[0]
    expect(llamada[1]).toMatchObject({
      telefono: 'PBX 4209999',
      correo_atencion: 'nuevo@santamarta.gov.co',
    })
  })

  it('propaga errores de red', async () => {
    vi.mocked(http.patch).mockRejectedValueOnce(new Error('Network error') as never)
    await expect(actualizar({ nombre: 'Test' })).rejects.toThrow('Network error')
  })
})

// =============================================================================
// SECCIÓN 2 — Lógica de la vista (sin montar el componente, con unit tests puros)
// =============================================================================

/**
 * Simula la lógica de EntidadView.vue para los paths críticos que no requieren
 * montar el componente completo.
 */
describe('lógica de la vista — redes sociales', () => {
  // Simula las ref() y reactive() que usa la vista.
  const redes = ref<Array<{ red: string; url: string }>>([])

  beforeEach(() => { redes.value = [] })

  function agregarRed(red: string, url: string) {
    if (!redes.value.find((r) => r.red === red)) {
      redes.value.push({ red, url })
    }
  }

  function eliminarRed(index: number) {
    redes.value.splice(index, 1)
  }

  it('agregarRed añade una red nueva', () => {
    agregarRed('facebook', 'https://facebook.com/alcaldia')
    expect(redes.value).toHaveLength(1)
    expect(redes.value[0].red).toBe('facebook')
    expect(redes.value[0].url).toBe('https://facebook.com/alcaldia')
  })

  it('agregarRed ignora si la red ya existe (sin duplicados)', () => {
    agregarRed('facebook', 'https://facebook.com/uno')
    agregarRed('facebook', 'https://facebook.com/dos')
    expect(redes.value).toHaveLength(1)
  })

  it('eliminarRed elimina por índice', () => {
    redes.value.push({ red: 'facebook', url: 'https://fb.com' })
    redes.value.push({ red: 'twitter', url: 'https://x.com' })
    eliminarRed(0)
    expect(redes.value).toHaveLength(1)
    expect(redes.value[0].red).toBe('twitter')
  })

  it('varias redes se acumulan correctamente', () => {
    agregarRed('facebook', 'https://facebook.com/a')
    agregarRed('twitter', 'https://x.com/a')
    agregarRed('instagram', 'https://ig.com/a')
    expect(redes.value).toHaveLength(3)
  })
})

describe('lógica de la vista — políticas', () => {
  const politicas = ref<Array<{ slug: string; nombre: string }>>([])

  beforeEach(() => { politicas.value = [] })

  function agregarPolitica(slug: string, nombre: string) {
    if (!politicas.value.find((p) => p.slug === slug)) {
      politicas.value.push({ slug, nombre })
    }
  }

  function eliminarPolitica(index: number) {
    politicas.value.splice(index, 1)
  }

  it('agregarPolitica añade una política nueva', () => {
    agregarPolitica('privacidad', 'Política de privacidad')
    expect(politicas.value).toHaveLength(1)
    expect(politicas.value[0].slug).toBe('privacidad')
  })

  it('agregarPolitica ignora si el slug ya existe', () => {
    agregarPolitica('privacidad', 'Política uno')
    agregarPolitica('privacidad', 'Política dos')
    expect(politicas.value).toHaveLength(1)
  })

  it('eliminarPolitica elimina por índice', () => {
    politicas.value.push({ slug: 'a', nombre: 'Política A' })
    politicas.value.push({ slug: 'b', nombre: 'Política B' })
    eliminarPolitica(0)
    expect(politicas.value).toHaveLength(1)
    expect(politicas.value[0].slug).toBe('b')
  })
})

describe('lógica de la vista — hayCambios', () => {
  // Simula el computed hayCambios de la vista.
  function crearHayCambios(formulario: Record<string, unknown>, original: Record<string, unknown>): boolean {
    return JSON.stringify(formulario) !== JSON.stringify(original)
  }

  it('sin cambios devuelve false', () => {
    const original = { nombre: 'Alcaldía', telefono: '123' }
    const formulario = { nombre: 'Alcaldía', telefono: '123' }
    expect(crearHayCambios(formulario, original)).toBe(false)
  })

  it('con cambios en nombre devuelve true', () => {
    const original = { nombre: 'Alcaldía' }
    const formulario = { nombre: 'Otra Alcaldía' }
    expect(crearHayCambios(formulario, original)).toBe(true)
  })

  it('con cambios en redes devuelve true', () => {
    const original = { redes: [{ red: 'facebook', url: 'https://fb.com/a' }] }
    const formulario = { redes: [{ red: 'facebook', url: 'https://fb.com/b' }] }
    expect(crearHayCambios(formulario, original)).toBe(true)
  })

  it('con cambios en políticas devuelve true', () => {
    const original = { politicas: [{ slug: 'a', nombre: 'A' }] }
    const formulario = { politicas: [{ slug: 'a', nombre: 'B' }] }
    expect(crearHayCambios(formulario, original)).toBe(true)
  })

  it('comparación de arrays detecta adiciones', () => {
    const original = { redes: [] }
    const formulario = { redes: [{ red: 'x', url: 'https://x.com' }] }
    expect(crearHayCambios(formulario, original)).toBe(true)
  })

  it('comparación de arrays detecta eliminaciones', () => {
    const original = { politicas: [{ slug: 'a', nombre: 'A' }] }
    const formulario = { politicas: [] }
    expect(crearHayCambios(formulario, original)).toBe(true)
  })
})

describe('lógica de la vista — construir payload de actualizar', () => {
  // Simula la lógica de construir Partial<EntidadInput> en guardar().
  function construirPayload(formulario: Record<string, unknown>): Record<string, unknown> {
    const datos: Record<string, unknown> = {}
    if (formulario.nombre) datos.nombre = formulario.nombre
    if (formulario.sigla !== undefined) datos.sigla = formulario.sigla
    if (formulario.nit !== undefined) datos.nit = formulario.nit
    if (formulario.direccion !== undefined) datos.direccion = formulario.direccion
    if (formulario.redes && (formulario.redes as unknown[]).length > 0) datos.redes = formulario.redes
    if (formulario.politicas && (formulario.politicas as unknown[]).length > 0) datos.politicas = formulario.politicas
    return datos
  }

  it('solo incluye campos con valor', () => {
    const formulario = { nombre: 'Alcaldía', nit: undefined }
    const payload = construirPayload(formulario)
    expect(payload).toEqual({ nombre: 'Alcaldía' })
  })

  it('incluye redes cuando tiene elementos', () => {
    const formulario = { redes: [{ red: 'facebook', url: 'https://fb.com' }], politicas: [] }
    const payload = construirPayload(formulario)
    expect(payload).toHaveProperty('redes')
    expect((payload.redes as unknown[])).toHaveLength(1)
    expect(payload).not.toHaveProperty('politicas')
  })

  it('excluye redes vacías', () => {
    const formulario = { redes: [] }
    const payload = construirPayload(formulario)
    expect(payload).not.toHaveProperty('redes')
  })

  it('campos undefined se incluyen si !== undefined', () => {
    // El código usa !== undefined, así que null también se incluye.
    const formulario = { sigla: null, nit: '123' }
    const payload = construirPayload(formulario as Record<string, unknown>)
    expect(payload).toHaveProperty('sigla')
    expect(payload.sigla).toBeNull()
  })
})
