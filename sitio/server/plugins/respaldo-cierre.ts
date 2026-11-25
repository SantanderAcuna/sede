/**
 * Apagado ordenado del respaldo de la API (T9.4).
 *
 * Al recibir SIGTERM Nitro dispara `close`: se esperan las escrituras en vuelo y se cierra la
 * conexión a Redis, para que el contenedor termine sin dejar la conexión abierta del lado del
 * servidor. Medido con una compilación real (2026-11-24): el gancho se ejecuta y la conexión del
 * usuario `sitio` pasa de 1 a 0.
 */
import { cerrarEntornoDelSitio } from '../utils/respaldo-sitio'

export default defineNitroPlugin((nitroApp) => {
  nitroApp.hooks.hook('close', async () => {
    await cerrarEntornoDelSitio()
  })
})
