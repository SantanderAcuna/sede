/**
 * PerfilView — tests del componente con mock del servicio de auth.
 */
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { mount, flushPromises } from '@vue/test-utils'

vi.mock('../src/services/auth', () => {
  return {
    perfil: vi.fn().mockResolvedValue({
      id: 3,
      type: 'usuario' as const,
      email: 'jose.acuna@santamarta.gov.co',
      mfa_habilitado: true,
      estado: 'activo',
      roles: [
        { id: 1, type: 'rol' as const, nombre: 'operador', permisos: ['pqrsd.ver'] },
      ],
    }),
  }
})

import PerfilView from '../src/views/admin/PerfilView.vue'
import * as authModule from '../src/services/auth'

async function flush() {
  await flushPromises()
  await new Promise((r) => setTimeout(r, 10))
}

beforeEach(() => {
  vi.clearAllMocks()
  setActivePinia(createPinia())
  vi.stubGlobal('useHead', () => undefined)
  vi.stubGlobal('useSeoMeta', () => undefined)
  authModule.perfil.mockResolvedValue({
    id: 3,
    type: 'usuario' as const,
    email: 'jose.acuna@santamarta.gov.co',
    mfa_habilitado: true,
    estado: 'activo',
    roles: [{ id: 1, type: 'rol' as const, nombre: 'operador', permisos: ['pqrsd.ver'] }],
  })
})

describe('PerfilView', () => {
  function montar() {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/perfil', component: PerfilView }],
    })
    return mount(PerfilView, { global: { plugins: [router] } })
  }

  it('muestra skeleton cuando perfil está pendiente', () => {
    authModule.perfil.mockReturnValue(new Promise(() => {}))
    const wrapper = montar()
    expect(wrapper.find('.animate-pulse').exists()).toBe(true)
    wrapper.unmount()
  })

  it('tras cargar muestra los datos del usuario', async () => {
    const wrapper = montar()
    await flush()
    expect(wrapper.text()).toContain('Jose Acuna')
    expect(wrapper.text()).toContain('jose.acuna@santamarta.gov.co')
    wrapper.unmount()
  })

  it('muestra "Doble factor" cuando MFA está habilitado', async () => {
    const wrapper = montar()
    await flush()
    expect(wrapper.text()).toContain('Doble factor')
    wrapper.unmount()
  })

  it('muestra "Sin 2FA" cuando MFA está deshabilitado', async () => {
    authModule.perfil.mockResolvedValue({
      id: 3,
      type: 'usuario' as const,
      email: 'test@test.co',
      mfa_habilitado: false,
      estado: 'activo',
      roles: [],
    })
    const wrapper = montar()
    await flush()
    expect(wrapper.text()).toContain('Sin 2FA')
    wrapper.unmount()
  })

  it('muestra mensaje de error cuando la API falla', async () => {
    authModule.perfil.mockRejectedValue(new Error('Network error'))
    const wrapper = montar()
    await flush()
    expect(wrapper.text()).toContain('No se pudo cargar')
    wrapper.unmount()
  })

  it('renderiza la sección de roles y permisos', async () => {
    const wrapper = montar()
    await flush()
    expect(wrapper.text()).toContain('operador')
    expect(wrapper.text()).toContain('pqrsd.ver')
    wrapper.unmount()
  })
})
