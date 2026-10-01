/**
 * Plugin cliente: avisa antes de salir a un sitio externo (RF-B1-071).
 *
 * Un único escucha en el `document`, en fase de burbuja, en lugar de un
 * manejador por enlace: así funciona con cualquier `<a>` que se pinte después
 * —los resultados del catálogo, los enlaces del pie, los de una ficha de
 * trámite— sin que nadie tenga que acordarse de marcarlos uno por uno.
 *
 * **Sólo se intercepta lo que de verdad saca de la sede**: los enlaces externos
 * que no están en la lista blanca. Un enlace interno, un ancla, un `mailto:`, un
 * `tel:` o un dominio de confianza siguen su curso normal, sin JavaScript de por
 * medio, que es como deben funcionar.
 *
 * **Por qué no se intercepta nada dentro del propio aviso.** El modal de aviso
 * tiene su botón de confirmación; si el aviso contuviera un enlace externo y no
 * se excluyera, al pulsarlo este mismo escucha lo capturaría otra vez y volvería
 * a abrir el modal en lugar de dejar navegar. Se excluye por el contenedor
 * marcado con `data-aviso-salida`, que es más robusto que comprobar clases.
 */
import { useAvisoSalida } from '~/composables/useAvisoSalida'

export default defineNuxtPlugin(() => {
  if (!import.meta.client) return

  const { solicitarConfirmacion, esEnlaceExterno, esDominioConfianza } = useAvisoSalida()

  document.addEventListener('click', (evento: MouseEvent) => {
    // Si otro código ya decidió que este clic no navega, no hay nada que avisar.
    if (evento.defaultPrevented) return

    const objetivo = evento.target as HTMLElement | null
    const enlace = objetivo?.closest?.('a')
    if (!enlace) return

    // El propio aviso no se avisa a sí mismo.
    if (enlace.closest('[data-aviso-salida]')) return

    // Un enlace de descarga, o sin `href`, no lleva a otro sitio.
    if (enlace.hasAttribute('download')) return

    const href = enlace.getAttribute('href')
    if (!href) return

    if (!esEnlaceExterno(href) || esDominioConfianza(href)) return

    /*
     * Se cancela la navegación por defecto y se pregunta. Si el ciudadano
     * confirma, `confirmarNavegacion()` navega respetando si el enlace abría en
     * pestaña nueva; `stopPropagation()` evita que otros escuchas del documento
     * vuelvan a procesar el mismo clic.
     */
    evento.preventDefault()
    evento.stopPropagation()
    solicitarConfirmacion(href, enlace.target === '_blank', enlace)
  })
})
