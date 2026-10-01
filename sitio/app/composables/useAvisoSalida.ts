/**
 * Lógica del aviso de salida a sitio externo (RF-B1-071, RF-01-D03, RN-01-D03).
 *
 * Decide **cuándo** hay que avisar antes de salir de la sede y mantiene el
 * enlace pendiente de confirmación. El modal que lo pregunta es
 * `components/ModalAvisoSalida.vue`; quien detecta el clic es el plugin
 * `plugins/avisoSalida.client.ts`.
 *
 * **Lista blanca de dominios de confianza (RF-01-D03).** Los dominios de esta
 * lista no disparan el aviso porque no son una salida real del ecosistema: el
 * Portal Único, el SECOP, el SUIN, los Servicios Ciudadanos Digitales y la marca
 * país. Avisar de que se sale hacia GOV.CO cuando GOV.CO es el marco de la propia
 * sede sería ruido, y un aviso que aparece siempre deja de leerse.
 *
 * **Qué NO es una salida.** Ni una ruta interna, ni una ancla de la misma página,
 * ni un `mailto:` o un `tel:`. Estaban clasificándose como externos porque su
 * `hostname` está vacío y la comparación era sólo «distinto del dominio de la
 * sede»; ahora se exige que el protocolo sea `http(s)`, que es lo único que
 * puede llevarse al ciudadano a otro sitio.
 */
import { computed, ref } from 'vue'

/** Dominios de confianza que no activan el aviso de salida. */
const DOMINIOS_CONFIANZA = [
  'gov.co',
  'scd.gov.co',
  'secop.gov.co',
  'secopii.gov.co',
  'suin.gov.co',
  'colombia.co',
  'gacol.co',
  // CIIU — Clasificación Industrial Internacional Uniforme (DANE)
  'dane.gov.co',
  // Impuestos y aduanas (DIAN)
  'dian.gov.co',
]

/** Dominio de la propia sede. En el cliente es el que sirve la página. */
const DOMINIO_SEDE = import.meta.client
  ? window.location.hostname.replace(/^www\./, '')
  : 'santamarta.gov.co'

interface EnlaceExterno {
  url: string
  nombre: string
  entidad: string
  /**
   * Si el enlace original abría en pestaña nueva. Se conserva para respetar la
   * intención de quien escribió el enlace: un enlace externo que navega en la
   * misma pestaña debe seguir haciéndolo tras confirmar, y al revés.
   */
  nuevaPestana: boolean
}

const visible = ref(false)
const enlacePendiente = ref<EnlaceExterno | null>(null)
/** Elemento que abrió el aviso: es a quien hay que devolverle el foco al cerrar. */
const origenDelAviso = ref<HTMLElement | null>(null)

/** El dominio de la sede, para poder mostrarlo y compararlo. */
const dominioSede = computed(() => DOMINIO_SEDE)

/** Comprueba si una URL pertenece a un dominio de confianza. */
function esDominioConfianza(url: string): boolean {
  try {
    const hostname = new URL(url).hostname.toLowerCase()
    if (hostname === DOMINIO_SEDE || hostname === `www.${DOMINIO_SEDE}`) return true
    return DOMINIOS_CONFIANZA.some(
      (dominio) => hostname === dominio || hostname.endsWith(`.${dominio}`),
    )
  } catch {
    // URL inválida: se trata como no confiable, pero tampoco se sale a ningún
    // sitio con ella, así que el aviso no llega a mostrarse.
    return false
  }
}

/**
 * Comprueba si una URL saca al ciudadano de la sede.
 *
 * Sólo `http(s)` puede hacerlo: `mailto:`, `tel:`, `sms:` y las anclas no
 * navegan a otro sitio y no deben disparar un aviso de salida.
 */
function esEnlaceExterno(url: string): boolean {
  try {
    const urlObj = new URL(url)
    if (urlObj.protocol !== 'http:' && urlObj.protocol !== 'https:') return false
    const hostname = urlObj.hostname.toLowerCase()
    return hostname !== DOMINIO_SEDE && hostname !== `www.${DOMINIO_SEDE}`
  } catch {
    // Ruta relativa o URL inválida: no es externa.
    return false
  }
}

/** Nombre legible del destino, inferido del hostname. */
function nombreDelDestino(url: string): string {
  try {
    const hostname = new URL(url).hostname.toLowerCase()
    if (hostname.includes('facebook')) return 'Facebook'
    if (hostname.includes('instagram')) return 'Instagram'
    if (hostname.includes('twitter') || hostname.includes('x.com')) return 'X (Twitter)'
    if (hostname.includes('youtube')) return 'YouTube'
    if (hostname.includes('linkedin')) return 'LinkedIn'
    if (hostname.includes('secop')) return 'SECOP — Sistema Electrónico de Contratación Pública'
    if (hostname.includes('suin')) return 'SUIN — Sistema Único de Información Normativa'
    if (hostname === 'www.gov.co' || hostname === 'gov.co') return 'Portal Único del Estado — GOV.CO'
    if (hostname.includes('colombia.co')) return 'Marca País Colombia'
    // Para el resto, el hostname sin el `www.`, que ya es información suficiente.
    return hostname.replace(/^www\./, '')
  } catch {
    return url
  }
}

/**
 * Entidad responsable del destino, inferida del hostname.
 *
 * Se publica porque el ciudadano tiene derecho a saber a quién le está dando sus
 * datos y a quién reclamarle: la Alcaldía no responde por el contenido ni por la
 * privacidad de un sitio ajeno.
 */
function entidadDelDestino(url: string): string {
  try {
    const hostname = new URL(url).hostname.toLowerCase()
    if (hostname.endsWith('gov.co')) return 'Entidad pública del Estado colombiano'
    if (hostname.includes('facebook') || hostname.includes('instagram')) return 'Meta Platforms, Inc.'
    if (hostname.includes('twitter') || hostname.includes('x.com')) return 'X Corp.'
    if (hostname.includes('youtube')) return 'Google LLC'
    if (hostname.includes('linkedin')) return 'LinkedIn Corporation'
    return 'Tercero externo'
  } catch {
    return 'Entidad externa'
  }
}

/**
 * Deja un enlace pendiente de confirmación y abre el aviso.
 *
 * No navega: sólo pregunta. La navegación la hace `confirmarNavegacion()`.
 */
function solicitarConfirmacion(url: string, nuevaPestana = true, origen: HTMLElement | null = null): void {
  enlacePendiente.value = {
    url,
    nombre: nombreDelDestino(url),
    entidad: entidadDelDestino(url),
    nuevaPestana,
  }
  // Quién abrió el aviso, para devolverle el foco al cerrarlo. Se guarda el
  // elemento y no se lee `document.activeElement` al montar: cuando el aviso se
  // cierra, el foco está dentro del diálogo y el disparador ya no se puede
  // deducir (CAG-21).
  origenDelAviso.value = origen
  visible.value = true
}

/**
 * Confirma y navega al destino pendiente.
 *
 * Se respeta si el enlace original abría en pestaña nueva: se replica con
 * `window.open` y `noopener`, o se navega la pestaña actual. `noopener` no es un
 * adorno: sin él, el sitio de destino recibe `window.opener` y puede manipular
 * la pestaña de la sede.
 */
function confirmarNavegacion(): void {
  const pendiente = enlacePendiente.value
  if (pendiente) {
    if (pendiente.nuevaPestana) {
      window.open(pendiente.url, '_blank', 'noopener,noreferrer')
    } else {
      window.location.assign(pendiente.url)
    }
  }
  visible.value = false
  enlacePendiente.value = null
}

/** Cancela: no se navega y el enlace pendiente se descarta. */
function cancelarNavegacion(): void {
  visible.value = false
  enlacePendiente.value = null
  // `origenDelAviso` no se limpia aquí: el modal lo necesita para devolver el
  // foco cuando reacciona al cierre, y borrarlo antes lo dejaría sin destino.
  // Se limpia en la siguiente apertura, que siempre lo vuelve a fijar.
}

export function useAvisoSalida() {
  return {
    visible,
    enlacePendiente,
    origenDelAviso,
    dominioSede,
    solicitarConfirmacion,
    confirmarNavegacion,
    cancelarNavegacion,
    esEnlaceExterno,
    esDominioConfianza,
  }
}
