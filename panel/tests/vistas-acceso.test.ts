/**
 * Vistas de acceso — smoke tests.
 *
 * Cubre las vistas estáticas del flujo de autenticación que no dependen
 * de servicios externos: NoEncontradoView, SinPermisoView, MfaView.
 * Cada una se monta y se verifica que renderiza sin errores.
 */
import { createMemoryHistory, createRouter } from 'vue-router'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

beforeEach(() => {
  vi.stubGlobal('useHead', () => undefined)
  vi.stubGlobal('useSeoMeta', () => undefined)
})

// ---------------------------------------------------------------------------
// NoEncontradoView (404)
// ---------------------------------------------------------------------------
describe('NoEncontradoView', () => {
  it('renderiza el mensaje de página no encontrada', async () => {
    const { default: NoEncontradoView } = await import('../src/views/acceso/NoEncontradoView.vue')
    const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/:pathMatch(.*)*', component: NoEncontradoView }] })
    const wrapper = mount(NoEncontradoView, { global: { plugins: [router] } })
    expect(wrapper.text()).toContain('La página no existe')
    expect(wrapper.text()).toContain('La ruta solicitada no está en el panel')
    wrapper.unmount()
  })

  it('ofrece enlace para volver al inicio', async () => {
    const { default: NoEncontradoView } = await import('../src/views/acceso/NoEncontradoView.vue')
    const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/:pathMatch(.*)*', component: NoEncontradoView }] })
    const wrapper = mount(NoEncontradoView, { global: { plugins: [router] } })
    const link = wrapper.find('a[href="/"]')
    expect(link.exists()).toBe(true)
    expect(link.text()).toContain('Volver al inicio')
    wrapper.unmount()
  })
})

// ---------------------------------------------------------------------------
// SinPermisoView (403)
// ---------------------------------------------------------------------------
describe('SinPermisoView', () => {
  it('renderiza el mensaje de acceso denegado', async () => {
    const { default: SinPermisoView } = await import('../src/views/acceso/SinPermisoView.vue')
    const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/sin-permiso', component: SinPermisoView }] })
    const wrapper = mount(SinPermisoView, { global: { plugins: [router] } })
    expect(wrapper.text()).toContain('No tiene permiso para ver esta página')
    wrapper.unmount()
  })

  it('tiene un enlace al panel principal', async () => {
    const { default: SinPermisoView } = await import('../src/views/acceso/SinPermisoView.vue')
    const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/sin-permiso', component: SinPermisoView }] })
    const wrapper = mount(SinPermisoView, { global: { plugins: [router] } })
    const link = wrapper.find('a')
    expect(link.exists()).toBe(true)
    wrapper.unmount()
  })
})

// ---------------------------------------------------------------------------
// MfaView (2FA placeholder)
// ---------------------------------------------------------------------------
describe('MfaView', () => {
  it('renderiza el título de verificación en dos pasos', async () => {
    const { default: MfaView } = await import('../src/views/acceso/MfaView.vue')
    const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/mfa', component: MfaView }] })
    const wrapper = mount(MfaView, { global: { plugins: [router] } })
    expect(wrapper.text()).toContain('Verificación en dos pasos')
    wrapper.unmount()
  })

  it('muestra el campo de código de verificación', async () => {
    const { default: MfaView } = await import('../src/views/acceso/MfaView.vue')
    const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/mfa', component: MfaView }] })
    const wrapper = mount(MfaView, { global: { plugins: [router] } })
    expect(wrapper.find('input[placeholder="000000"]').exists()).toBe(true)
    wrapper.unmount()
  })

  it('el botón de verificar está deshabilitado (preview)', async () => {
    const { default: MfaView } = await import('../src/views/acceso/MfaView.vue')
    const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/mfa', component: MfaView }] })
    const wrapper = mount(MfaView, { global: { plugins: [router] } })
    const btn = wrapper.find('button[type="submit"]')
    expect(btn.exists()).toBe(true)
    expect(btn.attributes('disabled')).toBeDefined()
    wrapper.unmount()
  })

  it('muestra mensaje de preview de MFA', async () => {
    const { default: MfaView } = await import('../src/views/acceso/MfaView.vue')
    const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/mfa', component: MfaView }] })
    const wrapper = mount(MfaView, { global: { plugins: [router] } })
    // El texto indica que la verificación todavía no está conectada.
    expect(wrapper.text()).toContain('verificación')
    expect(wrapper.text()).toContain('conectada')
    wrapper.unmount()
  })
})

// ---------------------------------------------------------------------------
// EmptyState y VistaEnConstruccion (componentes feedback)
// ---------------------------------------------------------------------------
describe('EmptyState', () => {
  it('renderiza con título por defecto', async () => {
    const { default: EmptyState } = await import('../src/components/feedback/EmptyState.vue')
    const wrapper = mount(EmptyState)
    expect(wrapper.text()).toContain('Sin datos')
    wrapper.unmount()
  })

  it('renderiza con título y subtítulo personalizados', async () => {
    const { default: EmptyState } = await import('../src/components/feedback/EmptyState.vue')
    const wrapper = mount(EmptyState, {
      props: { title: 'Sin datos', subtitle: 'No hay nada aquí.' },
    })
    expect(wrapper.text()).toContain('Sin datos')
    expect(wrapper.text()).toContain('No hay nada aquí.')
    wrapper.unmount()
  })

  it('renderiza el slot de acciones', async () => {
    const { default: EmptyState } = await import('../src/components/feedback/EmptyState.vue')
    const wrapper = mount(EmptyState, {
      props: { title: 'Vacío' },
      slots: { actions: '<button>Accionar</button>' },
    })
    expect(wrapper.find('button').exists()).toBe(true)
    wrapper.unmount()
  })
})
