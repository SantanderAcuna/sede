/**
 * Espera a que la API tenga cupo antes de medir algo que depende de ella.
 *
 * `/api/v1/*` limita a 60 peticiones por minuto y por IP (`throttle:api`, con
 * `X-RateLimit-Remaining` en cada respuesta y `Retry-After` en el 429). El
 * servidor del sitio consulta la API con la IP del visitante (`conf.d/interno.conf`),
 * y cada página servida gasta 2–3 peticiones. Un script que recorre muchas rutas
 * seguidas agota el cupo por sí solo y la página de trámites sale con «No se pudo
 * cargar el catálogo» (medido en el despliegue 42966617316). El límite funciona
 * como se diseñó: es la prueba la que lo respeta.
 *
 * No toca la aplicación ni el límite. Sin cabecera de cupo (servidor de desarrollo
 * sin API) o ante un fallo de red, no espera: no es asunto de esta ayuda.
 *
 * @returns {Promise<{listo: boolean, consultas: number, restantes: number | null}>}
 */
export async function esperarCupoApi(
  contexto,
  base,
  {
    minimo = 10,
    pausaMs = 15000,
    intentos = 6,
    dormir = (ms) => new Promise((resolver) => setTimeout(resolver, ms)),
  } = {},
) {
  let consultas = 0
  let restantes = null
  while (consultas < intentos) {
    consultas += 1
    let respuesta
    try {
      respuesta = await contexto.request.get(`${base}/api/v1/tramites?per_page=1`, { failOnStatusCode: false })
    } catch {
      return { listo: true, consultas, restantes: null }
    }
    const cabeceras = respuesta.headers()
    if (respuesta.status() === 429) {
      restantes = null
      const segundos = Math.min(Number(cabeceras['retry-after']) || pausaMs / 1000, 65)
      if (consultas < intentos) await dormir(segundos * 1000)
      continue
    }
    const cupo = cabeceras['x-ratelimit-remaining']
    if (cupo === undefined) return { listo: true, consultas, restantes: null }
    restantes = Number(cupo)
    if (restantes >= minimo) return { listo: true, consultas, restantes }
    if (consultas < intentos) await dormir(pausaMs)
  }
  return { listo: false, consultas, restantes }
}

/**
 * Una línea que describe lo que responde la API ahora: código y cupo, o cuánto
 * esperar. Sirve para que un «No se pudo cargar el catálogo» diga POR QUÉ en lugar
 * de obligar a adivinar (cupo agotado, error del servidor o sin respuesta).
 */
export async function describirApi(contexto, base) {
  try {
    const respuesta = await contexto.request.get(`${base}/api/v1/tramites?per_page=1`, { failOnStatusCode: false })
    const cabeceras = respuesta.headers()
    const codigo = respuesta.status()
    if (codigo === 429) return `HTTP 429, reintentar en ${cabeceras['retry-after'] ?? '?'} s`
    const restante = cabeceras['x-ratelimit-remaining']
    return restante === undefined ? `HTTP ${codigo}` : `HTTP ${codigo}, cupo ${restante}/${cabeceras['x-ratelimit-limit'] ?? '?'}`
  } catch (error) {
    return `sin respuesta (${error.message})`
  }
}

/**
 * Adaptador: hace las consultas de `esperarCupoApi` y `describirApi` con el `fetch` de
 * una página ya abierta en el sitio, en lugar del cliente HTTP de Playwright.
 *
 * Medido en el despliegue 43068782240: el cliente HTTP de Playwright recibe de
 * Cloudflare un 403 SIN cabeceras de cupo (no es un navegador), y la espera lo
 * confundía con «no hay API» y seguía sin esperar. El `fetch` de una página del
 * sitio viaja con la identidad del navegador, igual que las peticiones del propio
 * sitio. Debe ser una página del mismo origen (cualquier ruta; `/robots.txt` basta).
 */
export function sondaDesdePagina(pagina) {
  return {
    request: {
      get: async (url) => {
        const r = await pagina.evaluate(async (u) => {
          const respuesta = await fetch(u, { cache: 'no-store' })
          return { status: respuesta.status, headers: Object.fromEntries(respuesta.headers) }
        }, url)
        return { status: () => r.status, headers: () => r.headers }
      },
    },
  }
}
