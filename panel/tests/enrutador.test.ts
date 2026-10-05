/**
 * El enrutador de verdad: rutas, guardias y títulos.
 *
 * Tercera pasada de cobertura del panel. Se usa el enrutador real —no un doble—
 * porque lo que hay que comprobar es precisamente lo que él hace: que cada módulo
 * exija sesión y permiso, que un invitado no pueda abrir el panel y que la
 * pestaña lleve el nombre del producto (D-47).
 */
import { createPinia, setActivePinia, type Pinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { PERMISO_POR_RUTA } from '../src/config/permisos'
import enrutador from '../src/router/index'
import { useSesionStore } from '../src/stores/sesion'

let pinia: Pinia

// Permissions granted to the "full access" mock user.
const ALL_PERMISSIONS = [
  'panel-administrative',
  'pqrsd.ver',
  'tramites.ver',
  'citas.ver',
  'notificaciones.ver',
  'sede.publicar',
  'carpeta.ver',
  'autenticacion.gestionar',
  'cms.gestionar',
  'portal.publicar',
  'transparencia.ver',
  'documental.gestionar',
  'sigmi.ver',
  'integraciones.gestionar',
  'usuarios.gestionar',
  'auditoria.ver',
  'reportes.ver',
  'asignacion.gestionar',
  'entidad.gestionar',
  'configuracion.gestionar',
  'perfil.ver',
]

/**
 * Inicia sesión de prueba sin hacer llamadas HTTP.
 *
 * El store tiene `iniciarSesion` que llama a la API. Para los tests del router
 * necesitamos una sesión ya establecida. Esta función manipula el estado interno
 * del store directamente.
 */
function establecerSesion(permisos: string[]): void {
  const sesion = useSesionStore()
  // @ts-ignore — accedemos al estado interno del store para los tests.
  sesion.usuario = {
    id: 99,
    email: 'test@santamarta.gov.co',
    nombre: 'Test User',
    permisos,
  }
  // @ts-ignore
  sesion.token = 'test-token'
  // @ts-ignore
  sesion.inicializado = true
}

/**
 * Limpia la sesión de prueba.
 */
function limpiarSesion(): void {
  const sesion = useSesionStore()
  // @ts-ignore
  sesion.usuario = null
  // @ts-ignore
  sesion.token = null
  // @ts-ignore
  sesion.inicializado = false
}

beforeEach(() => {
  vi.clearAllMocks()
  localStorage.clear()
  vi.stubGlobal('useHead', () => undefined)
  // Stub location.href para que no hayan errores de navegación.
  Object.defineProperty(window, 'location', {
    value: { href: '', pathname: '/', assign: vi.fn(), replace: vi.fn() },
    writable: true,
  })
  pinia = createPinia()
  setActivePinia(pinia)
})

describe('guardias de navegación (D-04, D-42)', () => {
  it('un invitado que pide un módulo acaba en la pantalla de acceso', async () => {
    await enrutador.push('/pqrsd')
    await enrutador.isReady()

    // Sin sesión, la guardia devuelve a la entrada: el panel no se abre.
    expect(enrutador.currentRoute.value.name).toBe('acceso.entrar')
  })

  it('con sesión y permiso, el módulo se abre', async () => {
    establecerSesion(Object.values(PERMISO_POR_RUTA))
    await enrutador.push('/pqrsd')
    await enrutador.isReady()

    expect(enrutador.currentRoute.value.path).toBe('/pqrsd')
    limpiarSesion()
  })

  it('con sesión pero sin el permiso del módulo, se avisa en vez de abrirlo', async () => {
    establecerSesion([]) // Sin permisos.
    await enrutador.push('/tramites')
    await enrutador.isReady()

    expect(enrutador.currentRoute.value.name).toBe('sin-permiso')
    limpiarSesion()
  })

  it('un invitado no puede quedarse en la pantalla de acceso si ya entró', async () => {
    establecerSesion(Object.values(PERMISO_POR_RUTA))
    await enrutador.push('/acceso')
    await enrutador.isReady()

    expect(enrutador.currentRoute.value.name).not.toBe('acceso.entrar')
    limpiarSesion()
  })
})

describe('títulos y recorrido de todos los módulos', () => {
  it('cada módulo se abre y titula la pestaña (D-47)', async () => {
    establecerSesion(Object.values(PERMISO_POR_RUTA))
    const rutas = enrutador
      .getRoutes()
      .filter((ruta) => ruta.meta?.requiereSesion && ruta.meta?.titulo && !ruta.path.includes(':'))

    expect(rutas.length).toBeGreaterThanOrEqual(18)

    for (const ruta of rutas) {
      await enrutador.push(ruta.path)
      await enrutador.isReady()
      expect(enrutador.currentRoute.value.path).toBe(ruta.path)
      // El nombre del producto va siempre delante del título del módulo.
      expect(document.title).toContain('SGDI')
    }
    limpiarSesion()
  })

  it('una dirección que no existe cae en el comodín, no en un módulo', async () => {
    establecerSesion(Object.values(PERMISO_POR_RUTA))
    await enrutador.push('/modulo-que-no-existe')
    await enrutador.isReady()

    const ruta = enrutador.currentRoute.value
    expect(ruta.matched.length).toBeGreaterThan(0)
    expect(ruta.meta?.requiereSesion).toBeUndefined()
    limpiarSesion()
  })
})
