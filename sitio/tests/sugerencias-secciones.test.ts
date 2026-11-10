/**
 * Las secciones de la sede dentro del desplegable de sugerencias (M01-3 · RF-01-009).
 *
 * Enmienda la decisión D-I10 de `docs/busqueda-interaccion.md`, que las dejaba sólo en
 * `/buscar`: el criterio corregido CA-01-009 pide que «transparencia» devuelva la
 * página homónima y que la tolerancia a erratas valga también al escribir. Las
 * secciones salen del propio sitio (`config/sitemap.ts`), no de la API, así que se
 * mezclan con lo que ésta contesta cuando la respuesta llega.
 *
 *  - Van **primero** (hasta 3) y después los trámites y políticas; en total, las 6 de D-I2.
 *  - Si la API no contesta algo útil (sin resultados, error, 429), las secciones
 *    que calzan se ofrecen igual: son locales y no dependen de ella.
 *  - La pausa de 350 ms y los límites de la API no cambian: no hay peticiones nuevas.
 */
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import {
  CATEGORIA_SECCION,
  LIMITE_SUGERENCIAS,
  MAXIMO_SECCIONES,
  PAUSA_MS,
  useSugerenciasBusqueda,
  type PedirSugerencias,
  type RespuestaHttp,
} from '../app/composables/useSugerenciasBusqueda'
import { crearIndiceDeSecciones } from '../app/composables/seccionesBusqueda'

const INDICE = crearIndiceDeSecciones([
  { ruta: '/', etiqueta: 'Sede Electrónica', etiquetaMenu: 'Inicio', publicada: true, orden: 0 },
  { ruta: '/transparencia', etiqueta: 'Transparencia y acceso a la información pública', publicada: true, orden: 1 },
  { ruta: '/pqrsd', etiqueta: 'Peticiones, quejas, reclamos, sugerencias y denuncias', etiquetaMenu: 'PQRSD', publicada: true, orden: 2 },
  { ruta: '/realizar-una-peticion', etiqueta: 'Realizar una petición', publicada: true, orden: 3 },
  { ruta: '/atencion', etiqueta: 'Canales de atención', publicada: true, orden: 4 },
  { ruta: '/servicios-1', etiqueta: 'Servicios uno', publicada: true, orden: 5 },
  { ruta: '/servicios-2', etiqueta: 'Servicios dos', publicada: true, orden: 6 },
  { ruta: '/servicios-3', etiqueta: 'Servicios tres', publicada: true, orden: 7 },
  { ruta: '/servicios-4', etiqueta: 'Servicios cuatro', publicada: true, orden: 8 },
  { ruta: '/noticias', etiqueta: 'Noticias', publicada: false, orden: 9 },
])

function elemento(id: number, tipo = 'tramite') {
  return {
    id,
    type: 'sugerencias-busqueda',
    titulo: `Contenido ${id}`,
    categoria: tipo === 'tramite' ? 'Trámite' : 'Política',
    tipo_recurso: tipo,
    url: `/${tipo === 'tramite' ? 'tramites' : 'politicas'}/c-${id}`,
    score: 0.5,
  }
}

function ok(...ids: number[]): RespuestaHttp {
  return {
    status: 200,
    headers: { get: () => null },
    cuerpo: { success: true, message: null, data: ids.map((id) => elemento(id)), errors: null },
  }
}

function conEstado(status: number, cabeceras: Record<string, string> = {}): RespuestaHttp {
  return { status, headers: { get: (n: string) => cabeceras[n] ?? null }, cuerpo: { success: false, data: null } }
}

/** Un `pedir` que contesta de inmediato lo que se le diga, y cuenta las llamadas. */
function crearPedir(respuesta: RespuestaHttp) {
  const consultas: string[] = []
  const pedir: PedirSugerencias = async (consulta) => {
    consultas.push(consulta)
    return respuesta
  }
  return { pedir, consultas }
}

async function consultar(texto: string, respuesta: RespuestaHttp) {
  const { pedir, consultas } = crearPedir(respuesta)
  const motor = useSugerenciasBusqueda({ pedir, secciones: INDICE })
  motor.escribir(texto)
  await vi.advanceTimersByTimeAsync(PAUSA_MS)
  return { motor, consultas }
}

beforeEach(() => {
  vi.useFakeTimers()
  vi.setSystemTime(new Date('2026-11-10T12:00:00Z'))
})

afterEach(() => {
  vi.useRealTimers()
})

describe('secciones junto a lo que contesta la API', () => {
  it('«transparenci»: la API no halla nada y la sección sí, así que hay lista y no «sin sugerencias»', async () => {
    const { motor } = await consultar('transparenci', ok())
    expect(motor.estado.value).toBe('listo')
    expect(motor.sugerencias.value.map((s) => s.url)).toEqual(['/transparencia'])
  })

  it('la sección lleva su título completo, la categoría «Sección» y un tipo propio', async () => {
    const { motor } = await consultar('transparencia', ok())
    expect(CATEGORIA_SECCION).toBe('Sección')
    expect(motor.sugerencias.value[0]).toMatchObject({
      titulo: 'Transparencia y acceso a la información pública',
      categoria: 'Sección',
      tipoRecurso: 'seccion',
      url: '/transparencia',
    })
  })

  it('las secciones van antes que los trámites y políticas, y el total no pasa de 6', async () => {
    const { motor } = await consultar('transparencia', ok(1, 2, 3, 4, 5, 6))
    const urls = motor.sugerencias.value.map((s) => s.url)
    expect(urls).toHaveLength(LIMITE_SUGERENCIAS)
    expect(urls[0]).toBe('/transparencia')
    expect(urls.slice(1)).toEqual([1, 2, 3, 4, 5].map((n) => `/tramites/c-${n}`))
  })

  it('una consulta que no calza con ninguna sección deja la lista de la API tal cual', async () => {
    const { motor } = await consultar('licencia de construccion', ok(1, 2, 3))
    expect(motor.sugerencias.value.map((s) => s.url)).toEqual(['/tramites/c-1', '/tramites/c-2', '/tramites/c-3'])
    expect(motor.sugerencias.value.some((s) => s.tipoRecurso === 'seccion')).toBe(false)
  })

  it(`no pone más de ${MAXIMO_SECCIONES} secciones aunque calcen más`, async () => {
    const { motor } = await consultar('servicios', ok(1, 2))
    const secciones = motor.sugerencias.value.filter((s) => s.tipoRecurso === 'seccion')
    expect(MAXIMO_SECCIONES).toBe(3)
    expect(secciones).toHaveLength(MAXIMO_SECCIONES)
    expect(motor.sugerencias.value).toHaveLength(MAXIMO_SECCIONES + 2)
  })

  it('una sección en preparación no se ofrece', async () => {
    const { motor } = await consultar('noticias', ok())
    expect(motor.estado.value).toBe('sin-resultados')
    expect(motor.sugerencias.value).toEqual([])
  })

  it('sin sección ni trámite sigue diciendo «sin resultados»', async () => {
    const { motor } = await consultar('xyzxyz', ok())
    expect(motor.estado.value).toBe('sin-resultados')
  })

  it('tolera una letra mal escrita, como la API', async () => {
    const { motor } = await consultar('transparensia', ok())
    expect(motor.sugerencias.value.map((s) => s.url)).toEqual(['/transparencia'])
  })

  it('no hay peticiones de más: una por pausa, igual que antes', async () => {
    const { consultas } = await consultar('transparencia', ok())
    expect(consultas).toEqual(['transparencia'])
  })

  it('la lista cacheada también lleva las secciones', async () => {
    const { pedir, consultas } = crearPedir(ok(1))
    const motor = useSugerenciasBusqueda({ pedir, secciones: INDICE })
    motor.escribir('transparencia')
    await vi.advanceTimersByTimeAsync(PAUSA_MS)
    motor.reiniciar()
    motor.escribir('transparencia')
    await vi.advanceTimersByTimeAsync(PAUSA_MS)
    expect(consultas).toEqual(['transparencia'])
    expect(motor.sugerencias.value.map((s) => s.url)).toEqual(['/transparencia', '/tramites/c-1'])
  })
})

describe('cuando la API falla o limita, lo local sigue sirviendo', () => {
  it('con un error y una sección que calza, se ofrece la sección', async () => {
    const { motor } = await consultar('transparencia', conEstado(500))
    expect(motor.estado.value).toBe('listo')
    expect(motor.sugerencias.value.map((s) => s.url)).toEqual(['/transparencia'])
  })

  it('con un error y ninguna sección, el aviso de error de siempre', async () => {
    const { motor } = await consultar('licencia', conEstado(500))
    expect(motor.estado.value).toBe('error')
    expect(motor.sugerencias.value).toEqual([])
  })

  it('con un 429 y una sección que calza, se ofrece la sección y la pausa sigue en pie', async () => {
    const { pedir, consultas } = crearPedir(conEstado(429, { 'Retry-After': '30' }))
    const motor = useSugerenciasBusqueda({ pedir, secciones: INDICE })
    motor.escribir('transparencia')
    await vi.advanceTimersByTimeAsync(PAUSA_MS)
    expect(motor.estado.value).toBe('listo')
    expect(motor.sugerencias.value.map((s) => s.url)).toEqual(['/transparencia'])

    // Dentro de los 30 s no se vuelve a preguntar a la API (D-I7).
    motor.escribir('transparenciaa')
    await vi.advanceTimersByTimeAsync(PAUSA_MS)
    expect(consultas).toHaveLength(1)
  })

  it('durante la pausa de un 429 la lista local sigue al texto, sin preguntar a la API', async () => {
    const { pedir, consultas } = crearPedir(conEstado(429, { 'Retry-After': '30' }))
    const motor = useSugerenciasBusqueda({ pedir, secciones: INDICE })
    motor.escribir('transparencia')
    await vi.advanceTimersByTimeAsync(PAUSA_MS)
    motor.escribir('pqrsd')
    await vi.advanceTimersByTimeAsync(PAUSA_MS)
    expect(consultas).toHaveLength(1)
    expect(motor.estado.value).toBe('listo')
    expect(motor.sugerencias.value.map((s) => s.url)).toEqual(['/pqrsd'])
  })

  it('si la lista local queda vacía durante un 429, el aviso repite los mismos segundos (sin cuenta atrás)', async () => {
    const { pedir } = crearPedir(conEstado(429, { 'Retry-After': '30' }))
    const motor = useSugerenciasBusqueda({ pedir, secciones: INDICE })
    motor.escribir('licencia')
    await vi.advanceTimersByTimeAsync(PAUSA_MS)
    expect(motor.estado.value).toBe('limitado')
    expect(motor.esperaSegundos.value).toBe(30)

    await vi.advanceTimersByTimeAsync(5000)
    motor.escribir('licencias')
    await vi.advanceTimersByTimeAsync(PAUSA_MS)
    expect(motor.estado.value).toBe('limitado')
    expect(motor.esperaSegundos.value).toBe(30)
  })

  it('con un 429 y ninguna sección, el aviso de espera de siempre', async () => {
    const { motor } = await consultar('licencia', conEstado(429, { 'Retry-After': '30' }))
    expect(motor.estado.value).toBe('limitado')
    expect(motor.esperaSegundos.value).toBe(30)
  })
})

describe('las rutas de las secciones son del sitio', () => {
  it('toda sección ofrecida es una ruta interna', async () => {
    const { motor } = await consultar('servicios', ok())
    for (const s of motor.sugerencias.value) {
      expect(s.url.startsWith('/')).toBe(true)
      expect(s.url.startsWith('//')).toBe(false)
    }
  })

  it('las identificaciones no chocan con las de los trámites', async () => {
    const { motor } = await consultar('transparencia', ok(1, 2))
    const claves = motor.sugerencias.value.map((s) => `${s.tipoRecurso}-${s.id}`)
    expect(new Set(claves).size).toBe(claves.length)
  })
})
