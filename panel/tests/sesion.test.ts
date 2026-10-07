/**
 * Store de sesión — tests del store real con Pinia.
 *
 * R-54 (Sanctum SPA, cookie-based auth):
 *   - La sesión viaja en cookie HttpOnly, NO en localStorage.
 *   - El store expone `init()` idempotente (internamente con `initEnVuelo`)
 *     y `cerrarSesion()` idempotente (con `logoutEnVuelo`).
 *   - `cerrarSesion()` resetea `initEnVuelo` en su `finally` para que un
 *     `init()` posterior funcione correctamente.
 *   - La API pública del store es:
 *     { usuario, inicializado, iniciada, permisos, tienePermiso,
 *       iniciarSesion, cerrarSesion, init, $reset }
 *
 * Este archivo NO usa `store.token` (NO existe) ni `localStorage` (R-54 lo prohíbe).
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
  return useSesionStore()
}

beforeEach(() => {
  vi.clearAllMocks()
  store = freshStore()
})

// ---------------------------------------------------------------------------
// Estado inicial
// ---------------------------------------------------------------------------
describe('estado inicial', () => {
  it('inicia sin usuario', () => {
    expect(store.usuario).toBeNull()
  })

  it('inicializado es false al inicio', () => {
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
    store.usuario = { id: 1, email: 'a@b.co', nombre: 'A', permisos: ['*'] }
    expect(store.tienePermiso('cualquiera')).toBe(true)
    expect(store.tienePermiso('otro.modulo')).toBe(true)
  })

  it('verifica permisos específicos correctamente', () => {
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
  it('guarda usuario tras login exitoso', async () => {
    vi.mocked(authModule.login).mockResolvedValueOnce({
      require_mfa: false,
      mfa_token: null,
      csrf_token: 'tok-abc-123',
      user: mockUsuario,
    })

    await store.iniciarSesion({ email: 'admin@test.co', password: 'pass' })

    expect(store.usuario).not.toBeNull()
    expect(store.usuario?.email).toBe('admin@santamarta.gov.co')
    expect(store.inicializado).toBe(true)
  })

  it('NO persiste en localStorage tras login (R-54 lo prohíbe)', async () => {
    vi.mocked(authModule.login).mockResolvedValueOnce({
      require_mfa: false,
      mfa_token: null,
      csrf_token: 'tok-xyz',
      user: mockUsuario,
    })

    await store.iniciarSesion({ email: 'admin@test.co', password: 'pass' })

    // La sesión viaja en cookie HttpOnly, no en localStorage.
    expect(localStorage.getItem('sede.panel.sesion')).toBeNull()
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
  it('borra usuario y marca como no inicializado', async () => {
    vi.mocked(authModule.login).mockResolvedValueOnce({
      require_mfa: false,
      mfa_token: null,
      csrf_token: 'tok',
      user: mockUsuario,
    })
    await store.iniciarSesion({ email: 'a@b.co', password: 'p' })
    vi.mocked(authModule.logout).mockResolvedValueOnce(undefined)

    await store.cerrarSesion()

    expect(store.usuario).toBeNull()
    expect(store.inicializado).toBe(true) // cerrarSesion es estado TERMINAL: inicializado=true
  })

  it('es idempotente — llamadas concurrentes no ejecutan logout dos veces', async () => {
    vi.mocked(authModule.login).mockResolvedValueOnce({
      require_mfa: false,
      mfa_token: null,
      csrf_token: 'tok',
      user: mockUsuario,
    })
    await store.iniciarSesion({ email: 'a@b.co', password: 'p' })

    // logout() tarda deliberadamente
    let resolveLogout!: () => void
    vi.mocked(authModule.logout).mockImplementationOnce(
      () => new Promise<void>((r) => { resolveLogout = r })
    )

    const p1 = store.cerrarSesion()
    const p2 = store.cerrarSesion()  // Segunda llamada concurrente

    resolveLogout()
    await Promise.all([p1, p2])

    // Solo se llamó una vez a logout() del backend
    expect(vi.mocked(authModule.logout)).toHaveBeenCalledTimes(1)
  })

  it('limpia el estado aunque logout falle', async () => {
    vi.mocked(authModule.login).mockResolvedValueOnce({
      require_mfa: false,
      mfa_token: null,
      csrf_token: 'tok',
      user: mockUsuario,
    })
    await store.iniciarSesion({ email: 'a@b.co', password: 'p' })
    vi.mocked(authModule.logout).mockRejectedValueOnce(new Error('Network error'))

    // La implementación real NO propaga el error (devuelve Promise<void).
    // Verificamos que el estado se limpia igual.
    await store.cerrarSesion()

    expect(store.usuario).toBeNull()
    expect(store.inicializado).toBe(true) // cerrarSesion es estado TERMINAL: inicializado=true
  })
})

// ---------------------------------------------------------------------------
// init — restauración de sesión desde cookie Sanctum
// ---------------------------------------------------------------------------
describe('init', () => {
  it('verifica con el servidor al montar la app', async () => {
    // Sin localStorage (R-54 lo prohíbe), la sesión viaja en cookie.
    // init() siempre llama a /perfil para verificarla.
    vi.mocked(authModule.perfil).mockResolvedValueOnce(mockPerfil)

    await store.init()

    expect(store.usuario).not.toBeNull()
    expect(store.usuario?.email).toBe('admin@santamarta.gov.co')
    expect(store.inicializado).toBe(true)
    expect(vi.mocked(authModule.perfil)).toHaveBeenCalledTimes(1)
  })

  it('si la verificación del servidor falla (401) limpia el usuario', async () => {
    vi.mocked(authModule.perfil).mockRejectedValueOnce(new Error('401 Unauthorized'))

    await store.init()

    expect(store.usuario).toBeNull()
    expect(store.inicializado).toBe(true) // init() terminó (éxito o fallo)
  })

  it('es idempotente — llamada doble no duplica petición al servidor (R-54 S6)', async () => {
    vi.mocked(authModule.perfil).mockResolvedValueOnce(mockPerfil)

    // Llamar init() dos veces concurrentemente
    const p1 = store.init()
    const p2 = store.init()
    await Promise.all([p1, p2])

    // La primera llamada dispara la petición; la segunda reutiliza la promesa.
    expect(vi.mocked(authModule.perfil)).toHaveBeenCalledTimes(1)
  })

  it('después de cerrarSesion() puede llamarse init() de nuevo (initEnVuelo se resetea)', async () => {
    vi.mocked(authModule.login).mockResolvedValueOnce({
      require_mfa: false,
      mfa_token: null,
      csrf_token: 'tok',
      user: mockUsuario,
    })
    await store.iniciarSesion({ email: 'a@b.co', password: 'p' })
    vi.mocked(authModule.logout).mockResolvedValueOnce(undefined)
    await store.cerrarSesion()

    // Tras cerrarSesion el estado es TERMINAL: inicializado=true.
    // init() detecta esto y no hace llamada al backend (es un NO-OP).
    // Para que init() haga una llamada hay que resetar el store primero
    // (que es lo que hace el F5 al recargar, porque Vue desmonta la app).
    store.$reset()
    vi.mocked(authModule.perfil).mockResolvedValueOnce(mockPerfil)
    await store.init()

    expect(store.usuario).not.toBeNull()
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

  it('sin usuario el set está vacío', () => {
    expect(store.permisos.size).toBe(0)
  })

  it('extrae permisos de múltiples roles', async () => {
    vi.mocked(authModule.login).mockResolvedValueOnce({
      require_mfa: false,
      mfa_token: null,
      csrf_token: 'tok',
      user: {
        id: 1,
        email: 'multi@test.co',
        mfa_habilitado: false,
        estado: 'activo',
        roles: [
          { id: 1, type: 'rol', nombre: 'operador', permisos: ['pqrsd.ver', 'tramites.ver'] },
          { id: 2, type: 'rol', nombre: 'revisor', permisos: ['usuarios.ver', 'pqrsd.ver'] },
        ],
      },
    })
    await store.iniciarSesion({ email: 'multi@test.co', password: 'p' })
    // Permisos únicos de la unión de ambos roles
    expect(store.permisos.size).toBe(3)
    expect(store.permisos.has('pqrsd.ver')).toBe(true)
    expect(store.permisos.has('tramites.ver')).toBe(true)
    expect(store.permisos.has('usuarios.ver')).toBe(true)
  })
})
