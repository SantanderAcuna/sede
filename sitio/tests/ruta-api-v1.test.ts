// @vitest-environment node
/**
 * La ruta `/api/v1/[...path]` con el respaldo integrado (T9.4).
 *
 * Lo que se protege: que lo que NO es una lectura respaldable siga yendo por `proxyRequest` exactamente
 * como antes (mismas cabeceras, mismo tiempo máximo, sin seguir redirecciones); que una escritura siga
 * dando 405; que la cabecera `X-Sede-Respaldo` salga solo con una copia; y que una API caída sin copia
 * siga fallando como hoy (502), no con otro código.
 *
 * La ruta usa funciones que Nitro inyecta como globales; aquí se sustituyen por dobles mínimos.
 */
import { beforeEach, describe, expect, it, vi } from 'vitest'

const atender = vi.fn()
vi.mock('../server/utils/respaldo-sitio', () => ({
  entornoDelSitio: () => ({ atender }),
}))

interface Evento {
  method: string
  path: string
  cabeceras: Record<string, string>
  respuesta: { estado?: number; cabeceras: Record<string, string> }
}

const proxyRequest = vi.fn(async () => 'proxiado')
const config: Record<string, unknown> = { apiInterna: 'http://nginx:8080' }

function evento(extra: Partial<Evento> = {}): Evento {
  return { method: 'GET', path: '/api/v1/tramites?per_page=2', cabeceras: { 'x-forwarded-host': 'staging.santamarta.gov.co', 'x-real-ip': '203.0.113.9' }, respuesta: { cabeceras: {} }, ...extra }
}

let manejador: (e: Evento) => Promise<unknown>

beforeEach(async () => {
  atender.mockReset()
  proxyRequest.mockClear()
  config.apiInterna = 'http://nginx:8080'
  vi.stubGlobal('defineEventHandler', (f: unknown) => f)
  vi.stubGlobal('useRuntimeConfig', () => config)
  vi.stubGlobal('getRequestHeader', (e: Evento, n: string) => e.cabeceras[n.toLowerCase()])
  vi.stubGlobal('getProxyRequestHeaders', (e: Evento) => ({ ...e.cabeceras, cookie: 'sede-session=x', authorization: 'Bearer y' }))
  vi.stubGlobal('setResponseHeader', (e: Evento, n: string, v: string) => { e.respuesta.cabeceras[n.toLowerCase()] = v })
  vi.stubGlobal('setResponseStatus', (e: Evento, s: number) => { e.respuesta.estado = s })
  vi.stubGlobal('createError', (o: Record<string, unknown>) => Object.assign(new Error(String(o.statusMessage ?? '')), o))
  vi.stubGlobal('proxyRequest', proxyRequest)
  vi.resetModules()
  manejador = (await import('../server/routes/api/v1/[...path]')).default as unknown as typeof manejador
})

describe('lo que no cambia', () => {
  it.each(['POST', 'PUT', 'PATCH', 'DELETE'])('%s da 405 con Allow y no toca el respaldo ni el proxy', async (method) => {
    const e = evento({ method })
    await expect(manejador(e)).rejects.toMatchObject({ statusCode: 405 })
    expect(e.respuesta.cabeceras.allow).toBe('GET, HEAD')
    expect(atender).not.toHaveBeenCalled()
    expect(proxyRequest).not.toHaveBeenCalled()
  })

  it('sin base interna, 503 «API interna no configurada»', async () => {
    config.apiInterna = ''
    await expect(manejador(evento())).rejects.toMatchObject({ statusCode: 503, statusMessage: 'API interna no configurada' })
    expect(atender).not.toHaveBeenCalled()
  })

  it('una ruta fuera de /api/v1 da 503 (no es un proxy abierto)', async () => {
    await expect(manejador(evento({ path: '/otra/ruta' }))).rejects.toMatchObject({ statusCode: 503 })
  })

  it('HEAD va por el proxy, como hoy, sin pasar por el respaldo', async () => {
    expect(await manejador(evento({ method: 'HEAD' }))).toBe('proxiado')
    expect(atender).not.toHaveBeenCalled()
  })

  it('una lectura NO respaldable sigue por el proxy con las mismas opciones de siempre', async () => {
    atender.mockResolvedValue(null)
    expect(await manejador(evento({ path: '/api/v1/buscar?q=agua' }))).toBe('proxiado')
    const [, destino, opciones] = proxyRequest.mock.calls[0] as unknown as [Evento, string, { headers: Record<string, string>; fetchOptions: { signal: AbortSignal; redirect: string } }]
    expect(destino).toBe('http://nginx:8080/api/v1/buscar?q=agua')
    expect(opciones.headers).toEqual({ 'x-sede-cliente-ip': '203.0.113.9', host: 'staging.santamarta.gov.co' })
    expect(opciones.fetchOptions.redirect).toBe('manual')
    expect(opciones.fetchOptions.signal).toBeInstanceOf(AbortSignal)
  })

  it('sin x-forwarded-host no se añade `host` y sin IP válida la cabecera va vacía', async () => {
    atender.mockResolvedValue(null)
    await manejador(evento({ cabeceras: { 'x-real-ip': 'basura' } }))
    const opciones = (proxyRequest.mock.calls[0] as unknown as [unknown, unknown, { headers: Record<string, string> }])[2]
    expect(opciones.headers).toEqual({ 'x-sede-cliente-ip': '' })
  })
})

describe('lectura respaldable', () => {
  it('con respuesta de la API: la devuelve con su estado y tipo, sin pasar por el proxy ni X-Sede-Respaldo', async () => {
    atender.mockResolvedValue({ estado: 200, tipo: 'application/json', cuerpo: '{"data":[1]}', cabeceras: {} })
    const e = evento()
    expect(await manejador(e)).toBe('{"data":[1]}')
    expect(e.respuesta.estado).toBe(200)
    expect(e.respuesta.cabeceras['content-type']).toBe('application/json')
    expect(e.respuesta.cabeceras['x-sede-respaldo']).toBeUndefined()
    expect(proxyRequest).not.toHaveBeenCalled()
  })

  it('con copia: añade X-Sede-Respaldo', async () => {
    atender.mockResolvedValue({ estado: 200, tipo: 'application/json', cuerpo: '{}', cabeceras: { 'x-sede-respaldo': '42' } })
    const e = evento()
    await manejador(e)
    expect(e.respuesta.cabeceras['x-sede-respaldo']).toBe('42')
  })

  it('un 429 conserva su Retry-After', async () => {
    atender.mockResolvedValue({ estado: 429, tipo: 'application/json', cuerpo: '{}', cabeceras: { 'retry-after': '17' } })
    const e = evento()
    await manejador(e)
    expect(e.respuesta.estado).toBe(429)
    expect(e.respuesta.cabeceras['retry-after']).toBe('17')
  })

  it('sin tipo de contenido no inventa uno', async () => {
    atender.mockResolvedValue({ estado: 204, tipo: '', cuerpo: '', cabeceras: {} })
    const e = evento()
    await manejador(e)
    expect(e.respuesta.cabeceras['content-type']).toBeUndefined()
  })

  it('al núcleo le llegan el destino interno, el anfitrión del visitante y la IP; el resto lo decide el núcleo', async () => {
    atender.mockResolvedValue(null)
    await manejador(evento())
    expect(atender).toHaveBeenCalledTimes(1)
    const l = atender.mock.calls[0]![0] as { metodo: string; destino: string; anfitrion: string; cabeceras: Record<string, string> }
    expect(l.metodo).toBe('GET')
    expect(l.destino).toBe('http://nginx:8080/api/v1/tramites?per_page=2')
    expect(l.anfitrion).toBe('staging.santamarta.gov.co')
    expect(l.cabeceras['x-sede-cliente-ip']).toBe('203.0.113.9')
    expect(l.cabeceras.host).toBe('staging.santamarta.gov.co')
  })

  it('una X-Sede-Cliente-Ip que traiga la petición se sobrescribe con la deducida de X-Real-IP', async () => {
    atender.mockResolvedValue(null)
    await manejador(evento({ cabeceras: { 'x-forwarded-host': 'a.test', 'x-real-ip': '203.0.113.9', 'x-sede-cliente-ip': '6.6.6.6' } }))
    expect((atender.mock.calls[0]![0] as { cabeceras: Record<string, string> }).cabeceras['x-sede-cliente-ip']).toBe('203.0.113.9')
  })

  it('API caída sin copia: 502 «Bad Gateway», igual que el proxy de hoy', async () => {
    atender.mockRejectedValue(new TypeError('fetch failed'))
    await expect(manejador(evento())).rejects.toMatchObject({ statusCode: 502, statusMessage: 'Bad Gateway' })
  })
})
