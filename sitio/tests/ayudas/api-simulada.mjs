/**
 * API simulada para la puerta de diseño (`tests/diseno.mjs`).
 *
 * **Por qué existe.** La puerta de diseño levanta su propio servidor del sitio,
 * pero le apuntaba a la API del entorno de pruebas por Internet. Desde un
 * ejecutor de GitHub esa llamada la desafía Cloudflare (`cf-mitigated: challenge`,
 * HTTP 403: el paso «Sondear el entorno desde el ejecutor» de `despliegue.yml` lo
 * muestra), así que la página de la política de cookies —que responde 503 a
 * propósito cuando su API no contesta— fallaba siempre, y el flujo lo despachaba
 * como «no concluyente». Una prueba que depende de que un tercero la deje pasar no
 * mide el diseño: mide ese permiso.
 *
 * **Qué hace.** Un servidor HTTP local, en un puerto libre, que contesta las rutas
 * de la API que esta puerta consume y nada más. El sitio lo alcanza por el
 * canal interno que ya usa en producción (`NUXT_API_INTERNA`, ADR-0018): el
 * navegador pide `/api/v1/...` al propio sitio y este lo reenvía aquí. No toca
 * Cloudflare, ni el entorno de pruebas, ni la red.
 *
 * **Qué NO hace, a propósito.**
 *   - No inventa contenido institucional (AGENTS.md §7). Los textos son de prueba
 *     y lo dicen. La puerta mide estructura —foco, tamaños, contraste, estados—,
 *     no el contenido que apruebe la Entidad.
 *   - No oculta lo que no conoce. Cualquier ruta que el sitio pida y no esté aquí
 *     responde 404 con el sobre real de la API **y queda registrada**:
 *     `rutasDesconocidas()` la devuelve y la puerta falla con la lista. Así, si una
 *     página nueva empieza a necesitar un dato, el simulador no se queda
 *     silenciosamente corto.
 *   - Sólo GET y HEAD, como el canal interno.
 */
import { createServer } from 'node:http'

/** El sobre común de la API (`ApiEnvelope` en `contract/openapi.yaml`). */
const sobre = (data, extra = {}) => ({ success: true, message: null, data, errors: null, ...extra })

/**
 * `GET /identidad/footer`. Sin redes: el pie usa entonces su respaldo, que es lo
 * mismo que hace cuando la API no responde. La puerta no comprueba las redes de
 * la Entidad sino que el pie exista y cumpla (CAG-05, CAG-12).
 */
const pie = () => sobre({ redes: [] })

/**
 * `GET /tramites`. Catálogo vacío pero con la forma paginada del contrato
 * (`PageMeta`): es el estado en que el entorno de pruebas se ha medido, y el que
 * ejercita el estado «sin resultados» sin inventar un solo trámite.
 */
const tramites = (consulta) => {
  const porPagina = Number.parseInt(consulta.get('per_page') ?? '', 10)
  return sobre([], {
    meta: {
      current_page: 1,
      from: null,
      last_page: 1,
      path: '/api/v1/tramites',
      per_page: Number.isInteger(porPagina) && porPagina > 0 ? porPagina : 12,
      to: null,
      total: 0,
    },
    links: { first: null, last: null, prev: null, next: null },
  })
}

/**
 * `GET /politicas/uso-de-cookies`. Una política válida para el contrato (slug,
 * nombre y versión entera ≥ 1, que es lo que exige `politicaDesdeApi`) con un
 * texto que declara ser de prueba.
 */
const politicaDeCookies = () =>
  sobre({
    id: 0,
    type: 'politica',
    slug: 'uso-de-cookies',
    nombre: 'Uso de cookies',
    version: 1,
    vigente_desde: null,
    acto: { tipo: null, numero: null, fecha: null },
    es_ejemplo: true,
    documento_oficial: null,
    actualizada_en: null,
    secciones: [
      {
        id: 'prueba',
        titulo: 'Texto de prueba',
        parrafos: ['Texto de la puerta de diseño. No es contenido de la Entidad.'],
      },
    ],
  })

/**
 * Una respuesta con estado y cabeceras propios. Los manejadores devuelven el
 * cuerpo a secas (200) o, para simular un fallo, una `Respuesta`.
 */
class Respuesta {
  constructor(estado, cuerpo, cabeceras = {}) {
    this.estado = estado
    this.cuerpo = cuerpo
    this.cabeceras = cabeceras
  }
}

/**
 * Contenido de prueba de la búsqueda (T-22). **No es contenido de la Entidad**: 23
 * trámites y 2 políticas que se llaman «de prueba» y lo dicen en el extracto. Dan
 * lo que las pruebas de navegador necesitan —varias páginas, dos tipos, una fecha—
 * sin inventar un solo trámite real. Cualquier término que no sea «prueba» (o una
 * parte de sus títulos) no encuentra nada, que es el estado «sin resultados».
 */
const EXTRACTO_DE_PRUEBA = 'Texto de la puerta de pruebas. No es contenido de la Entidad.'
const CONTENIDO_DE_PRUEBA = [
  ...Array.from({ length: 23 }, (_sinUso, i) => ({
    id: i + 1,
    titulo: `Trámite de prueba ${String(i + 1).padStart(2, '0')}`,
    categoria: 'Trámite',
    tipo_recurso: 'tramite',
    url: `/tramites/tramite-de-prueba-${String(i + 1).padStart(2, '0')}`,
    fecha: '2026-03-14',
  })),
  ...['a', 'b'].map((letra, i) => ({
    id: 100 + i,
    titulo: `Política de prueba ${letra.toUpperCase()}`,
    categoria: 'Política',
    tipo_recurso: 'politica',
    url: `/politicas/politica-de-prueba-${letra}`,
    fecha: '2026-01-01',
  })),
]

const sinTildes = (texto) =>
  texto
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')

/**
 * Términos que provocan un fallo, para ejercitar los estados de error:
 *   `error-servidor`     → 500
 *   `limite-peticiones`  → 429 con `Retry-After: 7`
 */
function fallosDeBusqueda(q) {
  if (q === 'error-servidor') {
    return new Respuesta(500, { success: false, message: 'Error simulado.', data: null, errors: null })
  }
  if (q === 'limite-peticiones') {
    return new Respuesta(
      429,
      { success: false, message: 'Demasiadas solicitudes.', data: null, errors: null },
      { 'retry-after': '7' },
    )
  }
  return null
}

const coincidencias = (q, tipo) =>
  CONTENIDO_DE_PRUEBA.filter(
    (c) => (tipo === null || c.tipo_recurso === tipo) && sinTildes(c.titulo).includes(sinTildes(q)),
  )

/** `GET /buscar`: pertinencia fija (el orden de la lista), paginado y filtrable por tipo. */
const resultadosDeBusqueda = (consulta) => {
  const q = (consulta.get('q') ?? '').trim()
  const fallo = fallosDeBusqueda(q)
  if (fallo) return fallo
  if (q.length < 3 || q.length > 120) {
    return new Respuesta(422, {
      success: false,
      message: 'Datos no válidos.',
      data: null,
      errors: { q: [q.length < 3 ? 'Mínimo 3 caracteres.' : 'Máximo 120 caracteres.'] },
    })
  }

  const tipo = consulta.get('tipo')
  const porPagina = Math.max(1, Number.parseInt(consulta.get('per_page') ?? '', 10) || 10)
  const pagina = Math.max(1, Number.parseInt(consulta.get('page') ?? '', 10) || 1)
  const todas = coincidencias(q, tipo === 'tramite' || tipo === 'politica' ? tipo : null)
  const desde = (pagina - 1) * porPagina
  const pedidas = todas.slice(desde, desde + porPagina)
  const ultima = Math.max(1, Math.ceil(todas.length / porPagina))

  return sobre(
    pedidas.map((c, i) => ({
      id: c.id,
      type: 'resultados-busqueda',
      titulo: c.titulo,
      categoria: c.categoria,
      tipo_recurso: c.tipo_recurso,
      extracto: EXTRACTO_DE_PRUEBA,
      fecha: c.fecha,
      autor: null,
      miniatura: null,
      url: c.url,
      score: Number((1 - (desde + i) / 100).toFixed(2)),
    })),
    {
      meta: {
        current_page: pagina,
        from: pedidas.length > 0 ? desde + 1 : null,
        last_page: ultima,
        path: '/api/v1/buscar',
        per_page: porPagina,
        to: pedidas.length > 0 ? desde + pedidas.length : null,
        total: todas.length,
      },
      links: { first: null, last: null, prev: null, next: null },
    },
  )
}

/** `GET /busqueda/sugerencias`: las primeras coincidencias, hasta `limite` (6 por defecto). */
const sugerenciasDeBusqueda = (consulta) => {
  const q = (consulta.get('q') ?? '').trim()
  const fallo = fallosDeBusqueda(q)
  if (fallo) return fallo
  if (q.length < 3 || q.length > 120) {
    return new Respuesta(422, { success: false, message: 'Datos no válidos.', data: null, errors: { q: ['No válido.'] } })
  }
  const limite = Math.min(10, Math.max(1, Number.parseInt(consulta.get('limite') ?? '', 10) || 6))
  return sobre(
    coincidencias(q, null)
      .slice(0, limite)
      .map((c, i) => ({
        id: c.id,
        type: 'sugerencias-busqueda',
        titulo: c.titulo,
        categoria: c.categoria,
        tipo_recurso: c.tipo_recurso,
        url: c.url,
        score: Number((1 - i / 100).toFixed(2)),
      })),
  )
}

/** Ruta → manejador. La clave es la ruta exacta, sin la consulta. */
const RUTAS = new Map([
  ['/api/v1/identidad/footer', pie],
  ['/api/v1/buscar', resultadosDeBusqueda],
  ['/api/v1/busqueda/sugerencias', sugerenciasDeBusqueda],
  ['/api/v1/tramites', tramites],
  ['/api/v1/politicas/uso-de-cookies', politicaDeCookies],
])

/**
 * Arranca el simulador en un puerto libre de 127.0.0.1.
 *
 * @returns {Promise<{
 *   url: string,
 *   cerrar: () => Promise<void>,
 *   rutasDesconocidas: () => string[],
 *   rutasAtendidas: () => string[],
 * }>} `url` es la base que va en `NUXT_API_INTERNA`.
 */
export async function iniciarApiSimulada() {
  /** @type {Set<string>} */
  const desconocidas = new Set()
  /** @type {Set<string>} */
  const atendidas = new Set()

  const servidor = createServer((peticion, respuesta) => {
    const destino = new URL(peticion.url ?? '/', 'http://simulada.local')
    const clave = `${peticion.method} ${destino.pathname}`
    const cabeceras = { 'content-type': 'application/json; charset=utf-8', 'cache-control': 'no-store' }

    if (peticion.method !== 'GET' && peticion.method !== 'HEAD') {
      desconocidas.add(clave)
      respuesta.writeHead(405, { ...cabeceras, allow: 'GET, HEAD' })
      respuesta.end(JSON.stringify({ success: false, message: 'Método no permitido.', data: null, errors: null }))
      return
    }

    const manejador = RUTAS.get(destino.pathname)
    if (manejador === undefined) {
      desconocidas.add(clave)
      respuesta.writeHead(404, cabeceras)
      respuesta.end(JSON.stringify({ success: false, message: 'Recurso no encontrado.', data: null, errors: null }))
      return
    }

    atendidas.add(clave)
    const resultado = manejador(destino.searchParams)
    const { estado, cuerpo, extra } =
      resultado instanceof Respuesta
        ? { estado: resultado.estado, cuerpo: resultado.cuerpo, extra: resultado.cabeceras }
        : { estado: 200, cuerpo: resultado, extra: {} }
    respuesta.writeHead(estado, { ...cabeceras, ...extra })
    respuesta.end(peticion.method === 'HEAD' ? undefined : JSON.stringify(cuerpo))
  })

  await new Promise((resolver, rechazar) => {
    servidor.once('error', rechazar)
    // Puerto 0: el sistema asigna uno libre, así que dos ejecuciones a la vez (los
    // cuatro motores del CI comparten máquina en local) no se pisan.
    servidor.listen(0, '127.0.0.1', resolver)
  })

  const { port } = /** @type {import('node:net').AddressInfo} */ (servidor.address())

  return {
    url: `http://127.0.0.1:${port}`,
    rutasDesconocidas: () => [...desconocidas].sort(),
    rutasAtendidas: () => [...atendidas].sort(),
    cerrar: () =>
      new Promise((resolver) => {
        // Las conexiones abiertas del servidor del sitio (keep-alive) no deben
        // retener el cierre.
        servidor.closeAllConnections?.()
        servidor.close(() => resolver(undefined))
      }),
  }
}
