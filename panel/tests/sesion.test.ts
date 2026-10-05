/**
 * Store de sesión — tests del store real con Pinia.
 *
 * Se testa el store real importando useSesionStore. Las funciones auxiliares
 * de localStorage se testean indirectamente a través de iniciarSesion,
 * cerrarSesion e init.
 */
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { useSesionStore } from '../src/stores/sesion'
import * as authModule from '../src/services/auth'

// ---------------------------------------------------------------------------
// Mock del módulo auth — definido DENTRO del factory (evita elevación).
// ---------------------------------------------------------------------------
vi.mock('../src/services/auth', () => ({
  login: vi.fn(),
  logout: vi.fn(),
  perfil: vi.fn(),
}))

const mockUsuario = {
  id: 1,
  email: 'admin@santamarta.gov.co',
  mfa_habilitado: false,
  estado: 'activo',
  roles: [
    { id: 1, type: 'rol' as const, nombre: 'super-admin', permisos: [] },
  ],
}

const mockPerfil = {
  id: 1,
  type: 'usuario' as const,
  email: 'admin@santamarta.gov.co',
  mfa_habilitado: false,
  estado: 'activo',
  roles: [
    { id: 1, type: 'rol' as const, nombre: 'super-admin', permisos: [] },
  ],
}

let store: ReturnType<typeof useSesionStore>

function freshStore() {
  const pinia = createPinia()
  setActivePinia(pinia)
  const s = useSesionStore()
  // Forzar estado inicial conocido — el store no exporta inicializado.
  // @ts-ignore
  s.inicializado = false
  return s
}

beforeEach(() => {
  vi.clearAllMocks()
  localStorage.clear()
  store = freshStore()
})

// ---------------------------------------------------------------------------
// Estado inicial
// ---------------------------------------------------------------------------
describe('estado inicial', () => {
  it('inicia sin usuario ni token', () => {
    expect(store.usuario).toBeNull()
    expect(store.token).toBeNull()
  })

  it('no está inicializado', () => {
    expect(store.inicializado).toBe(false)
  })

  it('iniciada es false cuando no hay usuario', () => {
    expect(store.iniciada).toBe(false)
  })
})

// ---------------------------------------------------------------------------
// tienePermiso
// ---------------------------------------------------------------------------
describe('tienePermiso', () => {
  it('sin permiso declarado devuelve true (ruta pública)', () => {
    expect(store.tienePermiso(undefined)).toBe(true)
    expect(store.tienePermiso('')).toBe(true)
  })

  it('con wildcard (*) todo está permitido', () => {
    // @ts-ignore — forzamos el estado directamente.
    store.usuario = { id: 1, email: 'a@b.co', nombre: 'A', permisos: ['*'] }
    expect(store.tienePermiso('cualquiera')).toBe(true)
    expect(store.tienePermiso('otro.modulo')).toBe(true)
  })

  it('verifica permisos específicos correctamente', () => {
    // @ts-ignore
    store.usuario = { id: 1, email: 'a@b.co', nombre: 'A', permisos: ['pqrsd.ver', 'tramites.ver'] }
    expect(store.tienePermiso('pqrsd.ver')).toBe(true)
    expect(store.tienePermiso('tramites.ver')).toBe(true)
    expect(store.tienePermiso('usuarios.ver')).toBe(false)
  })
})

// ---------------------------------------------------------------------------
// iniciarSesion
// ---------------------------------------------------------------------------
describe('iniciarSesion', () => {
  it('guarda token y usuario tras login exitoso', async () => {
    vi.mocked(authModule.login).mockResolvedValueOnce({
      require_mfa: false,
      mfa_token: null,
      csrf_token: 'tok-abc-123',
      user: mockUsuario,
    })

    await store.iniciarSesion({ email: 'admin@test.co', password: 'pass' })

    expect(store.token).toBe('tok-abc-123')
    expect(store.usuario).not.toBeNull()
    expect(store.usuario?.email).toBe('admin@santamarta.gov.co')
  })

  it('persiste en localStorage tras login', async () => {
    vi.mocked(authModule.login).mockResolvedValueOnce({
      require_mfa: false,
      mfa_token: null,
      csrf_token: 'tok-xyz',
      user: mockUsuario,
    })

    await store.iniciarSesion({ email: 'admin@test.co', password: 'pass' })

    const stored = localStorage.getItem('sede.panel.sesion')
    expect(stored).not.toBeNull()
    const parsed = JSON.parse(stored!)
    expect(parsed.token).toBe('tok-xyz')
  })

  it('marca la sesión como iniciada', async () => {
    vi.mocked(authModule.login).mockResolvedValueOnce({
      require_mfa: false,
      mfa_token: null,
      csrf_token: 'tok',
      user: mockUsuario,
    })

    await store.iniciarSesion({ email: 'a@b.co', password: 'p' })
    expect(store.iniciada).toBe(true)
  })
})

// ---------------------------------------------------------------------------
// cerrarSesion
// ---------------------------------------------------------------------------
describe('cerrarSesion', () => {
  it('borra token, usuario y marca como no inicializado', async () => {
    vi.mocked(authModule.login).mockResolvedValueOnce({
      require_mfa: false,
      mfa_token: null,
      csrf_token: 'tok',
      user: mockUsuario,
    })
    await store.iniciarSesion({ email: 'a@b.co', password: 'p' })
    vi.mocked(authModule.logout).mockResolvedValueOnce(undefined)

    await store.cerrarSesion()

    expect(store.token).toBeNull()
    expect(store.usuario).toBeNull()
    expect(store.inicializado).toBe(false)
    expect(localStorage.getItem('sede.panel.sesion')).toBeNull()
  })

  it('limpia localStorage aunque logout falle (el error propagaga)', async () => {
    vi.mocked(authModule.login).mockResolvedValueOnce({
      require_mfa: false,
      mfa_token: null,
      csrf_token: 'tok',
      user: mockUsuario,
    })
    await store.iniciarSesion({ email: 'a@b.co', password: 'p' })
    vi.mocked(authModule.logout).mockRejectedValueOnce(new Error('Network error'))

    // El error PROPAGA después del finally — localStorage ya fue borrado.
    await expect(store.cerrarSesion()).rejects.toThrow('Network error')
    expect(store.token).toBeNull()
    expect(localStorage.getItem('sede.panel.sesion')).toBeNull()
  })
})

// ---------------------------------------------------------------------------
// init — restauración de sesión
// ---------------------------------------------------------------------------
describe('init', () => {
  it('con sesión persistida la restaura y verifica con el servidor', async () => {
    localStorage.setItem(
      'sede.panel.sesion',
      JSON.stringify({ token: 'tok-valido', usuario: { id: 5, email: 'x@y.co', nombre: 'X', permisos: [] } })
    )
    vi.mocked(authModule.perfil).mockResolvedValueOnce(mockPerfil)

    await store.init()

    expect(store.token).toBe('tok-valido')
    expect(store.usuario).not.toBeNull()
    expect(store.inicializado).toBe(true)
  })

  it('si la verificación del servidor falla limpia la sesión', async () => {
    localStorage.setItem(
      'sede.panel.sesion',
      JSON.stringify({ token: 'tok-invalido', usuario: { id: 1, email: 'a@b.co', nombre: 'A', permisos: [] } })
    )
    vi.mocked(authModule.perfil).mockRejectedValueOnce(new Error('401'))

    await store.init()

    expect(store.token).toBeNull()
    expect(store.usuario).toBeNull()
    expect(localStorage.getItem('sede.panel.sesion')).toBeNull()
  })

  it('es idempotente — llamada doble no duplica petición al servidor', async () => {
    localStorage.setItem(
      'sede.panel.sesion',
      JSON.stringify({ token: 'tok', usuario: { id: 1, email: 'a@b.co', nombre: 'A', permisos: [] } })
    )
    vi.mocked(authModule.perfil).mockResolvedValueOnce(mockPerfil)

    await store.init()
    await store.init()

    expect(vi.mocked(authModule.perfil)).toHaveBeenCalledTimes(1)
  })

  it('sin sesión persistida igual verifica con el servidor (cookie cookie)', async () => {
    // Incluso sin localStorage, el navegador envía la cookie de sesión.
    // init() debe verificar con el servidor.
    vi.mocked(authModule.perfil).mockResolvedValueOnce(mockPerfil)
    await store.init()
    expect(vi.mocked(authModule.perfil)).toHaveBeenCalledTimes(1)
  })
})

// ---------------------------------------------------------------------------
// permisos computados
// ---------------------------------------------------------------------------
describe('permisos computados', () => {
  it('con permisos específicos los expone en el set', async () => {
    vi.mocked(authModule.login).mockResolvedValueOnce({
      require_mfa: false,
      mfa_token: null,
      csrf_token: 'tok',
      user: {
        id: 1,
        email: 'op@test.co',
        mfa_habilitado: false,
        estado: 'activo',
        roles: [{ id: 1, type: 'rol', nombre: 'operador', permisos: ['pqrsd.ver'] }],
      },
    })
    await store.iniciarSesion({ email: 'op@test.co', password: 'p' })
    expect(store.permisos.has('pqrsd.ver')).toBe(true)
    expect(store.permisos.has('tramites.ver')).toBe(false)
  })

  it('con rol super-admin el permiso wildcard se infiere', async () => {
    // El permiso wildcard para super-admin se verifica en tienePermiso.
    vi.mocked(authModule.login).mockResolvedValueOnce({
      require_mfa: false,
      mfa_token: null,
      csrf_token: 'tok',
      user: {
        id: 1,
        email: 'admin@test.co',
        mfa_habilitado: false,
        estado: 'activo',
        roles: [{ id: 1, type: 'rol', nombre: 'super-admin', permisos: [] }],
      },
    })
    await store.iniciarSesion({ email: 'admin@test.co', password: 'p' })
    expect(store.tienePermiso('cualquiera')).toBe(true)
  })

  it('con permisos específicos los expone en el set', async () => {
    vi.mocked(authModule.login).mockResolvedValueOnce({
      require_mfa: false,
      mfa_token: null,
      csrf_token: 'tok',
      user: {
        id: 1,
        email: 'op@test.co',
        mfa_habilitado: false,
        estado: 'activo',
        roles: [{ id: 1, type: 'rol', nombre: 'operador', permisos: ['pqrsd.ver'] }],
      },
    })
    await store.iniciarSesion({ email: 'op@test.co', password: 'p' })
    expect(store.permisos.has('pqrsd.ver')).toBe(true)
    expect(store.permisos.has('tramites.ver')).toBe(false)
  })

  it('sin usuario el set está vacío', () => {
    store.$patch({ usuario: null })
    expect(store.permisos.size).toBe(0)
  })
})
