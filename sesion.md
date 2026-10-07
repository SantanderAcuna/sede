# Sesión de auditoría — Problemas y soluciones

> **Fecha:** 2026-10-07
> **Proyecto:** SGDI · Sede Electrónica Santa Marta
> **Stack:** Laravel 13 + Sanctum (SPA cookie-auth) + Vue 3 + Pinia + Axios

---

## 1. Problemas resueltos

### Problema 1 — F5 pone la pantalla en blanco

**Síntoma:** Al presionar F5 la pantalla queda completamente blanca durante segundos.

**Causa raíz:** Se intentó coordinar la inicialización entre recargas usando un flag en `sessionStorage`. Cuando el flag estaba puesto, el código entraba en un **polling** esperando que `inicializado.value` se volviera `true` — pero ese ref **nunca cambia** porque Pinia se re-inicializa en cada F5 con `inicializado = false`. El polling esperaba hasta 5 segundos sin esperanza.

Mientras tanto, `main.ts` estaba bloqueado en `sesion.init()` y no llamaba a `app.mount('#app')`. Resultado: pantalla en blanco durante hasta 5 segundos.

**Solución (`stores/sesion.ts`):** Eliminar completamente la coordinación con `sessionStorage`. La inicialización es por-pestaña y solo importa el estado de Pinia + `initPromise` en memoria.

```typescript
async function init(): Promise<void> {
  if (inicializado.value) return
  if (initPromise) return initPromise

  initPromise = (async () => {
    try {
      const perfil = await perfilApi()
      usuario.value = { id: perfil.id, email: perfil.email, ... }
    } catch {
      usuario.value = null
    } finally {
      inicializado.value = true
    }
  })()

  return initPromise
}
```

---

### Problema 2 — Race condition en navigation guard

**Síntoma:** El `beforeEach` del router se ejecutaba antes de que `sesion.init()` completara.

**Causa raíz:** El router se monta con `app.use(router)` y el `beforeEach` se ejecuta inmediatamente, antes de que `init()` resuelva. La guardia veía `iniciada=false` (porque `usuario` aún era `null`) y redirigía al login antes de que `perfilApi()` respondiera con la sesión real.

**Solución (`router/index.ts`):**

```typescript
enrutador.beforeEach(async (destino) => {
  const sesion = useSesionStore()
  // Esperar a que init() termine antes de decidir.
  if (!sesion.inicializado) {
    await sesion.init()
  }
  // ... resto de las reglas del guardia
})
```

---

### Problema 3 — Logout no redirige

**Síntoma:** Al cerrar sesión desde el menú, la sesión se destruye pero el navegador queda en el panel sin redirigir.

**Causa raíz:** `router.push()` es navegación interna del router Vue. El `beforeEach` se dispara y ve `inicializado=false` (porque `cerrarSesion` lo puso en `false`). El guardia llama a `init()` → `perfilApi()` con sesión ya invalidada → 401 → `cerrarSesion()` otra vez → **loop**.

**Solución (`layouts/AdminLayout.vue`):**

```typescript
async function cerrarSesion(): Promise<void> {
  menuUsuarioAbierto.value = false
  await sesion.cerrarSesion()
  // window.location.replace() es navegación HARD del navegador.
  // No dispara el beforeEach del router, evitando el loop.
  window.location.replace('/admin/acceso')
}
```

---

### Problema 4 — cerrarSesion no idempotente

**Síntoma:** Si `cerrarSesion()` se llamaba dos veces (ej: desde el botón y desde el interceptor 401), la segunda llamada interfería.

**Causa raíz:** No había protección contra llamadas concurrentes.

**Solución (`stores/sesion.ts`):**

```typescript
let isLoggingOut = false

async function cerrarSesion(): Promise<void> {
  if (isLoggingOut) return initPromise ?? Promise.resolve()

  isLoggingOut = true
  try {
    await logoutApi()
  } catch {
    // Si falla, limpiar igual — el estado local es lo importante.
  } finally {
    usuario.value = null
    inicializado.value = false
    isLoggingOut = false
  }
}
```

---

## 2. Configuración correcta

### `backend/.env`

```env
SESSION_DOMAIN=127.0.0.1
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
SESSION_HTTP_ONLY=true
SESSION_LIFETIME=120

FRONTEND_URL=http://localhost:5190
SANCTUM_STATEFUL_DOMAINS=localhost:5190,127.0.0.1:5190
```

### `backend/bootstrap/app.php`

```php
$middleware->api(prepend: [
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
])
$middleware->statefulApi()
```

### `backend/config/cors.php`

```php
'supports_credentials' => true,
'allowed_origins' => ['http://127.0.0.1:5190', 'http://localhost:5190'],
'paths' => ['api/*', 'sanctum/csrf-cookie'],
```

### `panel/src/services/http.ts`

```typescript
export const http: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? '/api/v1',
  withCredentials: true,
  withXSRFToken: true,
  timeout: 15_000,
})
```

### `panel/vite.config.ts`

```typescript
proxy: {
  '/api': { target: 'http://127.0.0.1:8010', changeOrigin: false },
  '/sanctum/csrf-cookie': { target: 'http://127.0.0.1:8010', changeOrigin: false },
  '/storage': { target: 'http://127.0.0.1:8010', changeOrigin: false },
}
```

---

## 3. Flujos corregidos

### F5 una vez

```
Usuario presiona F5
  └─ Pinia re-inicializa (usuario=null, inicializado=false)
  └─ main.ts: sesion.init() se llama
       └─ initPromise = (async () => {...})()
       └─ perfilApi() → 200 → usuario populated, inicializado=true
  └─ app.mount('#app')
       └─ Router initial navigation
       └─ beforeEach: inicializado=true → return true
       └─ Usuario ve el panel
```

### Logout

```
Click "Cerrar sesión"
  └─ AdminLayout.cerrarSesion()
       ├─ await sesion.cerrarSesion()
       │    ├─ POST /panel/logout → 200
       │    ├─ usuario = null, inicializado = false
       │    └─ isLoggingOut = false
       └─ window.location.replace('/admin/acceso')
            ├─ HARD navigation (no beforeEach)
            └─ Página se recarga completamente
                 ├─ Pinia re-inicializa
                 ├─ sesion.init() → perfilApi() → 401
                 ├─ inicializado=true, iniciada=false
                 └─ Ruta 'acceso.entrar' (soloInvitados) → permite
```

---

## 4. Qué NO hacer para no repetir los problemas

| Regla | Por qué |
|---|---|
| **No usar sessionStorage para coordinar init() entre F5** | Pinia y el módulo JS se re-evaluan en cada F5, pero sessionStorage persiste. Si el flag queda "stuck", cualquier intento de coordinación causará pantallas en blanco o loops |
| **No hacer polling dentro de init()** | Bloquea `main.ts` y por tanto `app.mount()`. Resultado: pantalla en blanco durante segundos |
| **No llamar `window.location.href` en el interceptor 401** | Interfiere con la navegación del router. La navegación debe estar en el componente |
| **No usar `router.push()` para logout** | El `beforeEach` se dispara durante la navegación y puede ver `inicializado=false`, llamando a `init()` con sesión ya invalidada → loop |
| **No hacer navegación desde un interceptor de Axios** | Los interceptores no tienen contexto de navegación; solo deben modificar estado, no decidir a dónde ir |
| **No nullificar `initPromise` en el `finally` de `init()`** | Las llamadas concurrentes deben devolver la misma promesa y esperar el mismo resultado |
| **No hacer login flow sin esperar CSRF cookie** | Sanctum requiere `GET /sanctum/csrf-cookie` antes de `POST /login`; sin eso el login falla con 419 |

---

## 5. Archivos modificados en esta sesión

```
panel/src/stores/sesion.ts     — init() idempotente sin sessionStorage, cerrarSesion() con isLoggingOut
panel/src/router/index.ts      — beforeEach async con await sesion.init()
panel/src/services/http.ts     — Interceptor 401 sin window.location
panel/src/layouts/AdminLayout.vue — window.location.replace() para logout
```

---

## 6. Rutas involucradas

| Ruta | Método | Middleware | Propósito |
|---|---|---|---|
| `/sanctum/csrf-cookie` | GET | web | Establece cookie XSRF-TOKEN |
| `/api/v1/panel/login` | POST | throttle:login | Inicia sesión + establece cookie de sesión |
| `/api/v1/panel/logout` | POST | auth:sanctum | Destruye sesión en backend |
| `/api/v1/panel/perfil` | GET | auth:sanctum | Verifica sesión y devuelve usuario |