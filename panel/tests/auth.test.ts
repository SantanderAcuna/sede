/**
 * Servicio de autenticación — tests unitarios.
 *
 * Cubre login(), logout() y perfil() de auth.ts, y las interfaces del módulo.
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'

// Mock del fetch global para que csrf() (que usa fetch) no falle en jsdom.
// En jsdom no hay `location.origin` y `fetch('/ruta')` requiere URL absoluta.
const mockFetch = vi.fn().mockResolvedValue({
  ok: true,
  status: 204,
  text: async () => '',
  json: async () => ({}),
  headers: new Headers(),
} as never)
vi.stubGlobal('fetch', mockFetch)

// ---------------------------------------------------------------------------
// Mock del módulo http — definido DENTRO del factory para evitar elevación.
// ---------------------------------------------------------------------------
vi.mock('../src/services/http', () => {
  const mockUser = {
    id: 1,
    type: 'usuario' as const,
    email: 'admin@santamarta.gov.co',
    mfa_habilitado: false,
    estado: 'activo',
    roles: [
      {
        id: 1,
        type: 'rol' as const,
        nombre: 'super-admin',
        permisos: ['*'],
      },
    ],
  }

  const mockPerfil = {
    id: 1,
    type: 'usuario' as const,
    email: 'admin@santamarta.gov.co',
    mfa_habilitado: false,
    estado: 'activo',
    roles: [
      {
        id: 1,
        type: 'rol' as const,
        nombre: 'super-admin',
        permisos: ['*'],
      },
    ],
  }

  return {
    http: {
      post: vi.fn(),
      get: vi.fn(),
    },
    desenvolver: vi.fn((sobre) => {
      if (!sobre.success || sobre.data === null) throw new Error(sobre.message ?? 'Error')
      return sobre.data
    }),
  }
})

import { login, logout, perfil } from '../src/services/auth'
import { http } from '../src/services/http'

beforeEach(() => {
  vi.clearAllMocks()
  mockFetch.mockClear()
  mockFetch.mockResolvedValue({
    ok: true,
    status: 204,
    text: async () => '',
    json: async () => ({}),
    headers: new Headers(),
  } as never)
})

// ---------------------------------------------------------------------------
// login
// ---------------------------------------------------------------------------
describe('login()', () => {
  it('llama a POST /panel/login con credenciales', async () => {
    vi.mocked(http.post).mockResolvedValueOnce({
      data: {
        success: true,
        message: null,
        data: {
          require_mfa: false,
          mfa_token: null,
          csrf_token: 'csrf-abc123',
          user: null,
        },
        errors: null,
      },
    } as never)

    await login({ email: 'admin@test.co', password: 'secret' })

    expect(http.post).toHaveBeenCalledWith('/panel/login', {
      email: 'admin@test.co',
      password: 'secret',
    })
  })

  it('devuelve require_mfa=false cuando 2FA no está habilitado', async () => {
    vi.mocked(http.post).mockResolvedValueOnce({
      data: {
        success: true,
        message: null,
        data: {
          require_mfa: false,
          mfa_token: null,
          csrf_token: 'csrf-abc',
          user: null,
        },
        errors: null,
      },
    } as never)

    const respuesta = await login({ email: 'a@b.co', password: 'pass' })
    expect(respuesta.require_mfa).toBe(false)
    expect(respuesta.mfa_token).toBeNull()
  })

  it('devuelve mfa_token cuando 2FA está habilitado', async () => {
    vi.mocked(http.post).mockResolvedValueOnce({
      data: {
        success: true,
        message: null,
        data: {
          require_mfa: true,
          mfa_token: 'mfa-token-xyz',
          csrf_token: 'csrf-xyz',
          user: null,
        },
        errors: null,
      },
    } as never)

    const respuesta = await login({ email: 'a@b.co', password: 'pass' })
    expect(respuesta.require_mfa).toBe(true)
    expect(respuesta.mfa_token).toBe('mfa-token-xyz')
  })

  it('incluye el usuario en la respuesta', async () => {
    const usuario = {
      id: 5,
      type: 'usuario' as const,
      email: 'operador@test.co',
      mfa_habilitado: false,
      estado: 'activo',
      roles: [
        { id: 2, type: 'rol' as const, nombre: 'operador', permisos: ['pqrsd.ver'] },
      ],
    }

    vi.mocked(http.post).mockResolvedValueOnce({
      data: {
        success: true,
        message: null,
        data: { require_mfa: false, mfa_token: null, csrf_token: 'tok', user: usuario },
        errors: null,
      },
    } as never)

    const respuesta = await login({ email: 'operador@test.co', password: 'pass' })
    expect(respuesta.user).not.toBeNull()
    expect(respuesta.user?.email).toBe('operador@test.co')
    expect(respuesta.user?.roles[0].nombre).toBe('operador')
  })
})

// ---------------------------------------------------------------------------
// logout
// ---------------------------------------------------------------------------
describe('logout()', () => {
  it('llama a POST /panel/logout', async () => {
    vi.mocked(http.post).mockResolvedValueOnce({ data: { success: true } } as never)
    await logout()
    expect(http.post).toHaveBeenCalledWith('/panel/logout')
  })

  it('no devuelve valor (void)', async () => {
    vi.mocked(http.post).mockResolvedValueOnce({ data: { success: true } } as never)
    const resultado = await logout()
    expect(resultado).toBeUndefined()
  })
})

// ---------------------------------------------------------------------------
// perfil
// ---------------------------------------------------------------------------
describe('perfil()', () => {
  it('llama a GET /panel/perfil', async () => {
    vi.mocked(http.get).mockResolvedValueOnce({
      data: {
        success: true,
        message: null,
        data: {
          id: 1,
          type: 'usuario' as const,
          email: 'admin@test.co',
          mfa_habilitado: false,
          estado: 'activo',
          roles: [{ id: 1, type: 'rol' as const, nombre: 'admin', permisos: ['*'] }],
        },
        errors: null,
      },
    } as never)

    await perfil()
    expect(http.get).toHaveBeenCalledWith('/panel/perfil')
  })

  it('devuelve los datos del perfil', async () => {
    const perfilData = {
      id: 3,
      type: 'usuario' as const,
      email: 'jefe@test.co',
      mfa_habilitado: true,
      estado: 'activo',
      roles: [
        { id: 3, type: 'rol' as const, nombre: 'jefe', permisos: ['usuarios.ver', 'pqrsd.editar'] },
      ],
    }

    vi.mocked(http.get).mockResolvedValueOnce({
      data: { success: true, message: null, data: perfilData, errors: null },
    } as never)

    const resultado = await perfil()
    expect(resultado.id).toBe(3)
    expect(resultado.email).toBe('jefe@test.co')
    expect(resultado.mfa_habilitado).toBe(true)
    expect(resultado.roles[0].permisos).toContain('pqrsd.editar')
  })

  it('incluye MFA y estado del usuario', async () => {
    const perfilData = {
      id: 1,
      type: 'usuario' as const,
      email: 'admin@test.co',
      mfa_habilitado: true,
      estado: 'activo',
      roles: [],
    }

    vi.mocked(http.get).mockResolvedValueOnce({
      data: { success: true, message: null, data: perfilData, errors: null },
    } as never)

    const resultado = await perfil()
    expect(resultado.mfa_habilitado).toBe(true)
    expect(resultado.estado).toBe('activo')
  })
})
