/**
 * Servicio HTTP — tests unitarios.
 *
 * Cubre las funciones exportadas de http.ts que no dependen del cliente axios
 * instalado: ErrorApi, desenvolver, desenvolverPagina, esErrorApi.
 * El cliente axios y sus interceptores se verifican indirectamente a través
 * de los tests de sesion.ts y auth.ts que mockean el módulo.
 */
import { AxiosError } from 'axios'
import { describe, expect, it } from 'vitest'

import type { ApiEnvelope, PaginatedEnvelope } from '../src/types/api'
import { ErrorApi, http } from '../src/services/http'
import { desenvolver, desenvolverPagina, esErrorApi, mensajeDeError } from '../src/services/http'

// ---------------------------------------------------------------------------
// ErrorApi
// ---------------------------------------------------------------------------
describe('ErrorApi', () => {
  it('construye con mensaje y estado', () => {
    const error = new ErrorApi('No encontrado', 404)
    expect(error.message).toBe('No encontrado')
    expect(error.estado).toBe(404)
    expect(error.name).toBe('ErrorApi')
  })

  it('incluye errores de validación cuando se proveen', () => {
    const errores = { email: ['El campo es requerido.'], password: ['Muy corta.'] }
    const error = new ErrorApi('Error de validación', 422, errores)
    expect(error.errores).toEqual(errores)
    expect(error.errores?.email).toContain('El campo es requerido.')
  })

  it('errores son null por defecto', () => {
    const error = new ErrorApi('Error genérico', 500)
    expect(error.errores).toBeNull()
  })

  it('es una instancia de Error', () => {
    const error = new ErrorApi('Fallo', 500)
    expect(error instanceof Error).toBe(true)
    expect(error instanceof ErrorApi).toBe(true)
  })
})

// ---------------------------------------------------------------------------
// desenvolver
// ---------------------------------------------------------------------------
describe('desenvolver', () => {
  it('devuelve los datos cuando success=true', () => {
    const sobre: ApiEnvelope<string> = {
      success: true,
      message: null,
      data: 'valor',
      errors: null,
    }
    expect(desenvolver(sobre)).toBe('valor')
  })

  it('devuelve los datos cuando success=true con mensaje', () => {
    const sobre: ApiEnvelope<{ id: number }> = {
      success: true,
      message: 'Creado',
      data: { id: 42 },
      errors: null,
    }
    expect(desenvolver(sobre)).toEqual({ id: 42 })
  })

  it('lanza ErrorApi cuando success=false', () => {
    const sobre: ApiEnvelope<never> = {
      success: false,
      message: 'No autorizado',
      data: null,
      errors: null,
    }
    expect(() => desenvolver(sobre)).toThrow(ErrorApi)
    expect(() => desenvolver(sobre)).toThrow('No autorizado')
  })

  it('lanza ErrorApi con estado 200 cuando success=false y message=null', () => {
    const sobre: ApiEnvelope<never> = {
      success: false,
      message: null,
      data: null,
      errors: null,
    }
    try {
      desenvolver(sobre)
    } catch (e) {
      expect(e).toBeInstanceOf(ErrorApi)
      expect((e as ErrorApi).estado).toBe(200)
    }
  })

  it('pasa los errores de validación al ErrorApi', () => {
    const errores = { nombre: ['El campo es obligatorio.'] }
    const sobre: ApiEnvelope<never> = {
      success: false,
      message: 'Validation failed',
      data: null,
      errors: errores,
    }
    try {
      desenvolver(sobre)
    } catch (e) {
      expect(e).toBeInstanceOf(ErrorApi)
      expect((e as ErrorApi).errores).toEqual(errores)
    }
  })
})

// ---------------------------------------------------------------------------
// desenvolverPagina
// ---------------------------------------------------------------------------
describe('desenvolverPagina', () => {
  const paginaSobre: PaginatedEnvelope<string> = {
    success: true,
    message: null,
    data: ['item-a', 'item-b'],
    meta: {
      current_page: 1,
      from: 1,
      last_page: 3,
      per_page: 15,
      to: 2,
      total: 45,
    },
    links: {
      first: '/?page=1',
      last: '/?page=3',
      prev: null,
      next: '/?page=2',
    },
    errors: null,
  }

  it('devuelve datos, meta y enlaces separados', () => {
    const resultado = desenvolverPagina(paginaSobre)
    expect(resultado.datos).toEqual(['item-a', 'item-b'])
    expect(resultado.meta.total).toBe(45)
    expect(resultado.meta.last_page).toBe(3)
  })

  it('incluye enlaces de paginación', () => {
    const resultado = desenvolverPagina(paginaSobre)
    expect(resultado.enlaces.first).toBe('/?page=1')
    expect(resultado.enlaces.last).toBe('/?page=3')
    expect(resultado.enlaces.next).toBe('/?page=2')
    expect(resultado.enlaces.prev).toBeNull()
  })

  it('lanza ErrorApi cuando success=false', () => {
    const sobreFallido: PaginatedEnvelope<never> = {
      success: false,
      message: 'Error del servidor',
      data: [],
      meta: { current_page: 1, from: 1, last_page: 1, per_page: 15, to: 0, total: 0 },
      links: { first: null, last: null, prev: null, next: null },
      errors: null,
    }
    expect(() => desenvolverPagina(sobreFallido)).toThrow(ErrorApi)
    expect(() => desenvolverPagina(sobreFallido)).toThrow('Error del servidor')
  })
})

// ---------------------------------------------------------------------------
// esErrorApi
// ---------------------------------------------------------------------------
describe('esErrorApi', () => {
  it('devuelve false para errores que no son AxiosError', () => {
    expect(esErrorApi(new Error('genérico'))).toBe(false)
    expect(esErrorApi({ code: 'ERR_NETWORK' })).toBe(false)
    expect(esErrorApi(null)).toBe(false)
    expect(esErrorApi(undefined)).toBe(false)
    expect(esErrorApi('texto')).toBe(false)
  })

  it('devuelve false para objetos sin la estructura de AxiosError', () => {
    // Un objeto que parece un error pero no tiene isAxiosError
    const fakeError = { message: 'falso', response: { status: 401 } }
    expect(esErrorApi(fakeError)).toBe(false)
  })

  it('devuelve true cuando el error es un AxiosError', () => {
    // Crear un AxiosError mockeado que pase la verificación de axios.isAxiosError.
    const mockError = new AxiosError('Request failed', 'ERR_BAD_REQUEST', {}, null)
    expect(esErrorApi(mockError)).toBe(true)
  })
})

// ---------------------------------------------------------------------------
// mensajeDeError (H-14, AC-13.3)
// ---------------------------------------------------------------------------
describe('mensajeDeError()', () => {
  function errorConRespuesta(status: number, data: unknown, headers: Record<string, string> = {}): AxiosError {
    return new AxiosError('x', 'ERR_BAD_REQUEST', undefined, undefined, {
      status,
      statusText: '',
      data,
      headers,
      config: {} as never,
    })
  }

  it('usa el mensaje del servidor cuando lo hay', () => {
    const e = errorConRespuesta(401, { success: false, message: 'Las credenciales no son válidas.' })
    expect(mensajeDeError(e)).toBe('Las credenciales no son válidas.')
  })

  it('ante un 429 dice cuándo volver a intentarlo, en segundos', () => {
    const e = errorConRespuesta(429, { success: false, message: 'Demasiadas solicitudes.' }, { 'retry-after': '42' })
    expect(mensajeDeError(e)).toBe('Demasiados intentos. Vuelve a intentarlo en 42 s.')
  })

  it('ante un 429 con espera larga la da en minutos', () => {
    const e = errorConRespuesta(429, null, { 'retry-after': '90' })
    expect(mensajeDeError(e)).toBe('Demasiados intentos. Vuelve a intentarlo en 2 min.')
  })

  it('ante un 429 sin Retry-After no inventa un plazo', () => {
    const e = errorConRespuesta(429, null)
    expect(mensajeDeError(e)).toBe('Demasiados intentos. Espera un momento antes de volver a intentarlo.')
  })

  it('sin respuesta (red caída) devuelve el texto por defecto', () => {
    expect(mensajeDeError(new AxiosError('Network Error'))).toBe('No se pudo conectar con el servidor.')
    expect(mensajeDeError(new Error('otra cosa'), 'Falló.')).toBe('Falló.')
  })

  it('un 429 llega a quien llamó y no cambia la ubicación (el interceptor ya no redirige)', async () => {
    const adaptadorOriginal = http.defaults.adapter
    http.defaults.adapter = async (config) => {
      throw new AxiosError('x', 'ERR_BAD_REQUEST', config, undefined, {
        status: 429,
        statusText: '',
        data: { success: false, message: 'Demasiadas solicitudes.' },
        headers: { 'retry-after': '30' },
        config,
      })
    }
    const antes = window.location.href

    try {
      await expect(http.get('/cualquiera')).rejects.toSatisfy((e: unknown) => mensajeDeError(e).includes('30 s'))
      expect(window.location.href).toBe(antes)
    } finally {
      http.defaults.adapter = adaptadorOriginal
    }
  })
})
