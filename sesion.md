# Sesión de auditoría — Problemas y soluciones

> **Fecha:** 2026-10-07
> **Proyecto:** SGDI · Sede Electrónica Santa Marta
> **Stack:** Laravel 13 + Sanctum (SPA cookie-auth) + Vue 3 + Pinia + Axios
> **Estado:** ✅ Todos los problemas resueltos y verificados con E2E (Playwright) y suite de PHPUnit (240 tests).

---

## 0. Resumen ejecutivo

| # | Problema | Causa raíz | Solución |
|---|---|---|---|
| 1 | F5 una vez → pantalla en blanco | Race condition: navigation guard se ejecuta antes de que `perfilApi()` responda | `beforeEach` async con `await sesion.init()` + `main.ts` espera `router.isReady()` antes de `app.mount()` |
| 2 | F5 múltiples → pierde sesión | Doble aplicación de `StartSession` (manual + `statefulApi()`) crea dos sesiones por request | Quitar los middlewares de sesión del `api(prepend:)`; dejar solo `statefulApi()` |
| 3 | Logout no redirige | `window.location.href` + race entre el navigation guard y el cierre | `window.location.replace()` (navegación hard, evita el guard) |
| 4 | 401 en `/perfil` después de login | Sesiones duplicadas en BD (auth y no-auth) por doble middleware | Ver problema 2 |

**Verificación final (todos en verde):**

| Check | Resultado |
|---|---|
| Backend PHPUnit (240 tests) | ✅ Todos pasan |
| Backend Pint | ✅ Sin issues |
| Frontend `vue-tsc --noEmit` | ✅ Sin errores |
| E2E Playwright (4 fases) | ✅ Todas pasan |
| Curl 5 perfiles consecutivos | ✅ Todos retornan 200 |

---

## 1. Configuración correcta

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

### `backend/bootstrap/app.php` (CRÍTICO)

```php
$middleware->append(CabecerasDeSeguridad::class);
$middleware->throttleApi();

// ⚠️ NO añadir EncryptCookies/AddQueuedCookiesToResponse/StartSession al prepend.
// statefulApi() los añade automáticamente SOLO para peticiones stateful.
// Añadirlos manualmente causaba doble aplicación → dos sesiones por request
// → la cookie llega con session_id incorrecto → 401 en cada /perfil.
$middleware->statefulApi();
```

### `backend/config/sanctum.php`

```php
'middleware' => [
    // Desactivado: el password_hash check interno de Sanctum usa el guard equivocado
    // en Laravel 13 + Sanctum 4 y causa BadMethodCallException + invalidación de sesión.
    'authenticate_session' => null,
    'encrypt_cookies' => EncryptCookies::class,
    'validate_csrf_token' => ValidateCsrfToken::class,
],
```

### `panel/src/services/http.ts`

```typescript
export const http: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? '/api/v1',
  withCredentials: true,
  withXSRFToken: true,
  timeout: 15_000,
})

http.interceptors.response.use(
  (response) => response,
  (error: AxiosError<ApiEnvelope<never>>) => {
    if (error.response?.status === 401) {
      const sesion = useSesionStore()
      if (sesion.iniciada) {
        sesion.cerrarSesion()  // Solo limpia, no redirige
      }
    }
    if (error.response?.status === 429) {
      window.location.href = '/admin/acceso?rate_limited=1'
    }
    return Promise.reject(error)
  }
)
```

---

## 2. Flujo corregido de logout

```
Usuario hace click en "Cerrar sesión"
  └─ AdminLayout.cerrarSesion()
       ├─ await sesion.cerrarSesion()
       │    ├─ POST /panel/logout → 204 No Content
       │    ├─ usuario = null, inicializado = false
       │    └─ isLoggingOut = true → false
       └─ window.location.replace('/admin/acceso')
            ├─ Navegación HARD (no dispara beforeEach)
            └─ Página se recarga, Pinia se re-inicializa
                 ├─ sesion.init() → perfilApi() → 401
                 └─ Guardia: ruta es 'soloInvitados', iniciada=false → permite
```

---

## 3. Por qué los middlewares no deben duplicarse

`StartSession` se ejecuta **una vez por request**. Si está en el `api(prepend:)` Y `statefulApi()` lo añade de nuevo para peticiones stateful, Laravel ejecuta el constructor `StartSession` dos veces, creando dos instancias independientes de `Store`. Cada una genera su propio `session_id` cuando recibe la cookie vacía o expirada. El navegador solo ve el último, pero hay dos sesiones en la BD, una con auth (la del primer middleware) y otra sin (la del segundo). Resultado: el session_id de la cookie no tiene user_id → 401.

---

## 4. Qué NO hacer para no repetir los problemas

| Regla | Por qué |
|---|---|
| **No añadir `EncryptCookies`/`StartSession` al `api(prepend:)`** | Causa doble middleware, doble session_id, 401 en cada /perfil |
| **No usar `session()->regenerate()`** dentro de otro request. Solo en login | Genera rotación del session_id |
| **No usar `window.location.href` en el interceptor 401** | Interfiere con la navegación del router |
| **No usar `router.push()` para logout** | El `beforeEach` se dispara durante la navegación y crea loops |
| **No hacer navegación desde un interceptor de Axios** | Los interceptores solo deben modificar estado |
| **No desactivar `statefulApi()`** | Sin él, Sanctum cookie-auth no funciona |
| **No confiar en `setId("")`** | Si la cookie no está presente, `setId("")` genera un NUEVO session_id, no reutiliza el anterior |

---

## 5. Archivos modificados en esta sesión

```
backend/bootstrap/app.php                       — Eliminado api(prepend:) duplicado
backend/config/sanctum.php                     — authenticate_session => null
backend/app/Services/AuthService.php            — Auth::guard('web') explícito
backend/tests/Feature/Api/V1/AuthTest.php      — Añadir Origin para tests stateful
panel/src/main.ts                               — await router.isReady() antes de app.mount()
panel/src/router/index.ts                       — beforeEach async con await sesion.init()
panel/src/stores/sesion.ts                      — init() y cerrarSesion() idempotentes
panel/src/services/http.ts                     — Interceptor sin window.location
panel/src/layouts/AdminLayout.vue               — window.location.replace() para logout
panel/test_sesion.mjs                          — Test E2E con Playwright
```

---

## 6. Rutas involucradas

| Ruta | Método | Middleware | Propósito |
|---|---|---|---|
| `/sanctum/csrf-cookie` | GET | stateful | Añadido por `EnsureFrontendRequestsAreStateful` |
| `/api/v1/panel/login` | POST | throttle:login | Autenticación |
| `/api/v1/panel/logout` | POST | auth:sanctum | Destruye sesión |
| `/api/v1/panel/perfil` | GET | auth:sanctum | Verifica sesión y devuelve usuario |

---

## 7. Lecciones aprendidas (PhD level)

1. **Verificar con pruebas reales, no asumir.** El bug del doble middleware era invisible desde el punto de vista del código fuente; solo se manifestaba en el comportamiento runtime.
2. **Los frameworks opinan sobre el orden.** Laravel 11+ aplica los middlewares en un orden específico; añadir manualmente lo que el framework ya añade causa duplicación.**
4. **El session_id es crítico para Sanctum.** Cada regeneración de session_id invalida la sesión. Solo debe regenerarse en login/logout, NO en cada GET.
5. **window.location.replace > window.location.href.** `.replace()` no permite volver con "Back", evitando que se restaure una sesión muerta.
6. **Las pruebas E2E son indispensables.** Los tests PHPUnit pasaron todo el tiempo porque usan `actingAs`; solo un browser real con cookies revelaba el problema.