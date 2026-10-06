import { type Plugin } from 'vite'

/**
 * Plugin de Vite que emite `version.json` tanto en dev como en build.
 *
 * Por qué existe
 * --------------
 * El cache-buster del cliente (`src/plugins/cache-buster.ts`) hace `fetch` a
 * `/version.json` para comparar la versión del bundle en el servidor con la
 * que tiene en `localStorage`. Si difieren, fuerza `location.reload()`.
 *
 * El archivo debe existir en **ambos** entornos:
 *  - **dev**: como middleware de Connect que responde en memoria (no toca
 *    disco, así no se sincroniza a git).
 *  - **build**: como asset emitido en `dist/version.json` para que Nginx lo
 *    sirva con headers no-cache.
 *
 * Si el archivo falta (404 en dev, fallback a index.html en prod), el
 * cliente falla silenciosamente (tolerante a fallos), pero perdemos la
 * capacidad de invalidar caches obsoletos.
 */
export function versionJsonPlugin(): Plugin {
  // Se calcula UNA vez al arrancar el dev server / al inicio del build.
  // En CI se inyecta APP_VERSION con el SHA del commit.
  const payload = JSON.stringify({
    version: process.env.APP_VERSION ?? new Date().toISOString(),
    builtAt: new Date().toISOString(),
  }, null, 2)

  return {
    name: 'version-json',

    // DEV: middleware de Connect. Se ejecuta antes que el SPA fallback.
    // Connect ignora el query string (`?t=…`), así que el `cache-buster.ts`
    // puede usarlo para evadir caches sin afectar el match de la ruta.
    configureServer(server) {
      server.middlewares.use('/version.json', (_req, res) => {
        res.setHeader('Content-Type', 'application/json')
        res.setHeader('Cache-Control', 'no-store, must-revalidate')
        res.end(payload)
      })
    },

    // BUILD: emite el archivo en `dist/version.json` (sin hash en el
    // nombre) para que Nginx lo pueda servir sin reescritura.
    generateBundle() {
      this.emitFile({
        type: 'asset',
        fileName: 'version.json',
        source: payload,
      })
    },
  }
}
