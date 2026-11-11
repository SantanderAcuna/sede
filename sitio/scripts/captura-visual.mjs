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
 *     node scripts/captura-visual.mjs --contra=origin/develop   # RECOMENDADO
 *     node scripts/captura-visual.mjs --guardar                 # fija la referencia (PNG)
 *     node scripts/captura-visual.mjs                           # compara contra ella
 *
 * Sale con código 1 si alguna captura cambió, para que sirva de puerta (2 si no había
 * nada que comparar).
 *
 * **`--contra=<rama>` compara dos renders de la MISMA máquina**: compila esa rama en un
 * árbol de trabajo temporal, la sirve en otro puerto, captura las mismas vistas y las
 * compara con el build actual (el que ya sirves en `BASE`). Es el modo fiable, porque el
 * hash de una captura es determinista dentro de una máquina y NO entre máquinas: medido el
 * 2026-11-10, la referencia PNG fijada el 2026-10-02 no se reproduce en otra máquina ni
 * compilando el código de aquel mismo día (ver `tests/captura-ab.test.ts`). El modo de
 * los PNG guardados sólo sirve en la máquina que los fijó.
 *
 * Las capturas viven en `tests/visual/referencia/`. Se versionan a propósito:
 * una referencia que sólo existe en la máquina de quien la creó no protege a
 * nadie más.
 */
import { createRequire } from 'node:module'
import { mkdir, readFile, writeFile } from 'node:fs/promises'
import { existsSync } from 'node:fs'
import path from 'node:path'
import { execFileSync, spawn } from 'node:child_process'
import { mkdtemp, rm, symlink } from 'node:fs/promises'
import { tmpdir } from 'node:os'

import { compararJuegos, huella as huellaDe, resumirComparacion } from './lib/captura-ab.mjs'

const requerir = createRequire('/home/sacunapolo/Documentos/sede/panel/')
const { chromium } = requerir('playwright')

const BASE = process.env.BASE ?? 'http://127.0.0.1:4300'
const GUARDAR = process.argv.includes('--guardar')
const CONTRA = process.argv.find((a) => a.startsWith('--contra='))?.slice('--contra='.length)
/** Dónde se sirve el build de la rama base cuando se usa `--contra`. */
const BASE_RAMA = process.env.BASE_RAMA ?? 'http://127.0.0.1:4301'
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
      // Elegir en el banner dispara un aviso emergente de confirmación (T-28,
      // `useToast`) arriba a la derecha. Es un estado pasajero que falsearía la
      // comparación igual que el propio banner, así que se cierra.
      await pagina
        .getByRole('button', { name: 'Cerrar notificación' })
        .first()
        .click({ timeout: 1500 })
        .catch(() => {})
      await esperar(200)
      return
    }
  }
}

async function capturar(navegador, vista, origen = BASE) {
  const pagina = await navegador.newPage({
    viewport: { width: vista.ancho, height: vista.alto },
    // Escala 1 y sin animaciones: una captura con transiciones a medias cambia
    // entre ejecuciones y produce falsos positivos en cada comparación.
    deviceScaleFactor: 1,
    reducedMotion: 'reduce',
  })

  await pagina.goto(origen + vista.ruta, { waitUntil: 'networkidle' })
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
     * El disparador se busca **por su clase** (el que esté visible: hay dos ejemplares y CSS muestra uno según el ancho), no por su etiqueta.
     *
     * La versión anterior buscaba un botón que dijera «accesibilidad» y
     * encontraba antes «×Cerrar los ajustes de accesibilidad» —el botón de
     * cerrar del propio panel—, así que **cerraba el panel en vez de abrirlo**.
     * Es el riesgo de seleccionar por texto: siempre hay alguien más que dice lo
     * mismo con el sentido contrario.
     */
    await pagina.locator('.boton-accesibilidad:visible').first().click().catch(() => {})
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

const huella = huellaDe

/** Espera a que un servidor responda; falla con un mensaje claro si no lo hace. */
async function esperarServidor(origen, intentos = 40) {
  for (let i = 0; i < intentos; i++) {
    try {
      const r = await fetch(origen + '/', { signal: AbortSignal.timeout(3000) })
      if (r.status < 500) return
    } catch {
      /* todavía no */
    }
    await esperar(500)
  }
  throw new Error(`No responde ${origen}. ¿Está servido el build? (PORT=… node .output/server/index.mjs)`)
}

async function capturarTodas(navegador, origen) {
  const juego = new Map()
  for (const vista of VISTAS) juego.set(vista.nombre, await capturar(navegador, vista, origen))
  return juego
}

/**
 * `--contra=<rama>`: compila la rama en un árbol temporal, la captura y la compara con el
 * build actual. Todo ocurre en esta máquina, que es lo que hace comparable el hash.
 */
async function contraLaRama(ref) {
  const raiz = execFileSync('git', ['rev-parse', '--show-toplevel'], { encoding: 'utf8' }).trim()
  const arbol = await mkdtemp(path.join(tmpdir(), 'captura-base-'))
  const sitioBase = path.join(arbol, 'sitio')
  let servidor = null
  let navegador = null

  try {
    console.log(`  Rama base: ${ref} (árbol temporal ${arbol})\n`)
    execFileSync('git', ['worktree', 'add', '--detach', '-q', arbol, ref], { cwd: raiz, stdio: 'inherit' })
    await symlink(path.resolve('node_modules'), path.join(sitioBase, 'node_modules'))

    console.log('  Compilando la rama base…')
    execFileSync('npx', ['nuxt', 'build'], { cwd: sitioBase, stdio: 'ignore' })

    const puerto = new URL(BASE_RAMA).port
    servidor = spawn('node', ['.output/server/index.mjs'], {
      cwd: sitioBase,
      env: { ...process.env, PORT: puerto },
      stdio: 'ignore',
    })
    await esperarServidor(BASE_RAMA)
    await esperarServidor(BASE)

    navegador = await chromium.launch()
    console.log('  Capturando la rama base y el build actual…\n')
    const base = await capturarTodas(navegador, BASE_RAMA)
    const actual = await capturarTodas(navegador, BASE)

    const vistas = compararJuegos(base, actual)
    for (const v of vistas) {
      const alturas = `${v.alturaBase ?? '—'} → ${v.alturaActual ?? '—'} px`
      console.log(`  ${v.estado === 'igual' ? 'IGUAL    ' : 'CAMBIADA '} ${v.nombre.padEnd(28)} ${alturas}`)
    }

    const { iguales, distintas, codigoDeSalida } = resumirComparacion(vistas)
    if (codigoDeSalida === 2) console.log('\n  No se capturó ninguna vista: no hay nada que comparar.')
    else if (distintas > 0) console.log(`\n  ${distintas} de ${vistas.length} vista(s) difieren de ${ref}. ¿Es lo que buscabas?`)
    else console.log(`\n  Las ${iguales} vistas son idénticas a ${ref}.`)
    return codigoDeSalida
  } finally {
    if (navegador) await navegador.close().catch(() => {})
    if (servidor) servidor.kill()
    try {
      execFileSync('git', ['worktree', 'remove', '--force', arbol], { cwd: raiz, stdio: 'ignore' })
    } catch {
      /* si git ya no lo conoce, basta con borrar la carpeta */
    }
    await rm(arbol, { recursive: true, force: true }).catch(() => {})
  }
}

async function main() {
  if (CONTRA) return contraLaRama(CONTRA)

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
