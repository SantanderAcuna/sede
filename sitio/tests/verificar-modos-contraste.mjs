/**
 * Verificación de los modos de contraste y del reflujo con el texto ampliado.
 *
 * Comprueba con navegador de verdad lo que el plan marcó como riesgo principal y
 * lo que no se puede deducir leyendo el CSS:
 *
 *  1. **La trampa del `filter`.** Un `filter` convierte a su elemento en bloque
 *     contenedor de los descendientes con `position: fixed`. El filtro se aplica
 *     a `.contenido-filtrable`, así que lo flotante tiene que seguir anclado a la
 *     ventana en los cuatro modos: se mide desplazándose y viendo si el botón de
 *     volver arriba se mueve con el documento.
 *  2. **Que el círculo y el panel no queden dentro del subárbol filtrado**, para
 *     que no se inviertan sus colores.
 *  3. **Que el círculo sea un círculo**, esté centrado verticalmente y pegado al
 *     lado derecho, a cualquier ancho, con área activa de 44 px o más.
 *  4. **Reflujo**: sin desplazamiento horizontal real en los dos casos que fijan
 *     las normas —320 px de ancho con el texto normal (WCAG 1.4.10) y 1280 px con
 *     el texto al 200 % (WCAG 1.4.4)—.
 *
 * Sobre el punto 4, una advertencia que costó un rato aprender: `scrollWidth` no
 * sirve bajo `zoom`. Con `zoom: 2` a 320 px, Chromium informa `scrollWidth: 620`
 * y `clientWidth: 320`, y sin embargo `window.scrollTo(99999, 0)` deja `scrollX`
 * en 0: no hay nada que desplazar. Por eso se mide el desplazamiento real.
 *
 * Uso: node tests/verificar-modos-contraste.mjs [url]
 */
const { chromium } = await import('@playwright/test')

const base = process.argv[2] ?? 'http://127.0.0.1:3000'
const modos = ['normal', 'alto', 'inverso', 'grises']

/** Sembrar las preferencias y recargar es lo que hace el composable al volver. */
const preferencias = (extra = {}) => ({
  v: 2,
  contraste: 'normal',
  letra: 0,
  espaciado: false,
  dislexia: false,
  resaltarEnlaces: false,
  guiaLectura: false,
  detenerAnimaciones: false,
  ...extra,
})

const navegador = await chromium.launch()
let fallos = 0

function informar(etiqueta, ok, detalle) {
  if (!ok) fallos += 1
  console.log(`  ${ok ? 'OK    ' : 'FALLA '} ${etiqueta}${detalle ? ` — ${detalle}` : ''}`)
}

/** Abre una página con unas preferencias dadas, sin el banner de cookies. */
async function abrir(ancho, alto, prefs) {
  const pagina = await navegador.newPage({ viewport: { width: ancho, height: alto } })
  await pagina.goto(`${base}/`, { waitUntil: 'domcontentloaded' })
  await pagina.evaluate((p) => localStorage.setItem('sede-accesibilidad', JSON.stringify(p)), prefs)
  await pagina.reload({ waitUntil: 'domcontentloaded' })
  // El banner tapa la vista y no es objeto de esta verificación.
  await pagina.addStyleTag({ content: '.banner-cookies{display:none !important}' })
  await pagina.waitForTimeout(400)
  return pagina
}

try {
  // ── 1 y 2 · La trampa del filtro, en los cuatro modos ────────────────────
  console.log('\nTrampa del `filter` en los cuatro modos de contraste')
  for (const modo of modos) {
    const pagina = await abrir(1280, 900, preferencias({ contraste: modo }))

    const estado = await pagina.evaluate(() => {
      const filtrable = document.querySelector('.contenido-filtrable')
      const circulo = document.querySelector('.boton-accesibilidad')
      const panel = document.querySelector('.panel-accesibilidad')
      return {
        clases: [...document.documentElement.classList].filter((c) => c.includes('contraste')),
        filtroContenido: filtrable ? getComputedStyle(filtrable).filter : 'sin envoltorio',
        filtroCirculo: circulo ? getComputedStyle(circulo).filter : 'sin círculo',
        filtroPanel: panel ? getComputedStyle(panel).filter : 'sin panel',
        circuloDentroDelFiltro: filtrable?.contains(circulo) ?? null,
        panelDentroDelFiltro: filtrable?.contains(panel) ?? null,
      }
    })

    // Se desplaza hasta que el botón de volver arriba aparece, y se mide su
    // posición respecto a la ventana antes y después de seguir bajando.
    await pagina.evaluate(() => window.scrollTo(0, document.body.scrollHeight * 0.6))
    await pagina.waitForTimeout(500)
    const antes = await pagina.evaluate(() => {
      const caja = document.querySelector('.posicion-volver-arriba')
      return caja ? caja.getBoundingClientRect().top : null
    })
    await pagina.evaluate(() => window.scrollTo(0, document.body.scrollHeight * 0.9))
    await pagina.waitForTimeout(500)
    const despues = await pagina.evaluate(() => {
      const caja = document.querySelector('.posicion-volver-arriba')
      const circulo = document.querySelector('.boton-accesibilidad')
      return {
        arriba: caja ? caja.getBoundingClientRect().top : null,
        circuloTop: circulo ? circulo.getBoundingClientRect().top : null,
        viewportCentro: window.innerHeight / 2,
        desplazamiento: window.scrollY,
      }
    })

    console.log(`\n  modo «${modo}» · filter del contenido: ${estado.filtroContenido}`)
    informar(`el círculo no está dentro del subárbol filtrado`, estado.circuloDentroDelFiltro === false)
    informar(`el panel no está dentro del subárbol filtrado`, estado.panelDentroDelFiltro === false)
    informar(`el círculo no lleva filtro propio`, estado.filtroCirculo === 'none', `filter: ${estado.filtroCirculo}`)
    informar(`el panel no lleva filtro propio`, estado.filtroPanel === 'none', `filter: ${estado.filtroPanel}`)

    if (antes !== null && despues.arriba !== null) {
      const deriva = Math.abs(despues.arriba - antes)
      informar(
        `«Volver arriba» sigue anclado a la ventana`,
        deriva < 20,
        `deriva ${deriva.toFixed(0)} px tras ${despues.desplazamiento.toFixed(0)} px de scroll`,
      )
    }
    if (despues.circuloTop !== null) {
      const derivaCirculo = Math.abs(despues.circuloTop + 28 - despues.viewportCentro)
      informar(
        `el círculo sigue centrado aunque se desplace la página`,
        derivaCirculo < 40,
        `centro a ${(despues.circuloTop + 28).toFixed(0)} px de una ventana de ${(despues.viewportCentro * 2).toFixed(0)}`,
      )
    }

    await pagina.close()
  }

  // ── 3 · Forma, posición y tamaño del círculo a cualquier ancho ───────────
  console.log('\nForma, posición y tamaño del círculo')
  for (const [ancho, alto] of [[320, 720], [575, 800], [800, 900], [1280, 900], [1920, 1080]]) {
    const pagina = await abrir(ancho, alto, preferencias())
    const medida = await pagina.evaluate(() => {
      const circulo = document.querySelector('.boton-accesibilidad')
      if (!circulo) return null
      const caja = circulo.getBoundingClientRect()
      const estilo = getComputedStyle(circulo)
      return {
        posicion: estilo.position,
        redondeo: Number.parseFloat(estilo.borderTopLeftRadius),
        ancho: caja.width,
        alto: caja.height,
        centroY: caja.top + caja.height / 2,
        centroVentana: window.innerHeight / 2,
        separacionDerecha: window.innerWidth - caja.right,
      }
    })
    if (medida === null) {
      informar(`a ${ancho} px hay círculo`, false)
      await pagina.close()
      continue
    }
    const esCirculo = Math.abs(medida.ancho - medida.alto) <= 2 && medida.redondeo >= medida.ancho / 2 - 1
    const centrado = Math.abs(medida.centroY - medida.centroVentana) <= 4
    const aLaDerecha = medida.separacionDerecha >= 0 && medida.separacionDerecha < 40
    const tactil = medida.ancho >= 44 && medida.alto >= 44
    informar(
      `${ancho}×${alto} px`,
      medida.posicion === 'fixed' && esCirculo && centrado && aLaDerecha && tactil,
      `${medida.ancho.toFixed(0)}×${medida.alto.toFixed(0)} px · ${medida.posicion} · ` +
        `centrado: ${centrado} · a la derecha: ${medida.separacionDerecha.toFixed(0)} px · ` +
        `área activa ≥44: ${tactil}`,
    )
    await pagina.close()
  }

  // ── 4 · Reflujo sin desplazamiento horizontal real ───────────────────────
  console.log('\nReflujo: desplazamiento horizontal real')
  const casos = [
    { nombre: '320 px con el texto al 100 % (WCAG 1.4.10)', ancho: 320, alto: 720, prefs: preferencias() },
    { nombre: '1280 px con el texto al 200 % (WCAG 1.4.4)', ancho: 1280, alto: 900, prefs: preferencias({ letra: 4 }) },
  ]
  for (const caso of casos) {
    const pagina = await abrir(caso.ancho, caso.alto, caso.prefs)
    const r = await pagina.evaluate(() => {
      window.scrollTo(99999, 0)
      const desplazado = window.scrollX
      window.scrollTo(0, 0)
      return { desplazado, zoom: document.documentElement.style.zoom || '1' }
    })
    informar(caso.nombre, r.desplazado === 0, `zoom ${r.zoom} · scrollX ${r.desplazado}`)
    await pagina.close()
  }

  console.log(`\n${fallos === 0 ? 'Sin fallos.' : `${fallos} fallo(s).`}`)
  process.exitCode = fallos === 0 ? 0 : 1
} finally {
  await navegador.close()
}
