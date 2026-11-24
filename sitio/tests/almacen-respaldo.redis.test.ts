// @vitest-environment node
/**
 * Almacén Redis del respaldo contra un Redis REAL y el usuario ACL `sitio` (T9.3, H35, B4).
 *
 * El Redis falso de `almacen-respaldo.test.ts` no puede decir si `ioredis` necesita un comando que
 * la ACL niega; esto sí. El servidor lo arranca quien corre la prueba (el CI lo hace con la imagen
 * de producción por su digest) y la describe con variables de entorno:
 *
 *   REDIS_PRUEBA_HOST / REDIS_PRUEBA_PORT      dónde escucha
 *   REDIS_PRUEBA_ADMIN_PASSWORD                contraseña del usuario `default` (solo para inspeccionar)
 *   REDIS_PRUEBA_SITIO_PASSWORD                contraseña del usuario `sitio`
 *
 * Sin ellas la prueba se omite en una máquina local, pero **falla en el CI**: una prueba que se
 * omite en silencio donde debe correr no prueba nada.
 */
import Redis from 'ioredis'
import { afterAll, beforeAll, describe, expect, it } from 'vitest'

import { conectarAlmacen } from '../server/utils/almacen-respaldo'
import type { EntradaRespaldo } from '../server/utils/respaldo-api'

const host = process.env.REDIS_PRUEBA_HOST ?? ''
const port = Number(process.env.REDIS_PRUEBA_PORT ?? 0)
const admin = process.env.REDIS_PRUEBA_ADMIN_PASSWORD ?? ''
const clave = process.env.REDIS_PRUEBA_SITIO_PASSWORD ?? ''
const disponible = host !== '' && port > 0 && admin !== '' && clave !== ''

const ENTRADA: EntradaRespaldo = { cuerpo: '{"data":[1]}', tipo: 'application/json', guardadoEn: 1_000 }

it('en el CI, la prueba con Redis real no puede omitirse', () => {
  if (process.env.CI) expect(disponible, 'faltan las variables REDIS_PRUEBA_* en el CI').toBe(true)
})

describe.skipIf(!disponible)('con Redis real y el usuario ACL `sitio`', () => {
  let inspector: Redis
  let sitio: Redis

  beforeAll(async () => {
    inspector = new Redis({ host, port, password: admin })
    sitio = new Redis({ host, port, username: 'sitio', password: clave, enableReadyCheck: false, disableClientInfo: true })
    await inspector.set('sede_session:ajena', 'no-tocar', 'EX', 600)
    await inspector.call('ACL', 'LOG', 'RESET')
  })

  afterAll(async () => {
    await inspector.del('sede_session:ajena', 'sitio:respaldo:/banners', 'sitio:respaldo:/tramites')
    sitio.disconnect()
    inspector.disconnect()
  })

  it('el usuario `sitio` tiene la ACL mínima esperada (si no, el resto de la prueba no vale)', async () => {
    await expect(sitio.get('sede_session:ajena')).rejects.toThrow(/NOPERM/)
    await expect(sitio.keys('*')).rejects.toThrow(/NOPERM/)
    await expect(sitio.flushall()).rejects.toThrow(/NOPERM/)
    await expect(sitio.ttl('sitio:x')).rejects.toThrow(/NOPERM/)
    await expect(sitio.call('INFO')).rejects.toThrow(/NOPERM/)
  })

  it('el primer comando justo después de conectar ya llega a Redis (la cola desactivada no lo pierde)', async () => {
    const almacen = await conectarAlmacen({ host, port, usuario: 'sitio', password: clave })
    try {
      await almacen!.guardar('sitio:respaldo:/banners', ENTRADA, 60)
      expect(await inspector.exists('sitio:respaldo:/banners')).toBe(1)
    } finally {
      await almacen!.cerrar()
    }
  })

  it('ida y vuelta con los permisos mínimos', async () => {
    const almacen = await conectarAlmacen({ host, port, usuario: 'sitio', password: clave })
    expect(almacen).not.toBeNull()
    try {
      await almacen!.guardar('sitio:respaldo:/banners', ENTRADA, 86_400)
      expect(await almacen!.leer('sitio:respaldo:/banners')).toEqual(ENTRADA)
    } finally {
      await almacen!.cerrar()
    }
  })

  it('la clave guardada tiene TTL (el Redis compartido solo expulsa claves con TTL)', async () => {
    const almacen = await conectarAlmacen({ host, port, usuario: 'sitio', password: clave })
    try {
      await almacen!.guardar('sitio:respaldo:/tramites', ENTRADA, 86_400)
      const ttl = await inspector.ttl('sitio:respaldo:/tramites')
      expect(ttl).toBeGreaterThan(0)
      expect(ttl).toBeLessThanOrEqual(86_400)
    } finally {
      await almacen!.cerrar()
    }
  })

  it('el almacén no puede leer ni escribir claves ajenas (la prueba de ruta lo impide antes de llegar a Redis)', async () => {
    const almacen = await conectarAlmacen({ host, port, usuario: 'sitio', password: clave })
    try {
      expect(await almacen!.leer('sede_session:ajena')).toBeNull()
      await almacen!.guardar('sede_session:ajena', ENTRADA, 60)
      expect(await inspector.get('sede_session:ajena')).toBe('no-tocar')
    } finally {
      await almacen!.cerrar()
    }
  })

  it('conectar y operar no deja rechazos de ACL en el registro (B4: sin `CLIENT SETINFO` ni `INFO`)', async () => {
    await inspector.call('ACL', 'LOG', 'RESET')
    const almacen = await conectarAlmacen({ host, port, usuario: 'sitio', password: clave })
    try {
      await almacen!.guardar('sitio:respaldo:/banners', ENTRADA, 60)
      await almacen!.leer('sitio:respaldo:/banners')
    } finally {
      await almacen!.cerrar()
    }
    const registro = (await inspector.call('ACL', 'LOG')) as unknown[]
    expect(registro).toEqual([])
  })

  it('una contraseña equivocada no lanza: el almacén responde «sin respaldo»', async () => {
    const almacen = await conectarAlmacen({ host, port, usuario: 'sitio', password: 'incorrecta' })
    try {
      expect(await almacen!.leer('sitio:respaldo:/banners')).toBeNull()
      await expect(almacen!.guardar('sitio:respaldo:/banners', ENTRADA, 60)).resolves.toBeUndefined()
    } finally {
      await almacen!.cerrar()
    }
  })
})

describe('Redis caído (puerto cerrado)', () => {
  it('conectar no se queda esperando, y luego no lanza y responde en unos milisegundos', async () => {
    const inicio = Date.now()
    const almacen = await conectarAlmacen({ host: '127.0.0.1', port: 1, usuario: 'sitio', password: 'x' })
    expect(Date.now() - inicio).toBeLessThan(700)
    expect(almacen).not.toBeNull()
    try {
      const inicio = Date.now()
      expect(await almacen!.leer('sitio:respaldo:/banners')).toBeNull()
      await almacen!.guardar('sitio:respaldo:/banners', ENTRADA, 60)
      expect(Date.now() - inicio).toBeLessThan(100)
    } finally {
      await almacen!.cerrar()
    }
  })
})
