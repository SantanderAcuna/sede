/**
 * Metadatos de compartición y URL canónica (D-29).
 *
 * **Qué faltaba.** El sitio no publicaba ni una etiqueta `og:` ni un
 * `<link rel="canonical">`. Al compartir un enlace en WhatsApp, en X o en
 * cualquier red social, la vista previa salía sin título, sin imagen y sin decir
 * de qué sede era; y los buscadores tenían que deducir por su cuenta cuál es la
 * dirección canónica de cada página, que es justo lo que la etiqueta existe para
 * no dejar a criterio de nadie.
 *
 * **Dónde se resuelve.** En un solo sitio: la disposición, que envuelve todas las
 * páginas y se ejecuta también en el servidor —importante, porque quien lee estas
 * etiquetas es un rastreador que no ejecuta JavaScript—. Cada página puede afinar
 * el título llamando a este mismo composable con el suyo.
 *
 * **Lo que no se hace.** No se inventan descripciones. `og:description` sólo se
 * emite cuando la página la declara: escribir veinticinco textos promocionales
 * para que la vista previa quede más bonita es exactamente el tipo de dato
 * inventado que esta auditoría persigue. Sin descripción, la vista previa muestra
 * el título y el nombre de la sede, y las dos cosas son ciertas.
 */
import { computed, toValue, type MaybeRefOrGetter } from 'vue'

import { todasLasRutas } from '~/config/sitemap'

/** Nombre de la sede tal y como aparece en la vista previa. */
const NOMBRE_SITIO = 'Sede Electrónica · Alcaldía Distrital de Santa Marta'

/** Imagen de la vista previa: el logotipo real de la Entidad. */
const IMAGEN_COMPARTICION = '/logo-entidad.png'

interface Metadatos {
  /**
   * Título para la vista previa. Si no llega, se usa el de la configuración.
   * Admite una función porque las páginas que lo saben después de pedir datos
   * —una ficha de trámite, una política— tienen que poder declararlo reactivo.
   */
  titulo?: MaybeRefOrGetter<string | undefined>
  /** Descripción. **Opcional a propósito**: ver la nota del encabezado. */
  descripcion?: MaybeRefOrGetter<string | undefined>
}

export function useMetadatosComparticion(metadatos: Metadatos = {}) {
  const ruta = useRoute()
  const peticion = useRequestURL()

  const configuracion = useRuntimeConfig()
  const dominioDeclarado = String(configuracion.public.dominio ?? '')

  /*
   * El origen sale del dominio declarado cuando existe, y si no, de la propia
   * petición. En producción el servidor va detrás de nginx y el `Host` que le
   * llega puede ser el nombre interno del contenedor: una canónica que anuncie
   * `http://app:3000/` sería peor que no declarar ninguna.
   */
  const origen = computed(() => {
    if (!dominioDeclarado) return `${peticion.protocol}//${peticion.host}`
    const limpio = dominioDeclarado.replace(/\/$/, '')
    // Se admite con o sin esquema, igual que el `robots.txt`: la configuración no
    // debería obligar a recordar en cuál de los dos formatos está escrita.
    return /^https?:\/\//.test(limpio) ? limpio : `https://${limpio}`
  })
  const absoluta = computed(() => new URL(ruta.fullPath, origen.value).href)

  /**
   * El título sale del catálogo de rutas, que es donde está escrito el nombre
   * completo de cada página. Así la vista previa y el mapa del sitio dicen lo
   * mismo, en vez de que cada uno invente su propio rótulo.
   */
  const tituloDeLaRuta = computed(() => {
    const catalogo = todasLasRutas()
    const exacta = catalogo.find((publica) => publica.ruta === ruta.path)
    if (exacta) return exacta.etiqueta
    // Rutas dinámicas (`/tramites/algo`, `/politicas/algo`): vale la sección.
    const seccion = catalogo
      .filter((publica) => publica.ruta !== '/' && ruta.path.startsWith(publica.ruta))
      .sort((a, b) => b.ruta.length - a.ruta.length)[0]
    return seccion?.etiqueta
  })

  const titulo = computed(
    () => toValue(metadatos.titulo) ?? tituloDeLaRuta.value ?? NOMBRE_SITIO,
  )

  /*
   * En la página de error (404) no se declara canónica ni `og:url`: no hay una
   * dirección «buena» que ofrecer, y anunciar como canónica la URL que acaba de
   * fallar es peor que no decir nada. `useError()` devuelve el error mientras se
   * pinta esa página con la disposición.
   *
   * Se llama **aquí**, en el cuerpo del setup, y no dentro del `computed`: los
   * composables de Nuxt que necesitan la instancia no se pueden invocar cuando el
   * `computed` se evalúa más tarde, y hacerlo revienta la página entera con
   * `NUXT_E1001`. Pasó en la primera versión y lo cazó la comprobación de la
   * puerta, no el typecheck.
   */
  const errorDePagina = useError()
  const hayError = computed(() => Boolean(errorDePagina.value))

  useSeoMeta({
    ogType: 'website',
    ogSiteName: NOMBRE_SITIO,
    ogLocale: 'es_CO',
    ogTitle: () => titulo.value,
    ogUrl: () => (hayError.value ? undefined : absoluta.value),
    ogImage: () => `${origen.value}${IMAGEN_COMPARTICION}`,
    ogImageAlt: 'Logotipo de la Alcaldía Distrital de Santa Marta',
    twitterCard: 'summary',
    twitterTitle: () => titulo.value,
  })

  // La descripción sólo se emite si la página la declara (ver el encabezado).
  useSeoMeta({
    description: () => (metadatos.descripcion ? toValue(metadatos.descripcion) : undefined),
    ogDescription: () => (metadatos.descripcion ? toValue(metadatos.descripcion) : undefined),
  })

  useHead({
    link: computed(() =>
      hayError.value ? [] : [{ rel: 'canonical', href: absoluta.value }],
    ),
  })
}
