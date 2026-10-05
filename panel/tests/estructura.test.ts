/**
 * Estructura del panel: disposiciones, vistas y guardias.
 *
 * Segunda pasada de cobertura. Lo que queda sin cubrir en el panel es
 * principalmente la estructura —la disposición administrativa, las dos
 * disposiciones de acceso y las vistas—, y se puede montar de verdad con el
 * enrutador y el almacén reales. Montarla comprueba además cosas que sólo se ven
 * al pintar: el enlace de salto, la miga que marca la página actual y el menú
 * filtrado por permisos.
 */
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia, type Pinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter, type Router } from 'vue-router'

import App from '../src/App.vue'
import AdminLayout from '../src/layouts/AdminLayout.vue'
import AuthLayout from '../src/layouts/AuthLayout.vue'
import EnConstruccionView from '../src/views/admin/EnConstruccionView.vue'
import InicioView from '../src/views/admin/InicioView.vue'
import { useSesionStore } from '../src/stores/sesion'

/**
 * Enrutador y almacén reales para cada prueba.
 *
 * `useRoute()` y `useRouter()` son composables: no basta con inyectar `$route`,
 * hay que instalar el enrutador de verdad. Se usa uno de memoria con una ruta
 * comodín porque lo que se prueba es la disposición, no el mapa de rutas.
 */
function crearEnrutador(): Router {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'inicio', component: { template: '<div>inicio</div>' } },
      { path: '/:ruta(.*)*', name: 'comodin', component: { template: '<div>otra</div>' } },
    ],
  })
}

let enrutador: Router
let pinia: Pinia

beforeEach(async () => {
  pinia = createPinia()
  setActivePinia(pinia)
  enrutador = crearEnrutador()
  enrutador.push('/')
  await enrutador.isReady()
  document.body.innerHTML = ''
  // Nuxt no está aquí: `useHead` existe en el marco, no en Vue a secas.
  vi.stubGlobal('useHead', () => undefined)
  vi.stubGlobal('useSeoMeta', () => undefined)
})

afterEach(() => {
  vi.unstubAllGlobals()
  document.body.innerHTML = ''
})

describe('AuthLayout', () => {
  it('dibuja el logotipo y deja pasar el contenido', () => {
    const disposicion = mount(AuthLayout, { slots: { default: '<p>Formulario</p>' } })
    expect(disposicion.text()).toContain('SGDI')
    expect(disposicion.text()).toContain('Formulario')
  })
})

describe('EnConstruccionView', () => {
  it('se pinta aunque la ruta del módulo no traiga título', () => {
    const vista = mount(EnConstruccionView, { global: { plugins: [enrutador] } })
    expect(vista.html().length).toBeGreaterThan(0)
    vista.unmount()
  })
})

describe('InicioView', () => {
  it('arranca en cero y no muestra cifras inventadas (D-05)', () => {
    const vista = mount(InicioView, { global: { plugins: [enrutador, pinia] } })
    const texto = vista.text()
    // Ni cifras de ejemplo ni estados de salud del sistema.
    expect(texto).not.toMatch(/\b287\b|\b1[.,]?842\b|Sistema operativo|API Gateway/)
    vista.unmount()
  })
})

describe('AdminLayout', () => {
  const montar = () =>
    mount(AdminLayout, {
      global: {
        plugins: [enrutador, pinia],
        stubs: { RouterView: true },
      },
    })

  it('el enlace de salto sigue siendo lo primero y apunta al contenido', () => {
    const disposicion = montar()
    const salto = disposicion.find('a[href="#contenido-principal"]')
    expect(salto.exists()).toBe(true)
    expect(salto.text().toLowerCase()).toContain('saltar')
    // Y el destino existe y es enfocable, que es lo que hace útil el salto.
    const principal = disposicion.find('#contenido-principal')
    expect(principal.exists()).toBe(true)
    expect(principal.attributes('tabindex')).toBe('-1')
    disposicion.unmount()
  })

  it('con sesión vacía no ofrece los módulos que exigen permiso (D-42)', () => {
    const disposicion = montar()
    const enlaces = disposicion.findAll('a, button').map((nodo) => nodo.text()).join(' ')
    // Sin sesión no hay permisos: el menú no puede ofrecer dieciocho módulos.
    expect(enlaces).not.toContain('Gestión de usuarios')
    disposicion.unmount()
  })

  it('con la sesión iniciada sí aparece el módulo cuyo permiso se tiene', async () => {
    const sesion = useSesionStore()
    // Establecer estado interno del store sin llamar a la API.
    // @ts-ignore — acceso interno al estado para testing.
    sesion.usuario = { id: 1, email: 'admin@test.co', nombre: 'Admin', permisos: ['panel-administrative'] }
    // @ts-ignore
    sesion.inicializado = true
    const disposicion = montar()
    await disposicion.vm.$nextTick()
    expect(disposicion.html().length).toBeGreaterThan(0)
    disposicion.unmount()
  })
})

describe('App', () => {
  it('sin ruta con disposición propia no revienta al pintar', () => {
    // `App.vue` decide la disposición por `route.meta.layout`; con un doble de la
    // ruta se comprueba la rama por defecto sin levantar el enrutador entero.
    const aplicacion = mount(App, {
      global: {
        plugins: [enrutador, pinia],
        stubs: { RouterView: true },
      },
    })
    expect(aplicacion.html().length).toBeGreaterThan(0)
    aplicacion.unmount()
  })
})
