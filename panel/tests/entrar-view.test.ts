/**
 * EntrarView — tests del formulario de login.
 *
 * vi.hoisted garantiza que las referencias de mock están disponibles ANTES de
 * que vi.mock factories se ejecuten — evitando el problema de TDZ con const.
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { flushPromises, mount } from '@vue/test-utils'

// ---------------------------------------------------------------------------
// Mock del store — el factory retorna vi.fn() directamente para que
// vi.mocked() funcione correctamente en los tests.
// ---------------------------------------------------------------------------
vi.mock('../src/stores/sesion', () => {
  const fn = vi.fn()
  return {
    useSesionStore: () => ({
      iniciarSesion: fn,
      token: null,
      usuario: null,
      inicializado: false,
      iniciada: false,
      permisos: new Set<string>(),
      tienePermiso: vi.fn(() => false),
    }),
    // Exportar la referencia fn para poder hacer vi.mocked(ms).iniciarSesion
    _mockFn: fn,
  }
})

vi.mock('@/components/base/FaIcon.vue', () => ({
  default: { name: 'FaIcon', props: ['icon'], template: '<span class="fa-icon" />' },
}))

import * as sesionModule from '../src/stores/sesion'
import EntrarView from '../src/views/acceso/EntrarView.vue'

const getMockFn = () => (sesionModule as unknown as { _mockFn: ReturnType<typeof vi.fn> })._mockFn

beforeEach(() => {
  vi.clearAllMocks()
  vi.stubGlobal('useHead', () => undefined)
  vi.stubGlobal('useSeoMeta', () => undefined)
})

async function montar() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/acceso', component: { template: '<div>login</div>' } },
      { path: '/', component: { template: '<div>home</div>' } },
    ],
  })
  const wrapper = mount(EntrarView, { global: { plugins: [router] } })
  return { wrapper, router }
}

// ---------------------------------------------------------------------------
// Renderizado
// ---------------------------------------------------------------------------
describe('EntrarView — renderizado', () => {
  it('muestra el título de iniciar sesión', async () => {
    const { wrapper } = await montar()
    expect(wrapper.text()).toContain('Iniciar sesión')
    wrapper.unmount()
  })

  it('tiene campos de correo y contraseña', async () => {
    const { wrapper } = await montar()
    expect(wrapper.find('input[type="email"]').exists()).toBe(true)
    expect(wrapper.find('input[type="password"]').exists()).toBe(true)
    wrapper.unmount()
  })

  it('el botón está deshabilitado si faltan campos', async () => {
    const { wrapper } = await montar()
    expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeDefined()
    wrapper.unmount()
  })

  it('el botón se habilita cuando email y password tienen valor', async () => {
    const { wrapper } = await montar()
    await wrapper.find('input[type="email"]').setValue('a@b.co')
    await wrapper.find('input[type="password"]').setValue('password')
    expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeUndefined()
    wrapper.unmount()
  })
})

// ---------------------------------------------------------------------------
// Login exitoso
// ---------------------------------------------------------------------------
describe('EntrarView — login exitoso', () => {
  const mockIniciarSesion = getMockFn()

  it('navega a / tras login exitoso', async () => {
    mockIniciarSesion.mockResolvedValueOnce(undefined as never)

    const { wrapper, router } = await montar()
    await wrapper.find('input[type="email"]').setValue('test@b.co')
    await wrapper.find('input[type="password"]').setValue('password123')
    await wrapper.find('button[type="submit"]').trigger('click')
    await flushPromises()

    expect(router.currentRoute.value.path).toBe('/')
    wrapper.unmount()
  })

  it('llama a iniciarSesion con las credenciales', async () => {
    mockIniciarSesion.mockResolvedValueOnce(undefined as never)

    const { wrapper } = await montar()
    await wrapper.find('input[type="email"]').setValue('admin@test.co')
    await wrapper.find('input[type="password"]').setValue('secret')
    await wrapper.find('button[type="submit"]').trigger('click')
    await flushPromises()

    expect(mockIniciarSesion).toHaveBeenCalledWith({ email: 'admin@test.co', password: 'secret' })
    wrapper.unmount()
  })
})

// ---------------------------------------------------------------------------
// Error de red
// ---------------------------------------------------------------------------
describe('EntrarView — error de red', () => {
  const mockIniciarSesion = getMockFn()

  it('muestra error genérico de conexión', async () => {
    mockIniciarSesion.mockRejectedValueOnce(new Error('Network failure') as never)

    const { wrapper } = await montar()
    await wrapper.find('input[type="email"]').setValue('a@b.co')
    await wrapper.find('input[type="password"]').setValue('p')
    await wrapper.find('button[type="submit"]').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('No se pudo conectar con el servidor')
    wrapper.unmount()
  })
})

// ---------------------------------------------------------------------------
// Doble submit
// ---------------------------------------------------------------------------
describe('EntrarView — protección doble submit', () => {
  const mockIniciarSesion = getMockFn()

  it('ignora el segundo click mientras está cargando', async () => {
    mockIniciarSesion.mockReturnValue(new Promise(() => {}) as never)

    const { wrapper } = await montar()
    await wrapper.find('input[type="email"]').setValue('a@b.co')
    await wrapper.find('input[type="password"]').setValue('p')

    await wrapper.find('button[type="submit"]').trigger('click')
    await flushPromises()
    await wrapper.find('button[type="submit"]').trigger('click')
    await flushPromises()

    expect(mockIniciarSesion).toHaveBeenCalledTimes(1)
    wrapper.unmount()
  })
})
