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

**Solución:**

- `router/index.ts` — El `beforeEach` ahora es `async` y espera a `sesion.init()` antes de decidir:

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

- `stores/sesion.ts` — `init()` ahora retorna una promesa y es idempotente:

```typescript
let initPromise: Promise<void> | null = null

async function init(): Promise<void> {
  if (inicializado.value) return
  if (initPromise) return initPromise  // ← evita llamadas concurrentes

  initPromise = (async () => {
    try {
      const perfil = await perfilApi()
      usuario.value = { id: perfil.id, email: perfil.email, ... }
    } catch {
      usuario.value = null
    } finally {
      inicializado.value = true
      initPromise = null
    }
  })()
  return initPromise
}
```

---

### Problema 2 — F5 múltiples cierra la sesión

**Síntoma:** Al presionar F5 dos veces o más rápidamente, la sesión se pierde.

**Causa raíz:** `cerrarSesion()` no era idempotente. Cuando el segundo F5 re-inicializaba Pinia (`initPromise = null`), una respuesta 401 de alguna petición en vuelo llamaba a `cerrarSesion()` y limpiaba el estado, sobreescribiendo la sesión válida que acababa de llegar.

**Solución (`stores/sesion.ts`):**

```typescript
let isLoggingOut = false

async function cerrarSesion(): Promise<void> {
  // Si ya se está cerrando, devolver la promesa en curso (idempotente)
  if (isLoggingOut) return initPromise ?? Promise.resolve()

  isLoggingOut = true
  try {
    await logoutApi()
  } catch {
    // Si falla la red o el servidor, igual se limpia el estado local
  } finally {
    usuario.value = null
    inicializado.value = false
    initPromise = null        // ← evita que el guardia restaure sesión tras logout
    isLoggingOut = false
  }
}
```

---

### Problema 3 — Logout no redirige al login

**Síntoma:** Al cerrar sesión desde el botón, la sesión se destruye pero no redirige al login (o entra en loop).

**Causa raíz:** El interceptor de http.ts hacía `window.location.href` mientras `cerrarSesion()` ejecutaba `logoutApi()` en paralelo. Eso causaba:
- Llamada doble a `cerrarSesion()` (interceptor + botón)
- Navegación fuera del router, perdiendo control del estado

**Solución:**

- `services/http.ts` — Interceptor 401 solo llama `cerrarSesion()`, **no** redirige:

```typescript
http.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      const sesion = useSesionStore()
      // Solo cierra sesión si YA estaba iniciada
      if (sesion.inicializado && sesion.iniciada) {
        sesion.cerrarSesion()
        // NO window.location.href aquí — el guardia del router redirigirá
        // en la siguiente navegación porque sesion.iniciada = false
      }
    }
    if (error.response?.status === 429) {
      window.location.href = '/admin/acceso?rate_limited=1'
    }
    return Promise.reject(error)
  }
)
```

- `layouts/AdminLayout.vue` — El componente SÍ espera el cierre antes de navegar:

```typescript
async function cerrarSesion(): Promise<void> {
  menuUsuarioAbierto.value = false
  await sesion.cerrarSesion()   // ← await asegura que termine antes de navegar
  router.push({ name: 'acceso.entrar' })
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

## 3. Flujo corregido de logout

```
Usuario hace click en "Cerrar sesión"
  └─ AdminLayout.cerrarSesion()
       ├─ menuUsuarioAbierto = false
       ├─ await sesion.cerrarSesion()
       │    ├─ POST /panel/logout → backend invalida sesión en BD
       │    ├─ usuario = null
       │    ├─ inicializado = false
       │    └─ initPromise = null
       └─ router.push('acceso.entrar')
            └─ Guardia beforeEach:
                 └─ !inicializado → await init()
                      └─ GET /panel/perfil → 401
                           └─ inicializado = true, iniciada = false
                                └─ Route 'acceso.entrar' tiene soloInvitados=true
                                     └─ sesi.iniciada=false → PERMITE acceso
```

---

## 4. Qué NO hacer para no repetir los problemas

| Regla | Por qué |
|---|---|
| **No llamar `window.location.href` dentro del interceptor 401** | Interfiere con la navegación del router y causa loops cuando `cerrarSesion()` se llama desde dos lugares a la vez |
| **No hacer `router.push()` sin `await cerrarSesion()`** | La navegación ocurre antes de que la sesión se destruya en el backend; el interceptor puede Interceptar una petición en vuelo y limpiar el estado |
| **No hacer navegación desde un interceptor de Axios** | Los interceptores no tienen contexto de navegación; solo deben modificar el estado de la sesión, nunca decidir a dónde ir |
| **No reinicializar `initPromise = null` fuera de `cerrarSesion()`** | Si otra parte del código lo resetea, el guardia puede restaurar sesión después de un logout |
| **No llamar `cerrarSesion()` dos veces sin bander** | La segunda llamada interfiere con la primera y puede limpiar la sesión válida |
| **No hacer login flow sin esperar CSRF cookie** | Sanctum requiere `GET /sanctum/csrf-cookie` antes de `POST /login`; sin eso el login falla con 419 |

---

## 5. Archivos modificados en esta sesión

```
panel/src/stores/sesion.ts     — isLoggingOut, idempotencia, reset initPromise
panel/src/router/index.ts      — beforeEach async con await sesion.init()
panel/src/services/http.ts     — Interceptor 401 sin window.location
```

---

## 6. Rutas involucradas

| Ruta | Método | Middleware | Propósito |
|---|---|---|---|
| `/sanctum/csrf-cookie` | GET | web | Establece cookie XSRF-TOKEN |
| `/api/v1/panel/login` | POST | throttle:login | Inicia sesión + establece cookie de sesión |
| `/api/v1/panel/logout` | POST | auth:sanctum | Destruye sesión en backend |
| `/api/v1/panel/perfil` | GET | auth:sanctum | Verifica sesión y devuelve usuario |
