/**
 * Puerta de cookies y consentimiento (T-26: AC-26.2, 26.3, 26.4, 26.6).
 *
 * Abre el sitio con un navegador limpio y comprueba, con un Chromium de verdad,
 * lo que un banner de cookies suele prometer y no cumplir:
 *
 *  1. Sin tocar el banner, **no hay cookies no esenciales ni peticiones a
 *     dominios de analítica** (AC-26.2).
 *  2. Rechazar deja el sitio sin analítica y envía el registro `rechazado`.
 *  3. Con analítica configurada (`ESPERAR_ANALITICA=1`): aceptar la carga; quitar
 *     la categoría y guardar la detiene y envía `revocado` (AC-26.3, 26.4).
 *  4. Un consentimiento caducado no carga la analítica y deja el registro
 *     `caducado` una sola vez (AC-26.6).
 *
 * Uso:
 *   BASE_URL=https://staging.santamarta.gov.co node tests/cookies.mjs
 *   BASE_URL=http://localhost:3000 SIMULAR_API=1 ESPERAR_ANALITICA=1 node tests/cookies.mjs
 *
 * `SIMULAR_API=1` contesta `POST /consentimientos` desde el propio script (para
 * un servidor de desarrollo sin backend). `ESPERAR_ANALITICA=1` sólo tiene
 * sentido si el sitio se levantó con `SEDE_ANALITICA_TOKEN`; en ese caso la
 * petición al script de Cloudflare se intercepta y no sale a internet.
 *
 * El punto 1 falla mientras Cloudflare inyecte la analítica por su cuenta
 * (AC-26.1): es un ajuste del panel de Cloudflare, no del repositorio.
 */

// `PLAYWRIGHT_MODULE=playwright` usa otra instalación de Playwright (por ejemplo la
// global, cuyos navegadores sí existen en la máquina); por defecto, la del proyecto.
import { lanzar } from './ayudas/motor.mjs'

const BASE = (process.env.BASE_URL ?? 'http://localhost:3000').replace(/\/+$/, '')
const SIMULAR_API = process.env.SIMULAR_API === '1'
const ESPERAR_ANALITICA = process.env.ESPERAR_ANALITICA === '1'

/** Cookies que Cloudflare pone por seguridad (gestión de bots): esenciales, no de analítica. */
const ESENCIALES = new Set(['__cf_bm', 'cf_clearance', '__cfruid'])
const DOMINIOS_ANALITICA = /cloudflareinsights\.com|google-analytics\.com|googletagmanager\.com|matomo|hotjar\.com|clarity\.ms|facebook\.net|segment\.(io|com)|mixpanel\.com/i
const BEACON = /static\.cloudflareinsights\.com\/beacon\.min\.js/i

let fallos = 0
const comprobar = (condicion, mensaje) => {
  console.log(`${condicion ? '  ✓' : '  ✗'} ${mensaje}`)
  if (!condicion) fallos += 1
}

const navegador = await lanzar()

/** Un contexto limpio que anota peticiones y registros enviados al servidor. */
async function contextoNuevo({ almacenamiento } = {}) {
  const contexto = await navegador.newContext({ viewport: { width: 1280, height: 900 } })
  if (almacenamiento) {
    await contexto.addInitScript((valor) => {
      if (!sessionStorage.getItem('__sembrado')) {
        localStorage.setItem('sede-consentimiento', valor)
        sessionStorage.setItem('__sembrado', '1')
      }
    }, JSON.stringify(almacenamiento))
  }
  const estado = { analitica: [], registros: [] }
  await contexto.route('**/*', async (ruta) => {
    const peticion = ruta.request()
    const url = peticion.url()
    if (BEACON.test(url)) {
      estado.analitica.push(url)
      return ruta.fulfill({ status: 200, contentType: 'application/javascript', body: '/* beacon simulado */' })
    }
    if (DOMINIOS_ANALITICA.test(url)) {
      estado.analitica.push(url)
      return ruta.abort()
    }
    if (/\/api\/v1\/consentimientos$/.test(url) && peticion.method() === 'POST') {
      try {
        estado.registros.push(JSON.parse(peticion.postData() ?? '{}'))
      } catch {
        estado.registros.push({ ilegible: true })
      }
      if (SIMULAR_API) {
        return ruta.fulfill({ status: 201, contentType: 'application/json', body: '{"success":true,"data":{}}' })
      }
    }
    return ruta.continue()
  })
  return { contexto, estado }
}

const esperar = (ms) => new Promise((r) => setTimeout(r, ms))

try {
  console.log(`\n1. Sin tocar el banner (${BASE})`)
  {
    const { contexto, estado } = await contextoNuevo()
    const pagina = await contexto.newPage()
    await pagina.goto(BASE + '/', { waitUntil: 'networkidle' })
    await esperar(1500)
    const cookies = (await contexto.cookies()).filter((c) => !ESENCIALES.has(c.name))
    comprobar(cookies.length === 0, `sin cookies no esenciales (${cookies.map((c) => c.name).join(', ') || 'ninguna'})`)
    comprobar(estado.analitica.length === 0, `0 peticiones a dominios de analítica (${estado.analitica.length})`)
    comprobar(await pagina.getByRole('button', { name: /Rechazar opcionales/i }).isVisible(), 'el banner está visible')
    await contexto.close()
  }

  console.log('\n2. Rechazar opcionales')
  {
    const { contexto, estado } = await contextoNuevo()
    const pagina = await contexto.newPage()
    await pagina.goto(BASE + '/', { waitUntil: 'networkidle' })
    await pagina.getByRole('button', { name: /Rechazar opcionales/i }).click()
    await esperar(1000)
    comprobar(estado.analitica.length === 0, 'sigue sin haber peticiones de analítica')
    comprobar(
      estado.registros.length === 1 && estado.registros[0].evento === 'rechazado' && estado.registros[0].categorias?.length === 0,
      `se envió el registro «rechazado» (${JSON.stringify(estado.registros)})`,
    )
    comprobar(Object.keys(estado.registros[0] ?? {}).sort().join() === 'categorias,evento,version_politica', 'el registro no lleva ningún identificador')
    await pagina.reload({ waitUntil: 'networkidle' })
    comprobar(!(await pagina.getByRole('button', { name: /Rechazar opcionales/i }).isVisible()), 'tras recargar, el banner no reaparece')
    await contexto.close()
  }

  if (ESPERAR_ANALITICA) {
    console.log('\n3. Aceptar carga la analítica; revocar la detiene')
    const { contexto, estado } = await contextoNuevo()
    const pagina = await contexto.newPage()
    await pagina.goto(BASE + '/', { waitUntil: 'networkidle' })
    comprobar(estado.analitica.length === 0, 'antes de decidir no se pidió el script')
    await pagina.getByRole('button', { name: /Aceptar todas/i }).click()
    await esperar(1500)
    comprobar(estado.analitica.length === 1, `tras aceptar se pidió el script una vez (${estado.analitica.length})`)
    comprobar((await pagina.locator('#cf-beacon-analitica').count()) === 1, 'el <script> está en el documento')
    comprobar(estado.registros.at(-1)?.evento === 'aceptado' && estado.registros.at(-1)?.categorias?.includes('analitica'), 'se registró «aceptado» con «analitica»')

    // Revocar desde «Configurar cookies»: desmarcar analítica y guardar.
    await pagina.getByRole('button', { name: /Configurar cookies/i }).click()
    await pagina.getByLabel(/anal[ií]tica/i).uncheck()
    const antes = estado.analitica.length
    await Promise.all([
      pagina.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => null),
      pagina.getByRole('button', { name: /Guardar mi elecci[oó]n/i }).click(),
    ])
    await esperar(1500)
    comprobar(estado.registros.some((r) => r.evento === 'revocado'), 'se registró «revocado»')
    comprobar((await pagina.locator('#cf-beacon-analitica').count()) === 0, 'tras revocar, el <script> ya no está')
    comprobar(estado.analitica.length === antes, 'tras revocar no hay nuevas peticiones de analítica')
    await contexto.close()
  } else {
    console.log('\n3. (omitido: sin ESPERAR_ANALITICA=1 no hay analítica configurada que probar)')
  }

  console.log('\n4. Consentimiento caducado')
  {
    const caducado = { fecha: new Date(Date.now() - 400 * 86_400_000).toISOString(), version: 1, aceptadas: ['analitica'] }
    const { contexto, estado } = await contextoNuevo({ almacenamiento: caducado })
    const pagina = await contexto.newPage()
    await pagina.goto(BASE + '/', { waitUntil: 'networkidle' })
    await esperar(1500)
    comprobar(estado.analitica.length === 0, 'no se carga la analítica con el consentimiento caducado')
    comprobar(await pagina.getByRole('button', { name: /Rechazar opcionales/i }).isVisible(), 'el banner vuelve a pedirse')
    const caducados = estado.registros.filter((r) => r.evento === 'caducado')
    comprobar(caducados.length === 1, `quedó un registro «caducado» (${caducados.length})`)
    await pagina.goto(BASE + '/tramites', { waitUntil: 'networkidle' })
    await esperar(500)
    comprobar(estado.registros.filter((r) => r.evento === 'caducado').length === 1, 'al navegar a otra página no se vuelve a registrar')
    await contexto.close()
  }
} finally {
  await navegador.close()
}

console.log(fallos === 0 ? '\nCookies: todo en orden.' : `\nCookies: ${fallos} comprobación(es) fallida(s).`)
process.exit(fallos === 0 ? 0 : 1)
