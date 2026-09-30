# GUÍA MAESTRA DE DESARROLLO
## Ciclo de Vida Completo — Fuente de Verdad Única

> **PRINCIPIO RECTOR:** Contrato primero → Implementación por Vertical Slice → Verificación continua en cada paso
>
> Este documento integra en un único ciclo secuencial e infalible:
> - **Reglas absolutas** del stack y la metodología
> - **Contract-First / API-First** como puerta de entrada obligatoria
> - **Backend + Frontend** organizados por fase de desarrollo
> - **Checkpoints de verificación** embebidos en cada fase (no al final)
> - **Auditoría final** de 25 puntos antes de cerrar cualquier módulo
>
> **Rigor:** Nivel Doctorado. Un sistema que no supere cada checkpoint NO avanza a la siguiente fase.
>
> **Fuente de verdad del contrato HTTP:** el archivo OpenAPI (`openapi.yaml`) es la única
> fuente de verdad del shape de la API. El backend (clases `JsonResource` de Laravel 13 con
> `withoutWrapping()`) lo emite y el frontend (interfaces TS + services) lo consume con la
> **misma** convención. El shape es **Flat Envelope** (`success`, `message`, `data`, `meta`,
> `errors`), media type **`application/json`**.

---

# CAPÍTULO 0 — FUNDAMENTOS Y COMPROMISOS

## 0.1 Reglas Absolutas del Stack

Stack personal para todos los proyectos de desarrollo.

**Fundamento:** Cada tecnología, librería, paquete y framework se usa **estrictamente según su documentación oficial**. Nada de tutoriales de terceros, blogs, workarounds no documentados, o prácticas no validadas por la fuente oficial.

1. **Documentación oficial siempre.** Toda decisión técnica debe poder rastrearse a la documentación oficial de la tecnología correspondiente.

2. **Backend y frontend desacoplados.** El backend es API REST profesional. El frontend es un cliente independiente. Comunicación exclusiva por HTTP API + WebSockets. Sin Inertia, sin Blade.

3. **Proyectos separados dentro de un mismo directorio raíz.** Backend (Laravel) y frontend (Vue) son carpetas independientes dentro del proyecto.

   ```
   /var/www/mi-proyecto/
   ├── backend/          ← Laravel (API REST)
   └── frontend/         ← Vue 3 + TypeScript (cliente SPA)
   ```

4. **Clean Architecture con Contracts.** Separación estricta de capas con interfaces.

5. **Desarrollo Full-Stack con integración continua.** Cada funcionalidad del backend se integra inmediatamente con el frontend y se valida con pruebas completas antes de pasar a la siguiente.

6. **Super-Admin del sistema.** Todo proyecto debe tener un rol `super-admin` con acceso total mediante `Gate::before()` según la documentación oficial de **Spatie Permission ^8**:
   - El callback **retorna `null`** —nunca `false`— cuando el usuario NO es super-admin.
   - Desde Laravel 11 el `Gate::before` se registra en `AppServiceProvider::boot()`.

   ```php
   public function boot(): void
   {
       Gate::before(function ($user, string $ability) {
           return $user->hasRole('super-admin') ? true : null;
       });
   }
   ```

7. **Seeders: usar `updateOrCreate` con password directo.** El password se escribe en texto plano y el cast `hashed` de Laravel lo hashea automáticamente.

   ```php
   User::updateOrCreate(
       ['email' => 'usuario@example.com'],
       ['name' => 'Usuario', 'password' => 'password_real']
   );
   ```

8. **Frontend: Accept y Content-Type deben ser `application/json`.** El backend usa `JsonResource` con `withoutWrapping()` (Laravel 13 nativo).

9. **Verificar login desde el navegador después de cambios en auth.** Las pruebas con curl no son suficientes.

10. **Después de cada sub-agente, verificar:** servidores activos, login funciona desde navegador, tests pasan, build pasa.

11. **Usar SDD para todo el desarrollo.**

    ```
    sdd-new → sdd-explore → sdd-propose → sdd-spec → sdd-design → sdd-tasks
        → [Contract-First: OpenAPI spec + Prism mock]
        → sdd-apply → sdd-verify
    ```

12. **Usar Engram para memoria persistente.**

13. **Usar Graphify para diagramas de arquitectura.**

14. **Actualizar memoria después de cada regla o aprendizaje.**

---

## 0.2 Metodología: Vertical Slice Development

> ⚠️ **CUMPLIMIENTO ESTRICTO — SIN EXCEPCIONES.**

Cada funcionalidad se desarrolla completa — desde base de datos hasta UI — antes de pasar a la siguiente.

### Principio fundamental (NO NEGOCIABLE)

> **No se avanza a una nueva funcionalidad sin antes haber integrado y validado la actual en el frontend.**
>
> **Cada slice vertical: base de datos → API → lógica → frontend → pruebas → validación visual.**

### Flujo por funcionalidad

| Paso | Actividad |
|------|-----------|
| 1 | Backend: API, lógica, base de datos |
| 2 | Integración con frontend |
| 3 | Pruebas unitarias (backend y frontend) |
| 4 | Pruebas de integración |
| 5 | Feature tests |
| 6 | Suite completa (unitarias + integración + feature + E2E) |
| 7 | Validación visual y funcional en el frontend |

Solo cuando **todas las pruebas pasan y la integración es visible y funcional en el frontend**, se considera la tarea completa.

---

## 0.3 Flujo de Cierre SDD

#### Por cada PR archivado:

1. Verificar que TODO esté guardado en Engram
2. Llamar `mem_session_summary` con resumen completo del PR
3. Ejecutar `/sinapsis-learning`
4. Ejecutar `/promote`

#### Solo al cerrar el cambio completo (último PR aprobado):

5. Ejecutar `/clear`

---

## 0.4 Estrategia de Ramas y Pull Requests

### Ramas permanentes (3)

| Rama | Propósito | Protegida |
|------|-----------|-----------|
| `main` | Producción | Sí — requiere PR + review |
| `develop` | Integración | Sí — requiere PR |
| `staging` | Pruebas/QA | Sí — requiere PR |

### Flujo por módulo/feature

```
main (producción)
 ↑
staging (pruebas/QA)
 ↑
develop (integración)
 ↑
feat/{modulo} (desarrollo)
```

### Flujo completo

```
1. feat/{modulo}  → desde develop
2. Merge feat → develop (tests, resolver conflictos)
3. Merge develop + staging → staging (fusionar)
4. Deploy a servidor de pruebas (staging)
5. Tests pasan en staging
6. Merge staging → main (producción)
```

### Tipos de ramas

| Prefijo | Uso |
|---------|-----|
| `feat/` | Nueva funcionalidad |
| `fix/` | Corrección de bug |
| `refactor/` | Reestructuración sin cambio funcional |
| `docs/` | Solo documentación |
| `test/` | Solo tests |
| `chore/` | Mantenimiento, dependencias, config |
| `hotfix/` | Fix urgente en producción |

### Pull Requests

**PR como unidad de review, no de código.** El PR debe poder revisarse en ≤ 30 minutos.

**Review Workload Guard:** > 400 líneas modificadas → el PR **debe** dividirse en 2 o más PRs encadenados.

---

# CAPÍTULO 1 — CONTRACT-FIRST: EL CONTRATO ANTES QUE EL CÓDIGO

> **"Contrato primero, código después"**
>
> **Posición en el flujo SDD:** este capítulo se ejecuta DESPUÉS de `sdd-tasks` y ANTES de `sdd-apply`.
>
> **Convención de respuesta:** Flat Envelope — `{success, message, data, meta, errors}`
> **Media type:** `application/json` (NO SE DEBE USAR ES PROHIBIDO `application/vnd.api+json`)

---

## Fuente de Verdad Única

El OpenAPI (`openapi.yaml`) es la **única fuente de verdad** del contrato HTTP. Backend y frontend NO se acoplan entre sí: ambos se acoplan al MISMO OpenAPI.

- **Backend** DEBE emitir exactamente lo que el OpenAPI declara.
- **Frontend** DEBE tipar y validar exactamente lo que el OpenAPI declara.
- Cada operación lleva `x-status` (`pending`/`implemented`) para indicar contra qué servidor apuntar.

---

## PASO 0 — Verificación Inicial

**¿Existe ya una especificación OpenAPI para esta API?**
- **SÍ** → continuar en el **Paso 2**
- **NO** → proceder al **Paso 1**

---

## PASO 1 — Diseño del Contrato

Redactar especificación OpenAPI (**3.1.x**) en formato YAML, en el archivo `openapi.yaml`.

El contrato **debe contener obligatoriamente**:
- `openapi`, `info`, `servers`, `paths`, `components.schemas`, `components.securitySchemes`, **ejemplos**

Se valida con **`openapi-spec-validator`** y de estilo con **Redocly CLI**.

---

## PASO 1.1 — Convenciones de Autoría del Contrato (Flat Envelope)

### Formato Flat Envelope (Convención Oficial)

Backend usa Laravel `JsonResource` con `withoutWrapping()` para producir envelopes planos.

**Respuesta de un recurso individual:**
```json
{
  "success": true,
  "message": null,
  "data": {
    "id": 1,
    "type": "users",
    "name": "Ada Lovelace",
    "email": "ada@example.com",
    "created_at": "2026-01-15T05:00:18.000000Z"
  }
}
```

**Respuesta de colección (paginada):**
```json
{
  "success": true,
  "message": null,
  "data": [
    { "id": 1, "type": "users", "name": "Ada Lovelace", "email": "ada@example.com" },
    { "id": 2, "type": "users", "name": "Alan Turing", "email": "alan@example.com" }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 75
  },
  "links": {
    "first": "/api/v1/users?page=1",
    "last": "/api/v1/users?page=5",
    "prev": null,
    "next": "/api/v1/users?page=2"
  }
}
```

**Respuesta de error (404/401/403):**
```json
{
  "success": false,
  "message": "El recurso no fue encontrado.",
  "data": null,
  "errors": null
}
```

**Respuesta de error de validación (422):**
```json
{
  "success": false,
  "message": "Error de validación.",
  "data": null,
  "errors": {
    "name": ["El campo name es obligatorio."],
    "email": ["El correo electrónico debe ser válido."]
  }
}
```

### Componentes compartidos canónicos

```yaml
components:
  schemas:
    ApiEnvelope:
      type: object
      required: [success, data]
      properties:
        success: { type: boolean }
        message: { type: [string, 'null'] }
        data: { type: [object, 'null'] }
        errors: { type: [object, 'null'] }

    PaginatedEnvelope:
      allOf:
        - $ref: '#/components/schemas/ApiEnvelope'
        - type: object
          required: [meta, links]
          properties:
            data: { type: array }
            meta: { $ref: '#/components/schemas/PageMeta' }
            links: { $ref: '#/components/schemas/CollectionLinks' }

    PageMeta:
      type: object
      required: [current_page, from, last_page, path, per_page, to, total]
      properties:
        current_page: { type: integer, minimum: 1 }
        from: { type: [integer, 'null'], minimum: 1 }
        last_page: { type: integer, minimum: 1 }
        path: { type: string, format: uri }
        per_page: { type: integer, minimum: 1 }
        to: { type: [integer, 'null'], minimum: 1 }
        total: { type: integer, minimum: 0 }

    CollectionLinks:
      type: object
      required: [first, last, prev, next]
      properties:
        first: { type: [string, 'null'], format: uri }
        last: { type: [string, 'null'], format: uri }
        prev: { type: [string, 'null'], format: uri }
        next: { type: [string, 'null'], format: uri }

    ApiError:
      type: object
      required: [message]
      properties:
        message: { type: string }
        errors: { type: [object, 'null'] }
```

### Patrón por recurso

Por cada recurso se declaran **tres** schemas (no cuatro):

```yaml
# 1) Item — recurso individual (va en data del envelope)
UserItem:
  type: object
  required: [id, type]
  properties:
    id: { type: integer }
    type: { type: string, const: users }
    name: { type: string }
    email: { type: string }
    role: { type: string }
    active: { type: boolean }
    created_at: { type: string, format: date-time, readOnly: true }
    updated_at: { type: string, format: date-time, readOnly: true }

# 2) Collection — array paginado (va en data del PaginatedEnvelope)
UserCollection:
  type: object
  required: [data, meta, links]
  properties:
    data: { type: array, items: { $ref: '#/components/schemas/UserItem' } }
    meta: { $ref: '#/components/schemas/PageMeta' }
    links: { $ref: '#/components/schemas/CollectionLinks' }

# 3) Input — request body (para POST/PATCH)
UserInput:
  type: object
  required: [name, email]
  properties:
    name: { type: string, maxLength: 120 }
    email: { type: string, format: email, maxLength: 200 }
    password: { type: string, format: password, minLength: 8 }
    role: { type: string, maxLength: 60 }
    active: { type: boolean }
```

### Reglas de autoría

| Regla | Convención |
|-------|-----------|
| Media type | `application/json` en toda respuesta y request |
| `id` | integer (no string) |
| `type` | plural en kebab-case, declarado con `const` |
| `readOnly` | campos autogenerados → excluidos del request body |
| Update | verbo `PATCH` (no `PUT`) |
| Paginación | parámetros `page` y `per_page`; `links` y `meta` en top-level |
| Errores | objeto `errors` con clave por campo: `{"campo": ["mensaje"]}` |

---

## PASO 2 — Mock con Prism

```bash
docker run --rm -p 4010:4010 \
  -v $(pwd)/openapi.yaml:/tmp/openapi.yaml \
  stoplight/prism:4 mock -h 0.0.0.0 /tmp/openapi.yaml
```

**No se continúa al Paso 3 hasta que el mock responda correctamente.**

---

## PASO 3 — Desarrollo en Paralelo

**Solo después del mock activo:**

- **Backend**: implementa lógica real.
- **Frontend**: Tipos TypeScript se generan desde el contrato:

  ```bash
  npx openapi-typescript openapi.yaml -o src/types/api.d.ts --read-write-markers
  ```

---

## Resumen del Flujo Contract-First

| # | Paso | Condición para avanzar |
|---|------|------------------------|
| 0 | Verificar si existe contrato | — |
| 1 | Diseñar contrato OpenAPI | Aprobación del Product Owner |
| 2 | Levantar mock con Prism | Mock responde correctamente |
| 3 | Desarrollo paralelo | Backend + frontend implementados |
| 4 | Validar implementación contra contrato | 100% de pruebas de contrato pasan |

---

## Reglas del Hook (Flat Envelope)

| Regla | Descripción | Estado |
|-------|-------------|--------|
| R-22 | Usar `JsonResource` con `withoutWrapping()` (Laravel 13) | ✅ Actualizada |
| R-24 | OpenAPI debe usar `application/json` | ✅ Vigente |

---

# CAPÍTULO 2 — ARQUITECTURA Y CONFIGURACIÓN INICIAL

## 2.1 Decisión de Autenticación

| Consumidores | Solución |
|---|---|
| Solo frontend web (Vue SPA) | Sanctum |
| Solo apps móviles (Flutter) o terceros | Passport |
| Ambos (web + móvil + terceros) | Sanctum + Passport |

### Sanctum

```bash
php artisan install:api
```

### Passport (OAuth2)

```bash
php artisan install:api --passport
```

El modelo `User` debe usar el trait `HasApiTokens` **y** implementar la interfaz `Laravel\Passport\Contracts\OAuthenticatable`.

### Coexistencia Sanctum + Passport

```php
// config/auth.php
'guards' => [
    'web' => ['driver' => 'session', 'provider' => 'users'],
    'api' => ['driver' => 'passport', 'provider' => 'users'],
],
```

## 2.2 Instalación de Frameworks

### Backend — Laravel

```bash
composer create-project laravel/laravel backend
php artisan migrate
```

### Frontend — Vue 3 + TypeScript + Vite

```bash
npm create vite@latest frontend -- --template vue-ts
npm install pinia vue-router@4 @tanstack/vue-query @tanstack/vue-table @tanstack/vue-form zod axios tailwindcss @tailwindcss/vite vue3-toastify @fortawesome/fontawesome-free
npm install -D openapi-typescript
```

## 2.3 Estructura de Carpetas y Clean Architecture

```
Controller → FormRequest → Service → Repository → Model
                ↑
    Contracts (Interface de Repository y Service)
```

---

# CAPÍTULO 3 — CICLO DE DESARROLLO BACKEND

> Basado en Laravel 13.x con PHP 8.4+ y PostgreSQL 18.
> Documentación oficial: `laravel-13-docs/`

## 3.1 Stack y Convenciones

| Categoría | Tecnología |
|-----------|-----------|
| Lenguaje | PHP ^8.4 |
| Framework | Laravel ^13 |
| Base de datos | PostgreSQL 18 |
| Testing | Pest 4 / PHPUnit 12 |
| Análisis estático | PHPStan nivel 8 |
| Formateo | Pint (auto-fix) |

### Convenciones PHP

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;
```

**`declare(strict_types=1)` es OBLIGATORIO en todo archivo PHP.**

---

## 3.2 Migraciones y Modelos

### Migración (Laravel 13 — `migrations.md`)

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 200)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('role', 60)->default('user');
            $table->boolean('active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

### Modelo (Laravel 13 — `eloquent.md`, `eloquent-relationships.md`)

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class User extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'role', 'active', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'active' => 'boolean',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
```

### Relaciones comunes (Laravel 13 — `eloquent-relationships.md`)

```php
// Uno a muchos
public function posts(): HasMany
{
    return $this->hasMany(Post::class);
}

// Uno a muchos (inverso)
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

// Muchos a muchos
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class, 'role_user')
        ->withPivot('created_at')
        ->withTimestamps();
}

// Uno a uno
public function account(): HasOne
{
    return $this->hasOne(Account::class);
}
```

### Seeder con Password Plano

```php
User::updateOrCreate(
    ['email' => 'admin@example.com'],
    ['name' => 'Admin', 'password' => 'password_real', 'role' => 'admin']
);
```

---

## 3.3 Resource (Flat Envelope — Laravel 13 Native)

> **Doc:** `laravel-13-docs/eloquent-resources.md`

Laravel 13 incluye `Illuminate\Http\Resources\JsonResource` que, combinado con `JsonResource::withoutWrapping()`, produce envelopes planos válidos.

### Generar Resource

```bash
php artisan make:resource UserResource
```

### Resource Declarativo (forma recomendada)

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonResource;

final class UserResource extends JsonResource
{
    /** Los atributos se exponen directamente en el envelope — Laravel los extrae automáticamente */
    public $attributes = [
        'name',
        'email',
        'phone',
        'role',
        'active',
        'created_at',
        'updated_at',
    ];

    public static $wrap = null; // disables wrapping — flat envelope output
}
```

> En `AppServiceProvider::boot()` o en el constructor del Resource:
> `JsonResource::withoutWrapping();`
> Esto hace que TODOS los recursos de la aplicación emitAN flat envelope.

### Resource con atributos condicionales

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonResource;

final class UserResource extends JsonResource
{
    public $attributes = ['name', 'email', 'role', 'created_at'];

    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'type' => 'users',
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'created_at' => $this->created_at?->toIso8601String(),
        ];

        if ($request->user()?->can('view-phone', $this->resource)) {
            $data['phone'] = $this->phone;
        }

        return $data;
    }
}
```

### Shapes de Respuesta Flat Envelope

**1. Recurso Individual:**
```json
{
  "success": true,
  "message": null,
  "data": {
    "id": 1,
    "type": "users",
    "name": "Ada Lovelace",
    "email": "ada@example.com",
    "active": true,
    "created_at": "2026-01-15T05:00:18.000000Z",
    "updated_at": "2026-01-15T05:00:18.000000Z"
  }
}
```

**2. Colección Paginada:**
```json
{
  "success": true,
  "message": null,
  "data": [
    { "id": 1, "type": "users", "name": "Ada Lovelace" }
  ],
  "meta": {
    "current_page": 1, "from": 1, "last_page": 5,
    "path": "/api/v1/users", "per_page": 15, "to": 15, "total": 75
  },
  "links": {
    "first": "/api/v1/users?page=1",
    "last": "/api/v1/users?page=5",
    "prev": null,
    "next": "/api/v1/users?page=2"
  }
}
```

**3. Error No-Validación:**
```json
{
  "success": false,
  "message": "El recurso no fue encontrado.",
  "data": null,
  "errors": null
}
```

**4. Error de Validación (422):**
```json
{
  "success": false,
  "message": "Error de validación.",
  "data": null,
  "errors": {
    "name": ["El campo name es obligatorio."],
    "email": ["El correo electrónico debe ser válido."]
  }
}
```

### Helpers Obligatorios (ApiResponse)

```php
<?php

declare(strict_types=1);

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function ok(mixed $data = null, ?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ]);
    }

    public static function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? 'Recurso creado.',
            'data' => $data,
            'errors' => null,
        ], 201);
    }

    public static function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status);
    }

    public static function notFound(string $message = 'Recurso no encontrado.'): JsonResponse
    {
        return self::error($message, 404);
    }

    public static function unauthorized(string $message = 'No autenticado.'): JsonResponse
    {
        return self::error($message, 401);
    }

    public static function forbidden(string $message = 'No autorizado.'): JsonResponse
    {
        return self::error($message, 403);
    }

    public static function validationError(array $errors): JsonResponse
    {
        return self::error('Error de validación.', 422, $errors);
    }

    public static function tooManyRequests(string $message = 'Demasiadas solicitudes.'): JsonResponse
    {
        return self::error($message, 429);
    }
}
```

---

## 3.4 FormRequest y Validación

> **Doc:** `laravel-13-docs/validation.md`, `laravel-13-docs/requests.md`

### StoreRequest (Laravel 13)

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

final class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\User::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:200', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['sometimes', 'string', 'max:60'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'data' => null,
                'errors' => $validator->errors()->toArray(),
            ], 422)
        );
    }
}
```

### UpdateRequest (Laravel 13)

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');
        return $this->user()?->can('update', $user) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => [
                'sometimes', 'email', 'max:200',
                'unique:users,email,' . $this->route('user'),
            ],
            'password' => ['sometimes', 'string', 'min:8'],
            'role' => ['sometimes', 'string', 'max:60'],
        ];
    }
}
```

### Validación de arrays (Laravel 13)

```php
// Validar items[].quantity como array de objetos
'items' => ['required', 'array', 'min:1'],
'items.*.quantity' => ['required', 'integer', 'min:1'],
'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
```

### Mensajes de error personalizados

```php
public function messages(): array
{
    return [
        'email.required' => 'El correo electrónico es obligatorio.',
        'email.unique' => 'Este correo ya está registrado.',
    ];
}
```

---

## 3.5 Service y Repository (Clean Architecture)

### Contrato de Repository

```php
<?php

declare(strict_types=1);

namespace App\Contracts\Repository;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    public function findAll(): Collection;
    public function findAllPaginated(int $perPage = 15);
    public function findById(int $id): ?Model;
    public function findByEmail(string $email): ?Model;
    public function create(array $data): Model;
    public function update(Model $model, array $data): Model;
    public function delete(Model $model): bool;
}
```

### Repository Concreto

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repository\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

final class UserRepository implements UserRepositoryInterface
{
    public function findAll(): Collection
    {
        return User::all();
    }

    public function findAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?Model
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?Model
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data): Model
    {
        return User::create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($data);
        return $model->fresh();
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }
}
```

### Contrato de Service

```php
<?php

declare(strict_types=1);

namespace App\Contracts\Service;

use Illuminate\Pagination\LengthAwarePaginator;

interface UserServiceInterface
{
    public function list(int $perPage = 15): LengthAwarePaginator;
    public function show(int $id): User;
    public function store(array $data): User;
    public function update(int $id, array $data): User;
    public function destroy(int $id): bool;
}
```

### Service Concreto

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repository\UserRepositoryInterface;
use App\Contracts\Service\UserServiceInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

final class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->findAllPaginated($perPage);
    }

    public function show(int $id): User
    {
        return $this->userRepository->findById($id)
            ?? throw new ModelNotFoundException();
    }

    public function store(array $data): User
    {
        return $this->userRepository->create($data);
    }

    public function update(int $id, array $data): User
    {
        $user = $this->userRepository->findById($id)
            ?? throw new ModelNotFoundException();

        return $this->userRepository->update($user, $data);
    }

    public function destroy(int $id): bool
    {
        $user = $this->userRepository->findById($id)
            ?? throw new ModelNotFoundException();

        return $this->userRepository->delete($user);
    }
}
```

### Registro en ServiceProvider

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repository\UserRepositoryInterface;
use App\Contracts\Service\UserServiceInterface;
use App\Repositories\UserRepository;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
    }
}
```

---

## 3.6 Policy y Autorización

> **Doc:** `laravel-13-docs/authorization.md`

### Policy completa

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Post;
use Illuminate\Auth\Access\Response;

final class PostPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('view posts')
            ? Response::allow()
            : Response::deny();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create posts')
            ? Response::allow()
            : Response::deny();
    }

    public function update(User $user, Post $post): Response
    {
        $isOwner = $user->id === $post->user_id;
        $isAdmin = $user->hasRole('admin');

        if ($isOwner || $isAdmin) {
            return Response::allow();
        }

        return $user->hasPermissionTo('edit posts')
            ? Response::allow()
            : Response::deny();
    }

    public function delete(User $user, Post $post): Response
    {
        $isOwner = $user->id === $post->user_id;
        $isAdmin = $user->hasRole('admin');

        if ($isOwner || $isAdmin) {
            return Response::allow();
        }

        return $user->hasPermissionTo('delete posts')
            ? Response::allow()
            : Response::deny();
    }
}
```

### Gate::before para Super-Admin (Laravel 13 — en AppServiceProvider)

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
    }
}
```

---

## 3.7 Rutas y Controladores

> **Doc:** `laravel-13-docs/routing.md`, `laravel-13-docs/controllers.md`

### Rutas (routes/api.php)

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('admin')
        ->middleware('permission:access admin')
        ->group(function () {
            Route::apiResource('users', UserController::class)->except(['update']);
            Route::patch('users/{user}', [UserController::class, 'update']);
        });
});
```

### Controlador

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserServiceInterface;
use Illuminate\Http\JsonResponse;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {}

    public function index(): JsonResponse
    {
        $perPage = (int) request()->integer('per_page', 15);

        return UserResource::collection($this->userService->list($perPage))->response();
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->store($request->validated());

        return UserResource::make($user)->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        return UserResource::make($this->userService->show($id))->response();
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();
        $user = $this->userService->update($id, $validated['data']['attributes'] ?? $validated);

        return UserResource::make($user)->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $this->userService->destroy($id);

        return response()->json(null, 204);
    }
}
```

---

## 3.8 Autenticación: Sanctum

> **Doc:** `laravel-13-docs/sanctum.md`

### Sanctum SPA (cookie-based — Vue SPA)

```php
// Rutas protegidas por Sanctum (cookies) — Vue SPA
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn() => request()->user());
    Route::post('/logout', [AuthController::class, 'logout']);
});
```

### Sanctum API Token (para Flutter/móvil)

```php
// Rutas protegidas por Sanctum API token (Bearer) — Flutter/terceros
Route::middleware('auth:sanctum')->group(function () {
    // El token se envía en el header: Authorization: Bearer <token>
});

// Proteger rutas con habilidades específicas:
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tasks', fn() => Task::all())
        ->middleware('scope:read-tasks');

    Route::post('/tasks', fn() => Task::create(['title' => request('title')]))
        ->middleware('scope:create-tasks');
});
```

### CSRF y Sanctum SPA

```php
// En bootstrap/app.php (Laravel 11+):
->withMiddleware(function (Middleware $middleware) {
    $middleware->statefulApi();
})
```

### Probar Sanctum en tests

```php
use App\Models\User;
use Laravel\Sanctum\Sanctum;

public function test_task_list_can_be_retrieved(): void
{
    Sanctum::actingAs(User::factory()->create(), ['view-tasks']);

    $response = $this->get('/api/task');

    $response->assertOk();
}

// Todos los permisos:
Sanctum::actingAs(User::factory()->create(), ['*']);
```

---

## 3.9 Autenticación: Passport (OAuth2)

> **Doc:** `laravel-13-docs/passport.md`

### Modelo User con Passport

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Contracts\OAuthenticatable;

final class User extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens;
    // ...
}
```

### Rutas Passport

```php
Route::middleware('auth:api')->group(function () {
    Route::apiResource('posts', PostController::class);
});
```

### Alcances (Scopes)

```php
// Definir alcances en AuthServiceProvider
public function boot(): void
{
    Passport::tokensCan([
        'create-posts' => 'Crear posts',
        'read-posts' => 'Leer posts',
        'update-posts' => 'Actualizar posts',
        'delete-posts' => 'Eliminar posts',
    ]);

    Passport::setDefaultScope(['read-posts']);
}
```

---

## 3.10 Rate Limiting

> **Doc:** `laravel-13-docs/rate-limiting.md`

```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('login', function (Request $request) {
    return [
        Limit::perMinute(5)->by($request->input('email') . '|' . $request->ip()),
        Limit::perMinute(20)->by($request->ip()),
    ];
});

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

---

## 3.11 Colas y Jobs

> **Doc:** `laravel-13-docs/queues.md`

### Job sincrónico (para testing)

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessUserExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly int $userId,
        public readonly string $format,
    ) {}

    public function handle(): void
    {
        // Procesamiento pesado aquí
    }

    public function failed(\Throwable $exception): void
    {
        // Notificar al usuario del fallo
    }
}
```

### Dispatch con delay

```php
ProcessUserExport::dispatch($userId, 'pdf')
    ->delay(now()->addMinutes(5))
    ->onQueue('exports');
```

### ShouldQueue como interface

```php
// Cualquier clase que implemente ShouldQueue es un job
final class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 300];

    public function __construct(public readonly User $user) {}

    public function handle(): void
    {
        // Envío de email
    }
}
```

---

## 3.12 Horizon (Supervisor de Colas)

> **Doc:** `laravel-13-docs/horizon.md`

```bash
composer require laravel/horizon
php artisan horizon:install
```

### config/horizon.php

```php
<?php

return [
    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 10,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 1,
            'timeout' => 60,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-1' => [
                'maxProcesses' => 50,
                'balanceMaxShift' => 10,
                'balanceMinShift' => 1,
            ],
        ],
        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 3,
            ],
        ],
    ],
];
```

### Balance strategies

| Strategy | Descripción |
|----------|-------------|
| `auto` | Octane/Horizon escala automáticamente según el tráfico |
| `simple` | Los workers comparten las colas equitativamente |
| `none` | Cada worker procesa una cola específica |

### Desplegar Horizon en producción

```bash
php artisan horizon:terminate  # Termina los workers existentes
php artisan horizon          # Inicia los nuevos workers
```

---

## 3.13 Octane (FrankenPHP)

> **Doc:** `laravel-13-docs/octane.md`

### Instalación

```bash
composer require laravel/octane
php artisan octane:install --server=frankenphp
```

### Arranque

```bash
# Desarrollo
php artisan octane:frankenphp --host=0.0.0.0 --port=8000 --watch

# Producción
php artisan octane:frankenphp --host=0.0.0.0 --port=8000 --max-requests=500
```

### Reload (reinicia workers sin downtime)

```bash
php artisan octane:reload
```

### Tareas concurrentes (Octane)

```php
use Laravel\Octane\Facades\Octane;

$responses = Octane::concurrently([
    fn () => Http::get('https://api.github.com/users'),
    fn () => Cache::get('stats'),
    fn () => DB::table('users')->count(),
]);
```

### Octane Cache (Swoole/FrankenPHP tables)

```php
// config/octane.php
'cache' => [
    'stores' => [
        'octane' => [
            'driver' => 'octane',
        ],
    ],
],

// Uso
Cache::store('octane')->put('stats', $stats, 60);
```

---

## 3.14 Reverb (WebSockets)

> **Doc:** `laravel-13-docs/reverb.md`

### Instalación

```bash
php artisan install:broadcasting
```

### Canal privado (autorización)

```php
// routes/channels.php
use App\Models\ChatRoom;
use App\Models\User;

Broadcast::channel('chat-room.{room}', function (User $user, ChatRoom $room) {
    return $user->belongsToRoom($room);
});
```

### Evento que se transmite

```php
<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

final class MessageSent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Message $message,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat-room.' . $this->message->chat_room_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
```

### Reverb como servicio en Compose

```yaml
reverb:
  image: ${REGISTRY:-local}/${APP_NAME}-app:${APP_VERSION:-latest}
  restart: unless-stopped
  env_file: [.env]
  command: ["php","artisan","reverb:start","--host=0.0.0.0","--port=8080"]
  networks: [backend]
```

---

## 3.15 CORS y Headers de Seguridad

> **Doc:** `laravel-13-docs/responses.md`

### CORS (Laravel 13)

```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:5173'),
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'Retry-After'],
    'supports_credentials' => true,
    'max_age' => 0,
];
```

### Headers de Seguridad

```php
$response->headers->set('X-Content-Type-Options', 'nosniff');
$response->headers->set('X-Frame-Options', 'DENY');
$response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
$response->headers->set('Content-Security-Policy', "default-src 'self'");
```

---

## 3.16 Manejo de Errores

> **Doc:** `laravel-13-docs/errors.md`

### config/bootstrap/app.php (Laravel 11+)

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e) {
        return ApiResponse::unauthorized();
    });

    $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e) {
        return ApiResponse::forbidden();
    });

    $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return ApiResponse::notFound();
    });

    $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $e) {
        return ApiResponse::tooManyRequests();
    });
})
```

---

## 3.17 Códigos HTTP

| Código | Uso |
|--------|-----|
| 200 | GET, PATCH exitoso con cuerpo |
| 201 | POST creado |
| 204 | DELETE sin cuerpo |
| 401 | No autenticado |
| 403 | No autorizado |
| 404 | No encontrado |
| 409 | Conflicto |
| 422 | Error de validación |
| 429 | Rate limit |

---

## Checkpoints de Verificación Backend

### FASE 1: Migración y Modelo

| # | Verificación | Criterio |
|---|-------------|----------|
| 1 | FK en migración tiene relación en modelo | No puede haber FK sin relación |
| 2 | Soft deletes si el contrato lo declara | `SoftDeletes` trait y `$table->softDeletes()` |

### FASE 2: Resource y FormRequest

| # | Verificación | Criterio |
|---|-------------|----------|
| 3 | Resource declara todos los campos | Coincide con schema OpenAPI |
| 4 | Relaciones como `{ data: { type, id } }` | Nunca FK plana |
| 5 | StoreRequest cubre todos los campos | Excluye autogenerados |
| 6 | UpdateRequest con `sometimes` | Coherencia de tipos |
| 7 | `authorize()` usa Policy | Permiso `create` |

### FASE 3: Service y Policy

| # | Verificación | Criterio |
|---|-------------|----------|
| 8 | Service implementa reglas de negocio | Controller sin lógica compleja |
| 9 | Campos autoincrementables en Service | No recibidos del payload |
| 10 | Campos generados no en `$fillable` | Método dedicado en Service |
| 11 | Policy con métodos completos | `viewAny`, `create`, `update`, `delete` |

---

## Reglas del Hook Aplicables

| Regla | Descripción |
|-------|-------------|
| R-17 | PHP sin `declare(strict_types=1)` |
| R-37 | Lógica en Controller — debe estar en Service |
| R-38 | Repository sin Contract — crear Contract primero |
| R-39 | `hasRole()` en Policy — usar `$user->can()` |
| R-40 | FormRequest sin `#[FailOnUnknownFields]` |
| R-41 | Dinero como float/double — usar `decimal()` |
| R-44 | CORS con `*` y credentials — configuración insegura |
| R-45 | Migración con `->change()` — usar migración explícita |
| R-46 | FK sin `onDelete` — requerir `->onDelete('cascade')` |
| R-47 | Relaciones sin return type — requerido en Eloquent |
| R-51 | Service con Request en firma — usar DTO |

---

# CAPÍTULO 4 — CICLO DE DESARROLLO FRONTEND

## 4.1 Stack y Arquitectura

| Categoría | Tecnología | Notas |
|-----------|------------|-------|
| UI | Vue 3 `<script setup lang="ts">` + Composition API | **NO Options API** |
| Tipos | TypeScript strict | `noImplicitAny`, cero `any` |
| Estado | Pinia | Store tipado |
| HTTP | Axios | Instancia única |
| Routing | Vue Router 4 | Routing declarativo |
| Server state | TanStack Vue Query | Cache y refetch |
| Tablas | TanStack Table v8 | Composable |
| Forms | TanStack Form + Zod | Validación tipada |
| Estilos | TailwindCSS v4 | CSS-first con `@theme {}` |
| Notificaciones | `vue3-toastify` | Singleton global |

### Flujo de Datos

```
VIEW → COMPOSABLE → SERVICE → HTTP CLIENT → INTERFACE (OpenAPI)
```

---

## 4.2 Cliente Axios (Flat Envelope)

```ts
// src/api/client.ts
import axios from 'axios'

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  withCredentials: true,
  timeout: 15_000,
})

// Interceptor de respuestas
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore().clear()
      router.push({ name: 'forbidden' })
    }
    if (error.response?.status === 429) {
      useNotify().warning('Demasiados intentos. Intenta más tarde.')
    }
    return Promise.reject(error)
  }
)

export default apiClient
```

---

## 4.3 Types Generated from OpenAPI

```bash
npx openapi-typescript openapi.yaml -o src/types/api.d.ts --read-write-markers
```

### Interfaces TypeScript

```ts
import type { components } from '@/types/api'

type S = components['schemas']

export type UserItem = S['UserItem']
export type UserCollection = S['UserCollection']
export type UserInput = S['UserInput']

export type PageMeta = S['PageMeta']
export type CollectionLinks = S['CollectionLinks']
export type ApiEnvelope = S['ApiEnvelope']
export type PaginatedEnvelope = S['PaginatedEnvelope']

// Tipos utilitarios para el frontend
export interface ApiResponse<T> {
  success: boolean
  message: string | null
  data: T
  errors: Record<string, string[]> | null
}

export interface PaginatedResponse<T> extends ApiResponse<T[]> {
  meta: PageMeta
  links: CollectionLinks
}
```

---

## 4.4 Componentes Vue 3 — `<script setup>` (Patrones Oficiales)

> **Doc:** `vue-13-docs/api/sfc-script-setup.md`, `vue-13-docs/api/sfc-spec.md`

### SFC Spec (un archivo, una plantilla)

Un componente Vue Single-File Component (`.vue`) puede tener:
- **1 `<template>`** — obligatorio
- **1 `<script setup>`** (o `<script>`) — obligatorio
- **Múltiples `<style>`** (normal + `scoped` + `module`)

```vue
<script setup lang="ts">
// El código se compila como contenido de setup()
import { ref, computed, onMounted } from 'vue'
</script>

<template>
  <!-- Exactamente una raíz -->
  <div>...</div>
</template>

<style scoped>
/* Scoped al componente */
</style>
```

### Compiler Macros (Vue 3.5+)

```ts
// defineProps — inferencia automática de tipos desde el valor por defecto
const props = withDefaults(defineProps<{
  title: string
  count?: number
  status: 'pending' | 'active' | 'closed'
}>(), {
  count: 0,
})

// defineEmits — inferencia de tipos desde el cuerpo
const emit = defineEmits<{
  (e: 'update', id: string, data: Record<string, unknown>): void
  (e: 'delete', id: string): void
}>()

// defineModel (Vue 3.4+) — two-way binding simplificado
const model = defineModel<string>()
// Equivale a:
const model = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

// defineExpose — expose public instance to parent via template ref
defineExpose({ focus, value: model.value })

// defineOptions — opciones de componente sin <script> adicional
defineOptions({ name: 'UserCard', inheritAttrs: false })

// defineSlots — verificación de slots
defineSlots<{
  default(): any
  icon(): any
}>()
```

### Props tipadas con validación (Vue 3 — Style Guide Priority A)

```ts
// ✅ CORRECTO — props detalladas con tipo
const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  status: {
    type: String,
    validator: (v: string) => ['pending', 'active', 'closed'].includes(v),
    default: 'pending',
  },
  count: {
    type: Number,
    default: 0,
  },
})

// ❌ INCORRECTO — props array simple
const props = defineProps(['title', 'status'])
```

### Destructuring reactivo de props (Vue 3.5+)

```vue
<script setup lang="ts">
// Vue 3.5+ permite destructuring reactivo
const { title, status = 'pending' } = defineProps<{
  title: string
  status?: 'pending' | 'active' | 'closed'
}>()
</script>
```

### Scoped CSS (Vue 3 — `vue-13-docs/api/sfc-css-features.md`)

```vue
<style scoped>
/* Scoped: solo afecta al componente actual */
.card {
  padding: 1rem;
}

/* Deep selector — afecta hijos incluso fuera del componente */
.card :deep(.child-element) {
  color: blue;
}

/* Slotted — afecta contenido del slot */
:slotted(div) {
  color: red;
}

/* Global — afecta todo el documento */
:global(.red) {
  color: red;
}

/* CSS Modules — clases como $style.red */
</style>

<style module="classes">
.red { color: red; }
</style>

<!-- v-bind en CSS — conecta variable CSS con estado del componente -->
<script setup>
const theme = ref({ color: 'red' })
</script>
<style scoped>
.text { color: v-bind('theme.color'); }
</style>
```

---

## 4.5 Vue 3 Reactivity (Patrones Oficiales)

> **Doc:** `vue-13-docs/api/reactivity-core.md`, `vue-13-docs/api/reactivity-advanced.md`, `vue-13-docs/guide/essentials/reactivity-fundamentals.md`

### ref vs reactive

```ts
import { ref, reactive, computed, watch, watchEffect, shallowRef, shallowReactive, readonly, toRaw } from 'vue'

// ref — valor escalar (auto-unwrapped en template)
const count = ref(0)
count.value++           // en JS
{{ count }}             // en template (auto-unwrapped)

// reactive — objeto profundo (Proxy)
const state = reactive({
  count: 0,
  user: { name: 'Ada' },
})
state.count++

// readonly — inmutable
const original = reactive({ count: 0 })
const copy = readonly(original)

// shallowRef — solo .value es reactivo (para datos grandes)
const items = shallowRef([])

// shallowReactive — solo raíz reactiva
const state = shallowReactive({ foo: 1, nested: { bar: 2 } })

// shallowReadonly — solo raíz inmutable
```

### computed

```ts
// computed — solo lectura
const double = computed(() => count.value * 2)

// computed — writable
const plusOne = computed({
  get: () => count.value + 1,
  set: (val) => { count.value = val - 1 },
})
```

### watch vs watchEffect

```ts
// watch — explícito, perezoso (no corre en init salvo immediate)
watch(count, (newVal, oldVal) => {
  console.log(`count changed from ${oldVal} to ${newVal}`)
})

// watch con múltiples fuentes
watch([count, another], ([newCount, newAnother]) => { ... })

// watchEffect — inmediato, dependencias automáticas
watchEffect(() => {
  console.log(`count is ${count.value}`) // se re-ejecuta cuando count cambia
})

// watchEffect con cleanup
watchEffect((onCleanup) => {
  const timer = setTimeout(() => {}, 1000)
  onCleanup(() => clearTimeout(timer))
})
```

### triggerRef y customRef

```ts
// triggerRef — forzar re-render de shallowRef
import { triggerRef } from 'vue'
const shallow = shallowRef({ count: 0 })
shallow.value.count = 2  // no triggerea
triggerRef(shallow)       // ahora sí

// customRef — ref con control total (ej: debounce)
import { customRef } from 'vue'
export function useDebouncedRef(value, delay = 200) {
  let timeout: ReturnType<typeof setTimeout>
  return customRef((track, trigger) => ({
    get() {
      track()
      return value
    },
    set(newValue) {
      clearTimeout(timeout)
      timeout = setTimeout(() => {
        value = newValue
        trigger()
      }, delay)
    },
  }))
}
```

### toRaw y markRaw

```ts
import { toRaw, markRaw } from 'vue'

// toRaw — obtiene el objeto original del proxy
const raw = toRaw(reactiveFoo)

// markRaw — marca para nunca convertir en proxy
const complex = markRaw({ heavy: true })
```

### Lifecycle hooks en Composition API

> **Doc:** `vue-13-docs/api/composition-api-lifecycle.md`

```ts
import {
  onMounted, onUpdated, onUnmounted,
  onBeforeMount, onBeforeUpdate, onBeforeUnmount,
  onActivated, onDeactivated,
  onRenderTracked, onRenderTriggered,
} from 'vue'

// setup() corre antes que beforeCreate — no usar created aquí
onMounted(() => {
  // Equivalente a mounted()
})

onBeforeMount(() => { /* antes del mount */ })
onMounted(() => { /* después del mount */ })
onBeforeUpdate(() => { /* antes del update */ })
onUpdated(() => { /* después del update */ })
onBeforeUnmount(() => { /* antes del unmount */ })
onUnmounted(() => { /* después del unmount */ })
```

---

## 4.6 Provide / Inject (Comunicación entre componentes)

> **Doc:** `vue-13-docs/api/composition-api-dependency-injection.md`

```ts
// Ancestro (provide)
import { provide, ref } from 'vue'
const theme = ref('dark')
provide('theme', theme)
provide('user', userData)

// Descendiente (inject)
import { inject } from 'vue'
const theme = inject('theme')
const user = inject('user', defaultUser) // con valor por defecto

// Provide/inject con símbolos (evitar colisiones)
import { provide, inject, InjectionKey } from 'vue'
const ThemeKey: InjectionKey<Ref<string>> = Symbol('theme')
provide(ThemeKey, theme)
const theme = inject(ThemeKey)
```

---

## 4.7 Composables (Patrones Oficiales)

### useUser

```ts
// src/composables/useUser.ts
import { ref, computed } from 'vue'
import type { PaginatedResponse, UserItem } from '@/types/api'
import apiClient from '@/api/client'

export function useUser() {
  const users = ref<PaginatedResponse<UserItem> | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  async function fetchUsers(page = 1) {
    isLoading.value = true
    error.value = null
    try {
      const response = await apiClient.get<PaginatedResponse<UserItem>>(
        `/api/v1/users?page=${page}`
      )
      users.value = response.data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Error desconocido'
    } finally {
      isLoading.value = false
    }
  }

  const userCount = computed(() => users.value?.meta.total ?? 0)

  return { users, isLoading, error, fetchUsers, userCount }
}
```

### useNotify

```ts
// src/composables/useNotify.ts
import { toast } from 'vue3-toastify'
import type { ToastOptions } from 'vue3-toastify'

export function useNotify() {
  function success(message: string, options?: ToastOptions) {
    toast.success(message, { position: 'top-right', ...options })
  }
  function error(message: string, options?: ToastOptions) {
    toast.error(message, { position: 'top-right', ...options })
  }
  function info(message: string, options?: ToastOptions) {
    toast.info(message, { position: 'top-right', ...options })
  }
  function warning(message: string, options?: ToastOptions) {
    toast.warning(message, { position: 'top-right', ...options })
  }
  return { success, error, info, warning }
}
```

---

## 4.8 Pinia Store

```ts
// src/stores/auth.ts
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { UserResource } from '@/types/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<UserResource | null>(null)
  const token = ref<string | null>(null)
  const permissions = ref<string[]>([])

  const isAuthenticated = computed(() => !!token.value)

  function hasPermission(permission: string): boolean {
    return permissions.value.includes(permission)
  }

  function login(credentials: { email: string; password: string }) {
    // Login logic
  }

  function logout() {
    user.value = null
    token.value = null
    permissions.value = []
  }

  function clear() { logout() }

  return {
    user, token, permissions,
    isAuthenticated, hasPermission,
    login, logout, clear,
  }
})
```

---

## 4.9 Router y Guards

```ts
// src/router/index.ts
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'auth.login',
      component: () => import('@/views/public/auth/LoginView.vue'),
      meta: { guest: true },
    },
    {
      path: '/admin',
      component: () => import('@/views/admin/AdminLayout.vue'),
      meta: { requiresAuth: true, permission: 'panel-administrative' },
      children: [
        {
          path: 'users',
          name: 'admin.users',
          component: () => import('@/views/admin/users/UsersView.vue'),
          meta: { permission: 'view users' },
        },
      ],
    },
    {
      path: '/forbidden',
      name: 'forbidden',
      component: () => import('@/views/ForbiddenView.vue'),
    },
  ],
})

router.beforeEach((to, from) => {
  const authStore = useAuthStore()

  if (to.meta.guest && authStore.isAuthenticated) {
    return { name: 'admin.dashboard' }
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'forbidden' }
  }

  if (to.meta.permission && !authStore.hasPermission(to.meta.permission as string)) {
    return { name: 'forbidden' }
  }
})

export default router
```

---

## 4.10 Formularios con Zod

```ts
// src/schemas/user.ts
import { z } from 'zod'

export const UserCreateSchema = z.object({
  name: z.string().min(1).max(120),
  email: z.string().email().max(200),
  role: z.string().min(1).max(60),
})

export const UserUpdateSchema = UserCreateSchema.partial()

export type UserCreateInput = z.infer<typeof UserCreateSchema>
export type UserUpdateInput = z.infer<typeof UserUpdateSchema>
```

---

## 4.11 Vue 3 Style Guide — Priority A (Essential)

> **Doc:** `vue-13-docs/style-guide/rules-essential.md`

| Regla | Incorrecto | Correcto | Prioridad |
|-------|-----------|----------|-----------|
| **Multi-word names** | `<Item />`, `<TodoItem />` OK | `<Item />` ❌ → `<TodoItem />` ✅ | A |
| **Props tipadas** | `defineProps(['user'])` | `defineProps<{ user: User }>()` | A |
| **`:key` en v-for** | `v-for="item in items"` | `v-for="item in items" :key="item.id"` | A |
| **Styles scoped** | `<style>` global | `<style scoped>` | A |
| **v-if + v-for** | `<li v-for="i in items" v-if="i.active">` | Usar computed filter o `<template v-for>` | A |
| **v-bind inline** | `:style="{ color: isActive && 'red' }"` | Usar computed | B |

### Reglas Priority A detalladas (de la doc oficial)

**1. Multi-word component names:**
```vue
<!-- ✅ CORRECTO -->
<TodoItem />
<UserCard />
<ArticleList />

<!-- ❌ INCORRECTO -->
<Item />    <!-- conflictúa con HTML <item> -->
```

**2. Props detalladas:**
```ts
// ✅ CORRECTO
const props = defineProps({
  status: {
    type: String,
    required: true,
    validator: (v) => ['syncing', 'synced', 'version-conflict', 'error'].includes(v),
  },
})

// ❌ INCORRECTO (solo para prototipado)
const props = defineProps(['status'])
```

**3. :key en v-for — siempre requerido:**
```vue
<!-- ✅ CORRECTO -->
<div v-for="todo in todos" :key="todo.id">

<!-- ❌ INCORRECTO -->
<div v-for="todo in todos">
```

**4. Scoped CSS:**
```vue
<style scoped>
.button { padding: 0.5rem; }
</style>
```

**5. Evitar v-if + v-for juntos:**
```vue
<!-- ❌ INCORRECTO -->
<li v-for="user in users" v-if="user.active">

<!-- ✅ CORRECTO -->
<li v-for="user in activeUsers">

<!-- O con computed -->
const activeUsers = computed(() => users.value.filter(u => u.active))
```

---

## 4.12 Seguridad Frontend

| Método | ¿Seguro? | Alternativa |
|--------|----------|-------------|
| `localStorage` | ❌ NO | `httpOnly` cookie (Sanctum) |
| `httpOnly cookie` | ✅ SÍ | Solo Sanctum SPA |

### Prohibiciones OWASP

| Regla | Descripción |
|-------|-------------|
| R-06 | Token en localStorage — XSS |
| R-07 | `v-html` con datos de usuario |
| R-09 | `window.location` — usar `router.push()` |
| R-10 | `new Function()` — RCE |
| R-19 | `any` en TypeScript |

---

## 4.13 Accesibilidad (WCAG AA)

- `SkipToContent.vue` — saltar al contenido
- `BaseAccessibilityBar.vue` — contraste y tamaño
- Contraste mínimo 4.5:1
- `tabindex` y focus trapping en modales
- Labels asociados a inputs
- Tamaño táctil mínimo `min-h-[44px]`

---

## Checkpoints de Verificación Frontend

### FASE 4: Interfaces y Servicios

| # | Verificación | Criterio |
|---|-------------|----------|
| 12 | Interfaces TS mapean Flat Envelope | `ApiResponse<T>`, `PaginatedResponse<T>` |
| 13 | Schemas correctos por endpoint | `<Recurso>Item` para show, `<Recurso>Collection` para index |
| 14 | Tipos generados | `openapi-typescript --read-write-markers` |
| 15 | URLs de servicios | Coinciden con `routes/api.php` bajo `/api/v1` |

### FASE 5: Composables y Store

| # | Verificación | Criterio |
|---|-------------|----------|
| 16 | Composables tipados | Estados reactivos con tipos de interfaces |

### FASE 6: Vistas y Formularios

| # | Verificación | Criterio |
|---|-------------|----------|
| 17 | Campos del form | Coinciden con schema Input del contrato |
| 18 | Campos en migración | Existen en `$fillable` del modelo |
| 19 | Campos autogenerados | `disabled` + valor visible |
| 20 | Botones según permisos | `v-if="can('action', entity)"` |

### FASE 7: Guards

| # | Verificación | Criterio |
|---|-------------|----------|
| 21 | Usuario autenticado no ve login | Redirige a dashboard |
| 22 | No redirige automáticamente al login | Va a `forbidden` |
| 23 | Panel admin requiere `panel-administrative` | 403 sin permiso |
| 24 | Permisos FE ↔ BE | Coinciden exactamente |

### FASE 8: Validaciones

| # | Verificación | Criterio |
|---|-------------|----------|
| 25 | Zod vs FormRequest | Proyección estricta de constraints |

---

# CAPÍTULO 5 — AUDITORÍA FINAL: MATRIZ DE 25 PUNTOS

> **Esta auditoría se ejecuta al completar CADA módulo o Vertical Slice antes de marcarlo como terminado.**

| # | Requisito | Cumple |
|---|-----------|--------|
| 1 | Mapear campos de migración y relaciones | ⬜ |
| 2 | Resource retorna campos planos del contrato (`id`, `type`, campos) | ⬜ |
| 3 | FormRequest Store valida campos del contrato | ⬜ |
| 4 | FormRequest Update valida campos | ⬜ |
| 5 | FormRequest autoriza según Policy | ⬜ |
| 6 | Service implementa reglas de negocio | ⬜ |
| 7 | Service genera campos autoincrementados | ⬜ |
| 8 | Policy definida con métodos y lógica de roles | ⬜ |
| 9 | Frontend genera automáticamente el código | ⬜ |
| 10 | Input disabled, código visible | ⬜ |
| 11 | Frontend tipa el resource object flat envelope | ⬜ |
| 12 | Schemas por endpoint: `<Recurso>Item` y `<Recurso>Collection` | ⬜ |
| 13 | Servicios FE envían payload flat envelope con Content-Type: application/json | ⬜ |
| 14 | Services del FE mapean endpoints exactos | ⬜ |
| 15 | Composables y Storage usan interfaces completas | ⬜ |
| 16 | Formularios: campos del contrato = campos del form | ⬜ |
| 17 | Validaciones Frontend vs Backend | ⬜ |
| 18 | Botones según permiso del rol | ⬜ |
| 19 | Rutas validan acceso con guardia | ⬜ |
| 20 | Layout requiere `panel-administrative` | ⬜ |
| 21 | Sidebar filtra rutas según permisos | ⬜ |
| 22 | Usuario autenticado no accede a login | ⬜ |
| 23 | No autenticado NO redirige al login | ⬜ |
| 24 | Guard valida `panel-administrative` | ⬜ |
| 25 | Coherencia de permisos FE ↔ BE | ⬜ |

### Auditoría de Contrato (Flat Envelope)

| # | Verificación | Cumple |
|---|-----------|--------|
| C1 | ¿Flat Envelope `{success, message, data}` + Content-Type `application/json`? | ⬜ |
| C2 | ¿`meta` lleva las 7 claves del paginador? | ⬜ |
| C3 | ¿`links` de colección con 4 claves (first/last/prev/next)? | ⬜ |
| C4 | ¿Error con `success: false`, `message`, `errors`? | ⬜ |
| C5 | ¿Errores de validación como `{ errors: { campo: [mensaje] } }`? | ⬜ |
| C6 | ¿`per_page>100` dispara 422? | ⬜ |
| C7 | ¿Datos personales enmascarados salvo permiso? | ⬜ |

**Criterio de Aprobación:** 25/25 + C1–C7 ✅ → El módulo puede marcarse como terminado.

---

# CAPÍTULO 6 — INFRAESTRUCTURA

## 6.1 Stack

| Categoría | Tecnología |
|-----------|-----------|
| OS | Ubuntu 24.04 |
| Contenedores | Docker Compose |
| Proxy | Nginx |
| TLS | Certbot (Let's Encrypt) |
| CI/CD | GitHub Actions |

## 6.2 HSTS y CSP en Nginx

```nginx
add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
add_header X-Content-Type-Options nosniff;
add_header X-Frame-Options DENY;
add_header Referrer-Policy "strict-origin-when-cross-origin";
add_header Content-Security-Policy "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none';" always;
```

## 6.3 Seguridad Docker

| Práctica | Descripción |
|----------|-------------|
| No ejecutar como root | `user: www-data` en el contenedor PHP |
| Imágenes oficiales | Solo imágenes oficiales de Docker Hub |
| Secrets en .env | Variables sensibles vía .env |
| Redes separadas | Red interna para PHP+DB, red externa para Nginx |
| No exponer puertos innecesarios | Solo 80/443 |

---

# CAPÍTULO 7 — FRONTEND MÓVIL: FLUTTER (OFFLINE-FIRST)

> **Naturaleza.** Es **modular y opt-in**. Consume el **mismo contrato OpenAPI** (`application/json` flat envelope).

## 7.1 Stack móvil

| Categoría | Web (Vue 3) | Móvil (Flutter) | Paquete |
|---|---|---|---|
| Estado | Pinia | Riverpod (`Notifier`) | `flutter_riverpod` ^3.3.2 |
| HTTP | Axios | Dio | `dio` ^5.10.0 |
| Cliente tipado | `openapi-typescript` | `swagger_parser` → Retrofit + Freezed | `swagger_parser` ^1.44.0 |
| Routing | Vue Router 4 | go_router | `go_router` ^17.3.0 |
| Offline | *(PWA)* | **Drift** (SQLite typesafe) | `drift` ^2.34.0 |
| Sync | — | `workmanager` | `workmanager` ^0.9.0 |
| Token | cookie `httpOnly` | `flutter_secure_storage` | `flutter_secure_storage` ^10.3.1 |

```
WIDGET → RIVERPOD NOTIFIER → REPOSITORY ─┬─► LOCAL  (Drift DAO)
                                          └─► REMOTE (Retrofit/Dio)
```

## 7.2 Cliente Dio

```dart
Dio buildDio({
  required String baseUrl,
  required CacheStore cacheStore,
  required Interceptor authInterceptor,
}) {
  final dio = Dio(BaseOptions(
    baseUrl: baseUrl,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  ));
  dio.interceptors.addAll([
    authInterceptor,
    DioCacheInterceptor(
      options: CacheOptions(
        store: cacheStore,
        policy: CachePolicy.request,
        hitCacheOnNetworkFailure: true,
      ),
    ),
  ]);
  return dio;
}
```

## 7.3 Modelos tipados desde el contrato

```bash
dart run swagger_parser
dart run build_runner build --delete-conflicting-outputs
```

## 7.4 Offline-first con Drift

```dart
class Users extends Table {
  TextColumn get id => text()();
  TextColumn get name => text()();
  @override
  Set<Column> get primaryKey => {id};
}

class PendingOperations extends Table {
  IntColumn get seq => integer().autoIncrement()();
  TextColumn get entity => text()();
  TextColumn get op => text()();
  TextColumn get payload => text()();
}

@DriftDatabase(tables: [Users, PendingOperations])
class AppDatabase extends _$AppDatabase {
  Stream<List<User>> watchUsers() => select(users).watch();
}
```

### Flujo de escritura

```
UI → Repository:
  1. escribe en Drift (optimistic)
  2. encola en PendingOperations (outbox)
  3. si hay red → SyncEngine procesa la cola
  4. al confirmar → marca como sincronizado
```

### Sync Engine

```dart
Future<void> flush() async {
  final result = await Connectivity().checkConnectivity();
  if (result.contains(ConnectivityResult.none)) return;
  final pending = await _db.select(_db.pendingOperations).get();
  for (final op in pending) {
    try {
      await _apiClient.send(op);
      await (_db.delete(_db.pendingOperations)..where((t) => t.seq.equals(op.seq))).go();
    } catch (_) { break; }
  }
}
```

---

# CAPÍTULO 8 — DEVOPS Y CONTENEDORES (MÓDULOS)

> **Naturaleza.** Es **modular y opt-in**. Todos respetan las **Convenciones canónicas** del §8.1.

## 8.0 Catálogo de módulos

| Módulo | ¿Cuándo? |
|---|---|
| Convenciones canónicas | Siempre |
| Docker (build + compose) | Siempre |
| Nginx + TLS (Certbot) | Siempre que haya despliegue web |
| PHP / Octane / `phpstan.neon` | Siempre |
| Colas y workers (Horizon) | Si hay jobs en segundo plano |
| Realtime / WebSockets (Reverb) | Si hay notificaciones en vivo |
| Correo y notificaciones | Si el sistema notifica |
| Caché, sesión y scheduler | Casi siempre |
| Almacenamiento (S3/MinIO) | Si se manejan archivos |
| Observabilidad | Siempre en prod |
| Kubernetes | Proyectos grandes |

---

## 8.1 Convenciones canónicas

### 8.1.1 Mapa de puertos

| Servicio | Puerto interno | ¿Expuesto en prod? |
|---|---|---|
| Nginx (edge) | `80`, `443` | **Sí** (único) |
| App Laravel (Octane) | `8000` | No |
| Reverb (WebSockets) | `8080` | No |
| PostgreSQL | `5432` | No |
| Redis | `6379` | No |
| FastAPI (IA sidecar) | `8001` | No |
| vLLM (inferencia local) | `8200` | No |
| Ollama (dev) | `11434` | No |

### 8.1.2 Topología de redes

| Red | Quién vive | Quién la alcanza |
|---|---|---|
| `{proyecto}-edge` | Nginx | Internet → Nginx |
| `{proyecto}-backend` | app, reverb, workers | Nginx → app |
| `{proyecto}-data` | postgres, redis | Solo app/workers |

---

## 8.2 Docker

### Dockerfile

```dockerfile
# docker/app/Dockerfile
FROM dunglas/frankenphp:1-php8.4 AS vendor
WORKDIR /app
RUN install-php-extensions pcntl pdo_pgsql redis intl opcache zip gd bcmath
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

FROM node:22-alpine AS assets
WORKDIR /app
COPY frontend/package*.json ./
RUN npm ci && npm run build

FROM dunglas/frankenphp:1-php8.4 AS final
WORKDIR /app
RUN install-php-extensions pcntl pdo_pgsql redis intl opcache zip gd bcmath
COPY --from=vendor /app/vendor ./vendor
COPY backend/ ./
COPY --from=assets /app/dist ./public/build
RUN composer dump-autoload --optimize --no-dev
ENTRYPOINT ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000"]
EXPOSE 8000
```

### compose.yaml

```yaml
services:
  app:
    image: ${REGISTRY:-local}/${APP_NAME}-app:${APP_VERSION:-latest}
    build: { context: ., dockerfile: docker/app/Dockerfile }
    restart: unless-stopped
    env_file: [.env]
    depends_on:
      db: { condition: service_healthy }
      redis: { condition: service_healthy }
    networks: [backend, data]
    healthcheck:
      test: ["CMD-SHELL", "curl -fsS http://127.0.0.1:8000/up || exit 1"]
      interval: 15s

  nginx:
    build: { context: ., dockerfile: docker/nginx/Dockerfile }
    restart: unless-stopped
    ports: ["80:80", "443:443"]
    networks: [edge, backend]

  db:
    image: postgres:18
    restart: unless-stopped
    networks: [data]
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME} -d ${DB_DATABASE}"]

  redis:
    image: redis:7-alpine
    restart: unless-stopped
    command: ["redis-server", "--requirepass", "${REDIS_PASSWORD}", "--appendonly", "yes"]
    networks: [data]

networks:
  edge: { name: ${APP_NAME}-edge }
  backend: { name: ${APP_NAME}-backend }
  data: { name: ${APP_NAME}-data }
```

### compose.override.yaml (SOLO dev)

```yaml
services:
  app:
    volumes: ["./backend:/app"]
    command: ["php","artisan","octane:frankenphp","--host=0.0.0.0","--port=8000","--watch"]
    ports: ["8000:8000"]
  mailpit:
    image: axllent/mailpit
    ports: ["1025:1025", "8025:8025"]
```

---

## 8.3 Nginx + TLS

```nginx
map $http_upgrade $connection_upgrade { default upgrade; '' close; }
upstream octane { server app:8000; }
upstream reverb { server reverb:8080; }

server {
    listen 80;
    server_name _;
    location /.well-known/acme-challenge/ { root /var/www/certbot; }
    location / { return 301 https://$host$request_uri; }
}

server {
    listen 443 ssl http2;
    ssl_certificate /etc/letsencrypt/live/midominio.gov.co/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/midominio.gov.co/privkey.pem;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    location /app {
        proxy_pass http://reverb;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;
        proxy_read_timeout 3600s;
    }

    location / {
        proxy_pass http://octane;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
    }
}
```

---

## 8.4 PHP / Octane / phpstan.neon

```neon
# phpstan.neon
includes:
    - vendor/larastan/larastan/extension.neon
parameters:
    paths:
        - app/
    level: 8
```

---

## 8.5 Colas y Horizon

```bash
composer require laravel/horizon
php artisan horizon:install
```

Worker dedicado:
```yaml
worker:
  command: ["php","artisan","horizon"]
  networks: [backend, data]
```

---

## 8.6 Realtime / WebSockets (Reverb)

```bash
php artisan install:broadcasting
```

Cliente Echo:
```ts
import Echo from 'laravel-echo';
export const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: 443, wssPort: 443,
  forceTLS: true,
});
```

---

## 8.7 Kubernetes

### Equivalencias

| Compose | K8s |
|---|---|
| servicio `app` | `Deployment` + `Service` ClusterIP `:8000` |
| servicio `worker` | `Deployment` propio |
| `nginx` 80/443 | **Ingress** |
| Certbot | **cert-manager** + `ClusterIssuer` |

### Deployment

```yaml
apiVersion: apps/v1
kind: Deployment
metadata: { name: app, namespace: miapp-prod }
spec:
  replicas: 3
  template:
    spec:
      containers:
        - name: app
          image: registry.midominio.co/miapp-app:1.4.0
          ports: [{ containerPort: 8000 }]
          readinessProbe:
            httpGet: { path: /up, port: 8000 }
            initialDelaySeconds: 5
          livenessProbe:
            httpGet: { path: /up, port: 8000 }
            initialDelaySeconds: 15
```

### HPA

```yaml
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata: { name: app, namespace: miapp-prod }
spec:
  scaleTargetRef: { apiVersion: apps/v1, kind: Deployment, name: app }
  minReplicas: 3
  maxReplicas: 10
  metrics:
    - type: Resource
      resource: { name: cpu, target: { type: Utilization, averageUtilization: 70 } }
```

---

# CAPÍTULO 9 — INTEGRACIÓN DE IA (MÓDULO)

> **Naturaleza.** Opt-in. Se incluye solo si el proyecto tiene un requisito de IA. La inferencia va **siempre en cola** (8.5).

## 9.0 Enfoque canónico — sidecar Python

| Pieza | Tecnología | Rol |
|---|---|---|
| Servicio IA | **FastAPI** (Python 3.12, Pydantic 2.x) | Bus que expone los agentes |
| Orquestación | **LangGraph** (LangChain) | Máquinas de estado, multi-agente |
| Inferencia **local** | **vLLM** | Modelos on-prem; API OpenAI-compatible |
| Inferencia **cloud** | **MiniMax** (OpenAI-compatible) | Solo datos **anonimizados/no-PII** |
| RAG / embeddings | **pgvector** sobre PostgreSQL | Único motor vectorial |
| Herramientas | **MCP** | Servidores MCP por dominio |

---

## 9.1 Arquitectura

### Cliente OpenAI-compatible

```python
from openai import OpenAI

# Local soberano (datos con PII)
local = OpenAI(base_url="http://vllm:8200/v1", api_key="not-needed")

# Cloud opcional (datos anonimizados)
cloud = OpenAI(base_url="https://api.minimax.io/v1", api_key=os.environ["MINIMAX_API_KEY"])
```

### Estrategia cloud → local → reglas

| Fase | Motor | PII |
|---|---|---|
| 1 · MVP | MiniMax (cloud) | mask-then-cloud |
| 2 · Transición | vLLM (shadow) + cloud | local toma flujos con PII |
| 3 · Objetivo | vLLM local + reglas | local sin enmascarar |

### Corpus de decisiones

```sql
CREATE EXTENSION IF NOT EXISTS vector;
CREATE TABLE ai_decisions (
    decision_id        uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    trace_id           uuid NOT NULL,
    entity_id          text NOT NULL,
    graph_node         text NOT NULL,
    created_at         timestamptz NOT NULL DEFAULT now(),
    task_type          text NOT NULL,
    input_masked       text NOT NULL,
    input_hash         bytea NOT NULL,
    input_embedding    vector(1024),
    input_features     jsonb NOT NULL DEFAULT '{}',
    pii_detected       boolean NOT NULL DEFAULT false,
    masking_version    text NOT NULL,
    mask_map_ref       text,
    route_taken        text NOT NULL CHECK (route_taken IN ('regla','llm_local','llm_cloud','humano')),
    provider           text NOT NULL,
    model              text,
    prompt_version     text,
    rule_id            text,
    output_structured  jsonb,
    decision           text,
    confidence         numeric(4,3),
    tokens_prompt      integer,
    tokens_completion  integer,
    latency_ms         integer,
    cost_usd           numeric(10,6),
    human_reviewed     boolean NOT NULL DEFAULT false,
    human_decision     text,
    human_corrected    boolean NOT NULL DEFAULT false,
    phase              smallint NOT NULL CHECK (phase IN (1,2,3)),
    shadow             boolean NOT NULL DEFAULT false,
    shadow_agreement   boolean,
    data_residency     text NOT NULL CHECK (data_residency IN ('local','cloud')),
    schema_version     text NOT NULL DEFAULT 'v1'
);
CREATE INDEX ai_dec_task_time  ON ai_decisions (task_type, created_at DESC);
CREATE INDEX ai_dec_route      ON ai_decisions (route_taken);
CREATE INDEX ai_dec_embedding  ON ai_decisions USING hnsw (input_embedding vector_cosine_ops);
```

---

## 9.2 RAG con pgvector

```sql
CREATE EXTENSION IF NOT EXISTS vector;
ALTER TABLE documentos_chunks ADD COLUMN embedding vector(1024);
CREATE INDEX ON documentos_chunks USING hnsw (embedding vector_cosine_ops);
```

---

## 9.3 Inferencia asíncrona

Las llamadas a LLM **nunca** van en el request HTTP síncrono. Se encolan como **jobs** (módulo 8.5, Horizon) con timeout, reintentos con backoff e idempotencia.

---

## 9.4 Human-in-the-loop

- **PHP:** el job deja el ticket en estado `EN_REVISION_HUMANA`.
- **Python/LangGraph:** `interrupt()` pausa el grafo y persiste el checkpoint.

---

## 9.5 Observabilidad

| Necesidad | Herramienta |
|---|---|
| Trazas de cada llamada LLM | **Langfuse** (open-source) |
| Errores de aplicación | Sentry |

---

## 9.6 Seguridad

- **Enmascarado de PII** obligatorio antes de cualquier salida a cloud.
- **Secretos de LLM** (API keys) por entorno (Secret/vault), nunca en git.
- **Auditoría:** cada decisión asistida por IA se registra.
- **Versionado de prompts** como artefactos versionados.

---

# APÉNDICE A — ÍNDICE MAESTRO DE DOCUMENTACIÓN OFICIAL

### Backend — Laravel y PHP

| Tecnología | Doc oficial |
|---|---|
| Laravel 13.x | https://laravel.com/docs/13.x |
| PHP 8.4 | https://www.php.net/docs.php |
| Laravel Octane | https://laravel.com/docs/13.x/octane |
| FrankenPHP | https://frankenphp.dev/docs/ |
| Laravel Sanctum | https://laravel.com/docs/13.x/sanctum |
| Laravel Passport | https://laravel.com/docs/13.x/passport |
| Spatie Permission | https://spatie.be/docs/laravel-permission/v8 |

### Contrato y API

| Tecnología | Doc oficial |
|---|---|
| OpenAPI 3.1 | https://spec.openapis.org/oas/3.1.0 |
| JSON:API | https://jsonapi.org |
| openapi-typescript | https://openapi-ts.dev/ |
| Prism (mock) | https://docs.stoplight.io/prism |

### Frontend — Vue y ecosistema

| Tecnología | Doc oficial |
|---|---|
| Vue 3 | https://vuejs.org/guide/introduction.html |
| Vue Style Guide | https://vuejs.org/style-guide/ |
| Vue SFC Spec | https://vuejs.org/api/sfc-spec |
| Vue sfc-script-setup | https://vuejs.org/api/sfc-script-setup |
| Vue Reactivity Core | https://vuejs.org/api/reactivity-core |
| Vue Reactivity Advanced | https://vuejs.org/api/reactivity-advanced |
| Vue Composition API Lifecycle | https://vuejs.org/api/composition-api-lifecycle |
| Vue Composition API DI | https://vuejs.org/api/composition-api-dependency-injection |
| Vue SFC CSS Features | https://vuejs.org/api/sfc-css-features |
| TypeScript | https://www.typescriptlang.org/docs/ |
| Vite | https://vite.dev/ |
| Vue Router | https://router.vuejs.org/ |
| Pinia | https://pinia.vuejs.org/ |
| TanStack Query | https://tanstack.com/query/v5/docs/framework/vue/overview |
| TanStack Table | https://tanstack.com/table/v8/docs |
| Zod | https://zod.dev/ |
| Tailwind CSS | https://tailwindcss.com/docs |

### Frontend Móvil — Flutter

| Tecnología | Doc oficial |
|---|---|
| Flutter | https://docs.flutter.dev/ |
| Dart | https://dart.dev/guides |
| Riverpod | https://riverpod.dev/ |
| Dio | https://pub.dev/packages/dio |
| go_router | https://pub.dev/packages/go_router |
| Drift | https://drift.simonbinder.eu/ |
| flutter_secure_storage | https://pub.dev/packages/flutter_secure_storage |
| workmanager | https://pub.dev/packages/workmanager |

### Base de datos, caché y colas

| Tecnología | Doc oficial |
|---|---|
| PostgreSQL 18 | https://www.postgresql.org/docs/18/ |
| pgvector | https://github.com/pgvector/pgvector |
| Redis | https://redis.io/docs/latest/ |
| Laravel Queues | https://laravel.com/docs/13.x/queues |
| Laravel Horizon | https://laravel.com/docs/13.x/horizon |

### Realtime, correo y notificaciones

| Tecnología | Doc oficial |
|---|---|
| Laravel Reverb | https://laravel.com/docs/13.x/reverb |
| Laravel Notifications | https://laravel.com/docs/13.x/notifications |
| Laravel Mail | https://laravel.com/docs/13.x/mail |
| Mailpit | https://mailpit.axllent.org/ |

### DevOps y contenedores

| Tecnología | Doc oficial |
|---|---|
| Docker | https://docs.docker.com/ |
| Docker Compose | https://docs.docker.com/compose/ |
| Nginx | https://nginx.org/en/docs/ |
| Certbot | https://eff-certbot.readthedocs.io/ |
| Kubernetes | https://kubernetes.io/docs/ |
| GitHub Actions | https://docs.github.com/en/actions |

### Inteligencia Artificial — sidecar Python

| Tecnología | Doc oficial |
|---|---|
| FastAPI | https://fastapi.tiangolo.com/ |
| Pydantic | https://docs.pydantic.dev/latest/ |
| LangChain | https://python.langchain.com/docs/ |
| LangGraph | https://langchain-ai.github.io/langgraph/ |
| vLLM | https://docs.vllm.ai/ |
| MiniMax | https://platform.minimax.io/docs/api-reference/text-openai-api |
| pgvector | https://github.com/pgvector/pgvector |
| MCP | https://modelcontextprotocol.io/ |
| Langfuse | https://langfuse.com/docs |

### IA — tooling y alternativa PHP

| Tecnología | Doc oficial |
|---|---|
| Laravel Boost | https://laravel.com/docs/13.x/boost |
| Prism | https://prismphp.com/ |
| Laravel AI SDK | https://laravel.com/docs/13.x/ai-sdk |

### Calidad y testing

| Tecnología | Doc oficial |
|---|---|
| Pest | https://pestphp.com/docs |
| PHPUnit | https://docs.phpunit.de/ |
| PHPStan / Larastan | https://github.com/larastan/larastan |
| Laravel Pint | https://laravel.com/docs/13.x/pint |

---

*Guía Maestra de Desarrollo — Enriquecida con documentación oficial Laravel 13 y Vue 3 — 2025*
