/**
 * Envía al servidor cada evento del consentimiento de cookies (T-26, AC-26.4).
 *
 * El composable decide y guarda en el navegador; este plugin sólo lleva la
 * constancia a `POST /consentimientos`. Es «dispara y olvida»: si la API no
 * responde, la decisión del ciudadano ya está guardada y nada se interrumpe. El
 * cuerpo trae evento, categorías y versión de la política; la fecha la pone el
 * servidor y no viaja ningún identificador.
 */
import { configurarRegistroConsentimiento } from '~/composables/useConsentimientoCookies'
import type { Traer } from '~/utils/cliente-api'

export default defineNuxtPlugin(() => {
  // `$fetch` desnudo: el registro necesita `keepalive` y `credentials`, y este plugin solo corre en el navegador.
  const api = useClienteApi($fetch as unknown as Traer)

  configurarRegistroConsentimiento((registro) => {
    // `keepalive` para que la petición sobreviva a una recarga inmediata (la que
    // hace la analítica al revocar).
    return api.leer('/consentimientos', {
      method: 'POST',
      body: registro,
      keepalive: true,
      credentials: 'omit',
    }).catch(() => {
      // Sin constancia en el servidor, pero la decisión local sigue en pie.
    })
  })
})
