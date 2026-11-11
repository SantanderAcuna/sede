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
