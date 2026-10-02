# Errores y Códigos HTTP

> **Estándar:** JSON:API 1.0 §"Errors" + RFC 7807 (Problem Details for HTTP APIs) adaptado.
> **Catálogo exhaustivo:** cada error tiene código, título, descripción, ejemplo y respuesta estándar.

---

## 1. Formato de error estándar

```json
{
  "jsonapi": {
    "version": "1.0"
  },
  "errors": [
    {
      "id": "01HXYZ12345",
      "status": "422",
      "code": "VALIDATION_FAILED",
      "title": "Validación fallida",
      "detail": "El campo titulo es obligatorio",
      "source": {
        "pointer": "/data/attributes/titulo",
        "parameter": "titulo"
      },
      "meta": {
        "request_id": "01HXYZ12345",
        "timestamp": "2026-10-01T15:30:00Z"
      },
      "links": {
        "about": "https://docs.santamarta.gov.co/errors/VALIDATION_FAILED"
      }
    }
  ]
}
```

**Campos:**
- `id` (opcional): identificador único del error.
- `status` (string): código HTTP en string.
- `code` (string): código de aplicación machine-readable.
- `title` (string): título corto.
- `detail` (string): descripción legible.
- `source.pointer` (opcional): puntero JSON Pointer al campo problemático.
- `source.parameter` (opcional): nombre del parámetro query.
- `meta`: información adicional (request_id, timestamp, etc.).
- `links.about` (opcional): URL a documentación del error.

---

## 2. Catálogo de errores

### 2.1 Errores 4xx (cliente)

| HTTP | code | Título | Cuándo se emite |
|---|---|---|---|
| 400 | `BAD_REQUEST` | Solicitud malformada | JSON malformado, Content-Type incorrecto |
| 400 | `MISSING_CONTENT_TYPE` | Falta Content-Type | Se espera `application/vnd.api+json` |
| 401 | `UNAUTHENTICATED` | No autenticado | Sin token, token expirado |
| 401 | `TOKEN_EXPIRED` | Token expirado | Bearer token caducado |
| 401 | `INVALID_CREDENTIALS` | Credenciales inválidas | Login fallido |
| 401 | `MFA_REQUIRED` | MFA requerido | Se intenta acceder sin completar MFA |
| 401 | `MFA_INVALID` | Código MFA inválido | Código TOTP incorrecto |
| 403 | `FORBIDDEN` | Acceso prohibido | Usuario sin permiso para el recurso |
| 403 | `INSUFFICIENT_ROLE` | Rol insuficiente | Usuario no tiene el rol requerido |
| 403 | `POLICY_DENIED` | Política denegada | Gate/Policy rechaza la acción |
| 404 | `NOT_FOUND` | Recurso no encontrado | URL inexistente, slug no existe |
| 404 | `ROUTE_NOT_FOUND` | Ruta no encontrada | Endpoint no existe en API |
| 405 | `METHOD_NOT_ALLOWED` | Método no permitido | GET a endpoint POST |
| 406 | `NOT_ACCEPTABLE` | Accept no soportado | Se pide un Content-Type no ofrecido |
| 409 | `CONFLICT` | Conflicto | Recurso ya existe, Idempotency-Key duplicada |
| 409 | `VERSION_CONFLICT` | Conflicto de versión | ETag no coincide |
| 410 | `GONE` | Recurso eliminado | Soft-deleted permanentemente |
| 413 | `PAYLOAD_TOO_LARGE` | Payload demasiado grande | Archivo >100 MB |
| 415 | `UNSUPPORTED_MEDIA_TYPE` | Formato no soportado | MIME no en whitelist |
| 422 | `VALIDATION_FAILED` | Validación fallida | Form Request rechaza |
| 422 | `INVALID_HASH` | Hash inválido | SHA-256 malformado |
| 422 | `INVALID_DATE` | Fecha inválida | formato incorrecto |
| 422 | `INVALID_FECHA_PUBLICACION` | Fecha publicación inválida | Anterior a hoy |
| 422 | `LECTURABILIDAD_BAJA` | Lecturabilidad baja | Fernández-Huerta <60 (warning, no error) |
| 422 | `METADATOS_REQUERIDOS` | Metadatos obligatorios | Faltan Dublin Core mínimos |
| 429 | `TOO_MANY_REQUESTS` | Demasiadas solicitudes | Rate limit excedido |
| 429 | `LOGIN_BRUTE_FORCE` | Login bloqueado | 5 intentos fallidos en 15 min |
| 451 | `UNAVAILABLE_FOR_LEGAL_REASONS` | Bloqueado por ley | Información clasificada |

### 2.2 Errores 5xx (servidor)

| HTTP | code | Título | Cuándo |
|---|---|---|---|
| 500 | `INTERNAL_SERVER_ERROR` | Error interno | Excepción no controlada |
| 500 | `DATABASE_ERROR` | Error de BD | SQLSTATE error no recuperable |
| 502 | `BAD_GATEWAY` | Gateway incorrecto | Integración externa falla |
| 503 | `SERVICE_UNAVAILABLE` | Servicio no disponible | Mantenimiento, BD caída |
| 503 | `SCD_UNAVAILABLE` | SCD no disponible | AND / X-Road no responde |
| 504 | `GATEWAY_TIMEOUT` | Timeout de gateway | Integración externa >30 s |

---

## 3. Ejemplos detallados

### 3.1 Validación fallida (422)

```json
{
  "jsonapi": { "version": "1.0" },
  "errors": [
    {
      "status": "422",
      "code": "VALIDATION_FAILED",
      "title": "Validación fallida",
      "detail": "El campo titulo es obligatorio",
      "source": {
        "pointer": "/data/attributes/titulo"
      }
    },
    {
      "status": "422",
      "code": "VALIDATION_FAILED",
      "title": "Validación fallida",
      "detail": "El archivo debe ser PDF, XLSX, CSV, JSON, RDF u ODF",
      "source": {
        "pointer": "/data/attributes/archivo"
      }
    }
  ]
}
```

### 3.2 No encontrado (404)

```json
{
  "jsonapi": { "version": "1.0" },
  "errors": [
    {
      "id": "01HXYZ98765",
      "status": "404",
      "code": "NOT_FOUND",
      "title": "Recurso no encontrado",
      "detail": "No existe un documento con slug 'plan-accion-2026'",
      "meta": {
        "request_id": "01HXYZ98765"
      }
    }
  ]
}
```

### 3.3 Rate limit (429)

```json
{
  "jsonapi": { "version": "1.0" },
  "errors": [
    {
      "status": "429",
      "code": "TOO_MANY_REQUESTS",
      "title": "Demasiadas solicitudes",
      "detail": "Has excedido el límite de 60 req/min para esta IP",
      "meta": {
        "request_id": "01HXYZRATE01",
        "limit": 60,
        "window_seconds": 60,
        "retry_after": 42
      }
    }
  ]
}
```

Con cabecera HTTP:
```
HTTP/1.1 429 Too Many Requests
Content-Type: application/vnd.api+json
Retry-After: 42
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1700000042
```

### 3.4 MFA requerido (401)

```json
{
  "jsonapi": { "version": "1.0" },
  "errors": [
    {
      "status": "401",
      "code": "MFA_REQUIRED",
      "title": "MFA requerido",
      "detail": "Debes completar la verificación MFA para acceder",
      "meta": {
        "next_step": "POST /api/v1/panel/mfa",
        "mfa_token": "eyJ0eXAiOiJKV1Qi..."
      }
    }
  ]
}
```

### 3.5 Conflicto de versionado (409)

```json
{
  "jsonapi": { "version": "1.0" },
  "errors": [
    {
      "status": "409",
      "code": "VERSION_CONFLICT",
      "title": "Conflicto de versión",
      "detail": "El documento fue modificado por otro usuario. Refresca y reintenta.",
      "meta": {
        "current_etag": "\"abc123...\"",
        "your_etag": "\"def456...\""
      }
    }
  ]
}
```

### 3.6 Servicio caído (503)

```json
{
  "jsonapi": { "version": "1.0" },
  "errors": [
    {
      "status": "503",
      "code": "SERVICE_UNAVAILABLE",
      "title": "Servicio no disponible",
      "detail": "El servicio está en mantenimiento programado hasta las 2026-10-02T03:00:00Z",
      "meta": {
        "retry_after": "2026-10-02T03:00:00Z"
      }
    }
  ]
}
```

---

## 4. Manejo de excepciones en Laravel

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\JsonApi\ErrorResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler
{
    public function register(): void
    {
        $this->renderable(function (Throwable $e, Request $request) {
            if (!$request->expectsJson() && !$request->is('api/*')) {
                return null; // No intervenir en web
            }

            return match (true) {
                $e instanceof ValidationException => $this->validationResponse($e, $request),
                $e instanceof ModelNotFoundException => $this->notFound($e, $request),
                $e instanceof AuthenticationException => $this->unauthenticated($e, $request),
                $e instanceof AuthorizationException => $this->forbidden($e, $request),
                $e instanceof ThrottleRequestsException => $this->rateLimited($e, $request),
                $e instanceof NotFoundHttpException => $this->notFoundRoute($e, $request),
                $e instanceof HttpException => $this->httpException($e, $request),
                default => $this->internalError($e, $request),
            };
        });
    }

    private function validationResponse(ValidationException $e, Request $request): \Illuminate\Http\JsonResponse
    {
        $errors = [];
        foreach ($e->errors() as $field => $messages) {
            foreach ($messages as $msg) {
                $errors[] = [
                    'status' => '422',
                    'code' => 'VALIDATION_FAILED',
                    'title' => 'Validación fallida',
                    'detail' => $msg,
                    'source' => ['pointer' => "/data/attributes/{$field}"],
                ];
            }
        }
        return $this->respond($errors, 422, $request);
    }

    private function notFound(ModelNotFoundException $e, Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->respond([[
            'status' => '404',
            'code' => 'NOT_FOUND',
            'title' => 'Recurso no encontrado',
            'detail' => class_basename($e->getModel()) . " no encontrado",
        ]], 404, $request);
    }

    // ... otros métodos
}
```

---

## 5. Logging de errores

Cada error se loguea con contexto completo:

```php
Log::channel('errors')->error('API error', [
    'request_id' => $request->header('X-Request-ID'),
    'method' => $request->method(),
    'path' => $request->path(),
    'status' => 422,
    'code' => 'VALIDATION_FAILED',
    'user_id' => $request->user()?->id,
    'errors' => $errors,
    'exception_class' => $e::class,
    'exception_message' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

**Niveles de log:**
- `400-499` → `warning` (cliente).
- `500-503` → `error` (servidor).
- `429` → `warning` con rate-limit-context.

**Retención:**
- 30 días hot (Loki).
- 1 año cold (GCS).
- Backup a S3 indefinido.

---

## 6. Monitoreo de errores (Sentry)

```php
\Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($request, $e): void {
    $scope->setUser(['id' => $request->user()?->id, 'email' => $request->user()?->email]);
    $scope->setTag('request_id', $request->header('X-Request-ID'));
    $scope->setTag('route', $request->route()?->getName());
    $scope->setExtra('status', 422);
    $scope->setExtra('url', $request->fullUrl());
});
```

Solo se reportan errores 5xx a Sentry (los 4xx son esperados).

---

## 7. Códigos específicos del dominio

Para errores propios del dominio de transparencia:

| code | Cuándo |
|---|---|
| `DOCUMENTO_NO_PUBLICADO` | Se intenta acceder a un documento en estado ≠ publicado |
| `VERSION_HISTORICA_NO_ACCESIBLE` | Se intenta acceder a una versión archivada |
| `DEPENDENCIA_INACTIVA` | Se intenta asignar servidor a dependencia inactiva |
| `CARGO_NO_EXISTE_EN_ESCALA` | El cargo no está en la escala salarial vigente |
| `TRANSICION_ESTADO_INVALIDA` | borrador → publicado OK; borrador → archivado NO |
| `HASH_DUPLICADO` | El archivo ya existe (mismo SHA-256) |
| `SLUG_YA_EXISTE` | El slug único ya está en uso |
| `ITA_NO_CUMPLE` | Item ITA marcado como no cumple (info, no error) |

---

## 8. Internacionalización de errores

Los mensajes están en español por defecto. Se planea añadir `Accept-Language` para responder en la lengua del cliente:

```http
Accept-Language: en-US
```

```json
{
  "errors": [
    {
      "code": "VALIDATION_FAILED",
      "title": "Validation failed",
      "detail": "The title field is required"
    }
  ]
}
```

(En alcance futuro; por ahora todos los mensajes en español.)

---

## 9. Buenas prácticas

- **NO exponer stack traces** al cliente en producción.
- **NO filtrar detalles internos** (rutas internas, secrets).
- **Mensajes útiles**: el desarrollador debe saber qué corregir.
- **`request_id`** siempre incluido para correlación con logs.
- **`about` link** apuntando a documentación del error en `docs.santamarta.gov.co/errors/{code}`.
- **Idempotencia** de errores: el mismo input produce el mismo error.
