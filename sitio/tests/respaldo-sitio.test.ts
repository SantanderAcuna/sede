// @vitest-environment node
/**
 * El ensamblado del respaldo en el servidor del sitio (T9.4): configuración leída en EJECUCIÓN,
 * una sola instancia por proceso (el cortocircuito vive en ella), almacén perezoso que falla abierto
 * y cierre ordenado.
 */
import { describe, expect, it, vi } from 'vitest'

import {
  cerrarEntornoDelSitio,
  configDelSitio,
  crearEntornoDelSitio,
  entornoDelSitio,
  type ConfigSitio,
} from '../server/utils/respaldo-sitio'
import type { AlmacenRedis } from '../server/utils/almacen-respaldo'
import type { EntradaRespaldo } from '../server/utils/respaldo-api'

const DOMINIO = 'staging.santamarta.gov.co'
const REDIS = { host: 'redis', port: 6379, usuario: 'sitio', password: 'clave-secreta-123' }
const CONFIG: ConfigSitio = { base: 'http://nginx:8080', dominio: DOMINIO, redis: REDIS }

function almacenFalso() {
  const datos = new Map<string, EntradaRespaldo>()
  const almacen = {
    leer: vi.fn(async (c: string) => datos.get(c) ?? null),
    guardar: vi.fn(async (c: string, e: EntradaRespaldo) => { datos.set(c, e) }),
    informarFallo: vi.fn(),
    cerrar: vi.fn(async () => {}),
  }
  return { datos, almacen: almacen as unknown as AlmacenRedis & typeof almacen }
}

const json = (cuerpo: unknown, estado = 200) =>
  new Response(JSON.stringify(cuerpo), { status: estado, headers: { 'content-type': 'application/json' } })

const lectura = (extra: Record<string, unknown> = {}) => ({
  metodo: 'GET',
  destino: 'http://nginx:8080/api/v1/tramites?per_page=2',
  anfitrion: DOMINIO,
  cabeceras: { 'x-sede-cliente-ip': '203.0.113.9' },
  ...extra,
})

describe('configDelSitio: se lee del runtimeConfig (variables NUXT_… en ejecución)', () => {
  it('toma la base interna, el dominio público y el Redis', () => {
    expect(configDelSitio({
      apiInterna: 'http://nginx:8080',
      redisHost: 'redis', redisPort: 6379, redisUsuario: 'sitio', redisPassword: 'x',
      public: { dominio: DOMINIO },
    })).toEqual({ base: 'http://nginx:8080', dominio: DOMINIO, redis: { host: 'redis', port: 6379, usuario: 'sitio', password: 'x' } })
  })

  it('el puerto llega como texto desde el entorno y se convierte en número', () => {
    expect(configDelSitio({ redisPort: '6380' }).redis.port).toBe(6380)
  })

  it('faltantes o basura: valores vacíos y puerto 0 (el almacén queda sin configurar)', () => {
    const c = configDelSitio({ redisPort: 'abc', public: {} })
    expect(c).toEqual({ base: '', dominio: '', redis: { host: '', port: 0, usuario: '', password: '' } })
  })
})

describe('crearEntornoDelSitio', () => {
  it('API sana: pasa la respuesta y guarda la copia en segundo plano', async () => {
    const { almacen, datos } = almacenFalso()
    const entorno = crearEntornoDelSitio(CONFIG, {
      fetch: (async () => json({ data: [1] })) as unknown as typeof fetch,
      almacenCompartido: async () => almacen,
    })
    const r = await entorno.atender(lectura())
    expect(r).toMatchObject({ estado: 200, cabeceras: {} })
    await entorno.esperarEscrituras()
    expect([...datos.keys()]).toEqual(['sitio:respaldo:/tramites?per_page=2'])
  })

  it('API caída con copia: la sirve, con X-Sede-Respaldo y una línea de registro', async () => {
    const { almacen } = almacenFalso()
    const registrar = vi.fn()
    let sana = true
    const entorno = crearEntornoDelSitio(CONFIG, {
      fetch: (async () => { if (!sana) throw new TypeError('fetch failed'); return json({ data: [1] }) }) as unknown as typeof fetch,
      almacenCompartido: async () => almacen,
      registrar,
    })
    await entorno.atender(lectura())
    await entorno.esperarEscrituras()
    sana = false
    const r = await entorno.atender(lectura())
    expect(r).toMatchObject({ estado: 200, cuerpo: '{"data":[1]}' })
    expect(r!.cabeceras['x-sede-respaldo']).toMatch(/^\d+$/)
    expect(registrar).toHaveBeenCalledTimes(1)
    expect(registrar.mock.calls[0]![0]).toMatchObject({ evento: 'respaldo_servido', ruta: '/tramites', motivo: 'red' })
  })

  // Sin Redis no hay copia que servir: el respaldo no aporta nada y el sitio sigue como antes de T9.4 (proxy, 20 s). Antes del cambio de H31 el corte de 4 s convertía en 502 una API
  // lenta que el proxy (20 s) sí espera (medido: API a 6 s → TimeoutError a los 4,0 s). Por eso,
  // con la configuración incompleta, la lectura NO pasa por el respaldo y la ruta la sirve como hoy.
  it.each([
    ['sin nada', { host: '', port: 0, usuario: '', password: '' }],
    ['sin host', { ...REDIS, host: '' }],
    ['sin usuario', { ...REDIS, usuario: '' }],
    ['sin contraseña', { ...REDIS, password: '' }],
    ['puerto 0', { ...REDIS, port: 0 }],
  ])('Redis sin configurar (%s): devuelve null, no llama a la API ni a Redis, y la ruta usa el proxy', async (_caso, redis) => {
    const fetchFalso = vi.fn(async () => json({ data: [1] }))
    const almacen = vi.fn()
    const entorno = crearEntornoDelSitio({ ...CONFIG, redis }, {
      fetch: fetchFalso as unknown as typeof fetch,
      almacenCompartido: almacen as never,
    })
    expect(await entorno.atender(lectura())).toBeNull()
    expect(fetchFalso).not.toHaveBeenCalled()
    expect(almacen).not.toHaveBeenCalled()
  })

  it('con Redis configurado SÍ pasa por el respaldo (a los 4 s sirve la copia si existe; si no, espera a la API)', async () => {
    const { almacen } = almacenFalso()
    const fetchFalso = vi.fn(async () => json({ data: [1] }))
    const entorno = crearEntornoDelSitio(CONFIG, {
      fetch: fetchFalso as unknown as typeof fetch,
      almacenCompartido: (async () => almacen) as never,
    })
    expect((await entorno.atender(lectura()))!.estado).toBe(200)
    expect(fetchFalso).toHaveBeenCalledTimes(1)
  })

  it('un anfitrión que no es el dominio configurado no guarda nada (H34)', async () => {
    const { almacen, datos } = almacenFalso()
    const entorno = crearEntornoDelSitio(CONFIG, {
      fetch: (async () => json({ data: [1] })) as unknown as typeof fetch,
      almacenCompartido: async () => almacen,
    })
    await entorno.atender(lectura({ anfitrion: 'otro.example' }))
    await entorno.esperarEscrituras()
    expect(datos.size).toBe(0)
  })

  it('sin anfitrión (cabecera ausente) tampoco guarda: falla seguro', async () => {
    const { almacen, datos } = almacenFalso()
    const entorno = crearEntornoDelSitio(CONFIG, {
      fetch: (async () => json({ data: [1] })) as unknown as typeof fetch,
      almacenCompartido: async () => almacen,
    })
    await entorno.atender(lectura({ anfitrion: '' }))
    await entorno.esperarEscrituras()
    expect(datos.size).toBe(0)
  })

  it('un almacén que lanza al leer o al guardar no rompe la respuesta', async () => {
    const { almacen } = almacenFalso()
    almacen.guardar.mockRejectedValue(new Error('redis roto'))
    almacen.leer.mockRejectedValue(new Error('redis roto'))
    const entorno = crearEntornoDelSitio(CONFIG, {
      fetch: (async () => json({ data: [1] })) as unknown as typeof fetch,
      almacenCompartido: async () => almacen,
    })
    expect((await entorno.atender(lectura()))!.estado).toBe(200)
    await entorno.esperarEscrituras()
  })

  it('los fallos de Redis se registran sin la contraseña, aunque el error la mencione', async () => {
    const registrar = vi.fn()
    let alFallar: ((op: string, e: unknown) => void) | undefined
    crearEntornoDelSitio(CONFIG, {
      fetch: (async () => json({})) as unknown as typeof fetch,
      almacenCompartido: (async (_c: unknown, o: { alFallar?: typeof alFallar }) => { alFallar = o.alFallar; return null }) as never,
      registrar,
    }).atender(lectura()).catch(() => {})
    await new Promise(r => setTimeout(r, 10))
    alFallar!('conexion', new Error(`AUTH falló con clave-secreta-123 en redis:6379`))
    const linea = JSON.stringify(registrar.mock.calls)
    expect(linea).toContain('respaldo_redis_fallo')
    expect(linea).not.toContain('clave-secreta-123')
  })

  it('cerrar espera las escrituras en vuelo y DESPUÉS cierra las conexiones', async () => {
    const orden: string[] = []
    let liberar!: () => void
    const bloqueada = new Promise<void>((r) => { liberar = r })
    const { almacen } = almacenFalso()
    almacen.guardar.mockImplementation(async () => { await bloqueada; orden.push('guardado') })
    const entorno = crearEntornoDelSitio(CONFIG, {
      fetch: (async () => json({ data: [1] })) as unknown as typeof fetch,
      almacenCompartido: async () => almacen,
      cerrarAlmacenes: async () => { orden.push('cerrado') },
    })
    await entorno.atender(lectura())
    const cierre = entorno.cerrar()
    await new Promise(r => setTimeout(r, 20))
    expect(orden).toEqual([])
    liberar()
    await cierre
    expect(orden).toEqual(['guardado', 'cerrado'])
  })
})

describe('entornoDelSitio: una instancia por proceso', () => {
  it('devuelve la MISMA instancia en cada llamada (el cortocircuito no se reinicia por petición)', async () => {
    const rc = { apiInterna: 'http://nginx:8080', redisHost: '', redisPort: 6379, redisUsuario: '', redisPassword: '', public: { dominio: DOMINIO } }
    const a = entornoDelSitio(rc)
    const b = entornoDelSitio({ ...rc })
    expect(b).toBe(a)
    await cerrarEntornoDelSitio()
    expect(entornoDelSitio(rc)).not.toBe(a)
    await cerrarEntornoDelSitio()
  })

  it('cerrar sin haber creado nada no falla', async () => {
    await expect(cerrarEntornoDelSitio()).resolves.toBeUndefined()
  })
})
