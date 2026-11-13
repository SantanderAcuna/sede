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
import { analizarResultados } from '../app/composables/resultadosBusqueda'
import { analizarSugerencias } from '../app/composables/useSugerenciasBusqueda'
import { politicaDesdeApi } from '../app/config/politicas-contenido'
import { diapositivasDePortada } from '../app/utils/banners'
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

  it('la búsqueda pagina de a 10 con la forma del contrato y la lee el adaptador del sitio', async () => {
    const { estado, cuerpo } = await pedir('/api/v1/buscar?q=prueba&per_page=10&page=2')
    expect(estado).toBe(200)
    const pagina = analizarResultados(cuerpo)
    expect(pagina?.total).toBe(25)
    expect(pagina?.ultimaPagina).toBe(3)
    expect(pagina?.resultados).toHaveLength(10)
    expect(cuerpo.meta).toMatchObject({ current_page: 2, from: 11, to: 20, per_page: 10 })
  })

  it('el filtro por tipo acota, y un término sin coincidencias es «sin resultados»', async () => {
    const politicas = analizarResultados((await pedir('/api/v1/buscar?q=prueba&tipo=politica')).cuerpo)
    expect(politicas?.total).toBe(2)
    expect(politicas?.resultados.every((r) => r.tipoRecurso === 'politica')).toBe(true)
    const nada = analizarResultados((await pedir('/api/v1/buscar?q=salud')).cuerpo)
    expect(nada).toEqual({ resultados: [], total: 0, ultimaPagina: 1 })
  })

  it('compara sin tildes ni mayúsculas, como la API', async () => {
    const { cuerpo } = await pedir('/api/v1/buscar?q=TRAMITE')
    expect(analizarResultados(cuerpo)?.total).toBe(23)
  })

  it('el contenido de prueba se declara de prueba, no institucional', async () => {
    const { cuerpo } = await pedir('/api/v1/buscar?q=prueba')
    expect(JSON.stringify(cuerpo.data)).toContain('No es contenido de la Entidad')
  })

  it('una consulta fuera de 3 a 120 caracteres es un 422', async () => {
    expect((await pedir('/api/v1/buscar?q=ab')).estado).toBe(422)
    expect((await pedir(`/api/v1/buscar?q=${'a'.repeat(121)}`)).estado).toBe(422)
    expect((await pedir('/api/v1/busqueda/sugerencias?q=ab')).estado).toBe(422)
  })

  it('los términos de fallo dan 500 y 429 con Retry-After, para ejercitar esos estados', async () => {
    expect((await pedir('/api/v1/buscar?q=error-servidor')).estado).toBe(500)
    const limitada = await fetch(`${simulada.url}/api/v1/buscar?q=limite-peticiones`)
    expect(limitada.status).toBe(429)
    expect(limitada.headers.get('retry-after')).toBe('7')
    expect((await pedir('/api/v1/busqueda/sugerencias?q=error-servidor')).estado).toBe(500)
  })

  it('las sugerencias traen hasta `limite` (6 por defecto) y las acepta el composable', async () => {
    const porDefecto = analizarSugerencias((await pedir('/api/v1/busqueda/sugerencias?q=prueba')).cuerpo)
    expect(porDefecto).toHaveLength(6)
    const tres = analizarSugerencias((await pedir('/api/v1/busqueda/sugerencias?q=prueba&limite=3')).cuerpo)
    expect(tres).toHaveLength(3)
    expect(tres?.[0]?.url).toBe('/tramites/tramite-de-prueba-01')
  })

  it('los banners responden vacíos y la portada usa entonces sus diapositivas por defecto', async () => {
    const { estado, cuerpo } = await pedir('/api/v1/banners')
    expect(estado).toBe(200)
    expect(cuerpo.success).toBe(true)
    expect(cuerpo.data).toEqual([])
    const porDefecto = [{ id: 'a', imagen: '/x.jpg', alt: 'x' }]
    expect(diapositivasDePortada(cuerpo as { data?: unknown }, porDefecto)).toBe(porDefecto)
    expect(simulada.rutasDesconocidas()).not.toContain('GET /api/v1/banners')
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
