/**
 * Almacén de sesión (D-04).
 *
 * Lo que se prueba aquí es la propiedad que hace honesto el panel: **sin
 * autenticación no hay sesión**, y sin sesión no hay permisos. Es la razón por la
 * que las 18 rutas administrativas no se pueden abrir hoy, y conviene que esté
 * fijado por una prueba para que nadie «arregle» el panel dejando la guardia
 * pasando por defecto.
 */
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { useSesionStore } from '../src/stores/sesion'

vi.mock('../src/services/auth', () => {
  const mockUser = {
    id: 99,
    type: 'usuario' as const,
    email: 'test@santamarta.gov.co',
    estado: 'activo' as const,
    mfa_habilitado: false,
    roles: [
      {
        id: 1,
        type: 'rol' as const,
        nombre: 'test-admin',
        permisos: ['normativa.editar', 'panel-administrative'] as string[],
      },
    ],
  }
  return {
    login: vi.fn().mockResolvedValue({
      require_mfa: false,
      mfa_token: null,
      csrf_token: 'test-token-12345',
      user: mockUser,
    }),
    logout: vi.fn().mockResolvedValue(undefined),
    perfil: vi.fn().mockResolvedValue({
      id: 99,
      type: 'usuario' as const,
      email: 'test@santamarta.gov.co',
      estado: 'activo' as const,
      mfa_habilitado: false,
      roles: mockUser.roles,
    }),
  }
})

beforeEach(() => {
  vi.clearAllMocks()
  localStorage.clear()
  setActivePinia(createPinia())
})

describe('sesión sin autenticación', () => {
  it('empieza vacía: el panel no puede afirmar que hay sesión', () => {
    const sesion = useSesionStore()

    expect(sesion.usuario).toBeNull()
    expect(sesion.iniciada).toBe(false)
  })

  it('sin sesión no se tiene ningún permiso', () => {
    const sesion = useSesionStore()

    expect(sesion.tienePermiso('pqrsd.ver')).toBe(false)
    expect(sesion.tienePermiso('panel-administrative')).toBe(false)
  })

  it('una ruta sin permiso declarado se considera pública dentro del panel', () => {
    const sesion = useSesionStore()

    expect(sesion.tienePermiso(undefined)).toBe(true)
    expect(sesion.tienePermiso('')).toBe(true)
  })
})

describe('punto de entrada de la identidad real', () => {
  it('iniciar sesión marca la sesión y habilita sus permisos', async () => {
    const sesion = useSesionStore()

    await sesion.iniciarSesion({ email: 'test@santamarta.gov.co', password: 'test' })

    expect(sesion.iniciada).toBe(true)
    expect(sesion.tienePermiso('normativa.editar')).toBe(true)
    expect(sesion.tienePermiso('usuarios.gestionar')).toBe(false)
  })

  it('cerrar sesión deja el almacén como estaba', async () => {
    const sesion = useSesionStore()
    await sesion.iniciarSesion({ email: 'test@santamarta.gov.co', password: 'test' })

    await sesion.cerrarSesion()

    expect(sesion.usuario).toBeNull()
    expect(sesion.iniciada).toBe(false)
    expect(sesion.tienePermiso('panel-administrative')).toBe(false)
  })

  it('persiste en el almacenamiento del navegador para sobrevivir a F5', async () => {
    const sesion = useSesionStore()
    await sesion.iniciarSesion({ email: 'test@santamarta.gov.co', password: 'test' })

    // La sesión persiste en localStorage para sobrevivir a recargas del navegador.
    // Esto es deliberado: el requerimiento funcional exige que F5 no pierda la sesión.
    expect(Object.keys(localStorage).length).toBeGreaterThan(0)
  })
})
