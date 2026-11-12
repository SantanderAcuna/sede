/**
 * Segundo saneado del cuerpo de una noticia, en el sitio. El servidor ya sanea al guardar
 * y al entregar; esto es una segunda defensa INDEPENDIENTE (otra biblioteca, otro código):
 * si una falla, la otra sigue en pie.
 */
import { describe, expect, it } from 'vitest'

import { sanitizarHtmlDeNoticia } from '../app/utils/sanitizar-html'

const UUID = '0b9e0a5e-3a52-4c43-9f0e-5a0f4a4e2c11'

const ataques: Array<[string, string]> = [
  ['script', '<p>a</p><script>alert(1)</script>'],
  ['onerror', '<img src="x" onerror="alert(1)">'],
  ['onclick', '<p onclick="alert(1)">a</p>'],
  ['javascript href', '<a href="javascript:alert(1)">x</a>'],
  ['javascript mayúsculas', '<a href="JaVaScRiPt:alert(1)">x</a>'],
  ['javascript con tab', '<a href="java\tscript:alert(1)">x</a>'],
  ['javascript con entidad', '<a href="&#106;avascript:alert(1)">x</a>'],
  ['javascript con espacio', '<a href=" javascript:alert(1)">x</a>'],
  ['data html', '<a href="data:text/html,<script>alert(1)</script>">x</a>'],
  ['vbscript', '<a href="vbscript:msgbox(1)">x</a>'],
  ['svg onload', '<svg onload="alert(1)"><circle/></svg>'],
  ['svg script', '<svg><script>alert(1)</script></svg>'],
  ['iframe', '<iframe src="https://evil.test"></iframe>'],
  ['object', '<object data="x.swf"></object>'],
  ['embed', '<embed src="x.swf">'],
  ['form', '<form action="https://evil.test"><input name="p"></form>'],
  ['style tag', '<style>body{display:none}</style><p>a</p>'],
  ['style attr', '<p style="background:url(javascript:alert(1))">a</p>'],
  ['meta refresh', '<meta http-equiv="refresh" content="0;url=https://evil.test">'],
  ['base', '<base href="https://evil.test/">'],
  ['link', '<link rel="stylesheet" href="https://evil.test/x.css">'],
  ['mxss noscript', '<noscript><p title="</noscript><img src=x onerror=alert(1)>"></noscript>'],
  ['mxss math', '<math><mtext><table><mglyph><style><img src=x onerror=alert(1)>'],
  ['mxss svg style', '<svg></p><style><a id="</style><img src=1 onerror=alert(1)>">'],
  ['img javascript', '<img src="javascript:alert(1)">'],
  ['img data svg', '<img src="data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=">'],
  ['comentario', '<!--[if IE]><script>alert(1)</script><![endif]-->'],
  ['data-atributo', '<p data-x="1">a</p>'],
  ['textarea', '<textarea><script>alert(1)</script></textarea>'],
]

const PROHIBIDO = ['<script', 'onerror', 'onload', 'onclick', 'javascript:', 'vbscript:', '<iframe', '<object', '<embed', '<form', '<style', '<meta', '<base', '<link', '<svg', '<math', 'style=', 'data-', 'data:text', 'data:image/svg', '<!--', '<textarea']

describe('sanitizarHtmlDeNoticia — corpus hostil', () => {
  it.each(ataques)('%s no deja nada ejecutable', (_nombre, entrada) => {
    const salida = sanitizarHtmlDeNoticia(entrada).toLowerCase()
    for (const p of PROHIBIDO) expect(salida, `quedó «${p}» en: ${salida}`).not.toContain(p)
  })
})

describe('sanitizarHtmlDeNoticia — lo permitido', () => {
  it('conserva el formato del editor', () => {
    const entrada = '<h2>Título</h2><h3>Sub</h3><p>Un <strong>texto</strong> con <em>énfasis</em> y <a href="https://santamarta.gov.co">enlace</a>.</p><ul><li>uno</li></ul><ol><li>a</li></ol><blockquote><p>cita</p></blockquote>'
    const salida = sanitizarHtmlDeNoticia(entrada)
    for (const e of ['<h2>Título</h2>', '<h3>Sub</h3>', '<strong>texto</strong>', '<em>énfasis</em>', '<ul>', '<ol>', '<blockquote>', 'href="https://santamarta.gov.co"']) {
      expect(salida).toContain(e)
    }
  })

  it('fuerza rel seguro en todos los enlaces', () => {
    const salida = sanitizarHtmlDeNoticia('<a href="https://a.test" rel="opener" target="_blank">x</a>')
    expect(salida).toContain('rel="noopener noreferrer nofollow"')
    expect(salida).not.toContain('rel="opener"')
  })

  it('admite mailto y tel; no admite ftp ni file', () => {
    expect(sanitizarHtmlDeNoticia('<a href="mailto:a@b.co">x</a>')).toContain('mailto:a@b.co')
    expect(sanitizarHtmlDeNoticia('<a href="tel:+576054211000">x</a>')).toContain('tel:+576054211000')
    expect(sanitizarHtmlDeNoticia('<a href="ftp://x.test/a">x</a>')).not.toContain('ftp:')
    expect(sanitizarHtmlDeNoticia('<a href="file:///etc/passwd">x</a>')).not.toContain('file:')
  })

  it('solo deja imágenes propias del contenido y con alt', () => {
    const buena = `<img src="/api/v1/contenido/imagenes/${UUID}" alt="Alcalde">`
    expect(sanitizarHtmlDeNoticia(buena)).toContain(`src="/api/v1/contenido/imagenes/${UUID}"`)
    for (const mala of [
      '<img src="https://evil.test/p.png" alt="x">',
      `<img src="https://evil.test/api/v1/contenido/imagenes/${UUID}" alt="x">`,
      `<img src="//evil.test/api/v1/contenido/imagenes/${UUID}" alt="x">`,
      `<img src="/api/v1/contenido/imagenes/${UUID}">`,
      `<img src="/api/v1/contenido/imagenes/${UUID}" alt="">`,
      `<img src="/api/v1/contenido/imagenes/../../x" alt="x">`,
      `<img src="/api/v1/contenido/imagenes/${UUID}?x=1" alt="x">`,
    ]) {
      expect(sanitizarHtmlDeNoticia(mala), mala).not.toContain('<img')
    }
  })

  it('es idempotente y tolera vacío', () => {
    const una = sanitizarHtmlDeNoticia('<p>a <a href="https://a.test">b</a></p><script>x</script>')
    expect(sanitizarHtmlDeNoticia(una)).toBe(una)
    expect(sanitizarHtmlDeNoticia('')).toBe('')
  })

  it('el texto con símbolos se conserva escapado', () => {
    expect(sanitizarHtmlDeNoticia('<p>a &amp; b &lt; c</p>')).toBe('<p>a &amp; b &lt; c</p>')
  })
})
