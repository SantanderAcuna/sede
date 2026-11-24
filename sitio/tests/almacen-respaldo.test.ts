// @vitest-environment node
/**
 * Almacén Redis del respaldo (T9.3), con un cliente falso.
 *
 * Lo que se protege: que Redis sea una AYUDA. Si se cae, se congela o devuelve basura, el sitio se
 * comporta como hoy: ni lanza, ni se queda esperando, ni escribe fuera de `sitio:*`, ni deja una
 * clave sin TTL (el Redis es compartido con sesiones y colas, y `volatile-lru` solo expulsa claves
 * con TTL: una sin TTL nunca se libera).
 */
import { describe, expect, it, vi } from 'vitest'

import {
  TIEMPO_COMANDO_MS,
  conectarAlmacen,
  crearAlmacenRedis,
  opcionesRedis,
  type ClienteRedis,
} from '../server/utils/almacen-respaldo'
import type { EntradaRespaldo } from '../server/utils/respaldo-api'

const CLAVE = 'sitio:respaldo:/banners'
const ENTRADA: EntradaRespaldo = { cuerpo: '{"data":[1]}', tipo: 'application/json', guardadoEn: 1_000 }

function clienteFalso(sobrescribe: Partial<ClienteRedis> = {}) {
  const datos = new Map<string, { valor: string; ttl: number | undefined }>()
  const cliente = {
    get: vi.fn(async (clave: string) => datos.get(clave)?.valor ?? null),
    set: vi.fn(async (clave: string, valor: string, modo: 'EX', segundos: number) => {
      datos.set(clave, { valor, ttl: modo === 'EX' ? segundos : undefined })
      return 'OK'
    }),
    quit: vi.fn(async () => 'OK'),
    disconnect: vi.fn(),
    ...sobrescribe,
  } as ClienteRedis & { get: ReturnType<typeof vi.fn>; set: ReturnType<typeof vi.fn> }
  return { cliente, datos }
}

const colgado = () => new Promise<never>(() => {})

describe('guardar y leer', () => {
  it('ida y vuelta', async () => {
    const { cliente } = clienteFalso()
    const almacen = crearAlmacenRedis(cliente)
    await almacen.guardar(CLAVE, ENTRADA, 86_400)
    expect(await almacen.leer(CLAVE)).toEqual(ENTRADA)
  })

  it('toda escritura lleva TTL (`SET … EX`): jamás una clave inmortal', async () => {
    const { cliente, datos } = clienteFalso()
    await crearAlmacenRedis(cliente).guardar(CLAVE, ENTRADA, 86_400)
    expect(cliente.set).toHaveBeenCalledWith(CLAVE, expect.any(String), 'EX', 86_400)
    expect(datos.get(CLAVE)!.ttl).toBe(86_400)
  })

  it.each([0, -1, 1.5, Number.NaN, Number.POSITIVE_INFINITY])('un TTL inválido (%s) no escribe nada', async (ttl) => {
    const { cliente } = clienteFalso()
    await crearAlmacenRedis(cliente).guardar(CLAVE, ENTRADA, ttl)
    expect(cliente.set).not.toHaveBeenCalled()
  })

  it.each(['sede_session:abc', 'laravel_database_queues:default', 'respaldo:/banners', '', 'SITIO:x', 'sitio'])(
    'la clave «%s» está fuera de `sitio:` y no se lee ni se escribe',
    async (clave) => {
      const { cliente } = clienteFalso()
      const almacen = crearAlmacenRedis(cliente)
      await almacen.guardar(clave, ENTRADA, 60)
      expect(await almacen.leer(clave)).toBeNull()
      expect(cliente.set).not.toHaveBeenCalled()
      expect(cliente.get).not.toHaveBeenCalled()
    },
  )

  it('una clave ausente es null', async () => {
    expect(await crearAlmacenRedis(clienteFalso().cliente).leer(CLAVE)).toBeNull()
  })

  it.each([
    ['no es JSON', 'esto no es json'],
    ['es null', 'null'],
    ['es un número', '42'],
    ['falta `cuerpo`', '{"tipo":"a","guardadoEn":1}'],
    ['`cuerpo` no es texto', '{"cuerpo":1,"tipo":"a","guardadoEn":1}'],
    ['falta `tipo`', '{"cuerpo":"a","guardadoEn":1}'],
    ['`guardadoEn` no es número', '{"cuerpo":"a","tipo":"a","guardadoEn":"ayer"}'],
    ['`guardadoEn` no es finito', '{"cuerpo":"a","tipo":"a","guardadoEn":1e999}'],
  ])('un valor guardado que %s se trata como «sin respaldo»', async (_motivo, crudo) => {
    const { cliente } = clienteFalso({ get: vi.fn(async () => crudo) })
    expect(await crearAlmacenRedis(cliente).leer(CLAVE)).toBeNull()
  })
})

describe('Redis falla: el almacén no lanza y no espera', () => {
  it('un error al leer → null', async () => {
    const { cliente } = clienteFalso({ get: vi.fn(async () => { throw new Error('ECONNREFUSED') }) })
    expect(await crearAlmacenRedis(cliente).leer(CLAVE)).toBeNull()
  })

  it('un error al guardar no lanza', async () => {
    const { cliente } = clienteFalso({ set: vi.fn(async () => { throw new Error('NOPERM') }) })
    await expect(crearAlmacenRedis(cliente).guardar(CLAVE, ENTRADA, 60)).resolves.toBeUndefined()
  })

  it('un Redis congelado al leer se abandona al pasar el tiempo del comando', async () => {
    const { cliente } = clienteFalso({ get: vi.fn(colgado) })
    const inicio = Date.now()
    expect(await crearAlmacenRedis(cliente, { tiempoMs: 30 }).leer(CLAVE)).toBeNull()
    expect(Date.now() - inicio).toBeLessThan(500)
  })

  it('un Redis congelado al guardar se abandona igual', async () => {
    const { cliente } = clienteFalso({ set: vi.fn(colgado) })
    const inicio = Date.now()
    await crearAlmacenRedis(cliente, { tiempoMs: 30 }).guardar(CLAVE, ENTRADA, 60)
    expect(Date.now() - inicio).toBeLessThan(500)
  })

  it('el tiempo del comando es corto por defecto (el sitio no espera a Redis)', () => {
    expect(TIEMPO_COMANDO_MS).toBeLessThanOrEqual(500)
  })

  it('un fallo que llega tarde, tras abandonar, no produce un rechazo sin atender', async () => {
    const rechazos: unknown[] = []
    const alRechazar = (e: unknown) => rechazos.push(e)
    process.on('unhandledRejection', alRechazar)
    const { cliente } = clienteFalso({
      get: vi.fn(() => new Promise<never>((_, rechazar) => setTimeout(() => rechazar(new Error('tarde')), 60))),
    })
    await crearAlmacenRedis(cliente, { tiempoMs: 10 }).leer(CLAVE)
    await new Promise(r => setTimeout(r, 120))
    process.off('unhandledRejection', alRechazar)
    expect(rechazos).toEqual([])
  })

  it('informa cada fallo a `alFallar`, con la operación y la causa', async () => {
    const error = new Error('boom')
    const { cliente } = clienteFalso({ get: vi.fn(async () => { throw error }) })
    const alFallar = vi.fn()
    await crearAlmacenRedis(cliente, { alFallar }).leer(CLAVE)
    expect(alFallar).toHaveBeenCalledWith('leer', error)
  })

  it('el informe de una misma operación se limita a uno cada 30 s (un Redis caído no inunda el registro)', async () => {
    let t = 0
    const { cliente } = clienteFalso({ get: vi.fn(async () => { throw new Error('x') }) })
    const alFallar = vi.fn()
    const almacen = crearAlmacenRedis(cliente, { alFallar, ahora: () => t })
    for (let n = 0; n < 5; n++) await almacen.leer(CLAVE)
    expect(alFallar).toHaveBeenCalledTimes(1)
    t = 30_001
    await almacen.leer(CLAVE)
    expect(alFallar).toHaveBeenCalledTimes(2)
  })

  it('un `alFallar` que lanza no rompe al almacén', async () => {
    const { cliente } = clienteFalso({ get: vi.fn(async () => { throw new Error('x') }) })
    const almacen = crearAlmacenRedis(cliente, { alFallar: () => { throw new Error('registro roto') } })
    expect(await almacen.leer(CLAVE)).toBeNull()
  })
})

describe('cerrar', () => {
  it('cierra con `quit`', async () => {
    const { cliente } = clienteFalso()
    await crearAlmacenRedis(cliente).cerrar()
    expect(cliente.quit).toHaveBeenCalledOnce()
    expect(cliente.disconnect).not.toHaveBeenCalled()
  })

  it('si `quit` falla, corta la conexión y no lanza', async () => {
    const { cliente } = clienteFalso({ quit: vi.fn(async () => { throw new Error('cerrado') }) })
    await expect(crearAlmacenRedis(cliente).cerrar()).resolves.toBeUndefined()
    expect(cliente.disconnect).toHaveBeenCalledOnce()
  })

  it('si `quit` se cuelga, corta la conexión al pasar el tiempo', async () => {
    const { cliente } = clienteFalso({ quit: vi.fn(colgado) })
    await crearAlmacenRedis(cliente, { tiempoMs: 20 }).cerrar()
    expect(cliente.disconnect).toHaveBeenCalledOnce()
  })
})

describe('opcionesRedis: lo que se comprobó contra Redis 8 con la ACL mínima', () => {
  const base = { host: 'redis', port: 6379, usuario: 'sitio', password: 'secreta' }

  it('credenciales y destino', () => {
    expect(opcionesRedis(base)).toMatchObject({ host: 'redis', port: 6379, username: 'sitio', password: 'secreta', db: 0 })
  })

  it('no manda comandos que la ACL no permite ni hace esperar al sitio', () => {
    expect(opcionesRedis(base)).toMatchObject({
      enableReadyCheck: false, // evita `INFO`
      disableClientInfo: true, // evita `CLIENT SETINFO` (B4)
      enableOfflineQueue: false, // sin conexión, el comando falla al instante
      maxRetriesPerRequest: 0,
      connectTimeout: 500,
      commandTimeout: 300,
    })
  })

  it('no abre la conexión perezosa (con la cola desactivada, el primer comando fallaría)', () => {
    expect(opcionesRedis(base).lazyConnect).toBeFalsy()
  })

  it('reintenta con espera creciente y con tope', () => {
    const estrategia = opcionesRedis(base).retryStrategy as (n: number) => number
    const esperas = [1, 2, 5, 20, 1000].map(estrategia)
    expect(esperas[0]).toBeGreaterThan(0)
    expect(esperas[1]).toBeGreaterThan(esperas[0]!)
    expect(Math.max(...esperas)).toBeLessThanOrEqual(5_000)
  })
})

describe('conectarAlmacen', () => {
  it.each([
    [{ host: '', port: 6379, usuario: 'sitio', password: 'x' }],
    [{ host: 'redis', port: 6379, usuario: 'sitio', password: '' }],
    [{ host: 'redis', port: 6379, usuario: '', password: 'x' }],
    [{ host: 'redis', port: 0, usuario: 'sitio', password: 'x' }],
    [{ host: 'redis', port: Number.NaN, usuario: 'sitio', password: 'x' }],
  ])('sin configuración completa (%j) no hay almacén: el sitio sigue sin respaldo', async (config) => {
    expect(await conectarAlmacen(config)).toBeNull()
  })
})
