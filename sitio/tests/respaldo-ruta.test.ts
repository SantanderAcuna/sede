// @vitest-environment node
/**
 * Lo que la ruta `/api/v1/[...path]` decide antes de tocar el núcleo del respaldo (T9.4).
 *
 * Lo que se protege: que solo una lectura (`GET`) de la lista blanca pase por el respaldo; que la
 * petición se interprete SIN ambigüedad (un parámetro repetido o `__proto__` no puede colarse como
 * «limpio»); que la cabecera `X-Sede-Respaldo` aparezca solo cuando se sirvió una copia; y que el
 * `traer` que habla con la API nunca salga del canal interno ni pase del tiempo que le dan.
 */
import { describe, expect, it, vi } from 'vitest'

import {
  atenderConRespaldo,
  crearTraer,
  peticionRespaldable,
  type EntornoRespaldo,
} from '../server/utils/respaldo-ruta'
import { crearRespaldo, type Resultado } from '../server/utils/respaldo-api'

const BASE = 'http://nginx:8080'
const DOMINIO = 'staging.santamarta.gov.co'

function entorno(resultado: Resultado | null | Error) {
  const registrar = vi.fn()
  const resolver = vi.fn(async () => {
    if (resultado instanceof Error) throw resultado
    return resultado
  })
  const e: EntornoRespaldo = { respaldo: { resolver }, registrar }
  return { e, registrar, resolver }
}

const lectura = (extra: Partial<Parameters<typeof atenderConRespaldo>[1]> = {}) => ({
  metodo: 'GET',
  destino: `${BASE}/api/v1/tramites?per_page=2`,
  anfitrion: DOMINIO,
  cabeceras: { 'x-sede-cliente-ip': '203.0.113.9' },
  ...extra,
})

describe('peticionRespaldable: cómo se lee la URL', () => {
  it('separa la ruta (sin /api/v1) y los parámetros', () => {
    const p = peticionRespaldable(`${BASE}/api/v1/tramites?per_page=2&page=3`, DOMINIO, { a: 'b' })
    expect(p).toEqual({ ruta: '/tramites', query: { per_page: '2', page: '3' }, cabeceras: { a: 'b' }, anfitrion: DOMINIO })
  })

  it('sin parámetros, query vacía', () => {
    expect(peticionRespaldable(`${BASE}/api/v1/banners`, DOMINIO, {})!.query).toEqual({})
  })

  it('un parámetro repetido no se puede representar: no se respalda', () => {
    expect(peticionRespaldable(`${BASE}/api/v1/tramites?page=1&page=2`, DOMINIO, {})).toBeNull()
  })

  it('decodifica los parámetros igual que la API (page=%31 es page=1)', () => {
    expect(peticionRespaldable(`${BASE}/api/v1/tramites?page=%31`, DOMINIO, {})!.query).toEqual({ page: '1' })
  })

  it.each([
    ['URL inválida', 'no es una url'],
    ['otro prefijo', `${BASE}/otra/ruta`],
    ['solo el prefijo', `${BASE}/api/v1`],
    ['prefijo parecido', `${BASE}/api/v10/tramites`],
  ])('%s: null', (_nombre, destino) => {
    expect(peticionRespaldable(destino, DOMINIO, {})).toBeNull()
  })

  it('«__proto__» queda como parámetro propio (y el núcleo lo rechaza), no se pierde', async () => {
    const p = peticionRespaldable(`${BASE}/api/v1/tramites?__proto__=x`, DOMINIO, {})!
    expect(Object.keys(p.query)).toEqual(['__proto__'])
    const { claveDeRespaldo } = await import('../server/utils/respaldo-api')
    expect(claveDeRespaldo(p.ruta, p.query)).toBeNull()
  })
})

describe('atenderConRespaldo', () => {
  it.each(['POST', 'HEAD', 'PUT', 'DELETE', 'OPTIONS'])('%s no pasa por el respaldo', async (metodo) => {
    const { e, resolver } = entorno({ origen: 'api', estado: 200, tipo: 'x', cuerpo: 'y' })
    expect(await atenderConRespaldo(e, lectura({ metodo }))).toBeNull()
    expect(resolver).not.toHaveBeenCalled()
  })

  it('una URL que no se puede interpretar vuelve null sin llamar al núcleo', async () => {
    const { e, resolver } = entorno(null)
    expect(await atenderConRespaldo(e, lectura({ destino: `${BASE}/api/v1/tramites?page=1&page=2` }))).toBeNull()
    expect(resolver).not.toHaveBeenCalled()
  })

  it('si el núcleo dice «no respaldable», null (la ruta sigue como hoy)', async () => {
    const { e, resolver } = entorno(null)
    expect(await atenderConRespaldo(e, lectura({ destino: `${BASE}/api/v1/buscar?q=a` }))).toBeNull()
    expect(resolver).toHaveBeenCalledTimes(1)
  })

  it('le pasa al núcleo la ruta, los parámetros, las cabeceras y el anfitrión', async () => {
    const { e, resolver } = entorno({ origen: 'api', estado: 200, tipo: 'application/json', cuerpo: '{}' })
    await atenderConRespaldo(e, lectura())
    expect(resolver).toHaveBeenCalledWith({
      ruta: '/tramites',
      query: { per_page: '2' },
      cabeceras: { 'x-sede-cliente-ip': '203.0.113.9' },
      anfitrion: DOMINIO,
    })
  })

  it('respuesta de la API: estado, tipo y cuerpo; sin X-Sede-Respaldo y sin registro', async () => {
    const { e, registrar } = entorno({ origen: 'api', estado: 200, tipo: 'application/json', cuerpo: '{"a":1}' })
    expect(await atenderConRespaldo(e, lectura())).toEqual({ estado: 200, tipo: 'application/json', cuerpo: '{"a":1}', cabeceras: {} })
    expect(registrar).not.toHaveBeenCalled()
  })

  it('un 429 de la API conserva su Retry-After', async () => {
    const { e } = entorno({ origen: 'api', estado: 429, tipo: 'application/json', cuerpo: '{}', retryAfter: '17' })
    expect((await atenderConRespaldo(e, lectura()))!.cabeceras).toEqual({ 'retry-after': '17' })
  })

  it('copia servida: 200, X-Sede-Respaldo con la edad en segundos enteros y UNA línea de registro', async () => {
    const { e, registrar } = entorno({ origen: 'respaldo', estado: 200, tipo: 'application/json', cuerpo: '{"v":1}', edadMs: 125_999, motivo: 'tiempo' })
    const r = await atenderConRespaldo(e, lectura())
    expect(r).toEqual({ estado: 200, tipo: 'application/json', cuerpo: '{"v":1}', cabeceras: { 'x-sede-respaldo': '125' } })
    expect(registrar).toHaveBeenCalledTimes(1)
    expect(registrar).toHaveBeenCalledWith({ evento: 'respaldo_servido', ruta: '/tramites', motivo: 'tiempo', edad_s: 125 })
  })

  it('el registro no lleva los parámetros ni las cabeceras de la petición', async () => {
    const { e, registrar } = entorno({ origen: 'respaldo', estado: 200, tipo: 't', cuerpo: 'c', edadMs: 1000, motivo: 'red' })
    await atenderConRespaldo(e, lectura({ cabeceras: { cookie: 'sede-session=secreto' } }))
    expect(JSON.stringify(registrar.mock.calls)).not.toMatch(/secreto|per_page|203\.0\.113/)
  })

  it('una edad negativa (reloj atrasado) se informa como 0', async () => {
    const { e } = entorno({ origen: 'respaldo', estado: 200, tipo: 't', cuerpo: 'c', edadMs: -5000, motivo: 'red' })
    expect((await atenderConRespaldo(e, lectura()))!.cabeceras['x-sede-respaldo']).toBe('0')
  })

  it('si el núcleo lanza (API caída y sin copia), el error sube tal cual', async () => {
    const fallo = new Error('fetch failed')
    const { e } = entorno(fallo)
    await expect(atenderConRespaldo(e, lectura())).rejects.toBe(fallo)
  })
})

describe('crearTraer: la única salida hacia la API', () => {
  const respuesta = (estado = 200, cuerpo = '{"ok":true}', cabeceras: Record<string, string> = { 'Content-Type': 'application/json' }) =>
    new Response(cuerpo, { status: estado, headers: cabeceras })

  it('pide base + /api/v1 + ruta + parámetros, en GET, sin seguir redirecciones', async () => {
    const fetchFalso = vi.fn(async () => respuesta())
    const traer = crearTraer(BASE, fetchFalso as unknown as typeof fetch)
    await traer('/tramites', { page: '2', per_page: '10' }, { 'x-sede-cliente-ip': '203.0.113.9', host: DOMINIO }, 4000)
    const [url, init] = fetchFalso.mock.calls[0] as unknown as [string, RequestInit]
    expect(url).toBe(`${BASE}/api/v1/tramites?page=2&per_page=10`)
    expect(init.method).toBe('GET')
    expect(init.redirect).toBe('manual')
    expect(init.headers).toEqual({ 'x-sede-cliente-ip': '203.0.113.9', host: DOMINIO })
  })

  it('sin parámetros no deja un «?» colgando', async () => {
    const fetchFalso = vi.fn(async () => respuesta())
    await crearTraer(BASE, fetchFalso as unknown as typeof fetch)('/banners', {}, {}, 4000)
    expect((fetchFalso.mock.calls[0] as unknown as [string])[0]).toBe(`${BASE}/api/v1/banners`)
  })

  it('devuelve estado, cabeceras en minúsculas y cuerpo como texto', async () => {
    const fetchFalso = vi.fn(async () => respuesta(404, '{"m":"no"}', { 'Content-Type': 'application/json', 'Retry-After': '9' }))
    const r = await crearTraer(BASE, fetchFalso as unknown as typeof fetch)('/tramites/x', {}, {}, 4000)
    expect(r.estado).toBe(404)
    expect(r.cuerpo).toBe('{"m":"no"}')
    expect(r.cabeceras['content-type']).toBe('application/json')
    expect(r.cabeceras['retry-after']).toBe('9')
  })

  it('una base inválida o una ruta que sale de /api/v1 NO llama a fetch', async () => {
    const fetchFalso = vi.fn(async () => respuesta())
    await expect(crearTraer('', fetchFalso as unknown as typeof fetch)('/tramites', {}, {}, 4000)).rejects.toThrow(/canal interno/)
    await expect(crearTraer(BASE, fetchFalso as unknown as typeof fetch)('/../../otra', {}, {}, 4000)).rejects.toThrow(/canal interno/)
    expect(fetchFalso).not.toHaveBeenCalled()
  })

  it('una ruta que empieza por «//» no puede cambiar de anfitrión: sigue bajo la base y /api/v1', async () => {
    const fetchFalso = vi.fn(async () => respuesta())
    await crearTraer(BASE, fetchFalso as unknown as typeof fetch)('//otro-host/x', {}, {}, 4000)
    expect((fetchFalso.mock.calls[0] as unknown as [string])[0]).toBe(`${BASE}/api/v1//otro-host/x`)
  })

  it('el tiempo máximo corta también la espera de la API (TimeoutError)', async () => {
    const colgado = vi.fn((_u: string, init: RequestInit) => new Promise<Response>((_ok, no) => {
      init.signal!.addEventListener('abort', () => no(init.signal!.reason))
    }))
    const t0 = Date.now()
    await expect(crearTraer(BASE, colgado as unknown as typeof fetch)('/tramites', {}, {}, 60)).rejects.toMatchObject({ name: 'TimeoutError' })
    expect(Date.now() - t0).toBeLessThan(1000)
  })

  it('y también corta la lectura del cuerpo si la API contesta las cabeceras y se cuelga', async () => {
    const cuerpoColgado = vi.fn(async (_u: string, init: RequestInit) => ({
      status: 200,
      headers: new Headers({ 'content-type': 'application/json' }),
      text: () => new Promise<string>((_ok, no) => { init.signal!.addEventListener('abort', () => no(init.signal!.reason)) }),
    }))
    await expect(crearTraer(BASE, cuerpoColgado as unknown as typeof fetch)('/tramites', {}, {}, 60)).rejects.toMatchObject({ name: 'TimeoutError' })
  })

  it('integrado con el núcleo: la petición respaldable sale SIN cookie ni authorization', async () => {
    const fetchFalso = vi.fn(async () => respuesta())
    const respaldo = crearRespaldo({
      almacen: { leer: async () => null, guardar: async () => {} },
      traer: crearTraer(BASE, fetchFalso as unknown as typeof fetch),
      ahora: () => 1,
      dominio: DOMINIO,
    })
    await respaldo.resolver({
      ruta: '/tramites',
      query: {},
      cabeceras: { cookie: 'sede-session=x', Authorization: 'Bearer y', 'x-sede-cliente-ip': '203.0.113.9' },
      anfitrion: DOMINIO,
    })
    const enviadas = (fetchFalso.mock.calls[0] as unknown as [string, RequestInit])[1].headers as Record<string, string>
    expect(Object.keys(enviadas).map(n => n.toLowerCase())).toEqual(['x-sede-cliente-ip'])
  })
})
