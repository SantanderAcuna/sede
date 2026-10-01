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

beforeEach(() => {
  vi.stubGlobal('useHead', () => undefined)
  pinia = createPinia()
  setActivePinia(pinia)
})

/** Inicia sesión con todos los permisos declarados, para poder recorrer el panel. */
function entrarConTodo(): void {
  const sesion = useSesionStore()
  sesion.iniciarSesion({
    nombre: 'Verificación',
    permisos: [...Object.values(PERMISO_POR_RUTA), 'sin-permiso'],
  })
}

describe('guardias de navegación (D-04, D-42)', () => {
  it('un invitado que pide un módulo acaba en la pantalla de acceso', async () => {
    await enrutador.push('/pqrsd')
    await enrutador.isReady()

    // Sin sesión, la guardia devuelve a la entrada: el panel no se abre.
    expect(enrutador.currentRoute.value.name).toBe('acceso.entrar')
  })

  it('con sesión y permiso, el módulo se abre', async () => {
    entrarConTodo()
    await enrutador.push('/pqrsd')
    await enrutador.isReady()

    expect(enrutador.currentRoute.value.path).toBe('/pqrsd')
  })

  it('con sesión pero sin el permiso del módulo, se avisa en vez de abrirlo', async () => {
    const sesion = useSesionStore()
    sesion.iniciarSesion({ nombre: 'Sin permisos', permisos: [] })
    await enrutador.push('/tramites')
    await enrutador.isReady()

    expect(enrutador.currentRoute.value.name).toBe('sin-permiso')
  })

  it('un invitado no puede quedarse en la pantalla de acceso si ya entró', async () => {
    entrarConTodo()
    await enrutador.push('/acceso')
    await enrutador.isReady()

    expect(enrutador.currentRoute.value.name).not.toBe('acceso.entrar')
  })
})

describe('títulos y recorrido de todos los módulos', () => {
  it('cada módulo se abre y titula la pestaña (D-47)', async () => {
    entrarConTodo()
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
  })

  it('una dirección que no existe cae en el comodín, no en un módulo', async () => {
    entrarConTodo()
    await enrutador.push('/modulo-que-no-existe')
    await enrutador.isReady()

    const ruta = enrutador.currentRoute.value
    expect(ruta.matched.length).toBeGreaterThan(0)
    expect(ruta.meta?.requiereSesion).toBeUndefined()
  })
})
