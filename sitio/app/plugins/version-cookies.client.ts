/**
 * Ata la versión del consentimiento a la política de cookies que se edita en el
 * panel (T-20, AC-20.8).
 *
 * Cuando la Entidad cambia el texto de la política de cookies, su versión sube. Este
 * plugin la lee de la API al arrancar y se la pasa al composable del consentimiento:
 * un consentimiento dado a una versión anterior deja de valer y el banner vuelve a
 * preguntar. Sin este plugin, cambiar la política no volvía a pedir nada.
 *
 * Es `async` a propósito, para que la versión esté fijada **antes** de que el banner
 * decida si se muestra; el tiempo de espera es corto (1 s) para que una API lenta no
 * retrase la página. Si no responde, el composable no descarta consentimientos por
 * versión (ver `configurarVersionPolitica`): una caída de la API no debe volver a
 * preguntarle a toda la ciudadanía. No se guarda nada en el navegador para esto.
 */
import { configurarVersionPolitica } from '~/composables/useConsentimientoCookies'

export default defineNuxtPlugin(async () => {
  const urlApi = useRuntimeConfig().public.apiUrl.replace(/\/+$/, '')

  try {
    const respuesta = await $fetch<{ data?: { version?: unknown } }>(`${urlApi}/politicas/uso-de-cookies`, {
      timeout: 1000,
      credentials: 'omit',
    })
    configurarVersionPolitica(respuesta.data?.version)
  } catch {
    configurarVersionPolitica(null)
  }
})
