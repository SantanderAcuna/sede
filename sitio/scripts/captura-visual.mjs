/**
 * Captura visual comparativa de la Sede.
 *
 * **Por qué existe.** Antes de reducir los 136 hallazgos que Stylelint señala en
 * `sitio.css` hay que poder demostrar que el aspecto no cambia. Sin eso, tocar
 * 95 declaraciones en un archivo de 1424 líneas con 18 comprobaciones de
 * accesibilidad en verde es un salto al vacío: `axe` verifica contraste, foco y
 * estructura, pero **no verifica que el diseño siga viéndose igual**. Un párrafo
 * que se descoloca o una altura que cambia no violan ninguna norma.
 *
 * Uso
 * ---
 *     node scripts/captura-visual.mjs --guardar     # fija la referencia
 *     node scripts/captura-visual.mjs               # compara contra ella
 *
 * Sale con código 1 si alguna captura cambió, para que sirva de puerta.
 *
 * Las capturas viven en `tests/visual/referencia/`. Se versionan a propósito:
 * una referencia que sólo existe en la máquina de quien la creó no protege a
 * nadie más.
 */
import { createRequire } from 'node:module'
import { mkdir, readFile, writeFile } from 'node:fs/promises'
import { existsSync } from 'node:fs'
import { createHash } from 'node:crypto'
import path from 'node:path'

const requerir = createRequire('/home/sacunapolo/Documentos/sede/panel/')
const { chromium } = requerir('playwright')

const BASE = process.env.BASE ?? 'http://127.0.0.1:4300'
const GUARDAR = process.argv.includes('--guardar')
const RAIZ = path.resolve('tests/visual/referencia')

/**
 * Las vistas que se capturan.
 *
 * No se capturan todas las páginas: se capturan **las que ejercitan cada capa
 * del CSS**. La portada usa el carrusel y las tarjetas; Trámites, la galería con
 * paginación; PQRSD, el formulario; Normativa, la tabla; y el aviso de cookies
 * y el menú móvil son estados, no páginas.
 */
const VISTAS = [
  { nombre: 'portada-escritorio', ruta: '/', ancho: 1300, alto: 900 },
  { nombre: 'portada-movil', ruta: '/', ancho: 390, alto: 844 },
  { nombre: 'tramites-escritorio', ruta: '/tramites', ancho: 1300, alto: 900 },
  { nombre: 'tramites-movil', ruta: '/tramites', ancho: 390, alto: 844 },
  { nombre: 'pqrsd-escritorio', ruta: '/pqrsd', ancho: 1300, alto: 900 },
  { nombre: 'normativa-escritorio', ruta: '/normativa', ancho: 1300, alto: 900 },
  { nombre: 'atencion-escritorio', ruta: '/atencion', ancho: 1300, alto: 900 },
  { nombre: 'accesibilidad-escritorio', ruta: '/accesibilidad', ancho: 1300, alto: 900 },
  { nombre: 'menu-movil-abierto', ruta: '/', ancho: 390, alto: 844, menu: true },
  { nombre: 'alto-contraste', ruta: '/', ancho: 1300, alto: 900, contraste: true },
  { nombre: 'estrecho-320', ruta: '/', ancho: 320, alto: 800 },
]

const esperar = (ms) => new Promise((r) => setTimeout(r, ms))

/** Cierra el aviso de cookies: tapa la parte alta y falsearía la comparación. */
async function cerrarCookies(pagina) {
  for (const boton of await pagina.locator('button').all()) {
    const texto = ((await boton.textContent()) || '').toLowerCase()
    if (texto.includes('aceptar') || texto.includes('rechazar')) {
      await boton.click().catch(() => {})
      await esperar(300)
      return
    }
  }
}

async function capturar(navegador, vista) {
  const pagina = await navegador.newPage({
    viewport: { width: vista.ancho, height: vista.alto },
    // Escala 1 y sin animaciones: una captura con transiciones a medias cambia
    // entre ejecuciones y produce falsos positivos en cada comparación.
    deviceScaleFactor: 1,
    reducedMotion: 'reduce',
  })

  await pagina.goto(BASE + vista.ruta, { waitUntil: 'networkidle' })
  await cerrarCookies(pagina)

  if (vista.menu) {
    await pagina.locator('.navbar-toggler, .btn-menu-govco').first().click().catch(() => {})
    await esperar(500)
  }

  if (vista.contraste) {
    /*
     * **El modo de contraste se elige con radios dentro de un panel, no con un
     * botón.** La primera versión de esta captura buscaba un `button` con
     * «contraste» en su etiqueta, no lo encontraba, y guardaba la portada **sin
     * el modo aplicado**: dos vistas distintas quedaban con el mismo hash. Una
     * referencia donde dos entradas son idénticas no es una red de seguridad, es
     * una prueba rota que da falsa confianza.
     *
     * Así que se recorre el camino real —abrir el panel y marcar el radio—, que
     * además comprueba que ese camino funciona para quien navega.
     */
    /*
     * El disparador se busca **por su clase**, no por su etiqueta.
     *
     * La versión anterior buscaba un botón que dijera «accesibilidad» y
     * encontraba antes «×Cerrar los ajustes de accesibilidad» —el botón de
     * cerrar del propio panel—, así que **cerraba el panel en vez de abrirlo**.
     * Es el riesgo de seleccionar por texto: siempre hay alguien más que dice lo
     * mismo con el sentido contrario.
     */
    await pagina.locator('.boton-accesibilidad').first().click().catch(() => {})
    await esperar(600)

    const radio = pagina.locator('input[name="modo-contraste"][value="alto"]')
    if (await radio.count()) {
      await radio.first().check({ force: true }).catch(() => {})
      await esperar(500)
    }

    // Se cierra el panel: si queda abierto, tapa la página y la captura compara
    // el panel en vez del sitio.
    await pagina.keyboard.press('Escape').catch(() => {})
    await esperar(300)

    const aplicado = await pagina.evaluate(() =>
      getComputedStyle(document.body).backgroundColor + '|' + document.documentElement.className,
    )
    if (!/contraste/.test(aplicado)) {
      throw new Error(
        `El modo de alto contraste no se aplicó en ${vista.nombre}. ` +
          'La captura no sirve como referencia: revisa el selector del panel o del radio.',
      )
    }
  }

  await pagina.evaluate(() => window.scrollTo(0, 0))
  await esperar(200)

  // `fullPage` y no sólo el viewport: un cambio de altura se vería en cualquier
  // parte de la página, no sólo en la primera pantalla.
  const buffer = await pagina.screenshot({ fullPage: true })
  await pagina.close()
  return buffer
}

const huella = (buffer) => createHash('sha256').update(buffer).digest('hex').slice(0, 16)

async function main() {
  if (GUARDAR) await mkdir(RAIZ, { recursive: true })

  const navegador = await chromium.launch()
  let distintas = 0
  let faltantes = 0

  console.log(GUARDAR ? '  Fijando la referencia visual\n' : '  Comparando con la referencia visual\n')

  for (const vista of VISTAS) {
    const destino = path.join(RAIZ, `${vista.nombre}.png`)
    const buffer = await capturar(navegador, vista)
    const actual = huella(buffer)

    if (GUARDAR) {
      await writeFile(destino, buffer)
      console.log(`  GUARDADA  ${vista.nombre.padEnd(28)} ${actual}  ${(buffer.length / 1024).toFixed(0)} KB`)
      continue
    }

    if (!existsSync(destino)) {
      faltantes++
      console.log(`  FALTA     ${vista.nombre.padEnd(28)} no hay referencia`)
      continue
    }

    const referencia = huella(await readFile(destino))
    const igual = referencia === actual
    if (!igual) distintas++
    console.log(`  ${igual ? 'IGUAL    ' : 'CAMBIADA '} ${vista.nombre.padEnd(28)} ${referencia} → ${actual}`)
  }

  await navegador.close()

  if (GUARDAR) {
    console.log(`\n  ${VISTAS.length} referencias fijadas en tests/visual/referencia/`)
    return 0
  }

  if (faltantes > 0) {
    console.log(`\n  Sin referencia: ${faltantes}. Ejecuta con --guardar para fijarla.`)
    return 1
  }

  if (distintas > 0) {
    console.log(`\n  ${distintas} vista(s) cambiaron. Revisa si es lo que buscabas;`)
    console.log('  si lo es, vuelve a fijar la referencia con --guardar.')
    return 1
  }

  console.log('\n  Ninguna vista cambió.')
  return 0
}

process.exit(await main())
