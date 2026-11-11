/**
 * `esperarCupoApi` (tests/ayudas/cupo-api.mjs).
 *
 * Medido en staging (navegador integrado): `/api/v1/*` limita a 60 peticiones por
 * minuto y por IP (`X-RateLimit-Limit: 60`), y cada página que se sirve gasta 2–3
 * de ellas en el servidor del sitio. Un script que recorre 19 rutas × 5 anchos
 * (≈ 1 página por segundo) se agota el cupo él solo, y la siguiente página de
 * trámites sale con «No se pudo cargar el catálogo» (despliegue 42966617316:
 * webkit, msedge y firefox; chromium pasó por la hora a la que le tocó).
 *
 * El límite es de la aplicación y funciona como se diseñó; lo que se corrige es
 * que la prueba lo respete antes de medir lo que depende de la API.
 */
import { describe, expect, it } from 'vitest'
import { describirApi, esperarCupoApi } from './cupo-api.mjs'

type Respuesta = { status: number; cabeceras: Record<string, string> }

/** Contexto falso con las respuestas dadas, en orden; la última se repite. */
function contextoCon(respuestas: Respuesta[]) {
  let i = 0
  const llamadas: string[] = []
  return {
    llamadas,
    request: {
      get: async (url: string) => {
        llamadas.push(url)
        const r = respuestas[Math.min(i++, respuestas.length - 1)]
        return { status: () => r.status, headers: () => r.cabeceras }
      },
    },
  }
}
const sinPausa = { pausaMs: 0, intentos: 5 }

describe('esperarCupoApi', () => {
  it('con cupo de sobra no espera: una sola consulta', async () => {
    const ctx = contextoCon([{ status: 200, cabeceras: { 'x-ratelimit-remaining': '55' } }])
    const r = await esperarCupoApi(ctx, 'https://x.test', sinPausa)
    expect(r).toEqual({ listo: true, consultas: 1, restantes: 55 })
    expect(ctx.llamadas).toEqual(['https://x.test/api/v1/tramites?per_page=1'])
  })

  it('con poco cupo vuelve a consultar hasta que se recupera', async () => {
    const ctx = contextoCon([
      { status: 200, cabeceras: { 'x-ratelimit-remaining': '3' } },
      { status: 200, cabeceras: { 'x-ratelimit-remaining': '4' } },
      { status: 200, cabeceras: { 'x-ratelimit-remaining': '58' } },
    ])
    const r = await esperarCupoApi(ctx, 'https://x.test', sinPausa)
    expect(r).toEqual({ listo: true, consultas: 3, restantes: 58 })
  })

  it('un 429 cuenta como cupo agotado y respeta Retry-After', async () => {
    const esperas: number[] = []
    const ctx = contextoCon([
      { status: 429, cabeceras: { 'retry-after': '7' } },
      { status: 200, cabeceras: { 'x-ratelimit-remaining': '59' } },
    ])
    const r = await esperarCupoApi(ctx, 'https://x.test', { ...sinPausa, dormir: async (ms: number) => void esperas.push(ms) })
    expect(r.listo).toBe(true)
    expect(esperas).toEqual([7000])
  })

  it('si el cupo no vuelve, devuelve listo:false en vez de colgarse', async () => {
    const ctx = contextoCon([{ status: 429, cabeceras: { 'retry-after': '1' } }])
    const r = await esperarCupoApi(ctx, 'https://x.test', { pausaMs: 0, intentos: 3, dormir: async () => {} })
    expect(r).toEqual({ listo: false, consultas: 3, restantes: null })
  })

  it('sin API (sin cabecera de cupo, como el servidor de desarrollo) no espera', async () => {
    const ctx = contextoCon([{ status: 404, cabeceras: {} }])
    const r = await esperarCupoApi(ctx, 'https://x.test', sinPausa)
    expect(r).toEqual({ listo: true, consultas: 1, restantes: null })
  })

  it('un fallo de red tampoco bloquea la prueba', async () => {
    const ctx = { request: { get: async () => { throw new Error('ECONNRESET') } } }
    const r = await esperarCupoApi(ctx, 'https://x.test', sinPausa)
    expect(r).toEqual({ listo: true, consultas: 1, restantes: null })
  })
})

describe('describirApi', () => {
  it('resume código y cupo restante de una respuesta buena', async () => {
    const ctx = contextoCon([{ status: 200, cabeceras: { 'x-ratelimit-remaining': '41', 'x-ratelimit-limit': '60' } }])
    expect(await describirApi(ctx, 'https://x.test')).toBe('HTTP 200, cupo 41/60')
  })

  it('un 429 dice cuánto esperar', async () => {
    const ctx = contextoCon([{ status: 429, cabeceras: { 'retry-after': '12' } }])
    expect(await describirApi(ctx, 'https://x.test')).toBe('HTTP 429, reintentar en 12 s')
  })

  it('un error del servidor se informa con su código', async () => {
    const ctx = contextoCon([{ status: 503, cabeceras: {} }])
    expect(await describirApi(ctx, 'https://x.test')).toBe('HTTP 503')
  })

  it('un fallo de red se informa sin lanzar', async () => {
    const ctx = { request: { get: async () => { throw new Error('ECONNRESET') } } }
    expect(await describirApi(ctx, 'https://x.test')).toBe('sin respuesta (ECONNRESET)')
  })
})
