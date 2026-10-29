/**
 * Las cinco políticas del sitio tal como las entrega la API (T-20, H-21).
 *
 * **El texto ya no vive en el repositorio.** Antes este archivo guardaba un
 * `null` por política hasta que Jurídica entregara el documento; ahora el texto
 * vive en base de datos (`GET /politicas/{slug}`) y se edita desde el panel, con
 * su versión, su vigencia y su acto administrativo. Cambiar una política es una
 * edición en el panel, no un despliegue.
 *
 * **Lo que el servidor entrega hoy es texto de ejemplo**, y la respuesta lo dice
 * (`es_ejemplo: true`): lo redactó TIC como punto de partida y lleva marcadores
 * «[por definir: …]» donde falta un dato institucional. No es texto legal
 * adoptado. Cuando Jurídica lo apruebe y se registre su acto, se quita la marca
 * desde el panel y la página deja de avisarlo.
 *
 * Aquí quedan los tipos y la lectura defensiva de la respuesta: la API es una
 * frontera, y una página pública no debe romperse porque llegue un campo vacío.
 */

/** Una sección del documento: el `id` es el componente mínimo que cubre. */
export interface SeccionPolitica {
  id: string
  titulo: string
  parrafos: string[]
}

/** El documento oficial adjunto desde el panel (acto administrativo o texto aprobado). */
export interface DocumentoOficial {
  nombre: string
  tipo: string
  tamano: number
  /** Dirección estable, relativa al sitio. */
  url: string
}

/** Una política, lista para pintar. */
export interface PoliticaDelSitio {
  slug: string
  nombre: string
  version: number
  /** `AAAA-MM-DD` o `null` si aún no hay vigencia. */
  vigenteDesde: string | null
  acto: { tipo: string | null; numero: string | null; fecha: string | null }
  /** Verdadero mientras el texto sea de ejemplo, pendiente de aprobación por la Entidad. */
  esEjemplo: boolean
  documentoOficial: DocumentoOficial | null
  secciones: SeccionPolitica[]
}

const cadena = (valor: unknown): string | null =>
  typeof valor === 'string' && valor.trim() !== '' ? valor : null

/** Sólo direcciones del propio sitio: nunca se enlaza algo que la API invente fuera de él. */
const esRutaDelSitio = (valor: unknown): valor is string =>
  typeof valor === 'string' && /^\/politicas\/[a-z0-9-]+\/documento$/.test(valor)

/**
 * Lee la respuesta de `GET /politicas/{slug}` con tolerancia: lo que no
 * reconoce se descarta y, si falta lo esencial (slug, nombre, versión), devuelve
 * `null` para que la página muestre el aviso de error en vez de una política
 * rota.
 */
export function politicaDesdeApi(crudo: unknown): PoliticaDelSitio | null {
  if (typeof crudo !== 'object' || crudo === null) return null
  const datos = crudo as Record<string, unknown>

  const slug = cadena(datos.slug)
  const nombre = cadena(datos.nombre)
  const version = typeof datos.version === 'number' && Number.isInteger(datos.version) ? datos.version : null
  if (slug === null || nombre === null || version === null || version < 1) return null

  const acto = (typeof datos.acto === 'object' && datos.acto !== null ? datos.acto : {}) as Record<string, unknown>

  const secciones: SeccionPolitica[] = []
  if (Array.isArray(datos.secciones)) {
    for (const bruta of datos.secciones) {
      if (typeof bruta !== 'object' || bruta === null) continue
      const seccion = bruta as Record<string, unknown>
      const id = cadena(seccion.id)
      const titulo = cadena(seccion.titulo)
      if (id === null || titulo === null) continue
      const parrafos = Array.isArray(seccion.parrafos)
        ? seccion.parrafos.filter((p): p is string => typeof p === 'string' && p.trim() !== '')
        : []
      secciones.push({ id, titulo, parrafos })
    }
  }

  const oficial = datos.documento_oficial
  let documentoOficial: DocumentoOficial | null = null
  if (typeof oficial === 'object' && oficial !== null) {
    const o = oficial as Record<string, unknown>
    if (esRutaDelSitio(o.url)) {
      documentoOficial = {
        nombre: cadena(o.nombre) ?? 'Documento oficial',
        tipo: cadena(o.tipo) ?? 'application/octet-stream',
        tamano: typeof o.tamano === 'number' ? o.tamano : 0,
        url: o.url,
      }
    }
  }

  return {
    slug,
    nombre,
    version,
    vigenteDesde: cadena(datos.vigente_desde),
    acto: { tipo: cadena(acto.tipo), numero: cadena(acto.numero), fecha: cadena(acto.fecha) },
    // Si la API no lo dice, se trata como ejemplo: avisar de más es el error barato.
    esEjemplo: datos.es_ejemplo !== false,
    documentoOficial,
    secciones,
  }
}

/** `2026-10-29` → «29 de octubre de 2026». Una fecha ilegible se muestra tal cual. */
export function fechaLarga(iso: string): string {
  const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso)
  if (m === null) return iso
  const fecha = new Date(Date.UTC(Number(m[1]), Number(m[2]) - 1, Number(m[3])))
  return new Intl.DateTimeFormat('es-CO', { dateStyle: 'long', timeZone: 'UTC' }).format(fecha)
}

/** El acto administrativo en una línea («Resolución 123 del 10 de enero de 2026»), o `null` si no hay. */
export function actoEnUnaLinea(acto: PoliticaDelSitio['acto']): string | null {
  const partes = [acto.tipo, acto.numero].filter((p): p is string => p !== null)
  if (partes.length === 0) return null
  const base = partes.join(' ')
  return acto.fecha === null ? base : `${base} del ${fechaLarga(acto.fecha)}`
}

/** Tamaño legible: «182 KB», «1,4 MB». */
export function tamanoLegible(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  const kb = bytes / 1024
  if (kb < 1024) return `${Math.round(kb)} KB`
  return `${(kb / 1024).toLocaleString('es-CO', { maximumFractionDigits: 1 })} MB`
}

/**
 * Los slugs de las políticas que ya no son de ejemplo, a partir de la respuesta de
 * `GET /politicas`. Sólo esos se anuncian en el `sitemap.xml`: un texto de ejemplo
 * no debe indexarse como si fuera la política oficial de la Entidad (R-03).
 *
 * Es estricta a propósito: `es_ejemplo` tiene que ser exactamente `false`. Una
 * respuesta rara, vacía o sin el campo no anuncia nada.
 */
export function slugsAdoptados(crudo: unknown, conocidos: readonly string[]): string[] {
  if (typeof crudo !== 'object' || crudo === null) return []
  const lista = (crudo as { data?: unknown }).data
  if (!Array.isArray(lista)) return []

  const adoptados: string[] = []
  for (const elemento of lista) {
    if (typeof elemento !== 'object' || elemento === null) continue
    const { slug, es_ejemplo: esEjemplo } = elemento as Record<string, unknown>
    if (typeof slug === 'string' && esEjemplo === false && conocidos.includes(slug)) adoptados.push(slug)
  }
  return adoptados
}
