// @vitest-environment node
/**
 * El respaldo de la API de extremo a extremo (T9.6, Fase 0C): el sitio COMPILADO, un Redis REAL con
 * el usuario ACL `sitio` y una API que se apaga.
 *
 * Por qué existe. Las pruebas unitarias prueban cada pieza (núcleo, almacén, ruta) con piezas
 * falsas alrededor. Lo que ninguna prueba y puede fallar EN SILENCIO es el conjunto: que la
 * configuración llegue (`NUXT_REDIS_*`, `NUXT_PUBLIC_DOMINIO`), que el usuario ACL alcance para lo
 * que el cliente real hace, que Nitro guarde de verdad y que una página salga igual con la API
 * caída. Un respaldo que no guarda se ve idéntico a uno que sí, hasta el día de la caída.
 *
 * Qué mide, con las mismas variables que en producción (`compose.yaml`):
 *   1. Con la API sana la página responde y el sitio GUARDA su lectura en `sitio:respaldo:…` con
 *      tiempo de vida de 24 h.
 *   2. Con la API caída la MISMA página sale igual (texto) y el registro lo dice.
 *   3. Sin copia y con la API caída la página muestra su estado de error de siempre.
 *   4. Un 404 de la API no se guarda.
 *   5. La ficha de un trámite NO se hace pasar por «no existe» cuando la API falla: 404 solo si la
 *      API dijo 404; si no contestó, 503 (un buscador no debe retirar de su índice una ficha real
 *      por una caída pasajera).
 *   6. El usuario `sitio` no recibió ningún rechazo de la ACL en todo el recorrido.
 *
 * Necesita el sitio compilado (`npm run build`, como en el CI antes de las pruebas) y el Redis de
 * `REDIS_PRUEBA_*` (ver `almacen-respaldo.redis.test.ts`). Sin ellos se omite en una máquina local,
 * pero **falla en el CI**: una prueba omitida en silencio donde debe correr no prueba nada.
 */
import { type ChildProcess, spawn } from 'node:child_process'
import { existsSync } from 'node:fs'
import { type Server, createServer, request } from 'node:http'
import { createServer as crearServidorTcp } from 'node:net'
import { fileURLToPath } from 'node:url'

import Redis from 'ioredis'
import { afterAll, beforeAll, describe, expect, it } from 'vitest'
// @ts-expect-error — módulo .mjs sin declaraciones: lo usan scripts de Node, no la app.
import { iniciarApiSimulada } from './ayudas/api-simulada.mjs'

const SERVIDOR = fileURLToPath(new URL('../.output/server/index.mjs', import.meta.url))
const host = process.env.REDIS_PRUEBA_HOST ?? ''
const port = Number(process.env.REDIS_PRUEBA_PORT ?? 0)
const admin = process.env.REDIS_PRUEBA_ADMIN_PASSWORD ?? ''
const clave = process.env.REDIS_PRUEBA_SITIO_PASSWORD ?? ''
const disponible = host !== '' && port > 0 && admin !== '' && clave !== '' && existsSync(SERVIDOR)

const DOMINIO = 'sede.prueba'

it('en el CI, la prueba de extremo a extremo no puede omitirse', () => {
  if (process.env.CI) {
    expect(existsSync(SERVIDOR), 'falta el sitio compilado (.output/server/index.mjs)').toBe(true)
    expect(disponible, 'faltan las variables REDIS_PRUEBA_* en el CI').toBe(true)
  }
})

type Modo = 'sana' | 'caida' | '503'

interface Simulada {
  url: string
  cerrar: () => Promise<void>
}

/** Un puerto libre (se cierra antes de devolverlo: la carrera es mínima y solo afecta a esta prueba). */
function puertoLibre(): Promise<number> {
  return new Promise((resolver, rechazar) => {
    const s = crearServidorTcp()
    s.once('error', rechazar)
    s.listen(0, '127.0.0.1', () => {
      const { port: p } = s.address() as { port: number }
      s.close(() => resolver(p))
    })
  })
}

function pedir(
  puerto: number,
  ruta: string,
  cabeceras: Record<string, string> = {},
): Promise<{ estado: number; cuerpo: string }> {
  return new Promise((resolver, rechazar) => {
    const r = request(
      { host: '127.0.0.1', port: puerto, path: ruta, headers: { 'x-forwarded-host': DOMINIO, 'x-real-ip': '203.0.113.7', ...cabeceras } },
      (res) => {
        const trozos: Buffer[] = []
        res.on('data', (t: Buffer) => trozos.push(t))
        res.on('end', () => resolver({ estado: res.statusCode ?? 0, cuerpo: Buffer.concat(trozos).toString('utf8') }))
      },
    )
    r.setTimeout(30_000, () => r.destroy(new Error('tiempo agotado')))
    r.on('error', rechazar)
    r.end()
  })
}

/** El texto de la página, sin scripts ni estilos: lo que ve la persona, sin los nombres de compilación. */
function textoVisible(html: string): string {
  return html
    .replace(/<script[\s\S]*?<\/script>/g, '')
    .replace(/<style[\s\S]*?<\/style>/g, '')
    .replace(/<[^>]+>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
}

const dormir = (ms: number) => new Promise(resolver => setTimeout(resolver, ms))

async function hasta<T>(descripcion: string, fn: () => Promise<T | null | false>, ms = 8000): Promise<T> {
  const limite = Date.now() + ms
  for (;;) {
    const valor = await fn()
    if (valor) return valor
    if (Date.now() > limite) throw new Error(`No ocurrió a tiempo: ${descripcion}`)
    await dormir(100)
  }
}

describe.skipIf(!disponible)('el sitio compilado con Redis real y la API que se apaga', () => {
  let simulada: Simulada
  let frente: Server
  let modo: Modo = 'sana'
  let sitio: ChildProcess
  let puertoSitio = 0
  let registro = ''
  let inspector: Redis

  beforeAll(async () => {
    simulada = (await iniciarApiSimulada()) as Simulada
    const base = new URL(simulada.url)

    // Un frente delante de la API simulada: ella responde `no-store` (es una API de prueba de
    // diseño); Laravel responde `no-cache, private`, que es lo que el respaldo debe aceptar.
    // También es el interruptor: «caida» corta la conexión y «503» contesta 503.
    frente = createServer((entrada, salida) => {
      if (modo === 'caida') { entrada.socket.destroy(); return }
      if (modo === '503') { salida.writeHead(503, { 'content-type': 'application/json' }); salida.end('{"success":false}'); return }
      const salto = request(
        { host: base.hostname, port: base.port, path: entrada.url, method: entrada.method, headers: { ...entrada.headers, host: base.host } },
        (r) => {
          salida.writeHead(r.statusCode ?? 502, { ...r.headers, 'cache-control': 'no-cache, private' })
          r.pipe(salida)
        },
      )
      salto.on('error', () => { salida.statusCode = 502; salida.end() })
      entrada.pipe(salto)
    })
    await new Promise<void>(resolver => frente.listen(0, '127.0.0.1', resolver))
    const apiInterna = `http://127.0.0.1:${(frente.address() as { port: number }).port}`

    inspector = new Redis({ host, port, password: admin })
    await inspector.call('ACL', 'LOG', 'RESET')
    await borrarCopias()

    puertoSitio = await puertoLibre()
    sitio = spawn(process.execPath, [SERVIDOR], {
      env: {
        PATH: process.env.PATH ?? '',
        NITRO_HOST: '127.0.0.1',
        NITRO_PORT: String(puertoSitio),
        NUXT_API_INTERNA: apiInterna,
        NUXT_PUBLIC_DOMINIO: DOMINIO,
        NUXT_REDIS_HOST: host,
        NUXT_REDIS_PORT: String(port),
        NUXT_REDIS_USUARIO: 'sitio',
        NUXT_REDIS_PASSWORD: clave,
      },
      stdio: ['ignore', 'pipe', 'pipe'],
    })
    sitio.stdout?.on('data', (t: Buffer) => { registro += t.toString('utf8') })
    sitio.stderr?.on('data', (t: Buffer) => { registro += t.toString('utf8') })
    await hasta('el sitio escucha', async () => {
      try { return (await pedir(puertoSitio, '/robots.txt')).estado === 200 } catch { return false }
    }, 30_000)
  }, 60_000)

  afterAll(async () => {
    sitio?.kill('SIGTERM')
    await new Promise(resolver => setTimeout(resolver, 300))
    frente?.closeAllConnections?.()
    await new Promise<void>(resolver => (frente ? frente.close(() => resolver()) : resolver()))
    await simulada?.cerrar()
    await borrarCopias()
    inspector?.disconnect()
  })

  async function borrarCopias(): Promise<void> {
    const claves = await inspector.keys('sitio:*')
    if (claves.length > 0) await inspector.del(...claves)
  }

  const clavesCopia = () => inspector.keys('sitio:respaldo:*')

  it('1. con la API sana guarda la lectura del catálogo, con 24 h de vida', async () => {
    modo = 'sana'
    const r = await pedir(puertoSitio, '/tramites')
    expect(r.estado).toBe(200)

    const catalogo = await hasta('la copia del catálogo en Redis', async () => {
      const todas = await clavesCopia()
      return todas.find(k => k.startsWith('sitio:respaldo:/tramites?')) ?? null
    })
    const ttl = await inspector.ttl(catalogo)
    expect(ttl).toBeGreaterThan(86_000)
    expect(ttl).toBeLessThanOrEqual(86_400)
    const guardado = JSON.parse((await inspector.get(catalogo)) ?? '{}') as { cuerpo?: string; tipo?: string }
    expect(guardado.tipo).toMatch(/json/)
    expect(JSON.parse(guardado.cuerpo ?? 'null')).toHaveProperty('data')
  })

  it('2. con la API caída, la misma página sale igual y el registro lo dice', async () => {
    modo = 'sana'
    for (const ruta of ['/', '/tramites', '/noticias']) expect((await pedir(puertoSitio, ruta)).estado).toBe(200)
    // Cada lectura que las tres páginas hacen tiene que haber quedado guardada. Si una página cambia
    // sus parámetros (p. ej. otro `per_page`) y la lista blanca del respaldo no la reconoce, esa
    // lectura deja de guardarse EN SILENCIO: aquí se ve.
    const esperadas = ['sitio:respaldo:/identidad/footer', 'sitio:respaldo:/banners', 'sitio:respaldo:/tramites?', 'sitio:respaldo:/noticias?']
    await hasta('las copias de las lecturas de la portada, el catálogo y las noticias', async () => {
      const todas = await clavesCopia()
      return esperadas.every(p => todas.some(k => k.startsWith(p))) || null
    })
    const sana = await Promise.all(['/', '/tramites', '/noticias'].map(async ruta => textoVisible((await pedir(puertoSitio, ruta)).cuerpo)))

    modo = 'caida'
    const antes = registro.split('respaldo_servido').length
    const caida = await Promise.all(['/', '/tramites', '/noticias'].map(async (ruta) => {
      const r = await pedir(puertoSitio, ruta)
      expect(r.estado).toBe(200)
      return textoVisible(r.cuerpo)
    }))
    expect(caida).toEqual(sana)
    expect(registro.split('respaldo_servido').length).toBeGreaterThan(antes)
  })

  it('3. sin copia y con la API caída, la página muestra su estado de error de siempre', async () => {
    await borrarCopias()
    modo = 'caida'
    const r = await pedir(puertoSitio, '/tramites')
    expect(r.estado).toBe(200)
    expect(textoVisible(r.cuerpo)).toContain('No se pudo cargar el catálogo de trámites')
    expect(await clavesCopia()).toEqual([])
  })

  it('4. un 404 de la API no se guarda', async () => {
    modo = 'sana'
    const r = await pedir(puertoSitio, '/tramites/no-existe-en-ninguna-parte')
    expect(r.estado).toBe(404)
    await dormir(500)
    expect((await clavesCopia()).filter(k => k.includes('no-existe-en-ninguna-parte'))).toEqual([])
  })

  it('5. la ficha de un trámite no se hace pasar por «no existe» cuando la API falla', async () => {
    for (const falla of ['caida', '503'] as const) {
      modo = falla
      const r = await pedir(puertoSitio, '/tramites/tramite-de-prueba-01')
      expect(r.estado, `con la API en «${falla}»`).toBe(503)
    }
    modo = 'sana'
  })

  it('6. el usuario `sitio` no recibió ningún rechazo de la ACL en todo el recorrido', async () => {
    const rechazos = (await inspector.call('ACL', 'LOG')) as unknown[]
    expect(rechazos).toEqual([])
  })
})
