/**
 * Rutas públicas de la Sede Electrónica — fuente única.
 *
 * Este módulo es el **único** sitio donde se declara qué páginas existen, cómo se
 * llaman y si ya publican contenido. Lo consumen tres artefactos que antes
 * llevaban listas paralelas y divergían entre sí:
 *
 *  - `layouts/default.vue` — el menú de navegación (RF-B1-003, CAG-09).
 *  - `pages/mapa-del-sitio.vue` — el mapa HTML (RF-B1-010).
 *  - `server/routes/sitemap.xml.ts` — el mapa XML (RF-B1-010, RF-B3-044).
 *
 * **Por qué importa el campo `publicada`.** No es decorativo: es el dato que
 * decide si una dirección se anuncia a los buscadores. Anunciar una página que
 * sólo dice «en preparación» es peor que no anunciarla —el ciudadano llega desde
 * Google y encuentra un aviso, y la Entidad afirma ante el índice que publica lo
 * que no publica—, así que el `sitemap.xml` filtra por este campo y el mapa HTML
 * lo usa para marcar «(en preparación)». Cuando una sección se publique de
 * verdad se cambia aquí, y los tres artefactos quedan de acuerdo.
 *
 * Los rótulos siguen dos formas a propósito: `etiqueta` es el nombre completo
 * que se publica en el mapa del sitio, y `etiquetaMenu` el rótulo corto que cabe
 * en la barra de navegación. Cuando no hay rótulo corto se usa el completo.
 */

/** Una ruta individual pública. */
export interface RutaPublica {
  /** Ruta URL, siempre absoluta y en castellano (p. ej. `/transparencia`). */
  ruta: string
  /** Nombre completo de la página, como se publica en el mapa del sitio. */
  etiqueta: string
  /** Rótulo corto para el menú, cuando el completo no cabe. */
  etiquetaMenu?: string
  /**
   * `true` sólo cuando la página publica contenido real hoy. Es lo que decide
   * qué se anuncia en el `sitemap.xml` y qué se marca «(en preparación)».
   */
  publicada: boolean
  /** Orden de aparición (menor primero). */
  orden: number
  /** Frecuencia de cambio declarada al buscador. */
  frecuenciaSitemap?: 'daily' | 'weekly' | 'monthly' | 'yearly'
  /** Prioridad relativa en el sitemap (0–1). */
  prioridadSitemap?: number
}

/** Un grupo temático de rutas para el menú. */
export interface SeccionMenu {
  /** Título del grupo, usado en el menú. */
  titulo: string
  /** Ruta del grupo, si tiene página propia. */
  ruta?: string
  /** Rutas que pertenecen a este grupo. */
  enlaces: RutaPublica[]
  /** Si el grupo publica contenido real hoy. */
  publicado: boolean
}

/** Configuración completa de rutas del sitio. */
export interface ConfigRutas {
  secciones: SeccionMenu[]
  rutasSueltas: RutaPublica[]
}

/** Una opción del menú principal. */
export interface ItemMenu {
  etiqueta: string
  /** Ausente cuando la opción sólo despliega subsecciones. */
  ruta?: string
  subsecciones?: { titulo: string; enlaces: { etiqueta: string; ruta: string }[] }[]
}

/**
 * Estado de publicación de cada ruta, a fecha de hoy.
 *
 * Se declara aparte y con nombre para que el cambio de estado sea un acto
 * deliberado: publicar una sección consiste en cambiar un `false` por un `true`
 * en esta tabla, y eso mueve a la vez el menú, el mapa HTML y el `sitemap.xml`.
 *
 * Hoy publican contenido real: la portada, el catálogo de trámites y su ficha,
 * la página de PQRSD y el formulario de petición, los canales de atención, la
 * normativa, el mapa del sitio, la declaración de accesibilidad y la portada de
 * Participa. Todo lo demás existe, explica qué publicará y **no** se anuncia
 * como publicado.
 */
const PUBLICADAS = {
  inicio: true,
  transparencia: false,
  atencion: true,
  tramites: true,
  pqrsd: true,
  realizarPeticion: true,
  seguimiento: false,
  noticias: false,
  portales: false,
  servicios: false,
  normativa: true,
  buscar: false,
  mapaDelSitio: true,
  accesibilidad: true,
  participa: true,
} as const

/**
 * Las seis subcategorías de Participa que fija el §4.1.2.3 del Anexo 2 de la
 * Resolución 2893 de 2020.
 *
 * Ninguna publica contenido todavía: las seis son páginas que declaran qué
 * publicarán. Marcarlas como publicadas habría hecho que el `sitemap.xml` las
 * anunciara como secciones con información (regresión R-03).
 */
export const SUBCATEGORIAS_PARTICIPA: RutaPublica[] = [
  {
    ruta: '/participa/identificacion-de-problemas',
    etiqueta:
      'Participación para la identificación de problemas y diagnóstico de necesidades',
    publicada: false,
    orden: 1,
    prioridadSitemap: 0.6,
    frecuenciaSitemap: 'monthly',
  },
  {
    ruta: '/participa/presupuesto-participativo',
    etiqueta: 'Planeación y/o presupuesto participativo',
    publicada: false,
    orden: 2,
    prioridadSitemap: 0.6,
    frecuenciaSitemap: 'monthly',
  },
  {
    ruta: '/participa/consulta-ciudadana',
    etiqueta:
      'Participación y consulta ciudadana de proyectos, normas, políticas o programas',
    publicada: false,
    orden: 3,
    prioridadSitemap: 0.6,
    frecuenciaSitemap: 'monthly',
  },
  {
    ruta: '/participa/innovacion-abierta',
    etiqueta: 'Colaboración e innovación abierta',
    publicada: false,
    orden: 4,
    prioridadSitemap: 0.6,
    frecuenciaSitemap: 'monthly',
  },
  {
    ruta: '/participa/rendicion-de-cuentas',
    etiqueta: 'Rendición de cuentas',
    publicada: false,
    orden: 5,
    prioridadSitemap: 0.6,
    frecuenciaSitemap: 'monthly',
  },
  {
    ruta: '/participa/control-ciudadano',
    etiqueta: 'Control ciudadano',
    publicada: false,
    orden: 6,
    prioridadSitemap: 0.6,
    frecuenciaSitemap: 'monthly',
  },
]

const SECCIONES: SeccionMenu[] = [
  {
    titulo: 'Participa',
    ruta: '/participa',
    publicado: PUBLICADAS.participa,
    enlaces: SUBCATEGORIAS_PARTICIPA,
  },
]

const RUTAS_SUELTAS: RutaPublica[] = [
  {
    ruta: '/',
    etiqueta: 'Sede Electrónica',
    etiquetaMenu: 'Inicio',
    publicada: PUBLICADAS.inicio,
    orden: 0,
    prioridadSitemap: 1.0,
    frecuenciaSitemap: 'daily',
  },
  {
    ruta: '/transparencia',
    etiqueta: 'Transparencia y acceso a la información pública',
    etiquetaMenu: 'Transparencia',
    publicada: PUBLICADAS.transparencia,
    orden: 1,
    prioridadSitemap: 0.9,
    frecuenciaSitemap: 'weekly',
  },
  {
    ruta: '/atencion',
    etiqueta: 'Canales de atención y sedes',
    etiquetaMenu: 'Canales de atención',
    publicada: PUBLICADAS.atencion,
    orden: 2,
    prioridadSitemap: 0.8,
    frecuenciaSitemap: 'monthly',
  },
  {
    ruta: '/tramites',
    etiqueta: 'Trámites y servicios',
    publicada: PUBLICADAS.tramites,
    orden: 3,
    prioridadSitemap: 0.9,
    frecuenciaSitemap: 'weekly',
  },
  {
    ruta: '/pqrsd',
    etiqueta: 'Peticiones, quejas, reclamos, sugerencias y denuncias',
    etiquetaMenu: 'PQRSD',
    publicada: PUBLICADAS.pqrsd,
    orden: 4,
    prioridadSitemap: 0.8,
    frecuenciaSitemap: 'monthly',
  },
  {
    ruta: '/realizar-una-peticion',
    etiqueta: 'Realizar una petición',
    publicada: PUBLICADAS.realizarPeticion,
    orden: 5,
    prioridadSitemap: 0.8,
    frecuenciaSitemap: 'monthly',
  },
  {
    ruta: '/seguimiento',
    etiqueta: 'Seguimiento de una solicitud',
    publicada: PUBLICADAS.seguimiento,
    orden: 6,
    prioridadSitemap: 0.7,
    frecuenciaSitemap: 'monthly',
  },
  {
    ruta: '/noticias',
    etiqueta: 'Noticias',
    publicada: PUBLICADAS.noticias,
    orden: 7,
    prioridadSitemap: 0.7,
    frecuenciaSitemap: 'daily',
  },
  {
    ruta: '/portales',
    etiqueta: 'Portales de programas transversales',
    publicada: PUBLICADAS.portales,
    orden: 8,
    prioridadSitemap: 0.6,
    frecuenciaSitemap: 'monthly',
  },
  {
    ruta: '/servicios',
    etiqueta: 'Servicios a la Ciudadanía',
    publicada: PUBLICADAS.servicios,
    orden: 9,
    prioridadSitemap: 0.6,
    frecuenciaSitemap: 'monthly',
  },
  {
    ruta: '/normativa',
    etiqueta: 'Normativa',
    publicada: PUBLICADAS.normativa,
    orden: 10,
    prioridadSitemap: 0.7,
    frecuenciaSitemap: 'weekly',
  },
  {
    ruta: '/buscar',
    etiqueta: 'Buscar en la Sede',
    publicada: PUBLICADAS.buscar,
    orden: 11,
    prioridadSitemap: 0.3,
    frecuenciaSitemap: 'monthly',
  },
  {
    ruta: '/mapa-del-sitio',
    etiqueta: 'Mapa del sitio',
    publicada: PUBLICADAS.mapaDelSitio,
    orden: 12,
    prioridadSitemap: 0.4,
    frecuenciaSitemap: 'monthly',
  },
  {
    ruta: '/accesibilidad',
    etiqueta: 'Declaración de accesibilidad',
    publicada: PUBLICADAS.accesibilidad,
    orden: 13,
    prioridadSitemap: 0.5,
    frecuenciaSitemap: 'yearly',
  },
]

/**
 * Las cinco políticas obligatorias del pie (SEG-006).
 *
 * **Esta lista es la única.** Los rótulos y los slugs se declaran aquí y los
 * consumen los tres sitios que antes los repetían —y que ya habían divergido—:
 * el pie (`PiePaginaGovco.vue`), la página de cada política
 * (`pages/politicas/[slug].vue`) y el `sitemap.xml`. Lo que cada política
 * *dice* sigue viviendo en su página; lo que no puede vivir en tres sitios es su
 * nombre y su dirección (D-31).
 *
 * Ninguna publica todavía su documento: el pie lo advierte y aquí se declaran
 * como no publicadas, que es lo que impide anunciarlas al buscador.
 */
export const POLITICAS: { slug: string; etiqueta: string }[] = [
  { slug: 'terminos-y-condiciones-de-uso', etiqueta: 'Términos y condiciones de uso' },
  { slug: 'seguridad-y-privacidad', etiqueta: 'Seguridad y privacidad' },
  {
    slug: 'proteccion-y-tratamiento-de-datos-personales',
    etiqueta: 'Protección y tratamiento de datos personales',
  },
  { slug: 'uso-de-cookies', etiqueta: 'Uso de cookies' },
  {
    slug: 'derechos-de-autor-y-uso-sobre-contenidos',
    etiqueta: 'Derechos de autor y uso sobre contenidos',
  },
]

/** Índice por ruta, para resolver rótulos del menú sin repetirlos. */
const POR_RUTA = new Map<string, RutaPublica>()
for (const ruta of [...RUTAS_SUELTAS, ...SECCIONES.flatMap((seccion) => seccion.enlaces)]) {
  POR_RUTA.set(ruta.ruta, ruta)
}

/** El rótulo corto de una ruta registrada; si no está, la ruta tal cual. */
function etiquetaMenuDe(ruta: string): string {
  const registrada = POR_RUTA.get(ruta)
  return registrada?.etiquetaMenu ?? registrada?.etiqueta ?? ruta
}

/**
 * El menú principal de la Sede.
 *
 * El orden no es estético: lo fija el Anexo 2 §4.1.2. Los tres menús mínimos
 * obligatorios son **Transparencia, Servicios a la Ciudadanía y Participa**, y
 * las opciones adicionales «deberán estar ubicadas después de los tres menús
 * mínimos obligatorios»; por eso PQRSD, Normativa y Noticias van detrás de
 * Participa. Son siete en total, que es el tope de FUN-012 y CAG-09.
 *
 * La estructura se declara aquí —y no en la disposición— porque el menú es
 * contenido normado y porque así el menú, el mapa y el `sitemap.xml` no pueden
 * discrepar ni en nombres ni en destinos (RF-B1-010).
 */
export const menuPrincipal: ItemMenu[] = [
  { etiqueta: etiquetaMenuDe('/'), ruta: '/' },
  { etiqueta: etiquetaMenuDe('/transparencia'), ruta: '/transparencia' },
  {
    /*
     * **El rótulo es el de la norma, no uno propio.**
     *
     * RF-B1-003 del Anexo 2.1 exige el menú «Inicio, Transparencia y acceso a
     * la información pública, Atención y Servicios a la Ciudadanía, Participa»,
     * y la matriz de trazabilidad del proyecto resolvió el conflicto C-04
     * declarando la norma prevalente. La Sede decía «Atención al ciudadano»: un
     * nombre más corto, pero no el que la norma manda.
     */
    etiqueta: 'Atención y Servicios a la Ciudadanía',
    subsecciones: [
      {
        titulo: 'Atención y Servicios a la Ciudadanía',
        enlaces: [
          { etiqueta: etiquetaMenuDe('/tramites'), ruta: '/tramites' },
          { etiqueta: etiquetaMenuDe('/atencion'), ruta: '/atencion' },
          { etiqueta: etiquetaMenuDe('/realizar-una-peticion'), ruta: '/realizar-una-peticion' },
          { etiqueta: etiquetaMenuDe('/seguimiento'), ruta: '/seguimiento' },
        ],
      },
    ],
  },
  {
    etiqueta: 'Participa',
    subsecciones: [
      {
        titulo: 'Participa',
        enlaces: SUBCATEGORIAS_PARTICIPA.map((subcategoria) => ({
          etiqueta: etiquetaMenuDe(subcategoria.ruta),
          ruta: subcategoria.ruta,
        })),
      },
    ],
  },
  { etiqueta: etiquetaMenuDe('/pqrsd'), ruta: '/pqrsd' },
  { etiqueta: etiquetaMenuDe('/normativa'), ruta: '/normativa' },
  { etiqueta: etiquetaMenuDe('/noticias'), ruta: '/noticias' },
]

export const configRutas: ConfigRutas = {
  secciones: SECCIONES,
  rutasSueltas: RUTAS_SUELTAS,
}

/**
 * La lista plana de todas las rutas públicas, con su estado de publicación.
 *
 * El consumidor decide qué hacer con `publicada`: el `sitemap.xml` filtra y el
 * mapa HTML lo muestra como «(en preparación)». Los dos leen el mismo dato, que
 * es lo que evita que uno anuncie lo que el otro calla.
 */
export function todasLasRutas(): RutaPublica[] {
  const rutas: RutaPublica[] = [...RUTAS_SUELTAS]

  for (const seccion of SECCIONES) {
    if (seccion.ruta) {
      rutas.push({
        ruta: seccion.ruta,
        etiqueta: seccion.titulo,
        publicada: seccion.publicado,
        orden: rutas.length,
        prioridadSitemap: 0.8,
        frecuenciaSitemap: 'monthly',
      })
    }
    rutas.push(...seccion.enlaces)
  }

  for (const politica of POLITICAS) {
    rutas.push({
      ruta: `/politicas/${politica.slug}`,
      etiqueta: politica.etiqueta,
      // Ninguna política publica todavía su documento: el pie lo dice, y el
      // buscador no debe recibirlas como publicadas (D-10, R-03).
      publicada: false,
      orden: rutas.length,
      prioridadSitemap: 0.4,
      frecuenciaSitemap: 'yearly',
    })
  }

  return rutas
}
