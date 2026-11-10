/**
 * Sugerencias del buscador (T-22, E3-1 · AC-22.2, AC-22.7, AC-22.12).
 *
 * Una prueba por regla de `docs/busqueda-interaccion.md` §4.5. Las respuestas
 * son promesas que la prueba resuelve a mano y en el orden que quiera: así se
 * puede comprobar lo que un servidor real nunca hace a demanda, que es contestar
 * una consulta vieja después de la nueva.
 */
import { effectScope } from 'vue'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import {
  analizarSugerencias,
  CACHE_MAXIMO,
  crearPeticionSugerencias,
  ESPERA_MAXIMA_429_S,
  esRutaInterna,
  INDICADOR_CARGA_MS,
  LIMITE_SUGERENCIAS,
  MAXIMO_CARACTERES,
  normalizarConsulta,
  PAUSA_MS,
  segundosDeEspera,
  useSugerenciasBusqueda,
  type FetchCrudo,
  type PedirSugerencias,
  type RespuestaHttp,
} from '../app/composables/useSugerenciasBusqueda'

interface Llamada {
  consulta: string
  limite: number
  senal: AbortSignal
  resolver: (respuesta: RespuestaHttp) => void
  rechazar: (motivo: unknown) => void
}

/** Un `pedir` falso: cada llamada queda pendiente hasta que la prueba la resuelva. */
function crearPedir() {
  const llamadas: Llamada[] = []
  const pedir: PedirSugerencias = (consulta, limite, senal) =>
    new Promise<RespuestaHttp>((resolver, rechazar) => {
      llamadas.push({ consulta, limite, senal, resolver, rechazar })
    })
  return { pedir, llamadas }
}

function elemento(id: number, extra: Record<string, unknown> = {}) {
  return {
    id,
    type: 'sugerencias-busqueda',
    titulo: `Trámite ${id}`,
    categoria: 'Trámite',
    tipo_recurso: 'tramite',
    url: `/tramites/tramite-${id}`,
    score: 0.5,
    ...extra,
  }
}

function conCabeceras(cabeceras: Record<string, string> = {}) {
  return { get: (nombre: string) => cabeceras[nombre] ?? null }
}

function ok(...ids: number[]): RespuestaHttp {
  return {
    status: 200,
    headers: conCabeceras(),
    cuerpo: { success: true, message: null, data: ids.map((id) => elemento(id)), errors: null },
  }
}

function conEstado(status: number, cabeceras: Record<string, string> = {}): RespuestaHttp {
  return { status, headers: conCabeceras(cabeceras), cuerpo: { success: false, data: null } }
}

/** Deja correr los temporizadores y las promesas pendientes. */
async function avanzar(ms: number): Promise<void> {
  await vi.advanceTimersByTimeAsync(ms)
}

beforeEach(() => {
  vi.useFakeTimers()
  vi.setSystemTime(new Date('2026-11-10T12:00:00Z'))
})

afterEach(() => {
  vi.useRealTimers()
  vi.unstubAllGlobals()
  vi.restoreAllMocks()
})

describe('mínimo de caracteres y normalización (regla 1)', () => {
  it('con menos de 3 caracteres no consulta, aunque pase el tiempo', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.escribir('li')
    sugerencias.escribir('  a ')
    sugerencias.pedirYa('ab')
    await avanzar(5000)
    expect(llamadas).toHaveLength(0)
    expect(sugerencias.estado.value).toBe('inactivo')
  })

  it('recorta y colapsa espacios antes de consultar', async () => {
    const { pedir, llamadas } = crearPedir()
    useSugerenciasBusqueda({ pedir }).escribir('  licencia   de\tconstrucción  ')
    await avanzar(PAUSA_MS)
    expect(llamadas.map((llamada) => llamada.consulta)).toEqual(['licencia de construcción'])
  })

  it('nunca manda más de 120 caracteres', async () => {
    const { pedir, llamadas } = crearPedir()
    useSugerenciasBusqueda({ pedir }).escribir('a'.repeat(MAXIMO_CARACTERES + 80))
    await avanzar(PAUSA_MS)
    expect(llamadas[0]?.consulta).toHaveLength(MAXIMO_CARACTERES)
  })

  it('normalizarConsulta expone la misma regla', () => {
    expect(normalizarConsulta('  a   b  ')).toBe('a b')
  })
})

describe('pausa de 350 ms (regla 2, D-I1)', () => {
  it('consulta una sola vez, justo al cumplirse la pausa, pidiendo 6', async () => {
    const { pedir, llamadas } = crearPedir()
    useSugerenciasBusqueda({ pedir }).escribir('lic')
    await avanzar(PAUSA_MS - 1)
    expect(llamadas).toHaveLength(0)
    await avanzar(1)
    expect(llamadas).toHaveLength(1)
    expect(llamadas[0]).toMatchObject({ consulta: 'lic', limite: LIMITE_SUGERENCIAS })
    expect(LIMITE_SUGERENCIAS).toBe(6)
    expect(PAUSA_MS).toBe(350)
  })

  it('cada tecla reinicia la pausa: sólo se consulta el último texto', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.escribir('lic')
    await avanzar(300)
    sugerencias.escribir('lice')
    await avanzar(300)
    sugerencias.escribir('licen')
    await avanzar(300)
    expect(llamadas).toHaveLength(0)
    await avanzar(50)
    expect(llamadas.map((llamada) => llamada.consulta)).toEqual(['licen'])
  })

  it('pedirYa (↓) no espera la pausa y anula la que estaba pendiente', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.escribir('licen')
    sugerencias.pedirYa('licen')
    expect(llamadas).toHaveLength(1)
    await avanzar(5000)
    expect(llamadas).toHaveLength(1)
  })

  it('un espacio de más al final no cancela ni repite la consulta en vuelo', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.escribir('licencia')
    await avanzar(PAUSA_MS)
    sugerencias.escribir('licencia ')
    await avanzar(PAUSA_MS)
    expect(llamadas).toHaveLength(1)
    expect(llamadas[0]?.senal.aborted).toBe(false)
    llamadas[0]?.resolver(ok(1))
    await avanzar(0)
    expect(sugerencias.estado.value).toBe('listo')
  })
})

describe('sólo vale la última respuesta (regla 3, AC-22.12)', () => {
  it('una consulta nueva cancela la anterior', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.escribir('licenc')
    await avanzar(PAUSA_MS)
    sugerencias.escribir('licencia')
    await avanzar(PAUSA_MS)
    expect(llamadas).toHaveLength(2)
    expect(llamadas[0]?.senal.aborted).toBe(true)
    expect(llamadas[1]?.senal.aborted).toBe(false)
  })

  it('la respuesta de la consulta vieja, aunque llegue después, no pisa a la última', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.escribir('licenc')
    await avanzar(PAUSA_MS)
    sugerencias.escribir('licencia')
    await avanzar(PAUSA_MS)

    // En desorden: primero la nueva, después la vieja.
    llamadas[1]?.resolver(ok(2))
    await avanzar(0)
    expect(sugerencias.sugerencias.value.map((s) => s.id)).toEqual([2])
    llamadas[0]?.resolver(ok(1))
    await avanzar(0)
    expect(sugerencias.sugerencias.value.map((s) => s.id)).toEqual([2])
    expect(sugerencias.estado.value).toBe('listo')
  })

  it('el fallo de una consulta cancelada tampoco se enseña', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.escribir('licenc')
    await avanzar(PAUSA_MS)
    sugerencias.escribir('licencia')
    await avanzar(PAUSA_MS)
    llamadas[0]?.rechazar(new DOMException('cancelada', 'AbortError'))
    await avanzar(0)
    expect(sugerencias.estado.value).toBe('inactivo')
    llamadas[1]?.resolver(ok(2))
    await avanzar(0)
    expect(sugerencias.estado.value).toBe('listo')
  })

  it('cancelar() hace que una respuesta posterior se ignore', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.escribir('licencia')
    await avanzar(PAUSA_MS)
    sugerencias.cancelar()
    expect(llamadas[0]?.senal.aborted).toBe(true)
    llamadas[0]?.resolver(ok(1))
    await avanzar(0)
    expect(sugerencias.sugerencias.value).toEqual([])
    expect(sugerencias.estado.value).toBe('inactivo')
  })

  it('cancelar() también anula la pausa pendiente', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.escribir('licencia')
    sugerencias.cancelar()
    await avanzar(5000)
    expect(llamadas).toHaveLength(0)
  })
})

describe('composición (regla 4)', () => {
  it('no consulta mientras dura la composición y lo hace al terminar, tras la pausa', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.empezarComposicion()
    sugerencias.escribir('licenc')
    sugerencias.pedirYa('licenc')
    await avanzar(2000)
    expect(llamadas).toHaveLength(0)

    sugerencias.terminarComposicion('licencia')
    await avanzar(PAUSA_MS - 1)
    expect(llamadas).toHaveLength(0)
    await avanzar(1)
    expect(llamadas.map((llamada) => llamada.consulta)).toEqual(['licencia'])
  })

  it('empezar una composición anula la pausa que ya corría', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.escribir('licen')
    sugerencias.empezarComposicion()
    await avanzar(2000)
    expect(llamadas).toHaveLength(0)
  })
})

describe('memoria breve (regla 5)', () => {
  async function consultar(sugerencias: ReturnType<typeof useSugerenciasBusqueda>, texto: string) {
    sugerencias.escribir(texto)
    await avanzar(PAUSA_MS)
  }

  it('repetir una consulta, aun con otras tildes o mayúsculas, no vuelve a preguntar', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    await consultar(sugerencias, 'licencia')
    llamadas[0]?.resolver(ok(1))
    await avanzar(0)

    await consultar(sugerencias, 'LICÉNCIA')
    expect(llamadas).toHaveLength(1)
    expect(sugerencias.estado.value).toBe('listo')
    expect(sugerencias.sugerencias.value.map((s) => s.id)).toEqual([1])
  })

  it('caduca a los 60 s', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    await consultar(sugerencias, 'licencia')
    llamadas[0]?.resolver(ok(1))
    await avanzar(0)

    await avanzar(58_000)
    await consultar(sugerencias, 'licencia')
    expect(llamadas).toHaveLength(1)

    await avanzar(2_000)
    await consultar(sugerencias, 'licencia')
    expect(llamadas).toHaveLength(2)
  })

  it(`guarda como mucho ${CACHE_MAXIMO} consultas: la más antigua sale primero`, async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    const nombre = (n: number) => `consulta ${String(n).padStart(2, '0')}`

    for (let n = 0; n <= CACHE_MAXIMO; n += 1) {
      await consultar(sugerencias, nombre(n))
      llamadas[n]?.resolver(ok(n))
      await avanzar(0)
    }
    expect(llamadas).toHaveLength(CACHE_MAXIMO + 1)

    // La última sigue en memoria; la primera salió.
    await consultar(sugerencias, nombre(CACHE_MAXIMO))
    expect(llamadas).toHaveLength(CACHE_MAXIMO + 1)
    await consultar(sugerencias, nombre(0))
    expect(llamadas).toHaveLength(CACHE_MAXIMO + 2)
  })

  it('reiniciar() vacía lo que se enseña pero conserva la memoria', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    await consultar(sugerencias, 'licencia')
    llamadas[0]?.resolver(ok(1))
    await avanzar(0)

    sugerencias.reiniciar()
    expect(sugerencias.sugerencias.value).toEqual([])
    expect(sugerencias.estado.value).toBe('inactivo')

    await consultar(sugerencias, 'licencia')
    expect(llamadas).toHaveLength(1)
    expect(sugerencias.estado.value).toBe('listo')
  })

  it('borrar el campo hasta menos de 3 caracteres vacía la lista', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    await consultar(sugerencias, 'licencia')
    llamadas[0]?.resolver(ok(1))
    await avanzar(0)
    sugerencias.escribir('li')
    expect(sugerencias.sugerencias.value).toEqual([])
    expect(sugerencias.estado.value).toBe('inactivo')
  })
})

describe('429: pausa hasta Retry-After (regla 6, D-I7)', () => {
  async function limitado(segundos: string | null) {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.escribir('licencia')
    await avanzar(PAUSA_MS)
    llamadas[0]?.resolver(conEstado(429, segundos === null ? {} : { 'Retry-After': segundos }))
    await avanzar(0)
    return { sugerencias, llamadas }
  }

  it('pasa a «limitado» con los segundos de la cabecera y vacía la lista', async () => {
    const { sugerencias } = await limitado('5')
    expect(sugerencias.estado.value).toBe('limitado')
    expect(sugerencias.esperaSegundos.value).toBe(5)
    expect(sugerencias.sugerencias.value).toEqual([])
  })

  it('no vuelve a preguntar durante la espera, ni escribiendo ni con ↓', async () => {
    const { sugerencias, llamadas } = await limitado('5')
    sugerencias.escribir('licencias')
    await avanzar(PAUSA_MS)
    sugerencias.pedirYa('licencias')
    await avanzar(1000)
    expect(llamadas).toHaveLength(1)
    expect(sugerencias.estado.value).toBe('limitado')
  })

  it('al terminar la espera retira el aviso, pero no pregunta por su cuenta', async () => {
    const { sugerencias, llamadas } = await limitado('5')
    await avanzar(4_000)
    expect(sugerencias.estado.value).toBe('limitado')
    await avanzar(1_000)
    expect(sugerencias.estado.value).toBe('inactivo')
    expect(sugerencias.esperaSegundos.value).toBe(0)
    expect(llamadas).toHaveLength(1)

    sugerencias.escribir('licencias')
    await avanzar(PAUSA_MS)
    expect(llamadas).toHaveLength(2)
  })

  it('borrar el campo no es una forma de saltarse el límite', async () => {
    const { sugerencias, llamadas } = await limitado('30')
    sugerencias.escribir('')
    expect(sugerencias.estado.value).toBe('limitado')
    sugerencias.escribir('licencia')
    await avanzar(PAUSA_MS)
    expect(llamadas).toHaveLength(1)
  })

  it('sin cabecera espera 60 s', async () => {
    const { sugerencias } = await limitado(null)
    expect(sugerencias.esperaSegundos.value).toBe(ESPERA_MAXIMA_429_S)
  })

  it('segundosDeEspera: números, fechas HTTP, topes y basura', () => {
    const ahora = Date.UTC(2026, 10, 10, 12, 0, 0)
    expect(segundosDeEspera('12', ahora)).toBe(12)
    expect(segundosDeEspera(' 7 ', ahora)).toBe(7)
    expect(segundosDeEspera('0', ahora)).toBe(1)
    expect(segundosDeEspera('999', ahora)).toBe(60)
    expect(segundosDeEspera(new Date(ahora + 30_000).toUTCString(), ahora)).toBe(30)
    expect(segundosDeEspera(new Date(ahora - 30_000).toUTCString(), ahora)).toBe(1)
    expect(segundosDeEspera(new Date(ahora + 600_000).toUTCString(), ahora)).toBe(60)
    expect(segundosDeEspera('pronto', ahora)).toBe(60)
    expect(segundosDeEspera('', ahora)).toBe(60)
    expect(segundosDeEspera(null, ahora)).toBe(60)
  })
})

describe('fallos: sin reintento automático (regla 6)', () => {
  async function conFallo(causa: 'red' | 500 | 'forma') {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.escribir('licencia')
    await avanzar(PAUSA_MS)
    if (causa === 'red') llamadas[0]?.rechazar(new TypeError('fallo de red'))
    else if (causa === 500) llamadas[0]?.resolver(conEstado(500))
    else llamadas[0]?.resolver({ status: 200, headers: conCabeceras(), cuerpo: { data: 'no es una lista' } })
    await avanzar(0)
    return { sugerencias, llamadas }
  }

  it.each(['red', 500, 'forma'] as const)('un fallo (%s) pasa a «error»', async (causa) => {
    const { sugerencias } = await conFallo(causa)
    expect(sugerencias.estado.value).toBe('error')
    expect(sugerencias.sugerencias.value).toEqual([])
  })

  it('no se vuelve a preguntar antes de 2 s; después, la siguiente tecla sí consulta', async () => {
    const { sugerencias, llamadas } = await conFallo('red')
    sugerencias.escribir('licencias')
    await avanzar(PAUSA_MS)
    expect(llamadas).toHaveLength(1)
    expect(sugerencias.estado.value).toBe('error')

    await avanzar(2_000)
    sugerencias.escribir('licenciass')
    await avanzar(PAUSA_MS)
    expect(llamadas).toHaveLength(2)
  })

  it('un 422 no es un fallo: se trata como «sin resultados» y no bloquea', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.escribir('licencia')
    await avanzar(PAUSA_MS)
    llamadas[0]?.resolver(conEstado(422))
    await avanzar(0)
    expect(sugerencias.estado.value).toBe('sin-resultados')

    sugerencias.escribir('licencias')
    await avanzar(PAUSA_MS)
    expect(llamadas).toHaveLength(2)
  })

  it('una lista vacía es «sin resultados» y se guarda en memoria', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.escribir('zzzz')
    await avanzar(PAUSA_MS)
    llamadas[0]?.resolver(ok())
    await avanzar(0)
    expect(sugerencias.estado.value).toBe('sin-resultados')
    sugerencias.escribir('zzzz')
    await avanzar(PAUSA_MS)
    expect(llamadas).toHaveLength(1)
  })
})

describe('indicador de carga (§4.4)', () => {
  it('aparece a los 200 ms y desaparece al llegar la respuesta', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.escribir('licencia')
    await avanzar(PAUSA_MS)
    expect(sugerencias.cargando.value).toBe(true)
    expect(sugerencias.cargandoVisible.value).toBe(false)

    await avanzar(INDICADOR_CARGA_MS - 1)
    expect(sugerencias.cargandoVisible.value).toBe(false)
    await avanzar(1)
    expect(sugerencias.cargandoVisible.value).toBe(true)

    llamadas[0]?.resolver(ok(1))
    await avanzar(0)
    expect(sugerencias.cargando.value).toBe(false)
    expect(sugerencias.cargandoVisible.value).toBe(false)
  })

  it('mientras carga la siguiente, la lista previa se conserva (nunca un vacío)', async () => {
    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    sugerencias.escribir('licenc')
    await avanzar(PAUSA_MS)
    llamadas[0]?.resolver(ok(1))
    await avanzar(0)

    sugerencias.escribir('licencia')
    await avanzar(PAUSA_MS + INDICADOR_CARGA_MS)
    expect(sugerencias.cargandoVisible.value).toBe(true)
    expect(sugerencias.estado.value).toBe('listo')
    expect(sugerencias.sugerencias.value.map((s) => s.id)).toEqual([1])
  })
})

describe('lo que llega de la API', () => {
  it('descarta las sugerencias malformadas o con una url que sale del sitio', () => {
    const lista = analizarSugerencias({
      data: [
        elemento(1),
        elemento(2, { url: '//otro.sitio/phishing' }),
        elemento(3, { url: 'javascript:alert(1)' }),
        elemento(4, { url: 'https://otro.sitio/a' }),
        elemento(5, { url: '/con\\barra' }),
        elemento(6, { url: '/con espacio' }),
        elemento(7, { titulo: '   ' }),
        elemento(8, { id: '8' }),
        elemento(9, { categoria: null }),
        elemento(10, { tipo_recurso: 5 }),
        null,
        'texto',
        elemento(11),
      ],
    })
    expect(lista?.map((s) => s.id)).toEqual([1, 11])
    expect(lista?.[0]).toEqual({
      id: 1,
      titulo: 'Trámite 1',
      categoria: 'Trámite',
      tipoRecurso: 'tramite',
      url: '/tramites/tramite-1',
    })
  })

  it('enseña como mucho 6 aunque la API mande 10', () => {
    const lista = analizarSugerencias({ data: Array.from({ length: 10 }, (_, i) => elemento(i + 1)) })
    expect(lista).toHaveLength(LIMITE_SUGERENCIAS)
  })

  it.each([null, undefined, 'texto', 3, {}, { data: 'x' }, { data: null }])(
    'un cuerpo sin la forma del contrato (%j) no es una lista',
    (cuerpo) => {
      expect(analizarSugerencias(cuerpo)).toBeNull()
    },
  )

  it('esRutaInterna acepta rutas del sitio y rechaza lo demás', () => {
    for (const buena of ['/', '/tramites/licencia', '/politicas/uso-de-cookies?x=1#a', '/a%20b']) {
      expect(esRutaInterna(buena), buena).toBe(true)
    }
    for (const mala of ['', 'tramites', '//x', 'http://x', 'https://x/a', '/a\\b', '/a b', '/a\nb', '/a\u0000b', null, 42]) {
      expect(esRutaInterna(mala), String(mala)).toBe(false)
    }
  })
})

describe('la petición real', () => {
  it('va a /busqueda/sugerencias, sin barra doble, con q y limite, y no lanza con 429', async () => {
    const fetcher = vi.fn<FetchCrudo>().mockResolvedValue({
      status: 429,
      headers: conCabeceras({ 'Retry-After': '9' }),
      _data: { success: false },
    })
    const senal = new AbortController().signal
    const respuesta = await crearPeticionSugerencias(fetcher, 'https://sede.example/api/v1/')('licencia', 6, senal)

    expect(fetcher).toHaveBeenCalledWith('https://sede.example/api/v1/busqueda/sugerencias', {
      query: { q: 'licencia', limite: 6 },
      signal: senal,
      ignoreResponseError: true,
    })
    expect(respuesta.status).toBe(429)
    expect(respuesta.headers.get('Retry-After')).toBe('9')
    expect(respuesta.cuerpo).toEqual({ success: false })
  })

  it('sin `pedir` inyectado usa $fetch.raw y la dirección de la API del entorno', async () => {
    const raw = vi.fn<FetchCrudo>().mockResolvedValue({
      status: 200,
      headers: conCabeceras(),
      _data: { data: [elemento(1)] },
    })
    vi.stubGlobal('$fetch', { raw })
    vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiUrl: '/api/v1' } }))

    const sugerencias = useSugerenciasBusqueda()
    sugerencias.escribir('licencia')
    await avanzar(PAUSA_MS)

    expect(raw).toHaveBeenCalledOnce()
    expect(raw.mock.calls[0]?.[0]).toBe('/api/v1/busqueda/sugerencias')
    expect(sugerencias.estado.value).toBe('listo')
  })
})

describe('privacidad (regla 7, D-I6, AC-22.7)', () => {
  it('lo escrito no llega al almacenamiento, a las cookies ni a la consola', async () => {
    const guardados = [
      vi.spyOn(Storage.prototype, 'setItem'),
      vi.spyOn(Storage.prototype, 'getItem'),
    ]
    const consola = (['log', 'info', 'warn', 'error', 'debug'] as const).map((metodo) =>
      vi.spyOn(console, metodo).mockImplementation(() => undefined),
    )
    const cookiesAntes = document.cookie

    const { pedir, llamadas } = crearPedir()
    const sugerencias = useSugerenciasBusqueda({ pedir })
    const documentoSecreto = '1234567890'
    sugerencias.escribir(documentoSecreto)
    await avanzar(PAUSA_MS)
    llamadas[0]?.rechazar(new TypeError(`fallo con ${documentoSecreto}`))
    await avanzar(0)
    sugerencias.escribir(`${documentoSecreto}1`)
    await avanzar(5_000)
    llamadas[1]?.resolver(conEstado(429))
    await avanzar(0)

    for (const espia of [...guardados, ...consola]) expect(espia).not.toHaveBeenCalled()
    expect(document.cookie).toBe(cookiesAntes)
  })
})

describe('ciclo de vida', () => {
  it('al destruirse el ámbito se cancela todo: pausa, petición y aviso de espera', async () => {
    const { pedir, llamadas } = crearPedir()
    const ambito = effectScope()
    const sugerencias = ambito.run(() => useSugerenciasBusqueda({ pedir }))
    if (!sugerencias) throw new Error('el ámbito no devolvió el composable')

    sugerencias.escribir('licencia')
    ambito.stop()
    await avanzar(5_000)
    expect(llamadas).toHaveLength(0)

    const otro = effectScope()
    const limitada = otro.run(() => useSugerenciasBusqueda({ pedir }))
    if (!limitada) throw new Error('el ámbito no devolvió el composable')
    limitada.escribir('licencia')
    await avanzar(PAUSA_MS)
    llamadas[0]?.resolver(conEstado(429, { 'Retry-After': '5' }))
    await avanzar(0)
    otro.stop()
    await avanzar(10_000)
    // El temporizador del aviso se canceló con el ámbito: no llegó a retirarlo.
    expect(limitada.estado.value).toBe('limitado')
  })
})
