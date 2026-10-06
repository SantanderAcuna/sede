/**
 * Cache-buster definitivo para el navegador.
 *
 * El problema que resolvemos
 * --------------------------
 * Vite sirve módulos con un hash estable (`?v=0ee01e96`) calculado del
 * contenido de `node_modules/.vite/deps/`. El navegador cachea estos
 * módulos agresivamente. Cambiar `index.html` con un `?t=…` no
 * invalida los imports transitivos que hace `main.ts`.
 *
 * Solución
 * --------
 * 1. Un plugin de Vite (`vite/plugins/version-json.ts`) emite
 *    `/version.json` en dev (middleware) y en build (asset en dist/).
 *    Ese archivo SIEMPRE se sirve con `Cache-Control: no-store`.
 * 2. Este módulo se ejecuta ANTES de Vue. Hace `fetch` a `/version.json`
 *    y compara la versión con la guardada en `localStorage`.
 * 3. Si difieren → `location.reload()` (recarga dura, ignora cache HTTP).
 *
 * El cliente es **tolerante a fallos**: si el fetch falla (404 en dev sin
 * plugin, 5xx, JSON inválido, HTML del fallback de SPA), retorna `null`
 * y la app sigue funcionando. El cache-buster es una optimización, no
 * un requisito para que la app cargue.
 */
const VERSION_KEY = 'app-version'
const VERSION_URL = `${import.meta.env.BASE_URL ?? '/'}version.json?t=${Date.now()}`

interface VersionInfo {
  version?: string
  build?: string
  builtAt?: string
}

/**
 * Intenta obtener la versión remota. Retorna `null` en cualquier fallo:
 *  - 404/5xx: el archivo no existe o el servidor falló
 *  - content-type != json: Nginx devolvió el fallback HTML del SPA
 *  - JSON inválido o sin `version`
 *  - timeout > 5s
 */
export async function fetchRemoteVersion(): Promise<string | null> {
  try {
    const response = await fetch(VERSION_URL, {
      cache: 'no-store',
      signal: AbortSignal.timeout(5000),
    })
    if (!response.ok) return null
    const ct = response.headers.get('content-type') ?? ''
    if (!ct.includes('json')) return null  // fallback HTML del SPA

    const data: VersionInfo = await response.json()
    return data.version ?? null
  } catch {
    return null
  }
}

export async function checkVersion(): Promise<void> {
  const serverVersion = await fetchRemoteVersion()
  if (!serverVersion) return  // no rompe la app si falla

  const clientVersion = localStorage.getItem(VERSION_KEY)

  if (!clientVersion) {
    // Primera visita: guardar la versión actual para futuras comparaciones
    localStorage.setItem(VERSION_KEY, serverVersion)
    return
  }

  if (clientVersion !== serverVersion) {
    // La versión del bundle cambió: forzar recarga dura
    // `location.reload(true)` es deprecated pero sigue bypasseando cache
    // en todos los navegadores modernos; con headers no-cache en el HTML
    // actual, es suficiente.
    console.info(
      `[cache-buster] Versión cambió: ${clientVersion} → ${serverVersion}`,
    )
    localStorage.setItem(VERSION_KEY, serverVersion)
    location.reload()
  }
}

// Se ejecuta al cargar este módulo, ANTES de que se cree la app Vue.
// Si la versión es nueva, location.reload() interrumpe el resto de la carga.
void checkVersion()
