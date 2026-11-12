/**
 * Diapositivas de la portada a partir de la API de banners.
 *
 * Lo que publica la Entidad sustituye a las diapositivas por defecto sólo si llega completo
 * y seguro. Se descarta toda diapositiva sin texto alternativo (CAG-04) o con una imagen que
 * no sea de la propia API; un enlace que no sea una ruta del sitio o `https://` se quita y la
 * diapositiva se conserva. Si no queda ninguna, se usan las de por defecto: el carrusel nunca
 * queda vacío.
 */
import type { DiapositivaCarrusel } from '~/components/govco/CarruselGovco.vue'

const IMAGEN = /^\/api\/v1\/banners\/[0-9a-f-]{36}\/imagen$/i
const RUTA = /^\/(?!\/)[^\s\\]*$/
const HTTPS = /^https:\/\/[^\s/$.?#].[^\s]*$/i

const esTexto = (v: unknown): v is string => typeof v === 'string'

export function diapositivasDePortada(
  respuesta: { data?: unknown } | null,
  porDefecto: DiapositivaCarrusel[],
): DiapositivaCarrusel[] {
  const datos = respuesta?.data
  if (!Array.isArray(datos)) return porDefecto

  const resultado: DiapositivaCarrusel[] = []
  for (const crudo of datos as unknown[]) {
    if (crudo === null || typeof crudo !== 'object') continue
    const b = crudo as Record<string, unknown>
    const imagen = b.imagen as { url?: unknown; alt?: unknown } | null | undefined
    if (!esTexto(b.uuid) || !esTexto(b.titulo) || b.titulo.trim() === '') continue
    if (!imagen || !esTexto(imagen.url) || !IMAGEN.test(imagen.url)) continue
    if (!esTexto(imagen.alt) || imagen.alt.trim() === '') continue

    const d: DiapositivaCarrusel = { id: b.uuid, imagen: imagen.url, alt: imagen.alt.trim(), titulo: b.titulo.trim() }
    if (esTexto(b.descripcion) && b.descripcion.trim() !== '') d.descripcion = b.descripcion.trim()
    if (esTexto(b.enlace) && (RUTA.test(b.enlace) || HTTPS.test(b.enlace))) d.enlace = b.enlace
    resultado.push(d)
  }
  return resultado.length > 0 ? resultado : porDefecto
}
