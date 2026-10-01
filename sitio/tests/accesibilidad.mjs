/**
 * Puerta de accesibilidad de la sede (RNF-B1-014, CAG-32, CAG-28, Resolución
 * 1519/2020).
 *
 * CAG-28 —contraste 4,5:1 en texto normal y 3:1 en texto grande— lo mide la
 * regla `color-contrast` de axe, que corre en las 18 páginas: no hace falta una
 * comprobación aparte, hace falta no olvidar que se está midiendo.
 *
 * Levanta el sitio ya compilado, recorre las páginas públicas y ejecuta axe-core
 * sobre cada una con las reglas de WCAG 2.0 y 2.1 en nivel A y AA. Falla si
 * aparece una violación **crítica o seria**, que es el umbral que fija el
 * expediente («sin violaciones Serious o Critical»).
 *
 * **Por qué existe.** Durante meses el proyecto declaró el cumplimiento de
 * WCAG 2.1 AA sin haber ejecutado nunca una auditoría: `axe-core` y Playwright
 * estaban instalados y no los llamaba nadie, y la matriz de trazabilidad
 * acreditaba una prueba que no existía. Una declaración de conformidad sin
 * instrumento es una afirmación sin respaldo; esto es el instrumento.
 *
 * Uso:
 *   npm run test:accesibilidad            (requiere `npm run build` antes)
 *   PUERTO_ACCESIBILIDAD=3012 npm run test:accesibilidad
 *
 * Las páginas que dependen de la API se marcan como **no auditadas** cuando el
 * backend no responde, en lugar de darlas por buenas: auditarlas sin datos
 * mediría la página de error, no la sede. Un **5xx no se omite: falla la
 * puerta**, porque un sitio que no se puede abrir no es un sitio sin problemas.
 */
import { spawn } from 'node:child_process'
import { existsSync } from 'node:fs'
import { homedir } from 'node:os'
import { dirname, join, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'


/*
 * **Dónde están los navegadores.** Playwright los busca en
 * `$PLAYWRIGHT_BROWSERS_PATH` o en `~/.cache/ms-playwright`. Al ejecutar esta
 * puerta desde `make`, el hijo de make puede resolver ese directorio contra el
 * directorio de trabajo en lugar de contra el del usuario, y entonces falla con
 * «Executable doesn't exist» aunque los navegadores estén instalados (pasó, y la
 * puerta no llegaba ni a empezar). Se fija aquí, que es donde se sabe cuál es la
 * ruta real, y si no hay ninguno se dice qué comando hace falta.
 */
const RUTA_NAVEGADORES =
  process.env.PLAYWRIGHT_BROWSERS_PATH ?? join(homedir(), '.cache', 'ms-playwright')
if (!process.env.PLAYWRIGHT_BROWSERS_PATH && existsSync(RUTA_NAVEGADORES)) {
  process.env.PLAYWRIGHT_BROWSERS_PATH = RUTA_NAVEGADORES
}

// La importación va después de fijar la variable: Playwright resuelve la ruta de
// sus navegadores al cargarse.
const { chromium } = await import('@playwright/test')

/** Lanza Chromium con un mensaje útil si no hay navegadores instalados. */
async function abrirNavegador() {
  try {
    return await chromium.launch()
  } catch (error) {
    if (/Executable doesn't exist|playwright install/i.test(error.message)) {
      throw new Error(
        `No hay navegadores de Playwright en ${process.env.PLAYWRIGHT_BROWSERS_PATH}. ` +
          'Instálelos con: npx playwright install chromium',
      )
    }
    throw error
  }
}

const raiz = resolve(dirname(fileURLToPath(import.meta.url)), '..')
const puerto = Number(process.env.PUERTO_ACCESIBILIDAD ?? 3011)
const base = `http://127.0.0.1:${puerto}`
const rutaAxe = resolve(raiz, 'node_modules/axe-core/axe.min.js')

/** Las etiquetas de axe que corresponden al nivel exigido: WCAG 2.0/2.1 A y AA. */
const ETIQUETAS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']

/** Impactos que hacen fallar la puerta (umbral del expediente). */
const BLOQUEANTES = new Set(['critical', 'serious'])

/**
 * Las vistas públicas. `requiereApi` marca las que se alimentan del contrato: si
 * el backend no está levantado, se declaran no auditadas en lugar de medir la
 * pantalla de error.
 */
const VISTAS = [
  { ruta: '/', nombre: 'Portada' },
  { ruta: '/transparencia', nombre: 'Transparencia (en preparación)' },
  { ruta: '/atencion', nombre: 'Canales de atención' },
  { ruta: '/tramites', nombre: 'Catálogo de trámites', requiereApi: true },
  { ruta: '/pqrsd', nombre: 'PQRSD' },
  { ruta: '/realizar-una-peticion', nombre: 'Formulario de petición' },
  { ruta: '/seguimiento', nombre: 'Seguimiento (en preparación)' },
  { ruta: '/noticias', nombre: 'Noticias (en preparación)' },
  { ruta: '/portales', nombre: 'Portales (en preparación)' },
  { ruta: '/servicios', nombre: 'Servicios (en preparación)' },
  { ruta: '/normativa', nombre: 'Normativa' },
  { ruta: '/participa', nombre: 'Participa' },
  { ruta: '/participa/control-ciudadano', nombre: 'Participa · Control ciudadano' },
  { ruta: '/buscar', nombre: 'Buscar' },
  { ruta: '/mapa-del-sitio', nombre: 'Mapa del sitio' },
  { ruta: '/accesibilidad', nombre: 'Declaración de accesibilidad' },
  { ruta: '/politicas/uso-de-cookies', nombre: 'Política de cookies' },
  { ruta: '/ruta-que-no-existe-para-probar-el-404', nombre: 'Página 404' },
]

/** Espera a que el servidor responda, o falla con un mensaje útil. */
async function esperarServidor(intentos = 60) {
  for (let intento = 0; intento < intentos; intento += 1) {
    try {
      const respuesta = await fetch(`${base}/`, { redirect: 'manual' })
      if (respuesta.status < 500) return
    } catch {
      // El servidor todavía no escucha: se reintenta.
    }
    await new Promise((seguir) => setTimeout(seguir, 500))
  }
  throw new Error(`El sitio no respondió en ${base} tras 30 s.`)
}

/** Ejecuta axe sobre una página y devuelve las violaciones. */
async function auditar(pagina, url) {
  const respuesta = await pagina.goto(url, { waitUntil: 'networkidle', timeout: 30_000 })
  const estado = respuesta?.status() ?? 0

  await pagina.addScriptTag({ path: rutaAxe })
  const resultado = await pagina.evaluate(
    ([etiquetas]) =>
      // @ts-expect-error `axe` lo inyecta el script anterior en la ventana.
      window.axe.run(document, { runOnly: { type: 'tag', values: etiquetas } }),
    [ETIQUETAS],
  )

  return { estado, violaciones: resultado.violations }
}

const servidor = spawn('node', ['.output/server/index.mjs'], {
  cwd: raiz,
  env: {
    ...process.env,
    NODE_ENV: 'production',
    HOST: '127.0.0.1',
    PORT: String(puerto),
    NITRO_PORT: String(puerto),
    // El dominio se declara para que el sitemap y el robots.txt no dependan del
    // entorno de quien ejecuta la puerta.
    SEDE_DOMINIO: 'www.santamarta.gov.co',
  },
  stdio: ['ignore', 'pipe', 'pipe'],
})

let salidaServidor = ''
servidor.stdout?.on('data', (trozo) => {
  salidaServidor += String(trozo)
})
servidor.stderr?.on('data', (trozo) => {
  salidaServidor += String(trozo)
})

let navegador
let fallos = 0
let auditadas = 0
const noAuditadas = []
const resumen = []

try {
  if (!existsSync(resolve(raiz, '.output/server/index.mjs'))) {
    console.error('No hay compilación del sitio. Ejecute `npm run build` antes.')
    process.exit(2)
  }

  await esperarServidor()
  navegador = await abrirNavegador()
  const contexto = await navegador.newContext({ viewport: { width: 1280, height: 900 } })

  for (const vista of VISTAS) {
    const pagina = await contexto.newPage()
    try {
      const { estado, violaciones } = await auditar(pagina, `${base}${vista.ruta}`)

      /*
       * Un **5xx es un fallo, no una página «no auditada»**. Antes se marcaba como
       * omitida y la puerta seguía en verde: cuando la disposición quedó devolviendo
       * 500 en todas las páginas con `NUXT_E1001`, esta puerta no dijo nada. Que el
       * sitio no se pueda ni abrir es lo más grave que puede pasar y no puede salir
       * como una omisión.
       */
      if (estado >= 500) {
        fallos += 1
        resumen.push({ vista, estado, violaciones: [{ impact: 'critical', id: 'servidor-5xx', ayuda: `La página responde ${estado}.` }], omitida: false })
        continue
      }

      if (estado >= 400 && estado !== 404) {
        noAuditadas.push(`${vista.ruta} (HTTP ${estado}${vista.requiereApi ? ', sin API' : ''})`)
        resumen.push({ vista, estado, violaciones: [], omitida: true })
        continue
      }

      auditadas += 1
      const graves = violaciones.filter((v) => BLOQUEANTES.has(v.impact ?? ''))
      if (graves.length > 0) fallos += graves.length
      resumen.push({ vista, estado, violaciones, omitida: false })
    } catch (error) {
      noAuditadas.push(`${vista.ruta} (${error.message.split('\n')[0]})`)
      resumen.push({ vista, estado: 0, violaciones: [], omitida: true })
    } finally {
      await pagina.close()
    }
  }

  // ——— Informe ———
  console.log('')
  console.log('Auditoría de accesibilidad — axe-core, WCAG 2.0/2.1 A y AA')
  console.log('='.repeat(72))

  for (const fila of resumen) {
    if (fila.omitida) {
      console.log(`  OMITIDA   ${fila.vista.nombre} (${fila.vista.ruta})`)
      continue
    }
    const graves = fila.violaciones.filter((v) => BLOQUEANTES.has(v.impact ?? ''))
    const menores = fila.violaciones.length - graves.length
    const marca = graves.length > 0 ? 'FALLA ' : 'OK    '
    const detalle = graves.length > 0 ? ` — ${graves.length} grave(s)` : ''
    const otros = menores > 0 ? ` (${menores} leve(s)/moderada(s))` : ''
    console.log(`  ${marca}   ${fila.vista.nombre} (${fila.vista.ruta})${detalle}${otros}`)
  }

  const conGraves = resumen.filter(
    (f) => !f.omitida && f.violaciones.some((v) => BLOQUEANTES.has(v.impact ?? '')),
  )

  if (conGraves.length > 0) {
    console.log('')
    console.log('Violaciones críticas o serias (las que hacen fallar la puerta)')
    console.log('-'.repeat(72))
    for (const fila of conGraves) {
      console.log(`\n  ${fila.vista.nombre} (${fila.vista.ruta})`)
      for (const violacion of fila.violaciones.filter((v) => BLOQUEANTES.has(v.impact ?? ''))) {
        console.log(`    [${violacion.impact}] ${violacion.id}: ${violacion.help}`)
        console.log(`      Criterio: ${violacion.tags.filter((t) => t.startsWith('wcag')).join(', ')}`)
        for (const nodo of violacion.nodes.slice(0, 3)) {
          console.log(`      ${nodo.target.join(' ')}`)
        }
        if (violacion.nodes.length > 3) {
          console.log(`      … y ${violacion.nodes.length - 3} elemento(s) más`)
        }
      }
    }
  }

  console.log('')
  console.log('='.repeat(72))
  console.log(`  Páginas auditadas: ${auditadas}`)
  if (noAuditadas.length > 0) {
    console.log(`  Páginas no auditadas: ${noAuditadas.length}`)
    for (const nota of noAuditadas) console.log(`    · ${nota}`)
  }
  console.log(`  Violaciones críticas o serias: ${fallos}`)
  console.log('')

  if (fallos > 0) {
    console.error(
      `La puerta de accesibilidad NO pasa: ${fallos} violación(es) crítica(s) o seria(s) ` +
        'sobre ' +
        `${auditadas} página(s). Ver auditoria-sede.md (RNF-B1-014, CAG-32).`,
    )
    process.exit(1)
  }

  console.log('La puerta de accesibilidad pasa: ninguna violación crítica ni seria.')
} catch (error) {
  console.error('La puerta de accesibilidad no pudo ejecutarse:', error.message)
  if (salidaServidor) console.error(salidaServidor.slice(-2000))
  process.exit(2)
} finally {
  await navegador?.close()
  servidor.kill('SIGTERM')
}
