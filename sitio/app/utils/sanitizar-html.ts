/**
 * Segundo saneado del cuerpo de una noticia (defensa en profundidad).
 *
 * El servidor ya sanea al guardar y al entregar. Aquí se vuelve a sanear con OTRA
 * biblioteca y OTRO código, a propósito: si una falla (un aviso nuevo, una errata en su
 * configuración), la otra sigue en pie. Lista blanca idéntica a la del servidor y a la
 * del editor; todo lo demás se descarta, incluido el contenido de `<script>`, `<style>`
 * y `<textarea>`.
 */
import sanitizeHtml from 'sanitize-html'

const IMAGEN_PROPIA = /^\/api\/v1\/contenido\/imagenes\/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/

const OPCIONES: sanitizeHtml.IOptions = {
  allowedTags: ['h2', 'h3', 'p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'blockquote', 'a', 'img'],
  allowedAttributes: {
    a: ['href', 'target', 'rel'],
    img: ['src', 'alt'],
  },
  allowedSchemes: ['https', 'http', 'mailto', 'tel'],
  allowedSchemesByTag: { img: [] },
  allowProtocolRelative: false,
  disallowedTagsMode: 'discard',
  parseStyleAttributes: false,
  transformTags: {
    a: (_etiqueta, atributos) => ({
      tagName: 'a',
      attribs: { ...atributos, rel: 'noopener noreferrer nofollow' },
    }),
  },
  // Una imagen solo se queda si es de las que sube el panel y trae su descripción.
  exclusiveFilter: (marco) =>
    marco.tag === 'img' &&
    !(IMAGEN_PROPIA.test((marco.attribs.src ?? '').trim()) && (marco.attribs.alt ?? '').trim() !== ''),
}

export function sanitizarHtmlDeNoticia(html: string): string {
  if (html.trim() === '') return ''
  return sanitizeHtml(html, OPCIONES)
}
