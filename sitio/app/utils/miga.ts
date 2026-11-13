/**
 * Miga de pan, derivada de la ruta (CAG-11).
 *
 * # Por qué existe este módulo
 *
 * La miga vivía en la disposición y llamaba «Noticias» al último nivel de
 * `/noticias/mi-noticia`, porque el prefijo más largo del menú que coincide es
 * `/noticias`: la ficha mostraba «Inicio > Noticias > Noticias» (medido en staging,
 * 2026-11-11; igual en trámites). El último nivel debe ser la página en la que se está.
 *
 * Una página no puede fijar ese texto desde dentro: la disposición dibuja la miga
 * **antes** de que la página termine de cargar su dato, y en el servidor eso deja el
 * HTML con el texto viejo y a la hidratación con otro. Por eso el título se lee antes
 * de dibujar nada (middleware `ficha-miga.global.ts`), con **la misma lectura que usa
 * la página**: no cuesta una consulta más a la API.
 */

export interface NivelMiga {
  etiqueta: string
  ruta: string
}

/** El título de la página en la que se está, y la ruta a la que pertenece. */
export interface TituloDePagina {
  ruta: string
  texto: string
}

export interface OpcionesMiga {
  /** Todos los destinos del menú, en un solo nivel. */
  destinos: { ruta: string; etiqueta: string }[]
  /** El nombre de una página que no está en el menú pero sí registrada (p. ej. `/politicas`). */
  fueraDelMenu: (ruta: string) => string | undefined
  /** El título de la página actual, si ya se conoce. Sólo vale si es de esta misma ruta. */
  titulo?: TituloDePagina | null
}

/**
 * El nombre de la sección a la que pertenece una ruta: el prefijo más largo del menú
 * que coincida, descartando `/` para que no se lea como prefijo de todo.
 */
function nombreDeLaSeccion(destino: string, destinos: OpcionesMiga['destinos']): string {
  const candidatos = destinos
    .filter((d) => d.ruta !== '/' && (destino === d.ruta || destino.startsWith(`${d.ruta}/`)))
    .sort((a, b) => b.ruta.length - a.ruta.length)
  return candidatos[0]?.etiqueta ?? ''
}

export function construirMiga(path: string, opciones: OpcionesMiga): NivelMiga[] {
  // En la portada no hay recorrido que mostrar.
  if (path === '/') return []

  const niveles: NivelMiga[] = [{ etiqueta: 'Inicio', ruta: '/' }]

  const segmentos = path.split('/').filter(Boolean)
  segmentos.forEach((segmento, indice) => {
    const destino = `/${segmentos.slice(0, indice + 1).join('/')}`
    const esElUltimo = indice === segmentos.length - 1
    const enElMenu = opciones.destinos.some((d) => d.ruta === destino)
    const registrada = opciones.fueraDelMenu(destino)

    // El título de la página sólo sustituye al nombre heredado de la sección, y sólo si
    // es de esta misma ruta: no se arrastra el de una página anterior.
    if (esElUltimo && !enElMenu && registrada === undefined && opciones.titulo?.ruta === destino) {
      niveles.push({ etiqueta: opciones.titulo.texto, ruta: destino })
      return
    }

    const delMenu = nombreDeLaSeccion(destino, opciones.destinos)
    // Si el menú no lo conoce —una subcategoría, un detalle— se deriva del segmento,
    // que es lo único que queda sin inventarse un nombre.
    const etiqueta =
      delMenu || registrada || (segmento.charAt(0).toUpperCase() + segmento.slice(1)).replace(/-/g, ' ')
    niveles.push({ etiqueta, ruta: destino })
  })

  return niveles
}

export type SeccionConFicha = 'noticias' | 'tramites' | 'politicas'

export interface FichaDeRuta {
  seccion: SeccionConFicha
  /** El slug ya decodificado, como lo entrega el enrutador. */
  slug: string
  /** La clave de `useAsyncData` que usa la página: así una sola lectura sirve a las dos. */
  clave: string
  /** La ruta de la API, relativa a la base (`/noticias/{slug}`), con el slug codificado. */
  endpoint: string
}

const CLAVE_POR_SECCION: Record<SeccionConFicha, string> = { noticias: 'noticia', tramites: 'tramite', politicas: 'politica' }

function decodificar(segmento: string): string {
  try {
    return decodeURIComponent(segmento)
  } catch {
    return segmento
  }
}

/** Si la ruta es la ficha de una noticia, un trámite o una política, de cuál; si no, `null`. */
export function fichaDeRuta(path: string): FichaDeRuta | null {
  const coincide = /^\/(noticias|tramites|politicas)\/([^/]+)$/.exec(path)
  if (!coincide) return null

  const seccion = coincide[1] as SeccionConFicha
  const slug = decodificar(coincide[2] ?? '')
  return {
    seccion,
    slug,
    clave: `${CLAVE_POR_SECCION[seccion]}-${slug}`,
    endpoint: `/${seccion}/${encodeURIComponent(slug)}`,
  }
}

/** El título que la ficha ya trae: la noticia su `titulo`, el trámite su `nombre`. Sin dato válido, `null`. */
export function tituloDeFicha(seccion: SeccionConFicha, respuesta: unknown): string | null {
  const datos = (respuesta as { data?: Record<string, unknown> | null } | null | undefined)?.data
  const valor = datos?.[seccion === 'noticias' ? 'titulo' : 'nombre']
  return typeof valor === 'string' && valor.trim() !== '' ? valor.trim() : null
}
