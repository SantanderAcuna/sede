/**
 * T9.8 (H43, D-0C.8) — un `419` no saca a la persona de la pantalla.
 *
 * Un `419` (sesión o token CSRF perdidos) llegaba al formulario como un error más y la
 * persona seguía «dentro» sin poder guardar, sin saber por qué. La decisión (José,
 * 2026-11-18): ante un `419` el panel NO navega ni cierra la sesión local; abre un aviso
 * para volver a entrar ahí mismo, con la MISMA cuenta, y lo escrito queda intacto. El `401`
 * y el `429` siguen como hoy.
 *
 * Qué protege:
 *   1. El interceptor REAL de `client.ts` (no un doble): un 419 con sesión activa levanta el
 *      aviso y conserva el usuario; sin sesión (pantalla de acceso) no hace nada; 401 y 429
 *      no cambian.
 *   2. El store: `reautenticar` usa el correo de la sesión, nunca otro; una contraseña mala,
 *      otra cuenta o un acceso que pide doble factor NO cierran el aviso.
 *   3. El modal: pide solo la contraseña, no permite cambiar de cuenta, muestra el error y
 *      se cierra al volver a entrar.
 *   4. De punta a punta: con un formulario a medias, tras el 419 y el nuevo acceso lo escrito
 *      sigue ahí y no hubo navegación.
 */
import { AxiosError, type InternalAxiosRequestConfig } from 'axios'
import { createPinia, setActivePinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { h, ref } from 'vue'

import { http, mensajeDeError } from '../src/api/client'
import * as authModule from '../src/api/auth'
import { useSesionStore } from '../src/stores/sesion'

vi.mock('../src/api/auth', () => ({
  login: vi.fn(),
  logout: vi.fn(),
  perfil: vi.fn(),
}))

const usuarioApi = {
  id: 7,
  type: 'usuario' as const,
  email: 'ana@santamarta.gov.co',
  mfa_habilitado: false,
  estado: 'activo',
  roles: [{ id: 2, type: 'rol' as const, nombre: 'editor', permisos: ['noticias.ver'] }],
}
const respuestaLogin = (user: typeof usuarioApi | null, extra: object = {}) => ({
  require_mfa: false,
  mfa_token: null,
  csrf_token: 'x',
  user,
  ...extra,
})

const adaptadorOriginal = http.defaults.adapter

/** Hace que la próxima petición del cliente REAL termine con ese estado. */
function responderCon(estado: number, mensaje = 'CSRF token mismatch.') {
  http.defaults.adapter = async (config: InternalAxiosRequestConfig) => {
    throw new AxiosError('fallo', 'ERR_BAD_REQUEST', config, null, {
      status: estado,
      statusText: '',
      headers: {},
      config,
      data: { success: false, message: mensaje, data: null, errors: null },
    })
  }
}

async function conSesion() {
  const sesion = useSesionStore()
  vi.mocked(authModule.login).mockResolvedValueOnce(respuestaLogin(usuarioApi))
  await sesion.iniciarSesion({ email: usuarioApi.email, password: 'clave-vieja' })
  vi.mocked(authModule.login).mockReset()
  return sesion
}

beforeEach(() => {
  setActivePinia(createPinia())
  vi.mocked(authModule.logout).mockReset().mockResolvedValue(undefined)
})
afterEach(() => {
  http.defaults.adapter = adaptadorOriginal
})

describe('1. interceptor de client.ts', () => {
  it('419 con sesión iniciada: levanta el aviso, conserva el usuario y no llama a logout', async () => {
    const sesion = await conSesion()
    responderCon(419)

    await expect(http.post('/panel/noticias', {})).rejects.toMatchObject({ response: { status: 419 } })

    expect(sesion.avisoSesionVencida).toBe(true)
    expect(sesion.iniciada).toBe(true)
    expect(sesion.usuario?.email).toBe(usuarioApi.email)
    expect(authModule.logout).not.toHaveBeenCalled()
  })

  it('419 sin sesión (pantalla de acceso): no levanta nada', async () => {
    const sesion = useSesionStore()
    responderCon(419)

    await expect(http.post('/panel/login', {})).rejects.toBeTruthy()

    expect(sesion.avisoSesionVencida).toBe(false)
  })

  it('varios 419 a la vez dejan un solo aviso', async () => {
    const sesion = await conSesion()
    responderCon(419)

    await Promise.allSettled([http.post('/a', {}), http.post('/b', {}), http.put('/c', {})])

    expect(sesion.avisoSesionVencida).toBe(true)
  })

  it('401 sigue como hoy: cierra la sesión local y no levanta el aviso', async () => {
    const sesion = await conSesion()
    responderCon(401, 'No autenticado.')

    await expect(http.get('/panel/noticias')).rejects.toBeTruthy()
    await flushPromises()

    expect(authModule.logout).toHaveBeenCalledTimes(1)
    expect(sesion.iniciada).toBe(false)
    expect(sesion.avisoSesionVencida).toBe(false)
  })

  it('429 sigue como hoy: no toca la sesión ni levanta el aviso', async () => {
    const sesion = await conSesion()
    responderCon(429, 'Demasiados intentos.')

    await expect(http.get('/panel/noticias')).rejects.toBeTruthy()

    expect(sesion.iniciada).toBe(true)
    expect(sesion.avisoSesionVencida).toBe(false)
    expect(authModule.logout).not.toHaveBeenCalled()
  })

  it('mensajeDeError explica el 419 en español en lugar de «CSRF token mismatch.»', async () => {
    await conSesion()
    responderCon(419)
    const error = await http.post('/x', {}).catch((e: unknown) => e)

    const texto = mensajeDeError(error)

    expect(texto).toMatch(/sesi[oó]n venci/i)
    expect(texto).not.toMatch(/CSRF/i)
  })
})

describe('2. store: reautenticar', () => {
  it('usa el correo de la sesión, cierra el aviso y conserva al usuario', async () => {
    const sesion = await conSesion()
    sesion.marcarSesionVencida()
    vi.mocked(authModule.login).mockResolvedValueOnce(respuestaLogin(usuarioApi))

    await sesion.reautenticar('clave-nueva')

    expect(authModule.login).toHaveBeenCalledWith({ email: usuarioApi.email, password: 'clave-nueva' })
    expect(sesion.avisoSesionVencida).toBe(false)
    expect(sesion.usuario?.id).toBe(7)
  })

  it('contraseña incorrecta: lanza, el aviso sigue y el usuario se conserva', async () => {
    const sesion = await conSesion()
    sesion.marcarSesionVencida()
    vi.mocked(authModule.login).mockRejectedValueOnce(new Error('credenciales'))

    await expect(sesion.reautenticar('mala')).rejects.toThrow()

    expect(sesion.avisoSesionVencida).toBe(true)
    expect(sesion.usuario?.email).toBe(usuarioApi.email)
  })

  it('si el servidor devuelve OTRA cuenta, lanza y cierra la sesión: la cookie ya no es de quien escribía', async () => {
    const sesion = await conSesion()
    sesion.marcarSesionVencida()
    vi.mocked(authModule.login).mockResolvedValueOnce(
      respuestaLogin({ ...usuarioApi, id: 99, email: 'otra@santamarta.gov.co' }),
    )

    await expect(sesion.reautenticar('x')).rejects.toThrow()

    expect(authModule.logout).toHaveBeenCalledTimes(1)
    expect(sesion.iniciada).toBe(false)
    expect(sesion.avisoSesionVencida).toBe(false)
  })

  it('un acceso que pide doble factor no se da por hecho', async () => {
    const sesion = await conSesion()
    sesion.marcarSesionVencida()
    vi.mocked(authModule.login).mockResolvedValueOnce(
      respuestaLogin(null, { require_mfa: true, mfa_token: 't' }),
    )

    await expect(sesion.reautenticar('x')).rejects.toThrow()

    expect(sesion.avisoSesionVencida).toBe(true)
  })

  it('sin sesión que reautenticar, lanza sin llamar a la API', async () => {
    const sesion = useSesionStore()

    await expect(sesion.reautenticar('x')).rejects.toThrow()

    expect(authModule.login).not.toHaveBeenCalled()
  })

  it('cerrarSesion y descartar apagan el aviso', async () => {
    const sesion = await conSesion()
    sesion.marcarSesionVencida()
    sesion.descartarAvisoSesionVencida()
    expect(sesion.avisoSesionVencida).toBe(false)

    sesion.marcarSesionVencida()
    await sesion.cerrarSesion()
    expect(sesion.avisoSesionVencida).toBe(false)
    expect(sesion.iniciada).toBe(false)
  })

  it('marcarSesionVencida sin sesión no hace nada', () => {
    const sesion = useSesionStore()
    sesion.marcarSesionVencida()
    expect(sesion.avisoSesionVencida).toBe(false)
  })
})

describe('3. modal SesionVencidaModal', () => {
  async function montarModal() {
    const { default: SesionVencidaModal } = await import('../src/components/feedback/SesionVencidaModal.vue')
    return mount(SesionVencidaModal, { attachTo: document.body })
  }
  const enCuerpo = () => document.body

  afterEach(() => {
    document.body.innerHTML = ''
  })

  it('no se ve sin aviso', async () => {
    await conSesion()
    const w = await montarModal()
    expect(enCuerpo().querySelector('[role="dialog"]')).toBeNull()
    w.unmount()
  })

  it('con aviso se ve, nombra la cuenta y no deja cambiarla', async () => {
    const sesion = await conSesion()
    const w = await montarModal()
    sesion.marcarSesionVencida()
    await flushPromises()

    const dialogo = enCuerpo().querySelector('[role="dialog"]')
    expect(dialogo).not.toBeNull()
    expect(dialogo!.textContent).toMatch(/sesi[oó]n venci/i)
    expect(dialogo!.textContent).toContain(usuarioApi.email)
    expect(dialogo!.querySelector('input[type="email"]')).toBeNull()
    expect(dialogo!.querySelectorAll('input[type="password"]')).toHaveLength(1)
    w.unmount()
  })

  it('el botón no se activa sin contraseña', async () => {
    const sesion = await conSesion()
    const w = await montarModal()
    sesion.marcarSesionVencida()
    await flushPromises()

    const boton = enCuerpo().querySelector<HTMLButtonElement>('button[type="submit"]')!
    expect(boton.disabled).toBe(true)
    w.unmount()
  })

  it('al entrar con la contraseña correcta, se cierra', async () => {
    const sesion = await conSesion()
    const w = await montarModal()
    sesion.marcarSesionVencida()
    await flushPromises()
    vi.mocked(authModule.login).mockResolvedValueOnce(respuestaLogin(usuarioApi))

    const campo = enCuerpo().querySelector<HTMLInputElement>('input[type="password"]')!
    campo.value = 'clave-nueva'
    campo.dispatchEvent(new Event('input'))
    await flushPromises()
    enCuerpo().querySelector('form')!.dispatchEvent(new Event('submit'))
    await flushPromises()

    expect(authModule.login).toHaveBeenCalledWith({ email: usuarioApi.email, password: 'clave-nueva' })
    expect(sesion.avisoSesionVencida).toBe(false)
    expect(enCuerpo().querySelector('[role="dialog"]')).toBeNull()
    w.unmount()
  })

  it('con contraseña incorrecta sigue abierto y muestra el error', async () => {
    const sesion = await conSesion()
    const w = await montarModal()
    sesion.marcarSesionVencida()
    await flushPromises()
    vi.mocked(authModule.login).mockRejectedValueOnce(new Error('Las credenciales no son válidas.'))

    const campo = enCuerpo().querySelector<HTMLInputElement>('input[type="password"]')!
    campo.value = 'mala'
    campo.dispatchEvent(new Event('input'))
    await flushPromises()
    enCuerpo().querySelector('form')!.dispatchEvent(new Event('submit'))
    await flushPromises()

    expect(enCuerpo().querySelector('[role="dialog"]')).not.toBeNull()
    expect(enCuerpo().querySelector('[role="alert"]')).not.toBeNull()
    expect(sesion.avisoSesionVencida).toBe(true)
    w.unmount()
  })

  it('no envía dos veces mientras espera', async () => {
    const sesion = await conSesion()
    const w = await montarModal()
    sesion.marcarSesionVencida()
    await flushPromises()
    let resolver!: (v: ReturnType<typeof respuestaLogin>) => void
    vi.mocked(authModule.login).mockReturnValueOnce(new Promise((r) => { resolver = r }))

    const campo = enCuerpo().querySelector<HTMLInputElement>('input[type="password"]')!
    campo.value = 'x'
    campo.dispatchEvent(new Event('input'))
    await flushPromises()
    const formulario = enCuerpo().querySelector('form')!
    formulario.dispatchEvent(new Event('submit'))
    formulario.dispatchEvent(new Event('submit'))
    await flushPromises()

    expect(authModule.login).toHaveBeenCalledTimes(1)
    resolver(respuestaLogin(usuarioApi))
    await flushPromises()
    w.unmount()
  })
})

describe('4. de punta a punta: lo escrito sobrevive y no hay navegación', () => {
  it('formulario a medias + 419 + nuevo acceso: el texto sigue ahí y la ruta no cambia', async () => {
    const sesion = await conSesion()
    const { default: SesionVencidaModal } = await import('../src/components/feedback/SesionVencidaModal.vue')

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/noticias/nueva', component: { template: '<div />' } },
        { path: '/acceso', component: { template: '<div />' } },
      ],
    })
    await router.push('/noticias/nueva')
    await router.isReady()
    const empujar = vi.spyOn(router, 'push')

    const titulo = ref('Titular a medio escribir')
    const error = ref('')
    const Formulario = {
      setup() {
        async function guardar() {
          try {
            await http.post('/panel/noticias', { titulo: titulo.value })
          } catch (e) {
            error.value = mensajeDeError(e)
          }
        }
        return () =>
          h('form', { id: 'f', onSubmit: (ev: Event) => { ev.preventDefault(); void guardar() } }, [
            h('input', { id: 'titulo', value: titulo.value, onInput: (ev: Event) => { titulo.value = (ev.target as HTMLInputElement).value } }),
            h('p', { id: 'err' }, error.value),
          ])
      },
    }
    const Raiz = { render: () => h('div', [h(Formulario), h(SesionVencidaModal)]) }
    const w = mount(Raiz, { attachTo: document.body, global: { plugins: [router] } })

    responderCon(419)
    document.querySelector<HTMLFormElement>('#f')!.dispatchEvent(new Event('submit'))
    await flushPromises()

    expect(sesion.avisoSesionVencida).toBe(true)
    expect(document.querySelector('[role="dialog"]')).not.toBeNull()
    expect(document.querySelector('#err')!.textContent).toMatch(/sesi[oó]n venci/i)

    vi.mocked(authModule.login).mockResolvedValueOnce(respuestaLogin(usuarioApi))
    const campo = document.querySelector<HTMLInputElement>('[role="dialog"] input[type="password"]')!
    campo.value = 'clave-nueva'
    campo.dispatchEvent(new Event('input'))
    await flushPromises()
    document.querySelector('[role="dialog"] form')!.dispatchEvent(new Event('submit'))
    await flushPromises()

    expect(document.querySelector('[role="dialog"]')).toBeNull()
    expect(document.querySelector<HTMLInputElement>('#titulo')!.value).toBe('Titular a medio escribir')
    expect(titulo.value).toBe('Titular a medio escribir')
    expect(router.currentRoute.value.path).toBe('/noticias/nueva')
    expect(empujar).not.toHaveBeenCalled()

    // Y el reintento de «Guardar» ya no choca con el 419.
    http.defaults.adapter = async (config: InternalAxiosRequestConfig) => ({
      data: { success: true, message: null, data: { id: 1 }, errors: null },
      status: 201, statusText: 'Created', headers: {}, config,
    })
    error.value = ''
    document.querySelector<HTMLFormElement>('#f')!.dispatchEvent(new Event('submit'))
    await flushPromises()
    expect(document.querySelector('#err')!.textContent).toBe('')
    w.unmount()
  })
})
