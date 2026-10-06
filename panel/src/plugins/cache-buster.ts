/**
 * Cache-buster definitivo para el navegador.
 *
 * El problema que resolvemos:
 *  - Vite sirve módulos con un hash estable (`?v=0ee01e96`) calculado del
 *    contenido de `node_modules/.vite/deps/`.
 *  - El navegador cachea estos módulos agresivamente.
 *  - El query `?t=20261006` en `index.html` invalida el `main.ts` pero NO
 *    invalida los módulos que `main.ts` importa transitivamente.
 *
 * Solución:
 *  - Hacemos fetch a `/version.json` (que tiene headers no-cache).
 *  - Si la versión en el servidor es diferente a la del cliente (guardada en
 *    `localStorage`), forzamos `location.reload(true)` (recarga dura).
 *  - El servidor siempre devolverá la versión actual del `version.json`.
 *
 * La primera vez que el cliente visita, no hay versión guardada, así que
 * guarda la actual. En visitas posteriores compara.
 */
const VERSION_KEY = 'app-version'
const VERSION_URL = '/version.json?t=' + Date.now()  // anti-cache

interface VersionInfo {
  version: string
  build: string
  forceReload?: boolean
}

export async function checkVersion(): Promise<void> {
  try {
    const response = await fetch(VERSION_URL, {
      cache: 'no-store',
      headers: { 'Cache-Control': 'no-cache' }
    })
    if (!response.ok) return

    const serverVersion: VersionInfo = await response.json()
    const clientVersion = localStorage.getItem(VERSION_KEY)

    if (!clientVersion) {
      // Primera visita: guardar versión actual
      localStorage.setItem(VERSION_KEY, serverVersion.version)
      return
    }

    if (clientVersion !== serverVersion.version) {
      // Versión cambió: forzar recarga dura
      console.log(`[cache-buster] Versión cambió: ${clientVersion} → ${serverVersion.version}`)
      localStorage.setItem(VERSION_KEY, serverVersion.version)
      // location.reload(true) es la forma más agresiva: ignora cache HTTP
      location.reload()
    }
  } catch (err) {
    // Si falla el fetch (offline, etc.), no hacer nada
    console.warn('[cache-buster] No se pudo verificar la versión:', err)
  }
}

// Verificar la versión INMEDIATAMENTE al cargar el módulo
// (antes de que Vue monte nada). Esto evita que se renderice nada con código viejo.
checkVersion()
