# Sesión de auditoría — Problemas y soluciones

> **Fecha:** 2026-10-07
> **Proyecto:** SGDI · Sede Electrónica Santa Marta
> **Stack:** Laravel 13 + Sanctum (SPA cookie-auth) + Vue 3 + Pinia + Axios

---

## 1. Problemas resueltos

### Problema 1 — Sesión se pierde al presionar F5 una vez

**Síntoma:** Login funciona, pero al actualizar con F5 la sesión se pierde y redirige al login.

**Causa raíz:** Race condition entre el navigation guard del router y `sesion.init()`.

```
1. Página carga → Pinia se reinicia (usuario=null, inicializado=false)
2. app.use(router) → el guardia beforeEach se ejecuta INMEDIATAMENTE
3. Guardia: usuario=null → ¡redirige a /acceso/entrar ANTES de que perfilApi() responda!
4. Meanwhile: sesion.init() corre en background
5. init() termina, setea usuario.value (si la cookie era válida)
6. Pero ya estamos en /acceso/entrar...
```

**Solución (`router/index.ts`):**

```typescript
// ANTES (problemático)
enrutador.beforeEach((destino) => {
  const sesion = useSesionStore()
  if (destino.meta.requiereSesion && !sesion.iniciada) {
    return { name: 'acceso.entrar' }  // ← ejecuta con usuario=null
  }
})

// DESPUÉS (correcto)
enrutador.beforeEach(async (destino) => {
  const sesion = useSesionStore()
  if (!sesion.inicializado) {
    await sesion.init()  // ← espera a que la sesión se restaure
  }
  if (destino.meta.requiereSesion && !sesion.iniciada) {
    return { name: 'acceso.entrar' }
  }
})
```

---

### Problema 2 — F5 múltiples cierra la sesión

**Síntoma:** Al presionar F5 dos veces o más rápidamente, la sesión se pierde.

**Causa raíz:** Pinia se re-inicializa en cada F5 (el módulo se re-evalúa), pero las promesas de `perfilApi()` de las recargas anteriores siguen en vuelo. Cuando la última en completarse es un 401 (sesión expirada o invalidada), `cerrarSesion()` limpia el estado y la sesión se pierde aunque la primera respuesta hubiera sido válida.

**Solución (`stores/sesion.ts`):**

```typescript
const INIT_KEY = 'sesion:initInProgress'

async function init(): Promise<void> {
  if (inicializado.value) return

  // Si otra recarga de página ya está inicializando, esperar a que termine.
  // sessionStorage persiste en la misma pestaña (a diferencia de Pinia que se reinicia).
  if (sessionStorage.getItem(INIT_KEY)) {
    const inicio = Date.now()
    while (!inicializado.value && Date.now() - inicio < 5000) {
      await new Promise((r) => setTimeout(r, 50))
    }
    if (!inicializado.value) return init()
  }

  // Marcar inicio INMEDIATAMENTE para coordinar con otras recargas.
  sessionStorage.setItem(INIT_KEY, '1')

  if (initPromise) return initPromise

  initPromise = (async () => {
    try {
      const perfil = await perfilApi()
      usuario.value = { id: perfil.id, email: perfil.email, ... }
    } catch {
      usuario.value = null
    } finally {
      inicializado.value = true
      // NO nullificar initPromise: llamadasConcurrentes devuelven la misma promesa
      sessionStorage.removeItem(INIT_KEY)
    }
  })()

  return initPromise
}
```

---

### Problema 3 — Logout no redirige al login

**Síntoma:** Al cerrar sesión desde el botón, la sesión se destruye pero no redirige al login (o entra en loop).

**Causa raíz:** `router.push()` es navegación interna del router Vue. Cuando `cerrarSesion()` invalida la sesión y luego llama a `router.push({ name: 'acceso.entrar' })`, el `beforeEach` del router se dispara mientras la navegación está en curso. El guardia ve `inicializado=false` y llama a `init()`, que llama a `perfilApi()` con la sesión ya invalidada → 401 → `cerrarSesion()` otra vez → loop.

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

**Complemento (`stores/sesion.ts`):** `cerrarSesion()` ya no nullifica `initPromise`. Si hay un `perfilApi()` en vuelo cuando se hace logout, la promesa sigue viva y las siguientes llamadas esperan en su lugar. Cuando esa petición pendiente responde (con 401 porque la sesión fue invalidada), el resultado se ignora porque `inicializado=false` ya fue establecido por `cerrarSesion()`.

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

## 3. Flujo corregido de logout

```
Usuario hace click en "Cerrar sesión"
  └─ AdminLayout.cerrarSesion()
       ├─ menuUsuarioAbierto = false
       ├─ await sesion.cerrarSesion()
       │    ├─ POST /panel/logout → backend invalida sesión en BD
       │    ├─ usuario = null
       │    ├─ inicializado = false
       │    └─ isLoggingOut = true → false
       └─ window.location.replace('/admin/acceso')
            ├─ Navegación HARD del navegador (NO dispara beforeEach)
            └─ Página se recarga completamente
                 ├─ Pinia se re-inicializa
                 ├─ sessionStorage.initInProgress = null (limpio)
                 ├─ sesion.init() → perfilApi() → 401
                 └─ Guardia: !inicializado → await init() → 401
                      ├─ inicializado = true, iniciada = false
                      └─ Ruta 'acceso.entrar' (soloInvitados=true)
                           └─ Permitida → usuario ve login
```

---

## 4. Flujo corregido de F5 múltiple

```
Usuario presiona F5 (primera vez)
  ├─ Pinia se re-inicializa
  ├─ beforeEach dispara: !inicializado → await sesion.init()
  │    ├─ sessionStorage.setItem('sesion:initInProgress', '1')
  │    ├─ perfilApi() → 200 → usuario.value populated
  │    └─ finally: inicializado=true, sessionStorage.removeItem()
  └─ Guardia: iniciada=true → permite acceso al panel

Usuario presiona F5 (segunda vez, mientras la primera aún está en vuelo)
  ├─ Pinia se re-inicializa
  ├─ beforeEach dispara: sessionStorage.initInProgress='1' YA EXISTE
  │    └─ Polling: await mientras !inicializado (esperando la 1ra)
  ├─ Primera perfilApi() responde 200 → usuario populated
  ├─ Primera finally: inicializado=true, polling termina
  ├─ Segunda: init() retorna inmediatamente (inicializado=true)
  └─ Guardia: iniciada=true → permite acceso al panel
```

---

## 5. Qué NO hacer para no repetir los problemas

| Regla | Por qué |
|---|---|
| **No llamar `window.location.href` dentro del interceptor 401** | Interfiere con la navegación del router y causa loops cuando `cerrarSesion()` se llama desde dos lugares a la vez |
| **No usar `router.push()` para logout** | El `beforeEach` se dispara durante la navegación y puede ver `inicializado=false`, llamando a `init()` con sesión ya invalidada → loop de logout |
| **No hacer navegación desde un interceptor de Axios** | Los interceptores no tienen contexto de navegación; solo deben modificar el estado de la sesión, nunca decidir a dónde ir |
| **No nullificar `initPromise` en el `finally` de `init()`** | Si se nullifica, otra recarga de página puede crear una nueva promesa mientras la anterior aún está en vuelo, creando race conditions |
| **No nullificar `initPromise` en `cerrarSesion()`** | Si hay un `perfilApi()` en vuelo cuando se hace logout, nullificar la promesa hace que la siguiente llamada a `init()` cree una nueva promesa en lugar de esperar la existente |
| **No usar `localStorage` para coordinar init entre F5** | `localStorage` persiste entre pestañas; `sessionStorage` es por pestaña y es el correcto aquí |
| **No hacer login flow sin esperar CSRF cookie** | Sanctum requiere `GET /sanctum/csrf-cookie` antes de `POST /login`; sin eso el login falla con 419 |

---

## 6. Archivos modificados en esta sesión

```
panel/src/stores/sesion.ts     — sessionStorage coordination, init() idempotente, cerrarSesion() sin nullificar initPromise
panel/src/router/index.ts      — beforeEach async con await sesion.init()
panel/src/services/http.ts     — Interceptor 401 sin window.location
panel/src/layouts/AdminLayout.vue — window.location.replace() para logout
```

---

## 7. Rutas involucradas

| Ruta | Método | Middleware | Propósito |
|---|---|---|---|
| `/sanctum/csrf-cookie` | GET | web | Establece cookie XSRF-TOKEN |
| `/api/v1/panel/login` | POST | throttle:login | Inicia sesión + establece cookie de sesión |
| `/api/v1/panel/logout` | POST | auth:sanctum | Destruye sesión en backend |
| `/api/v1/panel/perfil` | GET | auth:sanctum | Verifica sesión y devuelve usuario |
