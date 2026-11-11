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
