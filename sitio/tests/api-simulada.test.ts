/**
 * El simulador de API de la puerta de diseño tiene que seguir cumpliendo el
 * contrato que el sitio exige.
 *
 * La puerta de diseño (`tests/diseno.mjs`) ya no consulta ninguna API externa: usa
 * `ayudas/api-simulada.mjs`. Si el adaptador del sitio empieza a pedir más
 * (otro campo obligatorio, otra forma de la paginación) y el simulador no se
 * actualiza, la puerta mediría páginas que degradan en silencio. Esta prueba lo
 * detecta en el CI de cada PR, que es mucho antes que el despliegue, y no depende
 * de ningún navegador.
 */
import { afterAll, beforeAll, describe, expect, it } from 'vitest'
import { politicaDesdeApi } from '../app/config/politicas-contenido'
// @ts-expect-error — módulo .mjs sin declaraciones: lo usan scripts de Node, no la app.
import { iniciarApiSimulada } from './ayudas/api-simulada.mjs'

interface Simulada {
  url: string
  cerrar: () => Promise<void>
  rutasDesconocidas: () => string[]
  rutasAtendidas: () => string[]
}

let simulada: Simulada

beforeAll(async () => {
  simulada = (await iniciarApiSimulada()) as Simulada
})

afterAll(async () => {
  await simulada.cerrar()
})

const pedir = async (ruta: string, init?: RequestInit) => {
  const respuesta = await fetch(`${simulada.url}${ruta}`, init)
  return { estado: respuesta.status, cuerpo: (await respuesta.json()) as Record<string, unknown> }
}

describe('API simulada de la puerta de diseño', () => {
  it('escucha en un puerto local libre, no en uno fijo', () => {
    expect(simulada.url).toMatch(/^http:\/\/127\.0\.0\.1:\d+$/)
  })

  it('la política de cookies pasa por el adaptador del sitio (slug, nombre, versión ≥ 1)', async () => {
    const { estado, cuerpo } = await pedir('/api/v1/politicas/uso-de-cookies')
    expect(estado).toBe(200)
    expect(cuerpo.success).toBe(true)
    const politica = politicaDesdeApi(cuerpo.data)
    // `null` es justo lo que el sitio convierte en un 503.
    expect(politica).not.toBeNull()
    expect(politica?.slug).toBe('uso-de-cookies')
    expect(politica?.version).toBeGreaterThanOrEqual(1)
    expect(politica?.secciones.length).toBeGreaterThan(0)
  })

  it('el texto de la política se declara de prueba y de ejemplo, no institucional', async () => {
    const { cuerpo } = await pedir('/api/v1/politicas/uso-de-cookies')
    expect(politicaDesdeApi(cuerpo.data)?.esEjemplo).toBe(true)
    expect(JSON.stringify(cuerpo.data)).toContain('No es contenido de la Entidad')
  })

  it('el catálogo de trámites trae la paginación completa del contrato (PageMeta)', async () => {
    const { estado, cuerpo } = await pedir('/api/v1/tramites?per_page=7')
    expect(estado).toBe(200)
    expect(cuerpo.data).toEqual([])
    expect(cuerpo.meta).toEqual({
      current_page: 1,
      from: null,
      last_page: 1,
      path: '/api/v1/tramites',
      per_page: 7,
      to: null,
      total: 0,
    })
  })

  it('per_page no válido cae al valor por defecto en vez de propagar basura', async () => {
    const { cuerpo } = await pedir('/api/v1/tramites?per_page=abc')
    expect((cuerpo.meta as { per_page: number }).per_page).toBe(12)
  })

  it('el pie responde con redes vacías (el sitio usa entonces su respaldo)', async () => {
    const { estado, cuerpo } = await pedir('/api/v1/identidad/footer')
    expect(estado).toBe(200)
    expect(cuerpo.data).toEqual({ redes: [] })
  })

  it('una ruta desconocida responde 404 con el sobre real y queda registrada', async () => {
    const { estado, cuerpo } = await pedir('/api/v1/ruta-que-no-existe')
    expect(estado).toBe(404)
    expect(cuerpo).toMatchObject({ success: false, data: null })
    expect(simulada.rutasDesconocidas()).toContain('GET /api/v1/ruta-que-no-existe')
  })

  it('sólo acepta GET y HEAD, como el canal interno, y registra lo demás', async () => {
    const { estado } = await pedir('/api/v1/consentimientos', { method: 'POST', body: '{}' })
    expect(estado).toBe(405)
    expect(simulada.rutasDesconocidas()).toContain('POST /api/v1/consentimientos')
  })

  it('registra las rutas que sí atendió, sin mezclarlas con las desconocidas', async () => {
    await pedir('/api/v1/identidad/footer')
    expect(simulada.rutasAtendidas()).toContain('GET /api/v1/identidad/footer')
    expect(simulada.rutasDesconocidas()).not.toContain('GET /api/v1/identidad/footer')
  })
})
