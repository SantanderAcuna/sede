/**
 * Núcleo del respaldo de la API (T9.2, Fase 0C).
 *
 * Lo que se protege: que la sede siga mostrando la última respuesta válida cuando la API NO
 * contesta (red, tiempo, 5xx), y que en ningún otro caso se sirva algo viejo: un 404, un 410 o un
 * 429 son respuestas de la API y se pasan tal cual. Tampoco se guarda nada que sea de una persona
 * (cookies, `no-store`, URLs firmadas) ni nada que no sea de la sede pública.
 */
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import {
  ApiNoDisponible,
  ApiNoEncontrada,
  ApiRechazada,
  ErrorApi,
} from '../app/utils/cliente-api'
import {
  EDAD_MAXIMA_MS,
  claveDeRespaldo,
  crearRespaldo,
  type Almacen,
  type EntradaRespaldo,
  type RespuestaApi,
  type Resultado,
} from '../server/utils/respaldo-api'

const DOMINIO = 'sede.example.test'

function almacenEnMemoria(): Almacen & { datos: Map<string, EntradaRespaldo>; guardados: string[] } {
  const datos = new Map<string, EntradaRespaldo>()
  const guardados: string[] = []
  return {
    datos,
    guardados,
    async leer(clave) { return datos.get(clave) ?? null },
    async guardar(clave, entrada) { guardados.push(clave); datos.set(clave, entrada) },
  }
}

const ok = (cuerpo: unknown = { data: [1] }, cabeceras: Record<string, string> = {}): RespuestaApi => ({
  estado: 200,
  cabeceras: { 'content-type': 'application/json', ...cabeceras },
  cuerpo: typeof cuerpo === 'string' ? cuerpo : JSON.stringify(cuerpo),
})
const conEstado = (estado: number): RespuestaApi => ({ estado, cabeceras: {}, cuerpo: '{}' })

function montar(respuestas: Array<RespuestaApi | Error>, extra: Record<string, unknown> = {}) {
  let t = 1_000_000
  const almacen = almacenEnMemoria()
  const llamadas: Array<{ ruta: string; query: Record<string, string>; cabeceras: Record<string, string>; tiempoMs: number }> = []
  let i = 0
  const traer = vi.fn(async (ruta: string, query: Record<string, string>, cabeceras: Record<string, string>, tiempoMs: number) => {
    llamadas.push({ ruta, query, cabeceras, tiempoMs })
    const siguiente = respuestas[Math.min(i++, respuestas.length - 1)]!
    if (siguiente instanceof Error) throw siguiente
    return siguiente
  })
  const respaldo = crearRespaldo({ almacen, traer, ahora: () => t, dominio: DOMINIO, ...extra })
  const pedir = (ruta: string, query: Record<string, string> = {}, cabeceras: Record<string, string> = {}, anfitrion = DOMINIO) =>
    respaldo.resolver({ ruta, query, cabeceras, anfitrion })
  return { almacen, traer, llamadas, pedir, respaldo, avanzar: (ms: number) => { t += ms } }
}

const caida = () => Object.assign(new TypeError('fetch failed'), { name: 'FetchError' })
const tiempo = () => Object.assign(new Error('timeout'), { name: 'TimeoutError' })

describe('claveDeRespaldo: qué rutas se respaldan y con qué clave', () => {
  it.each([
    '/identidad/footer', '/tramites', '/tramites/licencia-x', '/noticias', '/noticias/una-noticia',
    '/banners', '/politicas', '/politicas/habeas-data',
  ])('%s se respalda', (ruta) => {
    expect(claveDeRespaldo(ruta, {})).not.toBeNull()
  })

  it.each([
    '/buscar', '/buscar/sugerencias', '/entidad', '/identidad/top-bar', '/identidad/menu',
    '/tramites/x/valoraciones', '/tramites/x/y', '/noticias/', '/panel/noticias', '/otra', '/', '',
    '/tramites/Mayusculas', '/tramites/con espacio', '/tramites/..%2f',
  ])('%s NO se respalda', (ruta) => {
    expect(claveDeRespaldo(ruta, {})).toBeNull()
  })

  it('la clave no depende del orden de los parámetros', () => {
    expect(claveDeRespaldo('/tramites', { per_page: '10', page: '2' }))
      .toBe(claveDeRespaldo('/tramites', { page: '2', per_page: '10' }))
  })

  it('rutas y parámetros distintos dan claves distintas', () => {
    const claves = [
      claveDeRespaldo('/tramites', {}),
      claveDeRespaldo('/tramites', { page: '2' }),
      claveDeRespaldo('/tramites', { page: '2', per_page: '10' }),
      claveDeRespaldo('/noticias', {}),
      claveDeRespaldo('/tramites/a', {}),
    ]
    expect(new Set(claves).size).toBe(claves.length)
  })

  it('la clave lleva el prefijo del ACL (`sitio:`)', () => {
    expect(claveDeRespaldo('/banners', {})!.startsWith('sitio:respaldo:')).toBe(true)
  })

  it.each([
    [{ buscar: 'x' }], [{ categoria: 'a' }], [{ modalidad: 'a' }],
    [{ dependencia: 'a' }], [{ costo: 'a' }], [{ cualquiera: '1' }],
  ])('un filtro (%j) no se respalda (H32)', (query) => {
    expect(claveDeRespaldo('/tramites', query)).toBeNull()
  })

  it.each([['tramites'], ['opa'], ['consultas']])('/tramites con type=%s sí se respalda', (t) => {
    expect(claveDeRespaldo('/tramites', { page: '1', per_page: '10', type: t })).toBe(`sitio:respaldo:/tramites?page=1&per_page=10&type=${t}`)
  })

  it.each([[''], ['TRAMITES'], ['otro'], ['tramites,opa'], ['opa '], ['__proto__']])('type=«%s» fuera del enum no se respalda', (t) => {
    expect(claveDeRespaldo('/tramites', { type: t })).toBeNull()
  })

  it('type solo existe en /tramites: en noticias, políticas y fichas no se respalda', () => {
    expect(claveDeRespaldo('/noticias', { type: 'opa' })).toBeNull()
    expect(claveDeRespaldo('/politicas', { type: 'opa' })).toBeNull()
    expect(claveDeRespaldo('/tramites/x', { type: 'opa' })).toBeNull()
  })

  it('claves distintas por type', () => {
    const a = claveDeRespaldo('/tramites', { type: 'opa' })
    const b = claveDeRespaldo('/tramites', { type: 'consultas' })
    expect(a).not.toBe(b)
  })

  it.each([['0'], ['-1'], ['1.5'], ['abc'], ['101'], ['']])('per_page=«%s» fuera de rango no se respalda (B7)', (v) => {
    expect(claveDeRespaldo('/tramites', { per_page: v })).toBeNull()
  })

  it.each([['1'], ['10'], ['100']])('per_page=%s sí', (v) => {
    expect(claveDeRespaldo('/tramites', { per_page: v })).not.toBeNull()
  })

  it.each([['0'], ['-1'], ['1.5'], ['abc'], ['10001'], ['']])('page=«%s» inválida no se respalda', (v) => {
    expect(claveDeRespaldo('/noticias', { page: v })).toBeNull()
  })

  it('una ficha o el pie no admiten parámetros', () => {
    expect(claveDeRespaldo('/tramites/x', { page: '2' })).toBeNull()
    expect(claveDeRespaldo('/identidad/footer', { per_page: '3' })).toBeNull()
    expect(claveDeRespaldo('/banners', { page: '1' })).toBeNull()
  })

  it('un parámetro repetido (lista) no se respalda', () => {
    expect(claveDeRespaldo('/tramites', { page: ['1', '2'] as unknown as string })).toBeNull()
  })
})

describe('resolver: la API responde', () => {
  it('guarda la respuesta válida y la devuelve con origen «api»', async () => {
    const { pedir, almacen } = montar([ok({ data: [1] })])
    const r = await pedir('/tramites', { per_page: '10' })
    expect(r).toMatchObject({ origen: 'api', estado: 200, cuerpo: '{"data":[1]}' })
    expect(almacen.guardados).toEqual([claveDeRespaldo('/tramites', { per_page: '10' })])
  })

  it('una ruta fuera de la lista devuelve null: el llamador la proxea como hoy y no se guarda', async () => {
    const { pedir, traer, almacen } = montar([ok()])
    expect(await pedir('/buscar', { q: 'x' })).toBeNull()
    expect(traer).not.toHaveBeenCalled()
    expect(almacen.guardados).toEqual([])
  })

  it('la petición sale SIN cookie ni authorization (H33) y con el tiempo máximo de 20 s (la copia se busca aparte, a los 4 s)', async () => {
    const { pedir, llamadas } = montar([ok()])
    await pedir('/banners', {}, { Cookie: 'sesion=1', Authorization: 'Bearer x', 'Proxy-Authorization': 'y', 'Accept-Language': 'es' })
    const enviadas = Object.keys(llamadas[0]!.cabeceras).map(c => c.toLowerCase())
    expect(enviadas).not.toContain('cookie')
    expect(enviadas).not.toContain('authorization')
    expect(enviadas).not.toContain('proxy-authorization')
    expect(enviadas).toContain('accept-language')
    expect(llamadas[0]!.tiempoMs).toBe(20_000)
  })

  it.each([
    ['Set-Cookie', 'a=1'],
    ['Cache-Control', 'no-store'],
    ['Cache-Control', 'public, NO-STORE'],
    ['Cache-Control', 'private, no-store'],
  ])('con «%s: %s» se devuelve pero NO se guarda', async (cab, valor) => {
    const { pedir, almacen } = montar([ok({ data: 1 }, { [cab]: valor })])
    expect((await pedir('/banners'))!.origen).toBe('api')
    expect(almacen.guardados).toEqual([])
  })

  // La petición sale sin cookie ni authorization: `private` (el valor por defecto de Laravel,
  // «no-cache, private») no dice nada de una persona y bloquearlo dejaba el respaldo inerte.
  it.each([['no-cache, private'], ['private, max-age=0'], ['PRIVATE'], ['no-cache']])('«Cache-Control: %s» sí se guarda', async (valor) => {
    const { pedir, almacen } = montar([ok({ data: 1 }, { 'cache-control': valor })])
    await pedir('/banners')
    expect(almacen.guardados).toHaveLength(1)
  })

  it('«Cache-Control: public, max-age=60» sí se guarda', async () => {
    const { pedir, almacen } = montar([ok({ data: 1 }, { 'cache-control': 'public, max-age=60' })])
    await pedir('/banners')
    expect(almacen.guardados).toHaveLength(1)
  })

  it('un cuerpo sobre el tope no se guarda', async () => {
    const { pedir, almacen } = montar([ok('x'.repeat(101))], { topeBytes: 100 })
    expect((await pedir('/banners'))!.origen).toBe('api')
    expect(almacen.guardados).toEqual([])
  })

  it('el tope se mide en bytes, no en caracteres', async () => {
    const { pedir, almacen } = montar([ok('ñ'.repeat(60))], { topeBytes: 100 })
    await pedir('/banners')
    expect(almacen.guardados).toEqual([])
  })

  it('un anfitrión distinto del dominio configurado no guarda (H34)', async () => {
    const { pedir, almacen } = montar([ok()])
    expect((await pedir('/banners', {}, {}, 'otro.example.test'))!.origen).toBe('api')
    expect(almacen.guardados).toEqual([])
  })

  it('el anfitrión se compara sin puerto ni mayúsculas', async () => {
    const { pedir, almacen } = montar([ok()])
    await pedir('/banners', {}, {}, 'SEDE.example.test:443')
    expect(almacen.guardados).toHaveLength(1)
  })

  it.each([
    '{"data":{"url":"https://x.test/f?signature=abc"}}',
    '{"data":{"url":"https://x.test/f?a=1&expires=99"}}',
    '{"data":{"url":"https://x.test/f?X-Amz-Signature=abc"}}',
  ])('un cuerpo con URL firmada no se guarda', async (cuerpo) => {
    const { pedir, almacen } = montar([ok(cuerpo)])
    await pedir('/banners')
    expect(almacen.guardados).toEqual([])
  })

  it('un page mayor que meta.last_page no se guarda; uno dentro del rango sí (H32)', async () => {
    const fuera = montar([ok({ data: [], meta: { current_page: 9, last_page: 3 } })])
    await fuera.pedir('/noticias', { page: '9' })
    expect(fuera.almacen.guardados).toEqual([])

    const dentro = montar([ok({ data: [1], meta: { current_page: 3, last_page: 3 } })])
    await dentro.pedir('/noticias', { page: '3' })
    expect(dentro.almacen.guardados).toHaveLength(1)
  })

  it('page>1 sin meta.last_page legible no se guarda', async () => {
    const { pedir, almacen } = montar([ok('no es json')])
    await pedir('/noticias', { page: '2' })
    expect(almacen.guardados).toEqual([])
  })

  it('un fallo del almacén al guardar no rompe la respuesta (abre en falso)', async () => {
    const { pedir, almacen } = montar([ok()])
    almacen.guardar = async () => { throw new Error('redis caído') }
    expect((await pedir('/banners'))!.origen).toBe('api')
  })
})

describe('la escritura del respaldo no demora la respuesta ni se repite en cada visita', () => {
  it('un almacén que no contesta al guardar NO retrasa la respuesta de la API', async () => {
    const m = montar([ok()])
    m.almacen.guardar = () => new Promise<void>(() => {})
    expect(await m.pedir('/banners')).toMatchObject({ origen: 'api', estado: 200 })
  })

  it('`esperarEscrituras` espera a las que quedaron en vuelo', async () => {
    const m = montar([ok()])
    let terminada = false
    m.almacen.guardar = async () => { await new Promise(r => setTimeout(r, 20)); terminada = true }
    await m.pedir('/banners')
    expect(terminada).toBe(false)
    await m.respaldo.esperarEscrituras()
    expect(terminada).toBe(true)
  })

  it('una misma clave se escribe como mucho una vez cada 10 s', async () => {
    const m = montar([ok()])
    await m.pedir('/banners')
    m.avanzar(9_999)
    await m.pedir('/banners')
    expect(m.almacen.guardados).toHaveLength(1)
    m.avanzar(2)
    await m.pedir('/banners')
    expect(m.almacen.guardados).toHaveLength(2)
  })

  it('el límite es por clave: otra ruta se escribe enseguida', async () => {
    const m = montar([ok()])
    await m.pedir('/banners')
    await m.pedir('/politicas')
    expect(m.almacen.guardados).toHaveLength(2)
  })

  it('una respuesta que no se guarda (con Set-Cookie) no consume el turno de escritura', async () => {
    const m = montar([ok({ data: 1 }, { 'set-cookie': 'a=1' }), ok()])
    await m.pedir('/banners')
    await m.pedir('/banners')
    expect(m.almacen.guardados).toHaveLength(1)
  })

  it('una escritura fallida deja reintentar en la siguiente visita', async () => {
    const m = montar([ok()])
    let intento = 0
    m.almacen.guardar = async (clave, entrada) => {
      intento += 1
      if (intento === 1) throw new Error('redis')
      m.almacen.datos.set(clave, entrada)
    }
    await m.pedir('/banners')
    await m.respaldo.esperarEscrituras()
    await m.pedir('/banners')
    await m.respaldo.esperarEscrituras()
    expect(m.almacen.datos.size).toBe(1)
  })
})

describe('resolver: la API no contesta', () => {
  it('con respaldo, lo sirve con su edad y el motivo', async () => {
    const m = montar([ok({ data: 'viejo' }), caida()])
    await m.pedir('/banners')
    m.avanzar(5000)
    const r = await m.pedir('/banners')
    expect(r).toMatchObject({ origen: 'respaldo', estado: 200, cuerpo: '{"data":"viejo"}', edadMs: 5000, motivo: 'red' })
  })

  it.each([
    ['red', caida],
    ['tiempo', tiempo],
  ])('motivo «%s»', async (motivo, fallo) => {
    const m = montar([ok(), fallo()])
    await m.pedir('/banners')
    expect(await m.pedir('/banners')).toMatchObject({ origen: 'respaldo', motivo })
  })

  it.each([500, 502, 503, 504])('un %i sirve el respaldo (motivo 5xx)', async (estado) => {
    const m = montar([ok(), conEstado(estado)])
    await m.pedir('/banners')
    expect(await m.pedir('/banners')).toMatchObject({ origen: 'respaldo', motivo: '5xx' })
  })

  it('sin respaldo, una excepción se propaga como hoy', async () => {
    const m = montar([caida()])
    await expect(m.pedir('/banners')).rejects.toThrow('fetch failed')
  })

  it('sin respaldo, un 5xx se devuelve tal cual (origen «api»)', async () => {
    const m = montar([conEstado(503)])
    expect(await m.pedir('/banners')).toMatchObject({ origen: 'api', estado: 503 })
  })

  it.each([404, 410, 429, 401, 403, 422])('un %i NO usa el respaldo, aunque exista', async (estado) => {
    const m = montar([ok(), conEstado(estado)])
    await m.pedir('/banners')
    expect(await m.pedir('/banners')).toMatchObject({ origen: 'api', estado })
  })

  it.each([
    ['429', 429, '17'],
    ['503 sin respaldo', 503, '30'],
  ])('un %s lleva su Retry-After a quien llama (cualquier mayúscula) y no otras cabeceras', async (_nombre, estado, segundos) => {
    const m = montar([{ estado, cabeceras: { 'Retry-After': segundos, 'Set-Cookie': 'a=b', 'X-Otra': '1' }, cuerpo: '{}' }])
    const r = await m.pedir('/banners')
    expect(r).toMatchObject({ origen: 'api', estado, retryAfter: segundos })
    expect(Object.keys(r!)).not.toContain('cabeceras')
  })

  it('sin Retry-After en la respuesta, el resultado no inventa uno', async () => {
    const m = montar([conEstado(429)])
    expect((await m.pedir('/banners'))!.retryAfter).toBeUndefined()
  })

  it('un respaldo más viejo que la edad máxima (24 h) no se sirve', async () => {
    const m = montar([ok(), conEstado(503)])
    await m.pedir('/banners')
    m.avanzar(EDAD_MAXIMA_MS + 1)
    expect(await m.pedir('/banners')).toMatchObject({ origen: 'api', estado: 503 })
  })

  it('justo en la edad máxima todavía se sirve', async () => {
    const m = montar([ok(), conEstado(503)])
    await m.pedir('/banners')
    m.avanzar(EDAD_MAXIMA_MS)
    expect(await m.pedir('/banners')).toMatchObject({ origen: 'respaldo' })
  })

  it('un fallo del almacén al leer cuenta como «sin respaldo»', async () => {
    const m = montar([conEstado(503)])
    m.almacen.leer = async () => { throw new Error('redis caído') }
    expect(await m.pedir('/banners')).toMatchObject({ origen: 'api', estado: 503 })
  })

  it('no se sirve el respaldo de otra clave (otra página)', async () => {
    const m = montar([ok({ data: 1, meta: { last_page: 5 } }), conEstado(503)])
    await m.pedir('/noticias', { page: '1' })
    expect(await m.pedir('/noticias', { page: '2' })).toMatchObject({ origen: 'api', estado: 503 })
  })
})

// H31 revisada (decisión de José, 2026-11-25): a los 4 s se sirve la copia SI EXISTE; si no existe se
// sigue esperando a la API hasta 20 s, como el proxy de siempre. La petición a la API NO se cancela
// a los 4 s: si la respuesta llega después y es válida, se guarda como copia nueva; y una respuesta
// lenta que llega bien no cuenta como fallo del cortocircuito.
describe('tiempo: a los 4 s la copia si existe; sin copia se espera hasta 20 s', () => {
  beforeEach(() => { vi.useFakeTimers() })
  afterEach(() => { vi.useRealTimers() })

  interface Plan { ms: number; respuesta?: RespuestaApi }
  interface Pedido { listo: boolean; valor?: Resultado | null; error?: unknown }

  /** `respuesta` ausente = la API se cuelga: falla con TimeoutError cuando se agota el tiempo que se le dio. */
  function montarLento(planes: Plan[]) {
    const almacen = almacenEnMemoria()
    const tiempos: number[] = []
    let i = 0
    const traer = vi.fn((_r: string, _q: Record<string, string>, _c: Record<string, string>, tiempoMs: number) => {
      tiempos.push(tiempoMs)
      const plan = planes[Math.min(i++, planes.length - 1)]!
      return new Promise<RespuestaApi>((resolver, rechazar) => {
        if (plan.respuesta !== undefined && plan.ms <= tiempoMs) setTimeout(() => resolver(plan.respuesta!), plan.ms)
        else setTimeout(() => rechazar(Object.assign(new Error('tiempo agotado'), { name: 'TimeoutError' })), tiempoMs)
      })
    })
    const respaldo = crearRespaldo({ almacen, traer, ahora: () => Date.now(), dominio: DOMINIO })
    const clave = claveDeRespaldo('/tramites/uno', {})!
    const conCopia = (ruta = '/tramites/uno') => { almacen.datos.set(claveDeRespaldo(ruta, {})!, { cuerpo: '{"copia":1}', tipo: 'application/json', guardadoEn: Date.now() }) }
    const pedir = (ruta = '/tramites/uno'): Pedido => {
      const pedido: Pedido = { listo: false }
      respaldo.resolver({ ruta, query: {}, cabeceras: {}, anfitrion: DOMINIO })
        .then((v) => { pedido.listo = true; pedido.valor = v }, (e: unknown) => { pedido.listo = true; pedido.error = e })
      return pedido
    }
    return { almacen, traer, tiempos, respaldo, clave, conCopia, pedir }
  }
  const avanzar = (ms: number) => vi.advanceTimersByTimeAsync(ms)

  it('con copia y la API a 6 s: la copia sale a los 4 s, ni antes ni después', async () => {
    const t = montarLento([{ ms: 6000, respuesta: ok({ data: 'nueva' }) }])
    t.conCopia()
    const p = t.pedir()
    await avanzar(3999)
    expect(p.listo).toBe(false)
    await avanzar(1)
    expect(p.listo).toBe(true)
    expect(p.valor).toMatchObject({ origen: 'respaldo', motivo: 'tiempo', cuerpo: '{"copia":1}' })
    await avanzar(20_000)
  })

  it('la petición a la API sale con 20 s de plazo: no se cancela a los 4 s', async () => {
    const t = montarLento([{ ms: 6000, respuesta: ok() }])
    t.pedir()
    await avanzar(30_000)
    expect(t.tiempos).toEqual([20_000])
  })

  it('sin copia y la API a 6 s: responde 200 a los 6 s, no error a los 4 s', async () => {
    const t = montarLento([{ ms: 6000, respuesta: ok({ data: 'lenta' }) }])
    const p = t.pedir()
    await avanzar(5999)
    expect(p.listo).toBe(false)
    await avanzar(1)
    expect(p.error).toBeUndefined()
    expect(p.valor).toMatchObject({ origen: 'api', estado: 200, cuerpo: '{"data":"lenta"}' })
  })

  it('sin copia y la API colgada: TimeoutError a los 20 s, como el proxy de siempre', async () => {
    const t = montarLento([{ ms: 0 }])
    const p = t.pedir()
    await avanzar(19_999)
    expect(p.listo).toBe(false)
    await avanzar(1)
    expect(p.listo).toBe(true)
    expect((p.error as Error).name).toBe('TimeoutError')
  })

  it('sin copia y la API contesta 503 a los 6 s: devuelve ese 503 a los 6 s', async () => {
    const t = montarLento([{ ms: 6000, respuesta: conEstado(503) }])
    const p = t.pedir()
    await avanzar(5999)
    expect(p.listo).toBe(false)
    await avanzar(1)
    expect(p.valor).toMatchObject({ origen: 'api', estado: 503 })
  })

  it('servida la copia a los 4 s, la respuesta válida que llega a los 6 s se guarda como copia nueva', async () => {
    const t = montarLento([{ ms: 6000, respuesta: ok({ data: 'nueva' }) }])
    t.conCopia()
    t.pedir()
    await avanzar(6000)
    await t.respaldo.esperarEscrituras()
    expect(t.almacen.datos.get(t.clave)!.cuerpo).toBe('{"data":"nueva"}')
  })

  it('una respuesta lenta que llega bien NO cuenta como fallo: el circuito no abre', async () => {
    const t = montarLento([{ ms: 6000, respuesta: ok() }])
    t.conCopia()
    t.pedir(); t.pedir(); t.pedir()
    await avanzar(4500) // las tres ya sirvieron la copia; la API aún no ha contestado
    t.pedir()
    await avanzar(0)
    expect(t.traer).toHaveBeenCalledTimes(4) // la cuarta SÍ llama a la API: el circuito sigue cerrado
    await avanzar(30_000)
  })

  it('una respuesta lenta que acaba en 5xx SÍ cuenta: tras tres, el circuito abre y la cuarta ni llama', async () => {
    const t = montarLento([{ ms: 6000, respuesta: conEstado(503) }])
    t.conCopia()
    t.pedir(); t.pedir(); t.pedir()
    await avanzar(6000)
    const cuarta = t.pedir()
    await avanzar(1)
    expect(t.traer).toHaveBeenCalledTimes(3)
    expect(cuarta.valor).toMatchObject({ origen: 'respaldo', motivo: 'circuito' })
  })

  it('una API colgada con copia: los 3 plazos de 20 s agotados abren el circuito', async () => {
    const t = montarLento([{ ms: 0 }])
    t.conCopia()
    t.pedir(); t.pedir(); t.pedir()
    await avanzar(20_000)
    const cuarta = t.pedir()
    await avanzar(1)
    expect(t.traer).toHaveBeenCalledTimes(3)
    expect(cuarta.valor).toMatchObject({ origen: 'respaldo', motivo: 'circuito' })
  })

  it('la sonda sigue «en vuelo» hasta que la API contesta: otra petición no lanza otra sonda', async () => {
    const t = montarLento([{ ms: 0 }])
    t.conCopia()
    t.pedir(); t.pedir(); t.pedir()
    await avanzar(20_000) // circuito abierto por 30 s
    await avanzar(30_001) // pausa cumplida
    const sonda = t.pedir()
    await avanzar(4000)
    expect(sonda.valor).toMatchObject({ origen: 'respaldo', motivo: 'tiempo' }) // la sonda ya sirvió la copia
    expect(t.traer).toHaveBeenCalledTimes(4)
    const otra = t.pedir()
    await avanzar(1)
    expect(t.traer).toHaveBeenCalledTimes(4) // sin sonda nueva mientras la primera sigue sin contestar
    expect(otra.valor).toMatchObject({ origen: 'respaldo', motivo: 'circuito' })
    await avanzar(20_000)
  })

  it('un almacén que revienta al guardar la copia nueva NO deja un rechazo sin dueño (Node 24 lo trataría como error fatal)', async () => {
    const t = montarLento([{ ms: 6000, respuesta: ok({ data: 'nueva' }) }])
    t.conCopia()
    t.almacen.guardar = (() => { throw new Error('almacén roto') }) as never
    const sinDueno = vi.fn()
    process.on('unhandledRejection', sinDueno)
    try {
      t.pedir()
      await avanzar(6000)
      await t.respaldo.esperarEscrituras()
      await avanzar(10)
      expect(sinDueno).not.toHaveBeenCalled()
    } finally {
      process.off('unhandledRejection', sinDueno)
    }
  })

  it('si liquidar la respuesta tardía lanzara algo, tampoco queda un rechazo sin dueño', async () => {
    const almacen = almacenEnMemoria()
    const traer = vi.fn(() => new Promise<RespuestaApi>((resolver) => { setTimeout(() => resolver(ok()), 6000) }))
    // Un dominio que no es texto hace lanzar a `sePuedeGuardar`: simula un fallo inesperado del código.
    const respaldo = crearRespaldo({ almacen, traer, ahora: () => Date.now(), dominio: undefined as unknown as string })
    almacen.datos.set(claveDeRespaldo('/tramites/uno', {})!, { cuerpo: '{}', tipo: 'application/json', guardadoEn: Date.now() })
    const sinDueno = vi.fn()
    process.on('unhandledRejection', sinDueno)
    try {
      const r = respaldo.resolver({ ruta: '/tramites/uno', query: {}, cabeceras: {}, anfitrion: DOMINIO })
      await avanzar(4000)
      await expect(r).resolves.toMatchObject({ origen: 'respaldo', motivo: 'tiempo' })
      await avanzar(3000)
      await avanzar(10)
      expect(sinDueno).not.toHaveBeenCalled()
    } finally {
      process.off('unhandledRejection', sinDueno)
    }
  })

  it('un fallo inesperado al liquidar (guardar, contar) no convierte en error una respuesta sana de la API', async () => {
    const almacen = almacenEnMemoria()
    const traer = vi.fn(async () => ok({ data: 'sana' }))
    const respaldo = crearRespaldo({ almacen, traer, ahora: () => Date.now(), dominio: undefined as unknown as string })
    const r = respaldo.resolver({ ruta: '/tramites/uno', query: {}, cabeceras: {}, anfitrion: DOMINIO })
    await avanzar(10)
    await expect(r).resolves.toMatchObject({ origen: 'api', estado: 200, cuerpo: '{"data":"sana"}' })
  })

  describe('ventana de lentitud: tras servir una copia por lentitud, las lecturas siguientes no vuelven a esperar 4 s', () => {
    it('con la API colgada, una segunda lectura (otra ruta, con copia) sale al instante y la API sigue llamándose', async () => {
      const t = montarLento([{ ms: 0 }])
      t.conCopia('/banners'); t.conCopia('/politicas')
      const primera = t.pedir('/banners')
      await avanzar(4000)
      expect(primera.valor).toMatchObject({ origen: 'respaldo', motivo: 'tiempo' })
      const segunda = t.pedir('/politicas')
      await avanzar(0)
      expect(segunda.listo).toBe(true)
      expect(segunda.valor).toMatchObject({ origen: 'respaldo', motivo: 'tiempo', cuerpo: '{"copia":1}' })
      expect(t.traer).toHaveBeenCalledTimes(2)
      await avanzar(30_000)
    })

    it('la portada (4 lecturas seguidas) con la API colgada tarda 4 s, no 16', async () => {
      const t = montarLento([{ ms: 0 }])
      const rutas = ['/banners', '/identidad/footer', '/noticias', '/tramites']
      rutas.forEach(r => t.conCopia(r))
      const inicio = Date.now()
      let terminada = -1
      void (async () => {
        for (const r of rutas) await t.respaldo.resolver({ ruta: r, query: {}, cabeceras: {}, anfitrion: DOMINIO })
        terminada = Date.now() - inicio
      })()
      await avanzar(20_000)
      expect(terminada).toBeGreaterThanOrEqual(4000)
      expect(terminada).toBe(4000)
      await avanzar(30_000)
    })

    it('pasados 30 s la ventana se cierra: la siguiente lectura vuelve a esperar 4 s', async () => {
      const t = montarLento([{ ms: 0 }])
      t.conCopia('/banners'); t.conCopia('/politicas')
      t.pedir('/banners')
      await avanzar(4000)
      await avanzar(30_000)
      const p = t.pedir('/politicas')
      await avanzar(3999)
      expect(p.listo).toBe(false)
      await avanzar(1)
      expect(p.valor).toMatchObject({ origen: 'respaldo', motivo: expect.stringMatching(/tiempo|circuito/) })
      await avanzar(30_000)
    })

    it('si la API contesta bien, la ventana se cierra: la lectura siguiente espera a la API, no sirve la copia vieja', async () => {
      const t = montarLento([{ ms: 6000, respuesta: ok({ data: 'nueva' }) }, { ms: 1000, respuesta: ok({ data: 'rapida' }) }])
      t.conCopia('/banners'); t.conCopia('/politicas')
      t.pedir('/banners')
      await avanzar(6000) // sirvió la copia a los 4 s y la API contestó bien a los 6 s
      const p = t.pedir('/politicas')
      await avanzar(1000)
      expect(p.valor).toMatchObject({ origen: 'api', cuerpo: '{"data":"rapida"}' })
    })

    it('sin copia no hay atajo: la lectura sigue esperando a la API', async () => {
      const t = montarLento([{ ms: 0 }, { ms: 6000, respuesta: ok({ data: 'lenta' }) }])
      t.conCopia('/banners')
      t.pedir('/banners')
      await avanzar(4000)
      const sinCopia = t.pedir('/politicas')
      await avanzar(5999)
      expect(sinCopia.listo).toBe(false)
      await avanzar(1)
      expect(sinCopia.valor).toMatchObject({ origen: 'api', cuerpo: '{"data":"lenta"}' })
      await avanzar(30_000)
    })

    it('una respuesta 5xx de la API no cierra la ventana (no es una contestación sana)', async () => {
      const t = montarLento([{ ms: 0 }, { ms: 100, respuesta: conEstado(503) }])
      t.conCopia('/banners'); t.conCopia('/politicas'); t.conCopia('/noticias')
      t.pedir('/banners')
      await avanzar(4000)
      t.pedir('/politicas')
      await avanzar(200) // el 503 de la segunda lectura ya llegó
      const tercera = t.pedir('/noticias')
      await avanzar(0)
      expect(tercera.listo).toBe(true)
      await avanzar(30_000)
    })
  })

  it('el plazo de 4 s no deja temporizadores colgados cuando la API contesta antes', async () => {
    const t = montarLento([{ ms: 100, respuesta: ok() }])
    const p = t.pedir()
    await avanzar(100)
    expect(p.valor).toMatchObject({ origen: 'api' })
    expect(vi.getTimerCount()).toBe(0)
  })
})

describe('cortocircuito (H31)', () => {
  const sembrado = async (fallos: Array<RespuestaApi | Error>) => {
    const m = montar([ok({ data: 'bueno' }), ...fallos])
    await m.pedir('/banners')
    return m
  }

  it('abre tras 3 fallos seguidos y deja de llamar a la API', async () => {
    const m = await sembrado([caida(), caida(), caida(), ok()])
    for (let n = 0; n < 3; n++) await m.pedir('/banners')
    expect(m.traer).toHaveBeenCalledTimes(4)
    const r = await m.pedir('/banners')
    expect(r).toMatchObject({ origen: 'respaldo', motivo: 'circuito' })
    expect(m.traer).toHaveBeenCalledTimes(4)
  })

  it('dos fallos no abren; un éxito entre medias reinicia la cuenta', async () => {
    const m = await sembrado([caida(), caida(), ok(), caida(), caida(), ok()])
    for (let n = 0; n < 5; n++) await m.pedir('/banners')
    expect(m.traer).toHaveBeenCalledTimes(6)
  })

  it('el circuito es de la API, no de la ruta: abre también para otra ruta con respaldo', async () => {
    const m = montar([ok({ data: 'a' }), ok({ data: 'b' }), caida(), caida(), caida()])
    await m.pedir('/banners')
    await m.pedir('/politicas')
    for (let n = 0; n < 3; n++) await m.pedir('/banners')
    const llamadasAntes = m.traer.mock.calls.length
    expect(await m.pedir('/politicas')).toMatchObject({ origen: 'respaldo', motivo: 'circuito' })
    expect(m.traer.mock.calls.length).toBe(llamadasAntes)
  })

  it('abierto y sin respaldo para esa ruta, la petición sí va a la API (como hoy)', async () => {
    const m = await sembrado([caida(), caida(), caida(), ok({ data: 'otra' })])
    for (let n = 0; n < 3; n++) await m.pedir('/banners')
    const r = await m.pedir('/politicas')
    expect(r).toMatchObject({ origen: 'api', estado: 200 })
  })

  it('pasados 30 s, prueba con UNA sola petición y cierra si responde', async () => {
    const m = await sembrado([caida(), caida(), caida(), ok({ data: 'nuevo' }), ok({ data: 'nuevo' })])
    for (let n = 0; n < 3; n++) await m.pedir('/banners')
    m.avanzar(29_999)
    expect(await m.pedir('/banners')).toMatchObject({ origen: 'respaldo', motivo: 'circuito' })
    m.avanzar(2)
    expect(await m.pedir('/banners')).toMatchObject({ origen: 'api', cuerpo: '{"data":"nuevo"}' })
    expect(await m.pedir('/banners')).toMatchObject({ origen: 'api' })
  })

  it('si la sonda falla, vuelve a abrir otros 30 s', async () => {
    const m = await sembrado([caida(), caida(), caida(), caida(), ok()])
    for (let n = 0; n < 3; n++) await m.pedir('/banners')
    m.avanzar(30_001)
    expect(await m.pedir('/banners')).toMatchObject({ origen: 'respaldo', motivo: 'red' })
    const llamadas = m.traer.mock.calls.length
    m.avanzar(10_000)
    expect(await m.pedir('/banners')).toMatchObject({ origen: 'respaldo', motivo: 'circuito' })
    expect(m.traer.mock.calls.length).toBe(llamadas)
  })

  it('mientras la sonda está en vuelo, las demás peticiones sirven el respaldo sin llamar', async () => {
    const m = montar([ok({ data: 'bueno' }), caida(), caida(), caida()])
    await m.pedir('/banners')
    for (let n = 0; n < 3; n++) await m.pedir('/banners')
    m.avanzar(30_001)
    let liberar!: (r: RespuestaApi) => void
    m.traer.mockImplementationOnce(() => new Promise<RespuestaApi>((res) => { liberar = res }))
    const sonda = m.pedir('/banners')
    const llamadas = m.traer.mock.calls.length
    expect(await m.pedir('/banners')).toMatchObject({ origen: 'respaldo', motivo: 'circuito' })
    expect(m.traer.mock.calls.length).toBe(llamadas)
    liberar(ok({ data: 'nuevo' }))
    expect(await sonda).toMatchObject({ origen: 'api' })
  })

  it('un 404, un 410 o un 422 son respuestas de la API: reinician la cuenta', async () => {
    const m = await sembrado([caida(), caida(), conEstado(404), caida(), caida(), ok()])
    for (let n = 0; n < 5; n++) await m.pedir('/banners')
    expect(await m.pedir('/banners')).toMatchObject({ origen: 'api' })
  })

  it('un 429 no cuenta ni como fallo ni como éxito (lo pone nginx, no la API)', async () => {
    const m = await sembrado([caida(), caida(), conEstado(429), caida(), ok()])
    for (let n = 0; n < 4; n++) await m.pedir('/banners')
    expect(await m.pedir('/banners')).toMatchObject({ origen: 'respaldo', motivo: 'circuito' })
  })
})

describe('los errores de la API llevan su nombre explícito (el paquete minificado renombra las clases)', () => {
  it.each([
    [new ApiNoEncontrada('x'), 'ApiNoEncontrada'],
    [new ApiNoDisponible('x'), 'ApiNoDisponible'],
    [new ApiRechazada('x'), 'ApiRechazada'],
    [new ErrorApi('x'), 'ErrorApi'],
  ])('%#', (error, nombre) => {
    expect(error.name).toBe(nombre)
  })
})
