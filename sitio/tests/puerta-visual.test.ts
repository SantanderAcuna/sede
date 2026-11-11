/**
 * La puerta visual automática (hallazgo del 2026-11-10; decisión de José: que se evalúe
 * sola, sin su intervención, también lo que se cree de nuevo).
 *
 * Lo que ya existía: tras cada merge a staging corren, en cuatro navegadores, el humo
 * funcional, `movil.mjs`, `kit-ui.mjs` y `diseno.mjs` (CAG-01…34). Lo que faltaba: (1) correr
 * ANTES del merge, en el PR; (2) que las rutas se descubran solas —las listas de
 * `movil.mjs` y `diseno.mjs` están escritas a mano, así que una página nueva no entra—; (3)
 * `axe` y el buscador, que no corren en ningún flujo; (4) saber qué cambió de aspecto.
 *
 * Estas pruebas fijan la parte pura: descubrir rutas, juzgar medidas ya tomadas en el
 * navegador, y avisar qué archivos de diseño toca un cambio. No juzga gusto: juzga
 * propiedades objetivas (no se sale del ancho, no hay imágenes rotas, la cabecera es la
 * misma en todas las páginas…).
 */
import { describe, expect, it } from 'vitest'

import {
  ANCHOS,
  archivosDeDiseno,
  juzgarCromo,
  juzgarMedidas,
  objetivosIncumplidos,
  rutasDelSitemap,
} from '../scripts/lib/puerta-visual.mjs'

/** Una página sana a un ancho dado; cada prueba rompe UNA cosa. */
function sana(sobre: Record<string, unknown> = {}) {
  return {
    ruta: '/pqrsd',
    ancho: 390,
    estado: 200,
    titulo: 'PQRSD · Sede Electrónica',
    lang: 'es',
    h1: 1,
    tieneMain: true,
    tieneCabecera: true,
    tienePie: true,
    desborde: 0,
    elementosFuera: [] as Array<{ selector: string; derecha: number }>,
    imagenesRotas: [] as string[],
    imagenesSinAlt: 0,
    fuentesConError: [] as string[],
    cls: 0,
    objetivosPequenos: [] as Array<{ selector: string; ancho: number; alto: number }>,
    erroresConsola: [] as string[],
    recursosFallidos: [] as Array<{ url: string; estado: number }>,
    ...sobre,
  }
}

const reglas = (m: ReturnType<typeof sana>, base?: Record<string, any>) =>
  juzgarMedidas(m, base).map((v: { regla: string }) => v.regla)

describe('rutasDelSitemap: las rutas se descubren solas', () => {
  const xml = (locs: string[]) =>
    `<?xml version="1.0"?><urlset>${locs.map((l) => `<url><loc>${l}</loc></url>`).join('')}</urlset>`

  it('toma el camino de cada <loc>, sin importar el dominio', () => {
    const r = rutasDelSitemap(xml(['https://staging.santamarta.gov.co/', 'https://x.test/pqrsd']))
    expect(r).toEqual(['/', '/pqrsd'])
  })

  it('una página nueva en el sitemap entra sin tocar ninguna lista', () => {
    expect(rutasDelSitemap(xml(['https://x.test/', 'https://x.test/pagina-nueva']))).toContain('/pagina-nueva')
  })

  it('no repite rutas', () => {
    expect(rutasDelSitemap(xml(['https://x.test/a', 'https://x.test/a']))).toEqual(['/a'])
  })

  it('de las fichas (tramites/…, politicas/…) toma una muestra, no las cientos que haya', () => {
    const locs = ['https://x.test/tramites', ...Array.from({ length: 50 }, (_, i) => `https://x.test/tramites/t-${i}`)]
    const r = rutasDelSitemap(xml(locs), { maxPorPrefijo: 2 })
    expect(r.filter((x: string) => x.startsWith('/tramites/'))).toHaveLength(2)
    expect(r).toContain('/tramites')
  })

  it('cada prefijo de fichas tiene su propio cupo', () => {
    const locs = ['t', 'p'].flatMap((k) =>
      Array.from({ length: 5 }, (_, i) => `https://x.test/${k === 't' ? 'tramites' : 'politicas'}/f-${i}`),
    )
    const r = rutasDelSitemap(xml(locs), { maxPorPrefijo: 2 })
    expect(r.filter((x: string) => x.startsWith('/tramites/'))).toHaveLength(2)
    expect(r.filter((x: string) => x.startsWith('/politicas/'))).toHaveLength(2)
  })

  it('decodifica entidades XML y descarta lo que no es una URL', () => {
    const r = rutasDelSitemap(xml(['https://x.test/a?x=1&amp;y=2', 'esto-no-es-url', '']))
    expect(r).toEqual(['/a?x=1&y=2'])
  })

  it('un sitemap sin rutas devuelve vacío (el ejecutor lo trata como error, no como éxito)', () => {
    expect(rutasDelSitemap('<urlset></urlset>')).toEqual([])
  })
})

describe('juzgarMedidas: propiedades objetivas de una página a un ancho', () => {
  it('una página sana no tiene violaciones', () => {
    expect(juzgarMedidas(sana())).toEqual([])
  })

  it('PV-01 · una ruta publicada que no responde 200', () => {
    expect(reglas(sana({ estado: 500 }))).toEqual(['PV-01'])
    expect(reglas(sana({ estado: 404 }))).toEqual(['PV-01'])
  })

  it('PV-01 · la página 404 se mide con el estado que se espera de ella', () => {
    expect(reglas(sana({ estado: 404, estadoEsperado: 404 }))).toEqual([])
    expect(reglas(sana({ estado: 200, estadoEsperado: 404 }))).toEqual(['PV-01'])
  })

  it('PV-02 · estructura: un solo h1, main, cabecera, pie, idioma y título', () => {
    expect(reglas(sana({ h1: 0 }))).toContain('PV-02')
    expect(reglas(sana({ h1: 2 }))).toContain('PV-02')
    expect(reglas(sana({ tieneMain: false }))).toContain('PV-02')
    expect(reglas(sana({ tieneCabecera: false }))).toContain('PV-02')
    expect(reglas(sana({ tienePie: false }))).toContain('PV-02')
    expect(reglas(sana({ lang: 'en' }))).toContain('PV-02')
    expect(reglas(sana({ lang: '' }))).toContain('PV-02')
    expect(reglas(sana({ lang: 'espanol' }))).toContain('PV-02')
    expect(reglas(sana({ titulo: '  ' }))).toContain('PV-02')
  })

  it('PV-03 · reflujo: la página no se desplaza horizontalmente', () => {
    expect(reglas(sana({ desborde: 14 }))).toContain('PV-03')
  })

  it('PV-03 · y ningún elemento se sale del ancho aunque la página lo oculte', () => {
    const v = juzgarMedidas(sana({ elementosFuera: [{ selector: 'div.tabla', derecha: 512 }] }))
    expect(v.map((x: { regla: string }) => x.regla)).toContain('PV-03')
    expect(v[0].mensaje).toContain('div.tabla')
  })

  it('PV-04 · imágenes rotas o sin atributo alt', () => {
    expect(reglas(sana({ imagenesRotas: ['/img/x.png'] }))).toContain('PV-04')
    expect(reglas(sana({ imagenesSinAlt: 1 }))).toContain('PV-04')
  })

  it('PV-05 · una fuente que no cargó', () => {
    expect(reglas(sana({ fuentesConError: ['Work Sans'] }))).toContain('PV-05')
  })

  it('PV-06 · salto de maquetación (CLS) por encima de 0,1', () => {
    expect(reglas(sana({ cls: 0.1 }))).not.toContain('PV-06')
    expect(reglas(sana({ cls: 0.11 }))).toContain('PV-06')
  })

  it('PV-07 · un control menor de 24×24 px (WCAG 2.2, 2.5.8)', () => {
    expect(reglas(sana({ objetivosPequenos: [{ selector: 'button.x', ancho: 16, alto: 16 }] }))).toContain('PV-07')
  })

  describe('línea base del diseño ya aprobado (se puede mantener o mejorar, no empeorar)', () => {
    const pequeno = (selector: string) => ({ selector, ancho: 20, alto: 20 })

    it('el idioma «es» y las variantes regionales («es-CO») son válidos', () => {
      expect(reglas(sana({ lang: 'es' }))).not.toContain('PV-02')
      expect(reglas(sana({ lang: 'es-CO' }))).not.toContain('PV-02')
    })

    it('PV-07 · lo que la línea base ya admite en esa ruta no falla', () => {
      const m = sana({ objetivosPequenos: [pequeno('a'), pequeno('b')] })
      expect(reglas(m, { objetivosPequenos: { '/pqrsd': 2 } })).not.toContain('PV-07')
    })

    it('PV-07 · un control pequeño de más que la línea base sí falla, y el mensaje lo dice', () => {
      const m = sana({ objetivosPequenos: [pequeno('a'), pequeno('b'), pequeno('c')] })
      const v = juzgarMedidas(m, { objetivosPequenos: { '/pqrsd': 2 } })
      expect(v.map((x: { regla: string }) => x.regla)).toContain('PV-07')
      expect(v[0].mensaje).toMatch(/línea base/)
    })

    it('PV-07 · una ruta que no está en la línea base (página nueva) admite cero', () => {
      const m = sana({ ruta: '/nueva', objetivosPequenos: [pequeno('a')] })
      expect(reglas(m, { objetivosPequenos: { '/pqrsd': 5 } })).toContain('PV-07')
    })

    it('PV-06 · el CLS puede llegar al de la línea base de esa ruta más la tolerancia entre máquinas, no más', () => {
      expect(reglas(sana({ cls: 0.2 }), { cls: { '/pqrsd': 0.21 } })).not.toContain('PV-06')
      // Medido: 0,204 en un portátil y 0,215 en el CI para la misma portada a 320 px.
      expect(reglas(sana({ cls: 0.215 }), { cls: { '/pqrsd': 0.21 } })).not.toContain('PV-06')
      expect(reglas(sana({ cls: 0.26 }), { cls: { '/pqrsd': 0.21 } })).not.toContain('PV-06')
      expect(reglas(sana({ cls: 0.27 }), { cls: { '/pqrsd': 0.21 } })).toContain('PV-06')
    })

    it('PV-06 · una línea base menor que 0,1 no relaja el umbral general', () => {
      expect(reglas(sana({ cls: 0.11 }), { cls: { '/pqrsd': 0.05 } })).toContain('PV-06')
    })

    it('PV-06 · una ruta nueva se juzga con 0,1', () => {
      expect(reglas(sana({ ruta: '/nueva', cls: 0.15 }), { cls: { '/pqrsd': 0.5 } })).toContain('PV-06')
    })
  })

  it('PV-08 · errores de consola o recursos que fallan', () => {
    expect(reglas(sana({ erroresConsola: ['Uncaught TypeError'] }))).toContain('PV-08')
    expect(reglas(sana({ recursosFallidos: [{ url: '/_nuxt/a.js', estado: 404 }] }))).toContain('PV-08')
  })

  it('cada violación dice ruta, ancho y qué pasó', () => {
    const [v] = juzgarMedidas(sana({ desborde: 14, ruta: '/normativa', ancho: 320 }))
    expect(v).toMatchObject({ regla: 'PV-03', ruta: '/normativa', ancho: 320 })
    expect(v.mensaje).toMatch(/14/)
  })

  it('varias roturas se informan todas, no sólo la primera', () => {
    expect(reglas(sana({ desborde: 3, cls: 0.5, imagenesRotas: ['a'] })).sort()).toEqual(['PV-03', 'PV-04', 'PV-06'])
  })
})

describe('juzgarCromo: la cabecera y el pie son los mismos en todas las páginas', () => {
  const m = (ruta: string, ancho: number, cabecera: string, pie: string) => ({ ruta, ancho, firmaCabecera: cabecera, firmaPie: pie })

  it('todas iguales: sin violaciones', () => {
    expect(juzgarCromo([m('/', 1300, 'h1', 'f1'), m('/pqrsd', 1300, 'h1', 'f1')])).toEqual([])
  })

  it('una página con otra cabecera se señala (PV-09)', () => {
    const v = juzgarCromo([m('/', 1300, 'h1', 'f1'), m('/a', 1300, 'h1', 'f1'), m('/nueva', 1300, 'hX', 'f1')])
    expect(v).toHaveLength(1)
    expect(v[0]).toMatchObject({ regla: 'PV-09', ruta: '/nueva', ancho: 1300 })
    expect(v[0].mensaje).toMatch(/cabecera/)
  })

  it('el pie distinto también', () => {
    const v = juzgarCromo([m('/', 390, 'h', 'f'), m('/a', 390, 'h', 'f'), m('/b', 390, 'h', 'fX')])
    expect(v.map((x: { mensaje: string }) => x.mensaje)[0]).toMatch(/pie/)
  })

  it('se compara dentro de cada ancho: la cabecera móvil puede diferir de la de escritorio', () => {
    expect(juzgarCromo([m('/', 1300, 'esc', 'f'), m('/a', 1300, 'esc', 'f'), m('/', 390, 'mov', 'f'), m('/a', 390, 'mov', 'f')])).toEqual([])
  })

  it('con una sola página no hay con qué comparar', () => {
    expect(juzgarCromo([m('/', 1300, 'h', 'f')])).toEqual([])
  })
})

describe('archivosDeDiseno: qué cambio de un PR puede alterar el aspecto', () => {
  it('señala CSS, componentes, páginas, layouts, public y la configuración de Nuxt', () => {
    const r = archivosDeDiseno([
      'sitio/app/assets/css/tokens.css',
      'sitio/app/components/govco/Pie.vue',
      'sitio/app/pages/buscar.vue',
      'sitio/app/layouts/default.vue',
      'sitio/public/govco/all.css',
      'sitio/nuxt.config.ts',
    ])
    expect(r).toHaveLength(6)
  })

  it('no señala pruebas, scripts, documentación ni el backend', () => {
    expect(
      archivosDeDiseno([
        'sitio/tests/puerta-visual.test.ts',
        'sitio/scripts/captura-visual.mjs',
        'docs/busqueda.md',
        'backend/config/busqueda.php',
        'sitio/app/composables/useSugerenciasBusqueda.ts',
        'sitio/AGENTS.md',
        '.github/workflows/ci.yml',
      ]),
    ).toEqual([])
  })
})

describe('ANCHOS', () => {
  it('cubre escritorio, tableta y los dos teléfonos de la revisión móvil (320 y 390)', () => {
    expect(ANCHOS).toEqual(expect.arrayContaining([1300, 768, 390, 320]))
  })
})

/**
 * WCAG 2.2, 2.5.8 «Tamaño del objetivo (mínimo)»: 24×24 px CSS, salvo (a) Espaciado: el círculo de 24 px
 * centrado en el objetivo pequeño no corta a otro objetivo ni al círculo de otro objetivo pequeño;
 * (b) Equivalente: la misma función está en otro control que sí mide 24×24 (aquí: la etiqueta de una
 * casilla/radio, interpretación declarada); (c) En línea: enlace dentro de una frase.
 * Texto normativo consultado en la página «Understanding» del W3C el 2026-11-10; el círculo se centra en la
 * CAJA que envuelve al objetivo. Antes la puerta contaba todo lo menor de 24 como falla: eso era un exceso.
 */
describe('objetivosIncumplidos · 2.5.8 con sus excepciones', () => {
  const obj = (selector: string, x: number, y: number, w: number, h: number, extra: Record<string, unknown> = {}) => ({
    selector,
    x,
    y,
    w,
    h,
    ...extra,
  })

  it('un control de 24×24 o más no es problema', () => {
    expect(objetivosIncumplidos([obj('button.a', 0, 0, 24, 24)])).toEqual([])
  })

  it('un control pequeño y solo (nada a 24 px a la redonda) cumple por Espaciado', () => {
    expect(objetivosIncumplidos([obj('button.a', 100, 100, 16, 16), obj('a.lejos', 300, 300, 100, 30)])).toEqual([])
  })

  it('dos controles pequeños con los centros a ≥ 24 px cumplen (indicadores del carrusel: 27,2 px)', () => {
    const lista = [0, 1, 2, 3].map((i) => obj(`button.i${i}`, 380.7 + i * 27.2, 926.5, 16, 16))
    expect(objetivosIncumplidos(lista)).toEqual([])
  })

  it('dos controles pequeños con los centros a < 24 px fallan los dos', () => {
    const r = objetivosIncumplidos([obj('button.a', 0, 0, 16, 16), obj('button.b', 20, 0, 16, 16)])
    expect(r.map((x: { selector: string }) => x.selector)).toEqual(['button.a', 'button.b'])
  })

  it('un control pequeño cuyo círculo corta a otro objetivo (aunque grande) falla', () => {
    const r = objetivosIncumplidos([obj('button.a', 0, 0, 16, 16), obj('a.vecino', 18, 0, 200, 40)])
    expect(r.map((x: { selector: string }) => x.selector)).toEqual(['button.a'])
  })

  it('el círculo es de 24 px de DIÁMETRO: tocar el borde justo no corta, entrar 0,5 px sí', () => {
    // Círculo de a: centro (8,8), radio 12 → llega a x=20. Un vecino que empieza en x=20 sólo lo toca.
    expect(objetivosIncumplidos([obj('button.a', 0, 0, 16, 16), obj('a.v', 20, 0, 50, 50)])).toEqual([])
    expect(objetivosIncumplidos([obj('button.a', 0, 0, 16, 16), obj('a.v', 19.5, 0, 50, 50)]).length).toBe(1)
  })

  it('un enlace de 20 px de alto muy ancho cumple si el siguiente queda fuera de su círculo (pie de página)', () => {
    // Medidas reales de la portada a 1300 px: dos correos apilados, centros a 139 px en horizontal.
    const r = objetivosIncumplidos([obj('a.uno', 279, 2186.2, 313, 20), obj('a.dos', 388, 2208.7, 373, 20)])
    expect(r).toEqual([])
  })

  it('el mismo par de enlaces, pero a 1 px de distancia vertical, falla', () => {
    const r = objetivosIncumplidos([obj('a.uno', 279, 2186.2, 313, 20), obj('a.dos', 388, 2207.2, 373, 20)])
    expect(r.length).toBeGreaterThan(0)
  })

  it('una casilla con su etiqueta: la etiqueta propia no cuenta como vecino que corta', () => {
    const r = objetivosIncumplidos([obj('input', 873, 1073.8, 20, 20, { etiqueta: { x: 895, y: 1071, w: 150, h: 22 } })])
    expect(r).toEqual([])
  })

  it('una casilla pegada a OTRA etiqueta falla si su círculo la corta y no hay equivalente', () => {
    const r = objetivosIncumplidos([
      obj('input.a', 0, 0, 20, 20, { etiqueta: { x: 40, y: 0, w: 80, h: 22 } }),
      obj('label.otra', 8, 20, 100, 22),
    ])
    expect(r.map((x: { selector: string }) => x.selector)).toContain('input.a')
  })

  it('Equivalente: la etiqueta de 24×24 o más salva a la casilla que no cumple por espaciado', () => {
    const r = objetivosIncumplidos([
      obj('input.a', 0, 0, 20, 20, { etiqueta: { x: 22, y: 0, w: 300, h: 24 } }),
      obj('a.pegado', 0, 22, 100, 30),
    ])
    expect(r).toEqual([])
  })

  it('Equivalente no se aplica a un botón sin etiqueta', () => {
    const r = objetivosIncumplidos([obj('button.a', 0, 0, 16, 16), obj('a.pegado', 0, 18, 100, 30)])
    expect(r.length).toBe(1)
  })

  it('un enlace en línea dentro de una frase está exento', () => {
    expect(objetivosIncumplidos([obj('a.frase', 0, 0, 60, 18, { enLinea: true }), obj('a.otro', 0, 20, 60, 18)]).length).toBe(1)
    // sólo el otro (no en línea) queda; el primero se exime
  })

  it('el mensaje trae el tamaño medido y el motivo, para que se pueda actuar', () => {
    const [f] = objetivosIncumplidos([obj('button.a', 0, 0, 16, 16), obj('button.b', 20, 0, 16, 16)])
    expect(f).toMatchObject({ selector: 'button.a', ancho: 16, alto: 16 })
    expect(f.motivo).toMatch(/espaci|círculo/i)
  })
})
