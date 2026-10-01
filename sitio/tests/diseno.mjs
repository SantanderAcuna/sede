/**
 * Puerta de conformidad de diseño (CAG-01 a CAG-34, Anexo 2 de la Resolución
 * 2893 de 2020).
 *
 * **Por qué existe.** La auditoría acreditaba 15 criterios de diseño como
 * cumplidos leyendo el código, y eso tiene un techo: hay criterios que sólo se
 * pueden comprobar con la página delante —si la barra de accesibilidad se oculta
 * de verdad por debajo de 992 px, si las tarjetas enteras son pulsables, si el
 * aviso de salida aparece y devuelve el foco—. Leer `v-if` en una plantilla no
 * demuestra que el navegador haga lo que la plantilla dice. Esto lo demuestra.
 *
 * **Alcance honesto.** Comprueba lo que se puede medir con un navegador y no
 * repite lo que ya mide `tests/accesibilidad.mjs` con axe (CAG-32). Los criterios
 * que ADR-0015 declara como desviación justificada no se comprueban aquí: se
 * listan al final para que el lector sepa que están decididos y dónde.
 *
 * Uso:
 * Comprueba además los metadatos de compartición y la URL canónica (D-29), que
 * son parte de cómo se publica la sede y no de un criterio CAG.
 *
 *   npm run test:diseno                  (requiere `npm run build` antes)
 *   PUERTO_DISENO=3013 npm run test:diseno
 */
import { spawn } from 'node:child_process'
import { createHash } from 'node:crypto'
import { existsSync, readFileSync } from 'node:fs'
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
const puerto = Number(process.env.PUERTO_DISENO ?? 3012)
const base = `http://127.0.0.1:${puerto}`

/** Los criterios que ADR-0015 declara como desviación justificada. */
const DESVIACIONES = [
  'CAG-06 (conmutador de idioma que no lleva a ninguna parte)',
  'CAG-18 (listas nativas con más de 12 elementos)',
  'CAG-19 (tablas de datos sin paginación propia)',
  'CAG-22 (fecha en el encabezado del trámite)',
  'CAG-25 (títulos de trámite con más de dos palabras)',
  'CAG-31 (filtros de búsqueda inexistentes)',
]

const resultados = []
let fallos = 0

/** Registra una comprobación. `fn` devuelve `true`/`false` o un detalle. */
async function comprobar(cag, descripcion, fn) {
  try {
    const salida = await fn()
    const ok = salida === true || (typeof salida === 'object' && salida.ok === true)
    const detalle = typeof salida === 'object' ? salida.detalle : ''
    resultados.push({ cag, descripcion, ok, detalle, error: false })
    if (!ok) fallos += 1
  } catch (error) {
    resultados.push({ cag, descripcion, ok: false, detalle: error.message.split('\n')[0], error: true })
    fallos += 1
  }
}

/** Espera a que el servidor responda, o falla con un mensaje útil. */
async function esperarServidor(intentos = 60) {
  let ultimoEstado = 0
  for (let intento = 0; intento < intentos; intento += 1) {
    try {
      const respuesta = await fetch(`${base}/`, { redirect: 'manual' })
      ultimoEstado = respuesta.status
      if (ultimoEstado < 500) return
    } catch {
      // Todavía no escucha: se reintenta.
    }
    await new Promise((seguir) => setTimeout(seguir, 500))
  }
  /*
   * El diagnóstico importa: «no respondió» y «responde 500» son averías
   * distintas y llevan a sitios distintos. Cuando la disposición devolvía 500 en
   * todas las páginas, este mensaje decía que el sitio no respondía, que es
   * justo lo contrario de lo que pasaba.
   */
  throw new Error(
    ultimoEstado >= 500
      ? `El sitio responde ${ultimoEstado} en ${base}/: no se puede comprobar el diseño de una página que no se abre.`
      : `El sitio no respondió en ${base} tras 30 s.`,
  )
}

const servidor = spawn('node', ['.output/server/index.mjs'], {
  cwd: raiz,
  env: {
    ...process.env,
    NODE_ENV: 'production',
    HOST: '127.0.0.1',
    PORT: String(puerto),
    NITRO_PORT: String(puerto),
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

try {
  if (!existsSync(resolve(raiz, '.output/server/index.mjs'))) {
    console.error('No hay compilación del sitio. Ejecute `npm run build` antes.')
    process.exit(2)
  }

  await esperarServidor()
  navegador = await abrirNavegador()
  const contexto = await navegador.newContext({ viewport: { width: 1280, height: 900 } })

  const portada = await contexto.newPage()
  await portada.goto(`${base}/`, { waitUntil: 'networkidle' })

  // ── Humo · El sitio no puede devolver errores de servidor ─────────────────
  await comprobar('5xx', 'Ninguna página del sitio responde con error de servidor', async () => {
    const rutas = [
      '/',
      '/accesibilidad',
      '/realizar-una-peticion',
      '/pqrsd',
      '/tramites',
      '/politicas/uso-de-cookies',
      '/mapa-del-sitio',
      '/ruta-que-no-existe-para-probar-el-404',
    ]
    const conError = []
    for (const ruta of rutas) {
      const respuesta = await portada.request.get(`${base}${ruta}`)
      if (respuesta.status() >= 500) conError.push(`${ruta} → ${respuesta.status()}`)
    }
    return {
      ok: conError.length === 0,
      detalle: conError.length > 0 ? `responden con error: ${conError.join(' · ')}` : `${rutas.length} rutas sin 5xx`,
    }
  })

  // ── D-37 · La tabla del Kit lleva la clase donde el Kit la espera ─────────
  await comprobar('D-37', 'La tabla del Kit lleva `tabla-govco` en el contenedor y sus reglas se aplican', async () => {
    await portada.goto(`${base}/pqrsd`, { waitUntil: 'networkidle' })
    const r = await portada.evaluate(() => {
      const contenedor = document.querySelector('.tabla-govco')
      if (!contenedor) return null
      const tabla = contenedor.querySelector('table')
      const thead = tabla?.querySelector('thead') ?? null
      return {
        enContenedor: contenedor.tagName,
        tablaSinClase: tabla ? !tabla.classList.contains('tabla-govco') : false,
        // `position: sticky` en el `thead` sólo llega por la regla de descendencia
        // `.tabla-govco:not(.responsive-tabla-govco) thead` de `all.css`. Es la
        // prueba de que el Kit está aplicando, y no una comprobación de clases.
        thead: thead ? getComputedStyle(thead).position : 'sin thead',
      }
    })
    if (!r) return { ok: false, detalle: 'no se encontró la tabla del Kit en /pqrsd' }
    return {
      ok: r.tablaSinClase && r.thead === 'sticky',
      detalle: `la clase está en <${r.enContenedor}> · la <table> no la lleva: ${r.tablaSinClase} · el thead calcula position: ${r.thead}`,
    }
  })

  // ── CAG-01 / CAG-02 / CAG-04 · Carrusel ────────────────────────────────────
  await comprobar('CAG-01/02/04', 'El carrusel de la portada tiene diapositivas, flechas, indicadores y pausa', async () => {
    // Cada comprobación abre la página que mide: darlo por hecho hacía que el
    // carrusel se midiera sobre la página que hubiera dejado la anterior.
    await portada.goto(`${base}/`, { waitUntil: 'networkidle' })
    const r = await portada.evaluate(() => {
      const diapositivas = document.querySelectorAll('.carousel-item')
      const anterior = document.querySelector('.carousel-control-prev')
      const siguiente = document.querySelector('.carousel-control-next')
      const indicadores = document.querySelectorAll('.carousel-indicators button')
      const imagenes = [...document.querySelectorAll('.carousel-item img')]
      const sinAlt = imagenes.filter((img) => !(img.getAttribute('alt') ?? '').trim())
      const botonPausa = [...document.querySelectorAll('.carrusel-govco button, .carousel button')].find((b) =>
        /reproduc|pausa/i.test(b.textContent ?? ''),
      )
      return {
        diapositivas: diapositivas.length,
        flechas: Boolean(anterior && siguiente),
        etiquetasFlechas: [anterior?.getAttribute('aria-label'), siguiente?.getAttribute('aria-label')],
        indicadores: indicadores.length,
        imagenes: imagenes.length,
        sinAlt: sinAlt.length,
        pausa: Boolean(botonPausa),
      }
    })
    const ok =
      r.diapositivas >= 2 &&
      r.flechas &&
      r.indicadores >= 2 &&
      r.pausa &&
      r.imagenes > 0 &&
      r.sinAlt === 0 &&
      r.etiquetasFlechas.every((e) => (e ?? '').trim().length > 0)
    return {
      ok,
      detalle: `${r.diapositivas} diapositivas · ${r.indicadores} indicadores · flechas con etiqueta: ${r.etiquetasFlechas.join(' / ')} · ${r.imagenes} imágenes (${r.sinAlt} sin alt) · botón de pausa: ${r.pausa ? 'sí' : 'no'}`,
    }
  })

  // ── CAG-03 · Contraste de los controles del carrusel ──────────────────────
  await comprobar('CAG-03', 'Los controles del carrusel superan 4,5:1 sobre su fondo', async () => {
    await portada.goto(`${base}/`, { waitUntil: 'networkidle' })
    const r = await portada.evaluate(() => {
      const leer = (color) => (color.match(/[\d.]+/g) ?? []).slice(0, 3).map(Number)
      const luminancia = ([r, g, b]) => {
        const f = (c) => {
          const v = c / 255
          return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4
        }
        return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b)
      }
      const contraste = (a, b) => {
        const [claro, oscuro] = [luminancia(a), luminancia(b)].sort((x, y) => y - x)
        return (claro + 0.05) / (oscuro + 0.05)
      }
      const fondoDe = (el) => {
        let actual = el
        while (actual) {
          const fondo = getComputedStyle(actual).backgroundColor
          if (fondo && !/rgba?\(0, 0, 0, 0\)|transparent/.test(fondo)) return leer(fondo)
          actual = actual.parentElement
        }
        return [255, 255, 255]
      }
      const controles = [...document.querySelectorAll('.carousel-control-prev, .carousel-control-next')]
      const peor = Math.min(
        ...controles.map((control) => {
          const icono = control.querySelector('span') ?? control
          return contraste(leer(getComputedStyle(icono).color), fondoDe(control))
        }),
      )
      return { controles: controles.length, peor }
    })
    return { ok: r.controles >= 2 && r.peor >= 4.5, detalle: `peor contraste medido: ${r.peor.toFixed(2)}:1` }
  })

  // ── CAG-05 · Barra superior del Estado ────────────────────────────────────
  await comprobar('CAG-05', 'La barra superior enlaza a GOV.CO y dibuja su logotipo', async () => {
    const r = await portada.evaluate(() => {
      const enlace = document.querySelector('.barra-superior-govco a[href*="gov.co"]')
      const caja = enlace?.getBoundingClientRect()
      return { existe: Boolean(enlace), href: enlace?.getAttribute('href') ?? '', ancho: caja?.width ?? 0, alto: caja?.height ?? 0 }
    })
    return { ok: r.existe && r.ancho > 0 && r.alto > 0, detalle: `${r.href} · ${Math.round(r.ancho)}×${Math.round(r.alto)} px` }
  })

  // ── CAG-07 · Barra de accesibilidad: botones y visibilidad por ancho ──────
  await comprobar('CAG-07', 'La barra de accesibilidad tiene los 3 botones y se oculta por debajo de 992 px', async () => {
    const enEscritorio = await portada.evaluate(() => {
      const barra = document.querySelector('.barra-accesibilidad-govco')
      if (!barra) return null
      return {
        contraste: Boolean(barra.querySelector('.contrast')),
        reducir: Boolean(barra.querySelector('.decrease-font-size')),
        aumentar: Boolean(barra.querySelector('.increase-font-size')),
        visible: getComputedStyle(barra).display !== 'none',
      }
    })
    await portada.setViewportSize({ width: 800, height: 900 })
    const enTableta = await portada.evaluate(() => {
      const barra = document.querySelector('.barra-accesibilidad-govco')
      return barra ? getComputedStyle(barra).display !== 'none' : null
    })
    await portada.setViewportSize({ width: 1280, height: 900 })
    if (!enEscritorio) return { ok: false, detalle: 'no se encontró la barra de accesibilidad' }
    const ok =
      enEscritorio.contraste &&
      enEscritorio.reducir &&
      enEscritorio.aumentar &&
      enEscritorio.visible === true &&
      enTableta === false
    return {
      ok,
      detalle: `3 botones: ${[enEscritorio.contraste, enEscritorio.reducir, enEscritorio.aumentar].filter(Boolean).length}/3 · visible a 1280: ${enEscritorio.visible} · visible a 800: ${enTableta}`,
    }
  })

  // ── CAG-08 · Enlace de salto ──────────────────────────────────────────────
  await comprobar('CAG-08', 'El primer tabulador es «Saltar al contenido principal» y su destino existe', async () => {
    await portada.goto(`${base}/tramites`, { waitUntil: 'networkidle' })
    await portada.evaluate(() => document.body.focus())
    await portada.keyboard.press('Tab')
    const r = await portada.evaluate(() => {
      const el = document.activeElement
      const destino = document.querySelector('#contenido-principal')
      return {
        texto: (el?.textContent ?? '').trim(),
        destino: Boolean(destino),
        enfocable: destino?.getAttribute('tabindex') === '-1',
      }
    })
    return { ok: r.texto === 'Saltar al contenido principal' && r.destino && r.enfocable, detalle: `primer tabulador: «${r.texto}» · destino: ${r.destino ? (r.enfocable ? 'existe y es enfocable' : 'existe sin tabindex') : 'no existe'}` }
  })

  // ── CAG-09 · Menú principal ───────────────────────────────────────────────
  await comprobar('CAG-09', 'El menú tiene 7 opciones y ninguna despliega más de 4 enlaces', async () => {
    const r = await portada.evaluate(() => {
      const menu = document.querySelector('nav.menu-govco .navbar-nav')
      if (!menu) return null
      const items = [...menu.children].filter((li) => li.classList.contains('nav-item'))
      // El Kit marca cada SECCIÓN interna con `ul[title]` y mete los enlaces en
      // `li` dentro de ella. Contar `ul li` medía los enlaces —y una sección con
      // las seis subcategorías de Participa daba 6—, que no es lo que el criterio
      // limita.
      const secciones = [...menu.querySelectorAll('li.nav-item')].map(
        (li) => li.querySelectorAll('ul[title]').length,
      )
      return { items: items.length, maxSub: secciones.length > 0 ? Math.max(...secciones) : 0 }
    })
    if (!r) return { ok: false, detalle: 'no se encontró el menú' }
    return { ok: r.items === 7 && r.maxSub <= 4, detalle: `${r.items} opciones · máximo ${r.maxSub} enlaces en un desplegable` }
  })

  // ── CAG-11 · Miga de pan ──────────────────────────────────────────────────
  await comprobar('CAG-11', 'La miga de pan aparece en las subpáginas y no en la portada', async () => {
    await portada.goto(`${base}/`, { waitUntil: 'networkidle' })
    const enPortada = await portada.evaluate(() => Boolean(document.querySelector('.breadcrumb-nav-govco')))
    await portada.goto(`${base}/tramites`, { waitUntil: 'networkidle' })
    const enSubpagina = await portada.evaluate(() => {
      const miga = document.querySelector('.breadcrumb-nav-govco')
      return { existe: Boolean(miga), etiqueta: miga?.getAttribute('aria-label') ?? '', niveles: miga?.querySelectorAll('li').length ?? 0 }
    })
    return {
      ok: enPortada === false && enSubpagina.existe && enSubpagina.niveles >= 2,
      detalle: `portada: ${enPortada ? 'aparece' : 'no aparece'} · /tramites: ${enSubpagina.niveles} niveles («${enSubpagina.etiqueta}»)`,
    }
  })

  // ── CAG-12 · Contraste del pie ────────────────────────────────────────────
  await comprobar('CAG-12', 'El texto del pie supera 4,5:1 sobre su fondo', async () => {
    const r = await portada.evaluate(() => {
      const leer = (color) => (color.match(/[\d.]+/g) ?? []).slice(0, 3).map(Number)
      const f = (c) => {
        const v = c / 255
        return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4
      }
      const luminancia = ([r, g, b]) => 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b)
      const contraste = (a, b) => {
        const [claro, oscuro] = [luminancia(a), luminancia(b)].sort((x, y) => y - x)
        return (claro + 0.05) / (oscuro + 0.05)
      }
      const fondoDe = (el) => {
        let actual = el
        while (actual) {
          const fondo = getComputedStyle(actual).backgroundColor
          if (fondo && !/rgba?\(0, 0, 0, 0\)|transparent/.test(fondo)) return leer(fondo)
          actual = actual.parentElement
        }
        return [255, 255, 255]
      }
      const pie = document.querySelector('footer, .footer-govco, .pie-govco')
      if (!pie) return null
      const textos = [...pie.querySelectorAll('p, a, span, li')].filter((el) => (el.textContent ?? '').trim().length > 3)
      const peor = Math.min(...textos.map((el) => contraste(leer(getComputedStyle(el).color), fondoDe(el))))
      return { medidos: textos.length, peor }
    })
    if (!r) return { ok: false, detalle: 'no se encontró el pie' }
    return { ok: r.medidos > 0 && r.peor >= 4.5, detalle: `${r.medidos} nodos de texto · peor contraste: ${r.peor.toFixed(2)}:1` }
  })

  // ── CAG-13 · Todo botón declara su tipo ───────────────────────────────────
  await comprobar('CAG-13', 'Todos los botones de la página declaran `type`', async () => {
    const r = await portada.evaluate(() => {
      const botones = [...document.querySelectorAll('button')]
      const sinTipo = botones.filter((b) => !b.getAttribute('type'))
      return { botones: botones.length, sinTipo: sinTipo.length }
    })
    return { ok: r.botones > 0 && r.sinTipo === 0, detalle: `${r.botones} botones · ${r.sinTipo} sin type` }
  })

  // ── CAG-14 · Los deshabilitados salen del orden de tabulación ─────────────
  await comprobar('CAG-14', 'Los controles deshabilitados no reciben el foco', async () => {
    const r = await portada.evaluate(() => {
      const deshabilitados = [...document.querySelectorAll('button[disabled], [aria-disabled="true"]')]
      // `el.tabIndex` devuelve 0 en un botón deshabilitado aunque el navegador no
      // lo enfoque: la única comprobación que vale es intentarlo.
      const recibenFoco = deshabilitados.filter((el) => {
        el.focus()
        return document.activeElement === el
      })
      return { deshabilitados: deshabilitados.length, recibenFoco: recibenFoco.length }
    })
    return { ok: r.recibenFoco === 0, detalle: `${r.deshabilitados} controles deshabilitados · ${r.recibenFoco} reciben el foco` }
  })

  // ── CAG-15 · Buscador ─────────────────────────────────────────────────────
  await comprobar('CAG-15', 'El buscador tiene nombre, es navegable por teclado y se puede borrar', async () => {
    await portada.goto(`${base}/`, { waitUntil: 'networkidle' })
    const campo = portada.locator('form[role="search"] input:visible').first()
    if ((await campo.count()) === 0) return { ok: false, detalle: 'no se encontró el campo de búsqueda' }

    const nombre = await portada.evaluate(() => {
      const form = document.querySelector('form[role="search"]')
      const input = form?.querySelector('input')
      const label = input?.id ? document.querySelector(`label[for="${input.id}"]`) : null
      return {
        etiqueta: (label?.textContent ?? '').trim(),
        aria: input?.getAttribute('aria-label') ?? '',
        placeholder: input?.getAttribute('placeholder') ?? '',
        etiquetaForm: form?.getAttribute('aria-label') ?? '',
      }
    })
    const tieneNombre = [nombre.etiqueta, nombre.aria, nombre.placeholder, nombre.etiquetaForm].some(
      (v) => v.trim().length > 0,
    )

    // «Navegable por teclado»: se enfoca y se escribe con el teclado de verdad.
    await campo.focus()
    const enfocado = await campo.evaluate((el) => document.activeElement === el)
    await portada.keyboard.type('pqrsd')
    const escrito = await campo.inputValue()

    // «Borrable»: con texto aparece el botón de limpiar, y limpiar vacía el campo.
    const botonLimpiar = portada.locator('.btn-clean-basic-govco.active').first()
    const aparece = (await botonLimpiar.count()) > 0
    if (aparece) await botonLimpiar.click()
    const vacio = (await campo.inputValue()) === ''

    return {
      ok: tieneNombre && enfocado && escrito === 'pqrsd' && aparece && vacio,
      detalle: `nombre: «${nombre.etiqueta || nombre.aria || nombre.placeholder}» · foco: ${enfocado} · escrito: «${escrito}» · botón de limpiar: ${aparece} · queda vacío: ${vacio}`,
    }
  })

  // ── CAG-21 · Aviso de salida ──────────────────────────────────────────────
  await comprobar('CAG-21', 'Un enlace externo abre el aviso, Escape lo cierra y el foco vuelve', async () => {
    await portada.goto(`${base}/`, { waitUntil: 'networkidle' })
    const enlace = portada.locator('footer a[href*="instagram.com"], footer a[href*="facebook.com"]').first()
    const hay = (await enlace.count()) > 0
    if (!hay) return { ok: false, detalle: 'no hay enlaces externos en el pie para probar el aviso' }
    const urlAntes = portada.url()
    // El banner de cookies ocupa la franja inferior de la pantalla en la primera
    // visita y el clic no llegaría al pie: se resuelve el consentimiento primero,
    // que es lo que haría cualquier persona antes de seguir navegando.
    const rechazar = portada.locator('button.btn-rechazar').first()
    if ((await rechazar.count()) > 0) {
      await rechazar.click()
      await portada.waitForTimeout(300)
    }
    await enlace.scrollIntoViewIfNeeded()
    await enlace.click({ timeout: 10_000 })
    await portada.waitForTimeout(400)
    const trasClic = await portada.evaluate(() => {
      const modal = document.querySelector('[data-aviso-salida]')
      if (!modal) return { visible: false }
      const estilo = getComputedStyle(modal)
      const boton = modal.querySelector('button[data-aviso-salida], button')
      return {
        visible: estilo.display !== 'none' && estilo.visibility !== 'hidden' && Number(estilo.opacity) > 0,
        tieneConfirmar: Boolean(boton),
        activo: document.activeElement?.tagName ?? '',
      }
    })
    await portada.keyboard.press('Escape')
    await portada.waitForTimeout(300)
    const trasEscape = await portada.evaluate(() => {
      const modal = document.querySelector('[data-aviso-salida]')
      const estilo = modal ? getComputedStyle(modal) : null
      return {
        oculto: !modal || estilo.display === 'none' || estilo.visibility === 'hidden' || Number(estilo.opacity) === 0,
        foco: document.activeElement?.tagName ?? '',
        enlace: document.activeElement?.tagName === 'A',
      }
    })
    const ok = trasClic.visible && trasClic.tieneConfirmar && trasEscape.oculto && trasEscape.enlace && portada.url() === urlAntes
    return {
      ok,
      detalle: `aviso visible: ${trasClic.visible} · Escape cierra: ${trasEscape.oculto} · foco devuelto al enlace: ${trasEscape.enlace} · sin navegar: ${portada.url() === urlAntes}`,
    }
  })

  // ── CAG-16 · Leyenda de obligatorios, antes del primer campo ──────────────
  await comprobar('CAG-16', 'La leyenda del asterisco aparece antes del primer campo obligatorio', async () => {
    await portada.goto(`${base}/realizar-una-peticion`, { waitUntil: 'networkidle' })
    const r = await portada.evaluate(() => {
      const leyenda = document.querySelector('#leyenda-obligatorios')
      if (!leyenda) return null
      const obligatorios = [...document.querySelectorAll('[required]')]
      const primero = obligatorios.find((campo) => ['INPUT', 'SELECT', 'TEXTAREA'].includes(campo.tagName))
      if (!primero) return { leyenda: true, obligatorios: 0 }
      // `compareDocumentPosition` es la comprobación de orden que no depende de
      // coordenadas: 4 = el primer nodo va después del segundo.
      const vaDespues = Boolean(leyenda.compareDocumentPosition(primero) & Node.DOCUMENT_POSITION_FOLLOWING)
      const explicaAsterisco = /\*/.test(leyenda.textContent ?? '')
      return { leyenda: true, vaDespues, explicaAsterisco, obligatorios: obligatorios.length, campos: Boolean(primero) }
    })
    if (!r) return { ok: false, detalle: 'no se encontró la leyenda de obligatorios (#leyenda-obligatorios)' }
    return {
      ok: r.vaDespues === true && r.explicaAsterisco === true,
      detalle: `${r.obligatorios} campos obligatorios · la leyenda va antes del primero: ${r.vaDespues} · explica el asterisco: ${r.explicaAsterisco}`,
    }
  })

  // ── CAG-17 · Estados de los controles y `autocomplete` ────────────────────
  await comprobar('CAG-17', 'Los campos declaran `autocomplete` y tienen estado deshabilitado legible', async () => {
    await portada.goto(`${base}/realizar-una-peticion`, { waitUntil: 'networkidle' })
    const r = await portada.evaluate(() => {
      // El alcance es el formulario de la petición: el buscador de la cabecera no
      // forma parte de él y tiene su propio criterio (CAG-15).
      const formulario = document.querySelector('form.formulario-pqrsd')
      if (!formulario) return null
      const visibles = [...formulario.querySelectorAll('input, select, textarea')].filter(
        (campo) =>
          campo.getBoundingClientRect().width > 0 &&
          !['hidden', 'submit', 'button', 'radio', 'checkbox'].includes(campo.type ?? ''),
      )
      const sinAutocomplete = visibles.filter((campo) => !campo.hasAttribute('autocomplete'))
      // El estado deshabilitado se mide sobre un control real creado al efecto:
      // mirar la hoja de estilos no demuestra cómo queda el campo.
      const prueba = document.createElement('input')
      prueba.className = 'form-control'
      prueba.disabled = true
      document.querySelector('form')?.appendChild(prueba)
      const cs = getComputedStyle(prueba)
      const estadoDeshabilitado = { opacidad: cs.opacity, fondo: cs.backgroundColor, color: cs.color }
      prueba.remove()
      return { visibles: visibles.length, sinAutocomplete: sinAutocomplete.length, estadoDeshabilitado }
    })
    const legible = Number(r.estadoDeshabilitado.opacidad) === 1 && r.estadoDeshabilitado.fondo !== 'rgba(0, 0, 0, 0)'
    return {
      ok: r.visibles > 0 && r.sinAutocomplete === 0 && legible,
      detalle: `${r.visibles} campos visibles · ${r.sinAutocomplete} sin autocomplete · deshabilitado: opacidad ${r.estadoDeshabilitado.opacidad}, fondo ${r.estadoDeshabilitado.fondo}`,
    }
  })

  // ── CAG-26 · Tarjetas pulsables en toda su superficie ─────────────────────
  await comprobar('CAG-26', 'Cada tarjeta es un <a> o un <button> y su título no pasa de dos palabras', async () => {
    await portada.goto(`${base}/`, { waitUntil: 'networkidle' })
    const r = await portada.evaluate(() => {
      const tarjetas = [...document.querySelectorAll('.tarjeta-informacion-govco')]
      if (tarjetas.length === 0) return null
      return {
        tarjetas: tarjetas.length,
        // La tarjeta **es** el elemento interactivo: el componente la dibuja con
        // `<component :is>` y la clase va en la raíz.
        noInteractivas: tarjetas.filter((t) => !['A', 'BUTTON'].includes(t.tagName)).length,
        // Un interactivo dentro de otro interactivo no es válido y rompe la
        // tarjeta entera como superficie de pulsación.
        anidadas: tarjetas.filter((t) => t.querySelectorAll('a, button, input, select, textarea').length > 0).length,
        titulosLargos: tarjetas
          .map((t) => (t.querySelector('h5, h6')?.textContent ?? '').trim())
          .filter((titulo) => titulo.length > 0 && titulo.split(/\s+/).length > 2).length,
      }
    })
    if (!r) return { ok: false, detalle: 'no se encontraron tarjetas en la portada' }
    return {
      ok: r.noInteractivas === 0 && r.anidadas === 0 && r.titulosLargos === 0,
      detalle: `${r.tarjetas} tarjetas · ${r.noInteractivas} sin ser <a>/<button> · ${r.anidadas} con interactivos dentro · ${r.titulosLargos} con título de más de dos palabras`,
    }
  })

  // ── CAG-27 · Sin justificar y con medida de línea ─────────────────────────
  await comprobar('CAG-27', 'Nada se justifica y la prosa mide entre 45 y 80 caracteres por línea', async () => {
    await portada.goto(`${base}/pqrsd`, { waitUntil: 'networkidle' })
    const r = await portada.evaluate(() => {
      const justificados = [...document.querySelectorAll('p, li, td')].filter(
        (el) => getComputedStyle(el).textAlign === 'justify',
      ).length
      const parrafo = document.querySelector('#contenido-principal p')
      if (!parrafo) return { justificados, porLinea: 0 }
      const cs = getComputedStyle(parrafo)
      const sonda = document.createElement('span')
      sonda.style.cssText = `position:absolute;visibility:hidden;white-space:nowrap;font:${cs.font};letter-spacing:${cs.letterSpacing}`
      sonda.textContent = 'x'.repeat(100)
      document.body.appendChild(sonda)
      const anchoPorCaracter = sonda.getBoundingClientRect().width / 100
      const porLinea = Math.round(parrafo.getBoundingClientRect().width / anchoPorCaracter)
      sonda.remove()
      return { justificados, porLinea }
    })
    return { ok: r.justificados === 0 && r.porLinea >= 45 && r.porLinea <= 80, detalle: `${r.justificados} bloques justificados · ${r.porLinea} caracteres por línea` }
  })

  // ── CAG-30 · Volver arriba ────────────────────────────────────────────────
  await comprobar('CAG-30', 'El botón «Volver arriba» está y aparece tras desplazarse', async () => {
    await portada.goto(`${base}/pqrsd`, { waitUntil: 'networkidle' })
    const alInicio = await portada.evaluate(() => {
      const contenedor = document.querySelector('.posicion-volver-arriba')
      if (!contenedor) return null
      const estilo = getComputedStyle(contenedor)
      return { existe: true, visible: estilo.display !== 'none' && estilo.visibility !== 'hidden' }
    })
    await portada.evaluate(() => window.scrollTo(0, document.body.scrollHeight))
    await portada.waitForTimeout(500)
    const trasScroll = await portada.evaluate(() => {
      const contenedor = document.querySelector('.posicion-volver-arriba')
      const estilo = contenedor ? getComputedStyle(contenedor) : null
      return { visible: Boolean(estilo) && estilo.display !== 'none' && estilo.visibility !== 'hidden' }
    })
    if (!alInicio) return { ok: false, detalle: 'no se encontró el botón de volver arriba' }
    return { ok: alInicio.visible === false && trasScroll.visible === true, detalle: `al inicio: ${alInicio.visible ? 'visible' : 'oculto'} · tras desplazarse: ${trasScroll.visible ? 'visible' : 'oculto'}` }
  })

  // ── CAG-20 + RF-B2-077 · Pasos numerados y línea de avance ────────────────
  await comprobar('CAG-20', 'El formulario largo se divide en pasos numerados con línea de avance', async () => {
    await portada.goto(`${base}/realizar-una-peticion`, { waitUntil: 'networkidle' })

    const leer = () =>
      portada.evaluate(() => ({
        encabezado: (document.querySelector('.pasos-encabezado')?.textContent ?? '')
          .replace(/\s+/g, ' ')
          .trim(),
        pasos: [...document.querySelectorAll('.paso')].map((boton) => ({
          nombre: (boton.querySelector('.paso-nombre')?.textContent ?? '').trim(),
          estado: (boton.querySelector('.paso-estado')?.textContent ?? '').trim(),
          actual: boton.getAttribute('aria-current') === 'step',
        })),
        visibles: [...document.querySelectorAll('.paso-contenido')].filter(
          (contenido) => getComputedStyle(contenido).display !== 'none',
        ).length,
        invalidos: document.querySelectorAll('[aria-invalid="true"]').length,
        foco: document.activeElement?.id ?? document.activeElement?.tagName ?? '',
      }))

    const inicio = await leer()
    // RF-B2-077: «Paso N de M» con los pendientes identificables.
    const patron = /^Paso 1 de \d+: .+/.test(inicio.encabezado)
    const identificaPendientes = inicio.pasos.some((paso) => paso.estado === 'pendiente')
    const unoSolo = inicio.visibles === 1
    const conActual = inicio.pasos.filter((paso) => paso.actual).length === 1

    // No se avanza con el paso incompleto: se señalan los campos y el foco va al primero.
    await portada.locator('button:has-text("Continuar a")').first().click()
    await portada.waitForTimeout(300)
    const trasIntentar = await leer()
    const frena = trasIntentar.encabezado === inicio.encabezado && trasIntentar.invalidos > 0
    const focoAlPrimero = trasIntentar.foco === 'tipoSolicitud'

    // CAG-20: se puede saltar libremente a un paso pendiente.
    await portada.locator('.paso').nth(2).click()
    await portada.waitForTimeout(250)
    const trasSalto = await leer()
    const salta = /^Paso 3 de \d+: /.test(trasSalto.encabezado)
    // Y saltar no convierte en «completado» lo que no lo está.
    const sinMentir = trasSalto.pasos.filter((paso) => paso.estado === 'completado').length === 0

    /*
     * Enviar desde el último paso con los anteriores sin rellenar tiene que
     * **devolver a la persona al paso donde está el fallo**: si se queda en el
     * último, los campos marcados están ocultos, el foco no se puede poner en un
     * elemento que no se ve y el botón parecería no hacer nada.
     */
    await portada.locator('form.formulario-pqrsd button[type="submit"]').first().click()
    await portada.waitForTimeout(300)
    const trasEnviar = await leer()
    const vuelveAlFallo = /^Paso 1 de \d+: /.test(trasEnviar.encabezado) && trasEnviar.invalidos > 0

    return {
      ok: patron && identificaPendientes && unoSolo && conActual && frena && focoAlPrimero && salta && sinMentir && vuelveAlFallo,
      detalle:
        `«${inicio.encabezado}» · ${inicio.pasos.length} pasos (${inicio.pasos.map((p) => p.estado).join(', ')}) · ` +
        `avanzar sin rellenar: ${frena ? 'frena y marca campos' : 'NO frena'} (foco en ${trasIntentar.foco}) · ` +
        `salto libre: ${salta ? 'sí' : 'no'} · estados tras saltar: ${trasSalto.pasos.map((p) => p.estado).join(', ')} · ` +
        `enviar desde el último paso: ${vuelveAlFallo ? `vuelve a «${trasEnviar.encabezado}» con el foco en el fallo` : 'NO vuelve al paso del fallo'}`,
    }
  })

  // ── D-29 · Metadatos de compartición y URL canónica ───────────────────────
  await comprobar('D-29', 'Las páginas declaran canónica y metadatos de compartición; el 404 no', async () => {
    const leer = async (ruta) => {
      const respuesta = await portada.request.get(`${base}${ruta}`)
      const html = await respuesta.text()
      const meta = (propiedad) =>
        (html.match(new RegExp(`property="${propiedad}"[^>]*content="([^"]*)"`)) ?? [])[1] ?? ''
      return {
        estado: respuesta.status(),
        canonical: (html.match(/rel="canonical"[^>]*href="([^"]*)"/) ?? [])[1] ?? '',
        ogTitulo: meta('og:title'),
        ogSitio: meta('og:site_name'),
        ogImagen: meta('og:image'),
        ogLocale: meta('og:locale'),
        ogUrl: meta('og:url'),
      }
    }

    const pagina = await leer('/accesibilidad')
    const error = await leer('/ruta-que-no-existe-para-probar-el-404')
    const absoluta = (url) => /^https?:\/\/.+/.test(url)

    const ok =
      absoluta(pagina.canonical) &&
      pagina.canonical.endsWith('/accesibilidad') &&
      pagina.ogTitulo.length > 0 &&
      pagina.ogSitio.length > 0 &&
      absoluta(pagina.ogImagen) &&
      pagina.ogLocale === 'es_CO' &&
      absoluta(pagina.ogUrl) &&
      // En el 404 no se anuncia canónica: la dirección que acaba de fallar no es
      // una dirección «buena» que ofrecer a nadie.
      error.canonical === '' &&
      error.ogUrl === ''

    return {
      ok,
      detalle:
        `página: canónica ${pagina.canonical || '—'} · og:title «${pagina.ogTitulo}» · og:image ${absoluta(pagina.ogImagen) ? 'absoluta' : 'NO absoluta'} · og:locale ${pagina.ogLocale} · ` +
        `404: canónica ${error.canonical === '' ? 'ausente' : error.canonical}`,
    }
  })

  // ── CAG-33 / CAG-34 · El Kit se sirve desde el propio dominio ─────────────
  await comprobar('CAG-33/34', 'El CSS del Kit se sirve desde el propio dominio y coincide con el instalado', async () => {
    const respuesta = await portada.request.get(`${base}/govco/all.css`)
    const servido = await respuesta.text()
    const instalado = readFileSync(resolve(raiz, 'public/govco/all.css'), 'utf8')
    const hash = (texto) => createHash('md5').update(texto).digest('hex')
    const igual = hash(servido) === hash(instalado)
    const externos = await portada.evaluate(() =>
      [...document.querySelectorAll('link[rel="stylesheet"]')].filter((l) => {
        try {
          return new URL(l.href).origin !== window.location.origin
        } catch {
          return true
        }
      }).length,
    )
    return {
      ok: respuesta.status() === 200 && igual && externos === 0,
      detalle: `HTTP ${respuesta.status()} · md5 ${hash(servido).slice(0, 12)} vs instalado ${hash(instalado).slice(0, 12)} · hojas externas: ${externos}`,
    }
  })

  // ——— Informe ———
  console.log('')
  console.log('Puerta de conformidad de diseño (CAG-01 a CAG-34)')
  console.log('='.repeat(80))
  for (const r of resultados) {
    const marca = r.ok ? 'OK     ' : r.error ? 'ERROR  ' : 'FALLA  '
    console.log(`  ${marca} ${r.cag.padEnd(12)} ${r.descripcion}`)
    if (r.detalle) console.log(`          ${r.detalle}`)
  }
  console.log('-'.repeat(80))
  console.log(`  Criterios comprobados: ${resultados.length}`)
  console.log(`  Fallos: ${fallos}`)
  console.log('')
  console.log('  Desviaciones declaradas en ADR-0015 (no se comprueban aquí):')
  for (const desviacion of DESVIACIONES) console.log(`    - ${desviacion}`)
  console.log('')
  console.log('  CAG-29 (aviso a los 10 s de carga) no se comprueba aquí: haría falta un servicio')
  console.log('  que responda con más de diez segundos de latencia, que este entorno no tiene.')
  console.log('  Está implementado en `pages/tramites/index.vue` y la matriz lo lista como')
  console.log('  «implementación», no como prueba.')
  console.log('  CAG-32 (accesibilidad) y CAG-28 (contraste de texto) los mide `make accesibilidad`')
  console.log('  con axe sobre 18 páginas.')
  console.log('='.repeat(80))

  if (fallos > 0) {
    console.error(`La puerta de diseño falla: ${fallos} comprobación(es).`)
    process.exitCode = 1
  } else {
    console.log('La puerta de diseño pasa.')
  }
} catch (error) {
  console.error('La puerta de diseño no pudo ejecutarse:', error.message)
  if (salidaServidor) console.error(salidaServidor.slice(-2000))
  process.exitCode = 1
} finally {
  await navegador?.close()
  servidor.kill('SIGTERM')
}
