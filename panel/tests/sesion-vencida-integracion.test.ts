/**
 * T9.8 — el aviso de sesión vencida, con el cableado REAL: interceptor, store, `api/auth` y
 * modal, sin simular el módulo de autenticación. Solo se sustituye la red (el adaptador de
 * Axios y `fetch` para la cookie CSRF), como haría el navegador.
 *
 * Por qué existe este archivo: las pruebas de `sesion-vencida.test.ts` simulan `api/auth` y por
 * eso NO ejercitan el 401 que `/panel/login` devuelve con credenciales malas (medido en
 * staging, 2026-11-26). Con el manejo antiguo del 401, una contraseña mal escrita DENTRO del
 * aviso cerraba la sesión local y el aviso desaparecía: el problema original, otra vez.
 *
 * Medido en staging (2026-11-26, peticiones sin credenciales a rutas sin efectos):
 *   escritura sin cookies .................................. 419
 *   escritura con XSRF viejo y sin cookie de sesión ......... 419
 *   escritura con una sesión que el servidor ya no tiene .... 419
 *   escritura con sesión de invitado y CSRF válido .......... 401
 *   lectura sin sesión ...................................... 401
 *   login con credenciales falsas ........................... 401
 */
import { AxiosError, type InternalAxiosRequestConfig } from 'axios'
import { createPinia, setActivePinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { h, ref } from 'vue'

import { http, mensajeDeError } from '../src/api/client'
import { useSesionStore } from '../src/stores/sesion'
import SesionVencidaModal from '../src/components/feedback/SesionVencidaModal.vue'

const usuario = {
  id: 7, type: 'usuario', email: 'ana@santamarta.gov.co', mfa_habilitado: false, estado: 'activo',
  roles: [{ id: 2, type: 'rol', nombre: 'editor', permisos: ['noticias.ver'] }],
}
const sobre = (data: unknown) => ({ success: true, message: null, data, errors: null })
const sobreError = (message: string) => ({ success: false, message, data: null, errors: null })

type Regla = (config: InternalAxiosRequestConfig) => { status: number; data: unknown }
let reglas: Record<string, Regla> = {}
let llamadas: string[] = []

const adaptadorOriginal = http.defaults.adapter

function instalarRed() {
  http.defaults.adapter = async (config: InternalAxiosRequestConfig) => {
    const clave = `${(config.method ?? 'get').toUpperCase()} ${config.url}`
    llamadas.push(clave)
    const regla = reglas[clave] ?? (() => ({ status: 200, data: sobre({}) }))
    const { status, data } = regla(config)
    const respuesta = { status, statusText: '', headers: {}, config, data }
    if (status >= 400) throw new AxiosError('fallo', 'ERR_BAD_REQUEST', config, null, respuesta)
    return respuesta
  }
}

const loginCorrecto: Regla = () => ({
  status: 200,
  data: sobre({ require_mfa: false, mfa_token: null, csrf_token: 'x', user: usuario }),
})

async function conSesionIniciada() {
  reglas['POST /panel/login'] = loginCorrecto
  const sesion = useSesionStore()
  await sesion.iniciarSesion({ email: usuario.email, password: 'clave-vieja' })
  llamadas = []
  return sesion
}

beforeEach(() => {
  setActivePinia(createPinia())
  reglas = {}
  llamadas = []
  vi.stubGlobal('fetch', vi.fn(async () => ({ ok: true, status: 204, text: async () => '' })))
  instalarRed()
})
afterEach(() => {
  http.defaults.adapter = adaptadorOriginal
  vi.unstubAllGlobals()
  document.body.innerHTML = ''
})

function montar() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', component: { template: '<div />' } },
      { path: '/acceso', name: 'acceso.entrar', component: { template: '<div />' } },
    ],
  })
  return { router, w: mount(SesionVencidaModal, { attachTo: document.body, global: { plugins: [router] } }) }
}
const dialogo = () => document.querySelector('[role="dialog"]')

async function escribirYEnviar(clave: string) {
  const campo = document.querySelector<HTMLInputElement>('[role="dialog"] input[type="password"]')!
  campo.value = clave
  campo.dispatchEvent(new Event('input'))
  await flushPromises()
  document.querySelector('[role="dialog"] form')!.dispatchEvent(new Event('submit'))
  await flushPromises()
}

describe('el aviso con el cableado real', () => {
  it('contraseña mala dentro del aviso (401 de /panel/login): sigue abierto, con el error, y la sesión intacta', async () => {
    const sesion = await conSesionIniciada()
    const { w } = montar()
    reglas['GET /panel/noticias'] = () => ({ status: 401, data: sobreError('No autenticado.') })
    await http.get('/panel/noticias').catch(() => undefined)
    await flushPromises()
    expect(dialogo()).not.toBeNull()

    reglas['POST /panel/login'] = () => ({ status: 401, data: sobreError('Las credenciales no son válidas.') })
    await escribirYEnviar('mala')

    expect(dialogo()).not.toBeNull()
    expect(document.querySelector('[role="alert"]')!.textContent).toContain('Las credenciales no son válidas.')
    expect(sesion.iniciada).toBe(true)
    expect(sesion.avisoSesionVencida).toBe(true)
    expect(llamadas).not.toContain('POST /panel/logout')
    w.unmount()
  })

  it('contraseña buena después de una mala: se cierra, lo escrito sigue y no hay navegación', async () => {
    const sesion = await conSesionIniciada()
    const { router, w } = montar()
    await router.push('/')
    const empujar = vi.spyOn(router, 'push')
    const titulo = ref('Titular a medias')

    reglas['POST /panel/noticias'] = () => ({ status: 419, data: sobreError('CSRF token mismatch.') })
    await http.post('/panel/noticias', { titulo: titulo.value }).catch(() => undefined)
    await flushPromises()
    expect(dialogo()).not.toBeNull()

    reglas['POST /panel/login'] = () => ({ status: 401, data: sobreError('Las credenciales no son válidas.') })
    await escribirYEnviar('mala')
    expect(dialogo()).not.toBeNull()

    reglas['POST /panel/login'] = loginCorrecto
    await escribirYEnviar('buena')

    expect(dialogo()).toBeNull()
    expect(sesion.iniciada).toBe(true)
    expect(titulo.value).toBe('Titular a medias')
    expect(empujar).not.toHaveBeenCalled()

    reglas['POST /panel/noticias'] = () => ({ status: 201, data: sobre({ id: 1 }) })
    await expect(http.post('/panel/noticias', {})).resolves.toBeTruthy()
    expect(sesion.avisoSesionVencida).toBe(false)
    w.unmount()
  })

  it('lectura 401 y después guardado 419: el aviso sigue ahí (la secuencia que dejaba a la persona sin salida)', async () => {
    const sesion = await conSesionIniciada()
    const { w } = montar()

    reglas['GET /panel/roles'] = () => ({ status: 401, data: sobreError('No autenticado.') })
    reglas['POST /panel/noticias'] = () => ({ status: 419, data: sobreError('CSRF token mismatch.') })
    await http.get('/panel/roles').catch(() => undefined)
    const error = await http.post('/panel/noticias', {}).catch((e: unknown) => e)
    await flushPromises()

    expect(sesion.iniciada).toBe(true)
    expect(dialogo()).not.toBeNull()
    expect(mensajeDeError(error)).toMatch(/sesi[oó]n venci/i)
    w.unmount()
  })

  it('401 por cuenta inactiva en una escritura: el aviso lleva la explicación del servidor', async () => {
    await conSesionIniciada()
    const { w } = montar()

    reglas['PUT /panel/noticias/3'] = () => ({
      status: 401, data: sobreError('La sesión terminó porque la cuenta ya no está activa.'),
    })
    await http.put('/panel/noticias/3', {}).catch(() => undefined)
    await flushPromises()

    expect(dialogo()!.textContent).toContain('la cuenta ya no está activa')
    w.unmount()
  })

  it('«Salir»: cierra la sesión en el servidor y lleva al acceso', async () => {
    const sesion = await conSesionIniciada()
    const { router, w } = montar()
    await router.push('/')
    reglas['POST /panel/noticias'] = () => ({ status: 419, data: sobreError('CSRF token mismatch.') })
    reglas['POST /panel/logout'] = () => ({ status: 401, data: sobreError('No autenticado.') })
    await http.post('/panel/noticias', {}).catch(() => undefined)
    await flushPromises()

    Array.from(document.querySelectorAll('button')).find((b) => /salir/i.test(b.textContent ?? ''))!.click()
    await flushPromises()

    expect(llamadas).toContain('POST /panel/logout')
    expect(sesion.iniciada).toBe(false)
    expect(sesion.avisoSesionVencida).toBe(false)
    expect(dialogo()).toBeNull()
    expect(router.currentRoute.value.name).toBe('acceso.entrar')
    w.unmount()
  })

  it('cerrar sesión desde el menú con la sesión ya muerta (401 del logout) no levanta ningún aviso', async () => {
    const sesion = await conSesionIniciada()
    const { w } = montar()
    reglas['POST /panel/logout'] = () => ({ status: 419, data: sobreError('CSRF token mismatch.') })

    await sesion.cerrarSesion()
    await flushPromises()

    expect(sesion.iniciada).toBe(false)
    expect(sesion.avisoSesionVencida).toBe(false)
    expect(dialogo()).toBeNull()
    w.unmount()
  })

  it('Esc cierra el aviso sin volver a entrar; el siguiente 419 lo levanta otra vez', async () => {
    const sesion = await conSesionIniciada()
    const { w } = montar()
    reglas['POST /panel/noticias'] = () => ({ status: 419, data: sobreError('CSRF token mismatch.') })

    await http.post('/panel/noticias', {}).catch(() => undefined)
    await flushPromises()
    expect(dialogo()).not.toBeNull()

    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    await flushPromises()
    expect(dialogo()).toBeNull()
    expect(sesion.iniciada).toBe(true)

    await http.post('/panel/noticias', {}).catch(() => undefined)
    await flushPromises()
    expect(dialogo()).not.toBeNull()
    w.unmount()
  })
})

// `h` se importa para poder montar fragmentos si hiciera falta ampliar el archivo.
void h
