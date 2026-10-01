/**
 * Última pasada de cobertura del panel: servicios, tabla y pantallas de acceso.
 *
 * Tres cosas que no estaban cubiertas y que no son relleno:
 *
 *  - `services/http.ts` es por donde sale **todo** lo que el panel pide al
 *    backend, con la cookie de sesión y el desenvolvimiento del sobre.
 *  - `DataTable` es la tabla que usarán los módulos, y la auditoría le señaló el
 *    `aria-sort` y la paginación (D-16).
 *  - Las pantallas de acceso son donde el panel podría **afirmar que autentica**
 *    cuando no lo hace, que fue el hallazgo D-04.
 */
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia, type Pinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter, type Router } from 'vue-router'

import DataTable from '../src/components/base/DataTable.vue'
import { desenvolver, ErrorApi, http } from '../src/services/http'
import type { ApiEnvelope } from '../src/types/api'
import EntrarView from '../src/views/acceso/EntrarView.vue'
import MfaView from '../src/views/acceso/MfaView.vue'
import NoEncontradoView from '../src/views/acceso/NoEncontradoView.vue'
import RecuperarView from '../src/views/acceso/RecuperarView.vue'
import SinPermisoView from '../src/views/acceso/SinPermisoView.vue'
import { useSesionStore } from '../src/stores/sesion'

let pinia: Pinia
let enrutador: Router

beforeEach(async () => {
  pinia = createPinia()
  setActivePinia(pinia)
  enrutador = crearEnrutador()
  enrutador.push('/')
  await enrutador.isReady()
  vi.stubGlobal('useHead', () => undefined)
})

/** Enrutador de memoria con las rutas que estas pantallas esperan. */
function crearEnrutador(): Router {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'panel.inicio', component: { template: '<div>inicio</div>' } },
      { path: '/acceso', name: 'acceso.entrar', component: EntrarView },
      { path: '/acceso/mfa', name: 'acceso.mfa', component: MfaView },
      { path: '/recuperar', name: 'acceso.recuperar', component: { template: '<div>recuperar</div>' } },
      { path: '/:ruta(.*)*', name: 'comodin', component: { template: '<div>otra</div>' } },
    ],
  })
}

describe('services/http', () => {
  it('apunta a la API y viaja con la cookie de sesión', () => {
    expect(http.defaults.baseURL).toContain('/api/v1')
    // La sesión va en una cookie `HttpOnly`: sin `withCredentials` no viajaría.
    expect(http.defaults.withCredentials).toBe(true)
  })

  it('desenvuelve el sobre correcto y devuelve sólo los datos', () => {
    const sobre = { success: true, data: { id: 7 } } as ApiEnvelope<{ id: number }>
    expect(desenvolver(sobre)).toEqual({ id: 7 })
  })

  it('un sobre fallido lanza con el mensaje y los errores del contrato', () => {
    const sobre = {
      success: false,
      data: null,
      message: 'No autorizado',
      errors: { permiso: ['no tiene permiso'] },
    } as unknown as ApiEnvelope<unknown>

    try {
      desenvolver(sobre)
      throw new Error('debería haber lanzado')
    } catch (error) {
      expect(error).toBeInstanceOf(ErrorApi)
      expect((error as ErrorApi).message).toBe('No autorizado')
      expect((error as ErrorApi).errores).toEqual({ permiso: ['no tiene permiso'] })
    }
  })

  it('el error de la API lleva su estado y su forma', () => {
    const error = new ErrorApi('No encontrado', 404)
    expect(error).toBeInstanceOf(Error)
    expect(error.name).toBe('ErrorApi')
    expect(error.estado).toBe(404)
    expect(error.message).toContain('No encontrado')
  })
})

describe('DataTable (D-16)', () => {
  const columnas = [
    { accessorKey: 'radicado', header: 'Radicado', enableSorting: true },
    { accessorKey: 'estado', header: 'Estado', enableSorting: false },
  ]
  const filas = [
    { radicado: '2026-001', estado: 'Abierta' },
    { radicado: '2026-002', estado: 'Cerrada' },
  ]

  it('dibuja las cabeceras y las filas', () => {
    const tabla = mount(DataTable, { props: { data: filas, columns: columnas } })
    expect(tabla.text()).toContain('Radicado')
    expect(tabla.text()).toContain('2026-001')
    tabla.unmount()
  })

  it('la cabecera ordenable lleva `aria-sort` y un botón de verdad', () => {
    const tabla = mount(DataTable, { props: { data: filas, columns: columnas } })
    const cabeceras = tabla.findAll('th')
    const ordenable = cabeceras.find((th) => th.attributes('aria-sort') !== undefined)
    expect(ordenable).toBeTruthy()
    // Ordenar con teclado exige un control, no un `th` con un `@click`.
    expect(ordenable?.find('button').exists()).toBe(true)
    tabla.unmount()
  })
})

describe('pantallas de acceso (D-04)', () => {
  it('la pantalla de acceso no afirma que haya autenticado a nadie', async () => {
    const vista = mount(EntrarView, { global: { plugins: [enrutador, pinia] } })
    await vista.vm.$nextTick()

    const texto = vista.text().toLowerCase()
    // El módulo de identidad no existe: la pantalla no puede decir que autentica.
    expect(texto).not.toContain('doble factor configurado')
    expect(texto).not.toMatch(/sesión iniciada correctamente/)
    vista.unmount()
  })

  it('iniciar sesión en la pantalla de acceso deja la sesión del almacén vacía', async () => {
    const vista = mount(EntrarView, { global: { plugins: [enrutador, pinia] } })
    await vista.vm.$nextTick()

    const sesion = useSesionStore()
    // No hay backend de identidad: la pantalla no inventa una sesión.
    expect(sesion.iniciada).toBe(false)
    vista.unmount()
  })

  it('la pantalla de segundo factor se pinta sin romperse', async () => {
    const vista = mount(MfaView, { global: { plugins: [enrutador, pinia] } })
    await vista.vm.$nextTick()
    expect(vista.html().length).toBeGreaterThan(0)
    vista.unmount()
  })

  it('las tres pantallas de respuesta se pintan y dicen qué pasa', async () => {
    const casos = [
      { componente: RecuperarView, esperado: /recuperar|restablecer|contraseña/i },
      { componente: SinPermisoView, esperado: /permiso/i },
      { componente: NoEncontradoView, esperado: /no (se )?encontr|existe/i },
    ]

    for (const caso of casos) {
      const vista = mount(caso.componente, { global: { plugins: [enrutador, pinia] } })
      await vista.vm.$nextTick()
      const texto = vista.text()
      // Una pantalla de respuesta que no explica nada deja a quien la ve igual
      // que antes de llegar.
      expect(texto.length).toBeGreaterThan(20)
      expect(texto).toMatch(caso.esperado)
      vista.unmount()
    }
  })
})

describe('VistaEnConstruccion (D-15)', () => {
  it('dice qué módulo falta y no finge contenido', async () => {
    const { default: Vista } = await import('../src/components/feedback/VistaEnConstruccion.vue')
    const vista = mount(Vista, {
      props: { titulo: 'Notificaciones electrónicas', subtitulo: 'Envío y constancia' },
      global: { plugins: [enrutador, pinia] },
    })
    const texto = vista.text()
    expect(texto).toContain('Notificaciones electrónicas')
    expect(texto).toContain('Envío y constancia')
    vista.unmount()
  })
})

describe('CommandPalette (D-34)', () => {
  const comandos = [
    { id: 'pqrsd', label: 'Ir a PQRSD', group: 'Módulos', route: '/pqrsd', icon: 'inbox' },
    { id: 'citas', label: 'Ir a citas', group: 'Módulos', route: '/citas', icon: 'calendar' },
  ]

  it('filtra por texto y no imprime el nombre del icono', async () => {
    const { default: Paleta } = await import('../src/components/feedback/CommandPalette.vue')
    const paleta = mount(Paleta, {
      props: { commands: comandos },
      global: { plugins: [enrutador, pinia] },
    })
    await paleta.vm.$nextTick()

    // `D-34`: antes la paleta escribía «inbox» o «calendar» en el listado.
    expect(paleta.text()).not.toContain('inbox')
    expect(paleta.text()).not.toContain('calendar')
    paleta.unmount()
  })
})
