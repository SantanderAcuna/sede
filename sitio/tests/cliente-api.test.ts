import { describe, expect, it, vi } from 'vitest'

import {
  ApiNoDisponible,
  ApiNoEncontrada,
  ApiRechazada,
  clasificarFallo,
  crearClienteApi,
  normalizarBase,
  type Traer,
} from '~/utils/cliente-api'

/** Un fallo con la forma que da ofetch: `statusCode` y `response.status`. */
const falloHttp = (estado: number, extra: Record<string, unknown> = {}) =>
  Object.assign(new Error(`HTTP ${estado}`), { statusCode: estado, response: { status: estado }, ...extra })

/** Un fallo de red: ofetch no pone ningún estado. */
const falloDeRed = () => Object.assign(new TypeError('fetch failed'), { name: 'FetchError' })

describe('normalizarBase', () => {
  it.each([
    ['/api/v1', '/api/v1'],
    ['/api/v1/', '/api/v1'],
    ['/api/v1///', '/api/v1'],
    ['https://api.example.test/api/v1/', 'https://api.example.test/api/v1'],
    ['', ''],
  ])('«%s» queda como «%s»', (entrada, esperado) => {
    expect(normalizarBase(entrada)).toBe(esperado)
  })

  it('un valor ausente se trata como cadena vacía, no como «undefined»', () => {
    expect(normalizarBase(undefined)).toBe('')
    expect(normalizarBase(null)).toBe('')
  })
})

describe('clasificarFallo', () => {
  it('404 → ApiNoEncontrada', () => {
    expect(clasificarFallo(falloHttp(404))).toBeInstanceOf(ApiNoEncontrada)
  })

  it.each([500, 502, 503, 504])('%i → ApiNoDisponible', (estado) => {
    expect(clasificarFallo(falloHttp(estado))).toBeInstanceOf(ApiNoDisponible)
  })

  it('un fallo de red (sin estado) → ApiNoDisponible', () => {
    expect(clasificarFallo(falloDeRed())).toBeInstanceOf(ApiNoDisponible)
  })

  it('el tiempo agotado (AbortError / TimeoutError) → ApiNoDisponible', () => {
    const tiempo = Object.assign(new Error('timeout'), { name: 'TimeoutError' })
    const abortado = Object.assign(new Error('aborted'), { name: 'AbortError' })
    expect(clasificarFallo(tiempo)).toBeInstanceOf(ApiNoDisponible)
    expect(clasificarFallo(abortado)).toBeInstanceOf(ApiNoDisponible)
  })

  it.each([400, 401, 403, 410, 422, 429])('%i → ApiRechazada (no es «caída» ni «no existe»)', (estado) => {
    const clasificado = clasificarFallo(falloHttp(estado))
    expect(clasificado).toBeInstanceOf(ApiRechazada)
    expect(clasificado).not.toBeInstanceOf(ApiNoDisponible)
    expect(clasificado).not.toBeInstanceOf(ApiNoEncontrada)
  })

  it('lee el estado de `response.status` cuando no hay `statusCode`', () => {
    const solo = Object.assign(new Error('x'), { response: { status: 404 } })
    expect(clasificarFallo(solo)).toBeInstanceOf(ApiNoEncontrada)
  })

  it('conserva el estado, el cuerpo y la causa para quien los necesite', () => {
    const original = falloHttp(422, { data: { message: 'inválido' } })
    const clasificado = clasificarFallo(original)
    expect(clasificado.statusCode).toBe(422)
    expect(clasificado.data).toEqual({ message: 'inválido' })
    expect(clasificado.cause).toBe(original)
  })

  it('conserva `statusMessage`: Nuxt lo serializa del servidor al navegador y no debe perderse', () => {
    const original = falloHttp(503, { statusMessage: 'Service Unavailable' })
    expect(clasificarFallo(original).statusMessage).toBe('Service Unavailable')
  })

  it('un valor que no es un Error (cadena, null) no rompe la clasificación', () => {
    expect(clasificarFallo('algo raro')).toBeInstanceOf(ApiNoDisponible)
    expect(clasificarFallo(null)).toBeInstanceOf(ApiNoDisponible)
    expect(clasificarFallo(undefined)).toBeInstanceOf(ApiNoDisponible)
  })

  it('un fallo ya clasificado no se reclasifica ni se envuelve de nuevo', () => {
    const ya = new ApiNoEncontrada('x', { statusCode: 404 })
    expect(clasificarFallo(ya)).toBe(ya)
  })
})

describe('crearClienteApi', () => {
  const traerQue = (resultado: () => Promise<unknown>): { traer: Traer; llamadas: unknown[][] } => {
    const llamadas: unknown[][] = []
    const traer = vi.fn((...args: unknown[]) => {
      llamadas.push(args)
      return resultado()
    }) as unknown as Traer
    return { traer, llamadas }
  }

  it('la base se normaliza una sola vez y se expone', () => {
    const { traer } = traerQue(async () => ({}))
    expect(crearClienteApi(traer, '/api/v1/').base).toBe('/api/v1')
    expect(crearClienteApi(traer, '/api/v1').base).toBe('/api/v1')
  })

  it('arma la URL con la base y la ruta, con y sin barra final en la base', async () => {
    for (const base of ['/api/v1', '/api/v1/']) {
      const { traer, llamadas } = traerQue(async () => ({ data: 1 }))
      const cliente = crearClienteApi(traer, base)
      await cliente.leer('/tramites')
      expect(llamadas[0]![0]).toBe('/api/v1/tramites')
    }
  })

  it('devuelve el cuerpo tal cual lo da el transporte', async () => {
    const { traer } = traerQue(async () => ({ data: [1, 2, 3] }))
    await expect(crearClienteApi(traer, '/api/v1').leer('/noticias')).resolves.toEqual({ data: [1, 2, 3] })
  })

  it('pasa las opciones sin tocarlas (query, método, cuerpo, tiempo)', async () => {
    const { traer, llamadas } = traerQue(async () => ({}))
    const opciones = { query: { per_page: 3 }, method: 'POST', body: { a: 1 }, timeout: 1000, ignoreResponseError: true }
    await crearClienteApi(traer, '/api/v1').leer('/x', opciones)
    expect(llamadas[0]![1]).toBe(opciones)
  })

  it('un 404 se lanza como ApiNoEncontrada', async () => {
    const { traer } = traerQue(async () => { throw falloHttp(404) })
    await expect(crearClienteApi(traer, '/api/v1').leer('/tramites/x')).rejects.toBeInstanceOf(ApiNoEncontrada)
  })

  it('un 5xx, la red y el tiempo agotado se lanzan como ApiNoDisponible', async () => {
    for (const fallo of [falloHttp(503), falloDeRed(), Object.assign(new Error('t'), { name: 'TimeoutError' })]) {
      const { traer } = traerQue(async () => { throw fallo })
      await expect(crearClienteApi(traer, '/api/v1').leer('/x')).rejects.toBeInstanceOf(ApiNoDisponible)
    }
  })

  it('un 422 o un 429 se lanzan como ApiRechazada', async () => {
    for (const estado of [422, 429]) {
      const { traer } = traerQue(async () => { throw falloHttp(estado) })
      await expect(crearClienteApi(traer, '/api/v1').leer('/x')).rejects.toBeInstanceOf(ApiRechazada)
    }
  })

  it('el error lanzado sigue exponiendo `statusCode`: el código que ya lo lee no cambia', async () => {
    const { traer } = traerQue(async () => { throw falloHttp(404) })
    const fallo = await crearClienteApi(traer, '/api/v1').leer('/x').catch((e: unknown) => e)
    expect((fallo as { statusCode?: number }).statusCode).toBe(404)
  })

  describe('leerONulo: 404 → null; el resto se propaga', () => {
    it('404 → null', async () => {
      const { traer } = traerQue(async () => { throw falloHttp(404) })
      await expect(crearClienteApi(traer, '/api/v1').leerONulo('/noticias/x')).resolves.toBeNull()
    })

    it('éxito → el cuerpo', async () => {
      const { traer } = traerQue(async () => ({ data: { id: 1 } }))
      await expect(crearClienteApi(traer, '/api/v1').leerONulo('/noticias/x')).resolves.toEqual({ data: { id: 1 } })
    })

    it('un 5xx NO es «no existe»: se propaga', async () => {
      const { traer } = traerQue(async () => { throw falloHttp(500) })
      await expect(crearClienteApi(traer, '/api/v1').leerONulo('/noticias/x')).rejects.toBeInstanceOf(ApiNoDisponible)
    })

    it('un 429 tampoco: se propaga', async () => {
      const { traer } = traerQue(async () => { throw falloHttp(429) })
      await expect(crearClienteApi(traer, '/api/v1').leerONulo('/noticias/x')).rejects.toBeInstanceOf(ApiRechazada)
    })
  })
})

describe('useClienteApi', () => {
  it('usa `useRequestFetch()` por defecto y lee la base de `public.apiUrl`, normalizada', async () => {
    const transporte = vi.fn(async () => ({ ok: true }))
    vi.stubGlobal('useRequestFetch', () => transporte)
    vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiUrl: '/api/v1/' } }))
    const { useClienteApi } = await import('~/composables/useClienteApi')

    const api = useClienteApi()
    expect(api.base).toBe('/api/v1')
    await api.leer('/banners')
    expect(transporte).toHaveBeenCalledWith('/api/v1/banners', undefined)
    vi.unstubAllGlobals()
  })

  it('con un transporte explícito no llama a `useRequestFetch()`', async () => {
    const usado = vi.fn()
    vi.stubGlobal('useRequestFetch', usado)
    vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiUrl: '/api/v1' } }))
    const { useClienteApi } = await import('~/composables/useClienteApi')

    const transporte = vi.fn(async () => ({}))
    await useClienteApi(transporte as unknown as Traer).leer('/x')
    expect(transporte).toHaveBeenCalledOnce()
    expect(usado).not.toHaveBeenCalled()
    vi.unstubAllGlobals()
  })
})
