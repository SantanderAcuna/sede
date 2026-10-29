/**
 * Compuerta de la analítica (T-26, D-5): el script de Cloudflare Web Analytics se
 * inyecta **sólo** cuando el ciudadano ha aceptado la categoría «analítica» con un
 * consentimiento vigente, y deja de cargarse al revocarla (AC-26.1 a 26.3, 26.6).
 *
 * Requisito en Cloudflare: desactivar la inyección automática («Web Analytics»
 * del sitio, sin «Automatic setup»); si no, el borde la sigue insertando antes de
 * que nadie consienta y esta compuerta no sirve de nada. Ese ajuste vive en el
 * panel de Cloudflare, no en este repositorio.
 *
 * Sin `SEDE_ANALITICA_TOKEN` no se hace nada: no hay analítica que gobernar.
 *
 * **Al revocar se recarga la página.** El script de Cloudflare engancha eventos
 * del navegador que quitar su `<script>` no deshace; recargar es la única forma
 * de garantizar que deja de medir en esa misma sesión.
 */
import { analiticaPermitida, esperarRegistros, useConsentimientoCookies } from '~/composables/useConsentimientoCookies'

const ID_SCRIPT = 'cf-beacon-analitica'
const URL_SCRIPT = 'https://static.cloudflareinsights.com/beacon.min.js'

export default defineNuxtPlugin(() => {
  const token = String(useRuntimeConfig().public.analiticaToken ?? '').trim()
  if (token === '') return

  const { preferencias, visible } = useConsentimientoCookies()
  let cargada = false

  const sincronizar = (): void => {
    const permitida = analiticaPermitida()

    if (permitida && !cargada) {
      if (document.getElementById(ID_SCRIPT)) return
      const script = document.createElement('script')
      script.id = ID_SCRIPT
      script.defer = true
      script.src = URL_SCRIPT
      script.setAttribute('data-cf-beacon', JSON.stringify({ token }))
      document.head.appendChild(script)
      cargada = true
    } else if (!permitida && cargada) {
      // Antes de recargar, se espera a que salga la constancia de la revocación.
      void esperarRegistros().then(() => window.location.reload())
    }
  }

  // Se reevalúa cuando cambian las preferencias o se cierra el banner (que es lo
  // que ocurre tras cada decisión). Antes de que el banner se inicialice, la
  // preferencia es `false`, así que no carga nada.
  watch([preferencias, visible], sincronizar, { deep: true, immediate: true })
})
