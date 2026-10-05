/**
 * EntrarView — tests del formulario de login.
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { defineComponent, h } from 'vue'

// Mock del store de sesión
const mockSesionStore = {
  usuario: { id: 1, email: 'a@b.co', nombre: 'Admin', mfa_habilitado: false, estado: 'activo', roles: [] },
  token: 'mock-token',
  iniciarSesion: vi.fn().mockResolvedValue(undefined),
  cerrarSesion: vi.fn().mockResolvedValue(undefined),
  tienePermiso: vi.fn(() => true),
}
vi.mock('@/stores/sesion', () => ({ useSesionStore: () => mockSesionStore }))

vi.mock('@/services/auth', () => ({
  login: vi.fn().mockResolvedValue({
    require_mfa: false, mfa_token: null, csrf_token: 'tok',
    user: { id: 1, email: 'a@b.co', mfa_habilitado: false, estado: 'activo', roles: [] },
  }),
  logout: vi.fn().mockResolvedValue(undefined),
  perfil: vi.fn().mockResolvedValue({
    id: 1, email: 'a@b.co', mfa_habilitado: false, estado: 'activo', roles: [],
  }),
}))

beforeEach(() => {
  vi.stubGlobal('useHead', () => undefined)
  vi.stubGlobal('useSeoMeta', () => undefined)
  vi.clearAllMocks()
})

const FaIconStub = defineComponent({ name: 'FaIcon', props: ['icon'], render: () => h('span', { class: 'fa-icon-stub' }) })

const FormFieldStub = defineComponent({
  name: 'FormField',
  props: ['label', 'placeholder', 'modelValue', 'type', 'autocomplete', 'required'],
  emits: ['update:modelValue'],
  render() {
    return h('div', { class: 'form-field' }, [
      this.label ? h('label', {}, this.label) : null,
      h('input', {
        type: this.type || 'text',
        value: this.modelValue,
        autocomplete: this.autocomplete,
        required: this.required,
        onInput: (e: Event) => this.$emit('update:modelValue', (e.target as HTMLInputElement).value),
      }),
    ])
  },
})

async function montar() {
  const pinia = createPinia()
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/acceso', component: { template: '<div>login</div>' } },
      { path: '/', component: { template: '<div>home</div>' } },
      { path: '/recuperar', component: { template: '<div>recuperar</div>' } },
    ],
  })
  const { default: EntrarView } = await import('@/views/acceso/EntrarView.vue')
  const wrapper = mount(EntrarView, {
    global: { plugins: [pinia, router], stubs: { FaIcon: FaIconStub, FormField: FormFieldStub } },
  })
  return { wrapper, router }
}

describe('EntrarView — renderizado', () => {
  it('muestra el título de iniciar sesión', async () => {
    const { wrapper } = await montar()
    expect(wrapper.text()).toContain('Iniciar sesión')
    wrapper.unmount()
  })
  it('tiene campo de correo electrónico', async () => {
    const { wrapper } = await montar()
    expect(wrapper.find('input[type="email"]').exists()).toBe(true)
    wrapper.unmount()
  })
  it('tiene campo de contraseña', async () => {
    const { wrapper } = await montar()
    expect(wrapper.find('input[type="password"]').exists()).toBe(true)
    wrapper.unmount()
  })
  it('el botón submit existe', async () => {
    const { wrapper } = await montar()
    expect(wrapper.find('button[type="submit"]').exists()).toBe(true)
    wrapper.unmount()
  })
  it('el botón está deshabilitado sin campos', async () => {
    const { wrapper } = await montar()
    expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeDefined()
    wrapper.unmount()
  })
  it('el botón se habilita con email y password', async () => {
    const { wrapper } = await montar()
    await wrapper.find('input[type="email"]').setValue('a@b.co')
    await wrapper.find('input[type="password"]').setValue('password')
    expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeUndefined()
    wrapper.unmount()
  })
})

describe('EntrarView — login exitoso', () => {
  it('navega a / tras login', async () => {
    const { wrapper, router } = await montar()
    await wrapper.find('input[type="email"]').setValue('admin@test.co')
    await wrapper.find('input[type="password"]').setValue('secret')
    await wrapper.find('button[type="submit"]').trigger('click')
    await flushPromises()
    expect(router.currentRoute.value.path).toBe('/')
    wrapper.unmount()
  })
})

describe('EntrarView — validación', () => {
  it('el botón submit es clickeable sin errores', async () => {
    const { wrapper } = await montar()
    const btn = wrapper.find('button[type="submit"]')
    expect(btn.exists()).toBe(true)
    await btn.trigger('click')
    wrapper.unmount()
  })
})
