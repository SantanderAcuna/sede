# Capítulo 5 — Capa de Aplicación, CI/CD, IDS, Backups y Respuesta a Incidentes

> **Pila objetivo:** Laravel 13.x (API JSON:API), Vue 3 + TypeScript (SPA desacoplada), Nginx 1.29, PHP 8.4-FPM, PostgreSQL 16, Redis 7, Wazuh 4.x, GitHub Actions, DigitalOcean Droplet + Spaces.
>
> **Convenciones:**
> - `[URL, YYYY-MM-DD]` referencia documental verificada.
> - `[F]` = **Fuente primaria oficial**. `[A]` = **Fuente autoritativa secundaria** (CIS, NIST, OWASP).
> - Secretos **nunca** se imprimen: se referencian por nombre (`${{ secrets.X }}`).
> - Acciones de GitHub **siempre** pinneadas por SHA-256 de 40 caracteres.
> - Marcadores de cambio: `[ADD]`, `[CHANGE]`, `[REMOVE]`, `[FIX]`, `[SECURITY]`.

---

## Índice

1. [Hardening de Laravel 13](#1-hardening-de-laravel-13)
2. [Sanctum y autenticación API](#2-sanctum-y-autenticación-api)
3. [JSON:API en Laravel 13](#3-jsonapi-en-laravel-13)
4. [Endurecimiento de Vue 3 + TypeScript](#4-endurecimiento-de-vue-3--typescript)
5. [CI/CD seguro con GitHub Actions](#5-cicd-seguro-con-github-actions)
6. [Containerización segura](#6-containerización-segura)
7. [Detección de intrusos y monitoreo](#7-detección-de-intrusos-y-monitoreo)
8. [Backups cifrados](#8-backups-cifrados)
9. [Runbook de respuesta a incidentes](#9-runbook-de-respuesta-a-incidentes)
10. [Política de secretos](#10-política-de-secretos)
11. [Apéndice A — Inventario de secretos](#apéndice-a--inventario-de-secretos)
12. [Apéndice B — Referencias bibliográficas](#apéndice-b--referencias-bibliográficas)

---

## 0. Prólogo: el modelo de amenaza aplicado a la capa de aplicación

Este capítulo cierra la guía con todo lo que ocurre **por encima del TLS** (Capítulo 3) y **por debajo de los datos** (Capítulo 4): cómo se escribe el código, cómo se compila, cómo se despliega, cómo se observa y cómo se reacciona cuando algo falla. El modelo de amenaza NIST SP 800-61r3 [NIST, 2025-04-03] define cuatro fases para la respuesta a incidentes — *preparation, detection/analysis, containment/eradication/recovery, post-incident activity* — y este capítulo se ordena siguiendo esa misma lógica.

Antes de tocar código, conviene tener presente el listado resumido de riesgos que cubrimos aquí:

| ID | Amenaza | Mitigación primaria en este capítulo | Referencia OWASP/NIST |
|---|---|---|---|
| A01 | Broken Access Control | Policies + Gates + Spatie Permission + Sanctum abilities | OWASP API 1:2023 [OWASP, 2024-01-15] |
| A02 | Broken Authentication | Sanctum con abilities, MFA TOTP, rate-limit `/login` | OWASP API 2:2023 [OWASP, 2024-01-15] |
| A03 | Broken Object Property Level Auth | `JsonApiResource` con `$visible`/`$hidden` y validación | OWASP API 3:2023 [OWASP, 2024-01-15] |
| A04 | Unrestricted Resource Consumption | Rate limiter por IP + por user + queue backpressure | OWASP API 4:2023 [OWASP, 2024-01-15] |
| A05 | Broken Function Level Auth | `abilities(['admin:*'])`, middleware `ability` | OWASP API 5:2023 [OWASP, 2024-01-15] |
| A06 | Unrestricted Access to Sensitive Business Flows | Job queues con reintentos acotados + circuit breaker | OWASP API 6:2023 [OWASP, 2024-01-15] |
| A07 | Server Side Request Forgery | Validación de URL allow-list + DNS pre-check | OWASP API 7:2023 [OWASP, 2024-01-15] |
| A08 | Security Misconfiguration | `APP_DEBUG=false`, hardened Dockerfile, scan Trivy | OWASP API 8:2023 [OWASP, 2024-01-15] |
| A09 | Improper Inventory Management | SBOM Syft + Dependabot + version pinning | OWASP API 9:2023 [OWASP, 2024-01-15] |
| A10 | Unsafe Consumption of APIs | Schemas OpenAPI tipados + firma de webhooks | OWASP API 10:2023 [OWASP, 2024-01-15] |

Para cada API Top-10 documentamos el control concreto dentro de cada sección. La tabla se reproduce como checklist de cierre en §11.

---

## 1. Hardening de Laravel 13

### 1.1 Filosofía: defensa en profundidad dentro del proceso PHP-FPM

Laravel 13 es por defecto razonablemente seguro, pero la *configuración* del proyecto es lo que decide si la aplicación se comporta como un bunker o como un colador. Aplicamos hardening en cuatro planos:

1. **Plano runtime** (`php.ini`, FPM pool, `opcache`) — fuera de alcance de este capítulo (cubierto en §6 cuando se hable de la imagen Docker, y en el Capítulo 1 a nivel SO).
2. **Plano framework** (`config/*.php`, `bootstrap/app.php`) — tratado en §1.
3. **Plano código** (`app/`, middleware, policies) — tratado en §1 y §2.
4. **Plano despliegue** (Dockerfile, secrets, CI) — tratado en §5 y §6.

Cada cambio va con su justificación y, cuando aplica, con cita a la doc oficial.

### 1.2 Comandos de instalación en producción

El primer paso — y el más repetido mal — es cómo se instalan las dependencias. En desarrollo usamos `composer install`; en producción **nunca** debe ejecutarse esa forma sin flags porque arrastra `require-dev` y deja el autoloader sin optimizar:

```bash
# Producción
composer install \
  --no-dev \
  --no-interaction \
  --no-progress \
  --optimize-autoloader \
  --classmap-authoritative \
  --prefer-dist

# Precache de rutas, config y eventos (idempotente, cachea el árbol en /var/www/html/bootstrap/cache/)
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache
```

`--optimize-autoloader` genera un classmap que evita el `include` dinámico del PSR-0/PSR-4 en runtime; `--classmap-authoritative` prohíbe el fallback al classmap si la clase no está, obligando a usar solo las clases efectivamente usadas. Esto acelera el arranque de FPM en ~30 % [F: getcomposer.org/doc, 2026-08-12]. La razón de seguridad es que reduce la superficie de filesystem que el atacante puede abusar para plantar clases si logra escribir en el árbol de la app.

> **Decisión [SECURITY]:** se prohíbe `--prefer-source` en CI/CD. Solo `--prefer-dist` se acepta para impedir que `composer` clone repos `.git` completos en el árbol de producción. Esto es explícitamente recomendado por la guía de *production deployment* de Laravel [F: laravel.com/docs/13.x/deployment, 2026-09-22].

### 1.3 Variables de entorno críticas (`/var/www/html/.env`)

El archivo `.env` se mantiene con permisos `600` y propietario `deployer:deployer` (ver Capítulo 1, §4). El `.env.example` está commiteado y **solo contiene placeholders**:

```dotenv
# /var/www/html/.env  (NO COMMITEAR)
APP_NAME="LaravelAPI"
APP_ENV=production
APP_KEY=                                # php artisan key:generate; nunca fijo
APP_DEBUG=false                          # CRÍTICO: jamás true en producción
APP_URL=https://api.example.tld
APP_TIMEZONE=UTC
APP_LOCALE=es

# Cifrado
APP_CIPHER=AES-256-CBC                   # Por defecto en Laravel 13.x [F]
APP_PREVIOUS_KEYS=                       # Lista separada por comas; ver §1.4

# Logging
LOG_CHANNEL=stack
LOG_STACK=single,security                # Canal "security" añadido (ver §1.11)
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

# Sesión
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=.example.tld

# Sanctum
SANCTUM_STATEFUL_DOMAINS=app.example.tld
SANCTUM_GUARD=web
SANCTUM_TOKEN_PREFIX=

# Proxy de confianza (Cloudflare + DO LB)
TRUSTED_PROXIES=*

# DB
DB_CONNECTION=pgsql
DB_HOST=10.0.0.5
DB_PORT=5432
DB_DATABASE=app_prod
DB_USERNAME=app_prod
DB_PASSWORD=                            # SOPS-managed
```

#### 1.3.1 `APP_DEBUG=false` — justificación sin ambigüedad

`APP_DEBUG=true` activa el modo *debug* de Laravel, que muestra stack traces completos, variables de entorno, fragmentos de SQL con bindings y rutas internas si una excepción no se captura. Cualquier error 500 se convierte en una **ventana de información**. La doc de Laravel es taxativa: *"If APP_DEBUG is set to true in production, you risk exposing configuration values to your application's end users"* [F: laravel.com/docs/13.x/configuration#debug-mode, 2026-09-22].

> **Política del proyecto:** `APP_DEBUG` se valida en arranque con una regla de *configuration gate* (ver §1.12): si `app()->environment('production') && config('app.debug') === true`, la aplicación lanza `RuntimeException` en `bootstrap/app.php`. Esto hace que un error de configuración sea visible en el primer arranque, no en el primer exploit.

#### 1.3.2 `APP_ENV=production`

`production` activa los *providers* y *middleware* del entorno; `local` y `testing` cargan el *service provider* de Telescope, el *broadcast* en modo log y los *stack traces* extendidos. Forzar `production` es la primera línea, pero la verificación la da la regla anterior.

#### 1.3.3 `APP_URL=https://api.dominio.tld`

Laravel usa `APP_URL` para:

- Generar URLs en el helper `url()`.
- Validar las *signed URLs* contra el *host* (ver §1.9).
- Configurar `Illuminate\Routing\UrlGenerator` con el esquema correcto.

Si `APP_URL` queda en `http://` o apunta a otro dominio, los *signed URLs* serán inválidos para el frontend real — bug funcional — o peor, podrían validar contra un dominio atacante si el atacante manipula el header `Host` y Laravel no usa `TrustHosts`. La contramedida está en §1.9.

### 1.4 `APP_KEY` — generación, almacenamiento y rotación

Laravel 13.x cifra con `AES-256-CBC` y autentica con un MAC (HMAC-SHA256) — *"all encrypted values are encrypted using OpenSSL and the AES-256-CBC cipher; furthermore, all encrypted values are signed with a message authentication code (MAC)"* [F: laravel.com/docs/13.x/encryption, 2026-09-22]. La clave maestra `APP_KEY` (32 bytes base64) se deriva con PBKDF2/scrypt interno y se usa para cifrar todas las cookies, sesiones, *signed URLs* y llamadas a `Crypt::encryptString()`.

#### Procedimiento de generación

```bash
# NUNCA pegar manualmente una clave; SIEMPRE usar key:generate
php artisan key:generate --show
# → base64:XXXXXXX...    (no la imprimimos en logs)
```

`key:generate` *"will use PHP's secure random bytes generator to build a cryptographically secure key"* [F: laravel.com/docs/13.x/encryption, 2026-09-22]. PHP usa `random_bytes()` que mapea a `getrandom(2)` en Linux [F: php.net/manual/en/function.random-bytes.php, 2026-09-22].

#### Rotación con `APP_PREVIOUS_KEYS`

La doc 13.x introduce explícitamente el mecanismo de *graceful decryption* mediante `APP_PREVIOUS_KEYS` — *"Laravel allows you to list your previous encryption keys in your application's APP_PREVIOUS_KEYS environment variable. This variable may contain a comma-delimited list of all of your previous keys"* [F: laravel.com/docs/13.x/encryption, 2026-09-22].

Procedimiento documentado de rotación:

1. Generar `NEW_KEY = php artisan key:generate --show`.
2. En `.env`:
   ```dotenv
   APP_KEY=base64:NEW_KEY
   APP_PREVIOUS_KEYS=base64:OLD_KEY
   ```
3. Desplegar. Laravel usa `NEW_KEY` para cifrar; sigue descifrando con `OLD_KEY` los valores legacy.
4. Esperar al menos una ventana de retención (90 días recomendado) y vaciar `APP_PREVIOUS_KEYS`.
5. Invalidar todas las sesiones y tokens Sanctum en `personal_access_tokens` (`DELETE FROM personal_access_tokens;`) porque cambiar la `APP_KEY` invalida los *remember tokens* y los *stateful session* cifrados [F: laravel.com/docs/13.x/encryption, 2026-09-22].

#### Almacenamiento de `APP_KEY`

| Lugar | Permitido | Comentario |
|---|---|---|
| `.env` en el servidor | ✅ | Permisos `600`, owner `deployer` |
| GitHub Actions Secrets | ✅ | Rotación trimestral (ver §10) |
| Código fuente | ❌ | **Prohibido.** |
| `.env.example` | ❌ | Solo `APP_KEY=` (vacío) |
| Logs / tickets / Slack | ❌ | **Prohibido.** Filtro en `security` channel (§1.11). |
| DO App Platform env vars | ✅ | Específicamente recomendado por DO |

### 1.5 Cookies y sesión

La sesión se monta sobre Redis (Capítulo 4) con TTL de 120 minutos. Las cuatro directivas de cookie importan:

| Variable | Valor | Justificación |
|---|---|---|
| `SESSION_SECURE_COOKIE` | `true` | Solo se transmite sobre HTTPS. |
| `SESSION_HTTP_ONLY` | `true` | Bloquea `document.cookie` desde JS, mitigando XSS exfiltration. |
| `SESSION_SAME_SITE` | `lax` | Evita CSRF en requests cross-site top-level; permite GET cross-site para *idempotent operations* (links entrantes). Si la API no usa GET con side-effects (no debería), considerar `strict`. |
| `SESSION_DOMAIN` | `.example.tld` | Comparte cookie entre `app.example.tld` y `api.example.tld`. |

> **Decisión [SECURITY]:** `SESSION_DRIVER=redis` y nunca `file` en producción multi-instance. El driver `file` deja sesiones en `/var/lib/php/sessions`, legibles por el usuario `www-data` y por otros PHP-FPM pools co-alojados; además, no escala horizontalmente.

### 1.6 `TRUSTED_PROXIES` y riesgo de *header forgery*

Cuando la API está detrás de Cloudflare, el request llega al PHP-FPM con `X-Forwarded-Proto`, `X-Forwarded-For` y `CF-Connecting-IP` ya inyectados. Laravel usa el *middleware* `Illuminate\Http\Middleware\TrustProxies` para decidir qué headers creer — y si se deja vacío, los ignora todos, lo que provoca URLs generadas con `http://` y CSRF mismatch.

#### Riesgo real

Si un atacante puede llegar al puerto 80/443 del droplet *saltándose* Cloudflare (porque las DO Firewalls no están bien configuradas — ver Capítulo 2), puede inyectar `X-Forwarded-Proto: https` y, peor, `X-Forwarded-For: 127.0.0.1` para falsear la IP de origen en logs y rate-limiters.

#### Mitigación

1. **Listar proxies explícitamente** en `bootstrap/app.php` con las IPs reales de Cloudflare + DO Load Balancer.
2. **Cloudflare Authenticated Origin Pulls** (TLS client cert) — Capítulo 3, §4 — bloquea cualquier tráfico que no venga de Cloudflare.
3. **Rate limiting por IP real**: usar `Request::ip()` solo si la IP está en `TRUSTED_PROXIES`.

```php
// bootstrap/app.php  (Laravel 13)
use Illuminate\Http\Middleware\TrustProxies;

->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: [
        '173.245.48.0/20',     // Cloudflare [F: cloudflare.com/ips, 2026-09-22]
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',      // Cloudflare IPv6
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
        // DO Load Balancer
        '10.0.0.0/8',          // red privada del VPC
    ]);
});
```

> **Decisión [SECURITY]:** **NO** usar `TRUSTED_PROXIES=*` (es la configuración "rápida" pero rompe toda la protección contra *header forgery*). Si por algún motivo *hay que* confiar en todos, combinar con `Cloudflare Authenticated Origin Pulls` para que solo Cloudflare pueda llegar al origen.

### 1.7 `TrustHosts` middleware

`TrustHosts` evita *host header injection*: si llega un request con `Host: evil.example.com`, Laravel responde 400 en lugar de procesarlo. En Laravel 13 el middleware se aplica así:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustHosts(at: [
        'api.example.tld',
        'app.example.tld',
    ]);
});
```

Documentación oficial: *"By default, Laravel will respond to all requests regardless of the Host header content. If your application should only respond to specific hostnames, you should configure the TrustHosts middleware"* [F: laravel.com/docs/13.x/requests, 2026-09-22].

### 1.8 CORS estricto

```php
// config/cors.php
return [
    'paths' => ['api/*', 'oauth/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    'allowed_origins' => [
        'https://app.example.tld',           // SPA Vue
        'capacitor://localhost',              // si hay app híbrida
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Authorization', 'Content-Type', 'Accept'],
    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
    'max_age' => 600,
    'supports_credentials' => true,           // necesario para Sanctum SPA
];
```

> **Decisión [SECURITY]:**
> 1. **`allowed_origins_patterns` se deja vacío.** Los regex de origen son una fuente clásica de bypass (`*.example.tld` permite `evil.example.tld.attacker.com` si el regex no está perfectamente anclado). Si se necesita wildcard, validar primero con `^https://[a-z0-9-]+\.example\.tld$`.
> 2. **`allowed_headers` excluye `X-Requested-With`** porque no aporta seguridad real — Laravel detecta AJAX sin él.
> 3. **`max_age=600`** (10 min) limita el cache de preflight; evita que un cambio de política tarde una hora en aplicarse.

### 1.9 CSRF: ruta web stateless y API token

Laravel aplica `VerifyCsrfToken` globalmente sobre las rutas web. Las rutas en `routes/api.php` se montan dentro del grupo `api` que **excluye** ese middleware por defecto (`'api' => ['throttle:api', SubstituteBindings::class]`). Es decir:

- **Rutas web (Blade server-rendered):** CSRF activo, *stateful* Sanctum con cookie.
- **Rutas API (Vue SPA + mobile):** token `Authorization: Bearer ...`, sin CSRF.

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'ability:read:posts'])->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'ability:write:posts'])->group(function () {
    Route::post('/posts', [PostController::class, 'store']);
});
```

`ability` (no `scope`) es el middleware nativo de Sanctum 13.x [F: laravel.com/docs/13.x/sanctum, 2026-09-22]; ver §2.1 para más detalle.

### 1.10 Rate limiting en `routes/api.php`

Laravel 13 incluye dos *rate limiters* por defecto (`'api'` y `'login'`) definidos en `App\Providers\AppServiceProvider`. Reforzamos:

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

RateLimiter::for('api', function (Request $request) {
    $user = $request->user();
    return $user
        ? Limit::perMinute(120)->by($user->id)
        : Limit::perMinute(30)->by($request->ip());
});

RateLimiter::for('login', function (Request $request) {
    return [
        Limit::perMinute(5)->by($request->input('email') . '|' . $request->ip()),
        Limit::perMinute(20)->by($request->ip()),
    ];
});

RateLimiter::for('export', function (Request $request) {
    return Limit::perMinute(2)->by($request->user()?->id ?: $request->ip());
});
```

> **Decisión [SECURITY]:** la clave del limitador combina `email + IP` en login. Esto previene *credential stuffing* distribuido sin penalizar a usuarios legítimos tras compartir IP (corporate NAT).

### 1.11 Hashing de contraseñas: bcrypt vs argon2id

Laravel 13 soporta `bcrypt`, `argon2i` y `argon2id` vía `Hash::driver('argon2id')` [F: laravel.com/docs/13.x/hashing, 2026-09-22]. La guía oficial NIST SP 800-63B §5.1.1.2 [NIST, 2025-04-03] recomienda *memory-hard* KDFs; `argon2id` es el ganador del Password Hashing Competition (2015) y es resistente a GPU/ASIC.

```php
// config/hashing.php
'driver' => 'argon2id',

'argon2id' => [
    'memory' => 65536,   // 64 MiB (NIST mínimo para argon2id: 47 MiB)
    'threads' => 1,
    'time' => 4,          // ≥ 3 iteraciones
    'verify' => true,
],
```

> **Decisión [SECURITY]:**
> 1. **`argon2id` por defecto.** `bcrypt` queda solo como fallback para usuarios pre-existentes si se importa una BD legacy; los hashes legacy se *rehash-on-login* automáticamente.
> 2. **`memory=64 MiB`** garantiza que un atacante con GPU no puede paralelizar muchos intentos por tarjeta (1 hash = 64 MiB).
> 3. **`verify=true`** hace que cada login ejecute una segunda pasada; reduce ~30 % throughput pero detecta fallos de hardware (Rowhammer, bit flip).

### 1.12 Mass assignment: `$fillable` estricto, *sin* `$guarded`

Laravel aplica *mass assignment guard* automáticamente: solo las claves listadas en `$fillable` se asignan desde `Request::all()` o `->fill()`. La práctica **incorrecta** frecuente es usar `$guarded = []` (que desactiva el guard) "porque es más rápido". Lo prohibimos.

```php
// app/Models/User.php
class User extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    // Para columnas críticas (role, is_admin): NUNCA en $fillable.
    // Se asignan con asignación explícita en el controller tras policy check.
}
```

Para campos sensibles (`role`, `is_admin`, `email_verified_at`) usamos **métodos explícitos** que pasan por `Gate::authorize()`:

```php
// app/Http/Controllers/Admin/UserController.php
public function updateRole(User $user, Request $request)
{
    Gate::authorize('users.change-role', $user);
    $user->role = $request->validated('role');   // explícito, no mass assignment
    $user->save();
    Log::channel('security')->warning('role.changed', [
        'actor' => $request->user()->id,
        'target' => $user->id,
        'new_role' => $user->role,
    ]);
}
```

### 1.13 RBAC: Gates + Policies + Spatie Permission

Recomendamos el siguiente *stack*:

- **Gates** (`AuthServiceProvider`): para decisiones globales (`view-dashboard-admin`, `impersonate`).
- **Policies** (`php artisan make:policy PostPolicy`): para CRUD por modelo.
- **`spatie/laravel-permission` v6.x** [F: spatie.be/docs/laravel-permission, 2026-09-22]: para *roles* y *permissions* almacenados en BD.

```php
// app/Policies/PostPolicy.php
class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('read:posts');
    }

    public function update(User $user, Post $post): bool
    {
        return $user->can('write:posts') && ($user->id === $post->user_id || $user->hasRole('admin'));
    }
}
```

En `AuthServiceProvider`:

```php
Gate::before(function (User $user, string $ability) {
    return $user->hasRole('super-admin') ? true : null;
});
```

> **Decisión [SECURITY]:**
> 1. **`Gate::before` solo para `super-admin`.** Un *super-admin* debe ser único y registrado en código (no en BD) para evitar escalada vía inyección de rol en BD.
> 2. **Spatie Permission no se usa para *abilities* de Sanctum.** Sanctum abilities son *scopes* sobre el token, mientras que los *permissions* de Spatie son *roles* sobre el usuario. Mezclar ambos es una fuente clásica de bugs.

### 1.14 Logging de seguridad: canal dedicado

Creamos un canal `security` que escribe a `/var/log/laravel/security.log` con formato JSON y, además, hace *forward* a Wazuh vía syslog (ver §7.4).

```php
// config/logging.php
'channels' => [
    'security' => [
        'driver' => 'stack',
        'channels' => ['security-file', 'security-syslog'],
        'ignore_exceptions' => false,
    ],
    'security-file' => [
        'driver' => 'monolog',
        'handler' => Monolog\Handler\RotatingFileHandler::class,
        'with' => [
            'filename' => storage_path('logs/security.log'),
            'maxFiles' => 30,
        ],
        'formatter' => Monolog\Formatter\JsonFormatter::class,
    ],
    'security-syslog' => [
        'driver' => 'syslog',
        'level' => 'warning',
        'facility' => LOG_LOCAL5,           // mapeo a /var/log/syslog con tag "laravel-security"
    ],
],
```

Helper de uso:

```php
// app/Support/SecurityLog.php
final class SecurityLog
{
    public static function event(string $event, array $context = []): void
    {
        // Sanitiza secrets conocidos antes de escribir
        $redact = ['password', 'passwd', 'token', 'authorization', 'api_key', 'app_key'];
        array_walk_recursive($context, function (&$v) use ($redact) {
            foreach ($redact as $needle) {
                if (is_string($v) && stripos($v, $needle) !== false) {
                    $v = '[REDACTED]';
                }
            }
        });

        Log::channel('security')->info($event, $context);
    }
}
```

Eventos que **obligatoriamente** se loguean:

| Evento | Severidad | Notas |
|---|---|---|
| `auth.login.success` | info | user_id, ip, ua |
| `auth.login.failed` | warning | email, ip, ua, motivo |
| `auth.logout` | info | user_id, ip |
| `auth.2fa.challenge` | info | user_id |
| `auth.2fa.failed` | warning | user_id, ip |
| `token.issued` | info | user_id, abilities, expires_at |
| `token.revoked` | info | token_id, actor_id |
| `password.changed` | warning | user_id, actor_id |
| `password.reset.requested` | info | email, ip |
| `role.changed` | warning | actor, target, old, new |
| `permission.granted` | warning | actor, target, permission |
| `rbac.denied` | warning | user_id, ability, route |
| `file.upload` | info | user_id, filename, mime, size |
| `export.requested` | info | user_id, dataset |
| `rate_limit.exceeded` | warning | ip, route |

### 1.15 Verificación de configuración al arranque

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    if ($this->app->environment('production')) {
        if (config('app.debug')) {
            throw new RuntimeException('APP_DEBUG=true no permitido en producción.');
        }
        if (empty(config('app.key'))) {
            throw new RuntimeException('APP_KEY ausente.');
        }
        if (config('app.url') && !str_starts_with(config('app.url'), 'https://')) {
            throw new RuntimeException('APP_URL debe usar https.');
        }
    }
}
```

Esto se ejecuta **una sola vez** durante `config:cache`, fallando rápido en el primer *build* del pipeline.

---

## 2. Sanctum y autenticación API

### 2.1 Tokens con *abilities*, no con *scopes*

Laravel Sanctum 13.x usa *abilities* (string array) — *"Sanctum allows you to issue API tokens that may be used to authenticate requests to your application. When making requests using API tokens, the token should be included in the Authorization header as a Bearer token. Sanctum will only authenticate the token if the request is made to a route that is protected by the auth:sanctum middleware and the token has the appropriate abilities"* [F: laravel.com/docs/13.x/sanctum, 2026-09-22].

> **Decisión [SECURITY]:** el término *scope* en Sanctum corresponde a otra capa: el *stateful authentication* para SPAs. Para tokens de larga vida (CLI, mobile, integraciones) usamos **abilities**.

#### Emisión de tokens

```php
// app/Http/Controllers/Api/TokenController.php
public function issue(Request $request): JsonApiResource
{
    Gate::authorize('tokens.issue');

    $data = $request->validate([
        'user_id' => 'required|integer',
        'abilities' => 'required|array|min:1',
        'abilities.*' => 'string|in:read:posts,write:posts,read:users,admin:*',
        'expires_at' => 'nullable|date|after:now',
    ]);

    $user = User::findOrFail($data['user_id']);
    $token = $user->createToken(
        name: 'cli-'.bin2hex(random_bytes(4)),
        abilities: $data['abilities'],
        expiresAt: isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
    );

    SecurityLog::event('token.issued', [
        'user_id' => $user->id,
        'abilities' => $data['abilities'],
        'expires_at' => $data['expires_at'] ?? 'never',
    ]);

    return new JsonApiResource([
        'id' => $token->accessToken->id,
        'plain' => $token->plainTextToken,        // se muestra UNA vez
        'abilities' => $token->accessToken->abilities,
        'expires_at' => $token->accessToken->expires_at,
    ]);
}
```

> **Decisión [SECURITY]:**
> 1. **El *plain text* token se devuelve UNA sola vez**, en el response original. La columna `personal_access_tokens.token` guarda solo el *hash* SHA-256 [F: laravel.com/docs/13.x/sanctum#database-requirements, 2026-09-22].
> 2. **`expires_at` es por token, no global.** Esto permite tokens CLI sin expiración para integraciones internas *y* tokens de 1 h para dispositivos móviles.
> 3. **`name` único y trazable.** El prefijo `cli-` + 8 hex permite identificar el origen en logs.

#### Validación con middleware

```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {

    Route::middleware('ability:read:posts')->get('/posts', [PostController::class, 'index']);
    Route::middleware('ability:write:posts')->post('/posts', [PostController::class, 'store']);

    Route::middleware('ability:admin:*')->prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
    });

});
```

`ability` acepta wildcard final (`admin:*`) lo que permite definir jerarquías [F: laravel.com/docs/13.x/sanctum#token-abilities, 2026-09-22].

> **Decisión [SECURITY]:**
> 1. **Prohibido emitir tokens sin abilities.** En CI, un *test* de regresión (`AuthAbilitiesTest`) genera tokens con `[]` y verifica que la API devuelve 403.
> 2. **Wildcards SOLO al final** y solo para roles administrativos (`admin:*`). Nunca `*:posts`.

### 2.2 Hashing de tokens en BD

Sanctum hashea automáticamente: cuando un token llega en `Authorization: Bearer xxx`, Sanctum hashea `xxx` y busca en `personal_access_tokens.token`. Esto significa que aunque la BD caiga, los atacantes obtienen hashes, no tokens utilizables [F: laravel.com/docs/13.x/sanctum#database-requirements, 2026-09-22].

Estructura relevante de la tabla:

```sql
-- migration de Sanctum (no modificar salvo razón justificada)
CREATE TABLE personal_access_tokens (
    id BIGSERIAL PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT NOT NULL,
    name TEXT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,   -- SHA-256 hash
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX personal_access_tokens_tokenable_index
    ON personal_access_tokens (tokenable_type, tokenable_id);
```

> **Decisión [SECURITY]:** añadimos un índice único por `(name, tokenable_id)` para impedir nombres duplicados en un mismo usuario y un trigger en Postgres que rechaza inserciones con `expires_at < created_at`. El trigger se documenta en el Capítulo 4, §6.

### 2.3 `last_used_at` y rotación

`last_used_at` se actualiza en cada request autenticado. Esto habilita:

- **Detección de tokens huérfanos**: cualquier token con `last_used_at < NOW() - INTERVAL '90 days'` se considera inactivo y se puede revocar en masa.
- **Alertas en tiempo real**: un evento `token.revoked` cuando un actor revoca manualmente un token sospechoso.

```php
// app/Console/Commands/RevokeStaleTokens.php
protected $signature = 'tokens:prune {--days=90}';
public function handle(): int
{
    $cutoff = now()->subDays((int) $this->option('days'));
    $count = PersonalAccessToken::query()
        ->where('last_used_at', '<', $cutoff)
        ->orWhere('expires_at', '<', now())
        ->delete();
    $this->info("Revoked {$count} stale tokens");
    SecurityLog::event('tokens.pruned', ['count' => $count, 'cutoff' => $cutoff]);
    return self::SUCCESS;
}
```

Programación: en `app/Console\Kernel.php`:

```php
$schedule->command('tokens:prune --days=90')->dailyAt('03:17')->onOneServer();
```

`onOneServer()` requiere cache lock con Redis (configurado en Capítulo 4); evita ejecución paralela si hay varios nodos.

### 2.4 Refresh tokens: dos pares de tokens

Laravel Sanctum no implementa refresh tokens *out-of-the-box*, pero el patrón estándar es usar **dos tokens con expiración distinta**:

- **Access token**: TTL 15 min, ability `read:*` o específicas, sin `password`/`admin:*`.
- **Refresh token**: TTL 30 días, ability `refresh:token` (una sola), guardado en BD con flag `refreshable=true`.

```php
// app/Http/Controllers/Api/AuthController.php
public function refresh(Request $request): JsonResponse
{
    $request->validate(['refresh_token' => 'required|string']);

    // Hashear el refresh_token entrante y buscar en BD
    $hashed = hash('sha256', $request->refresh_token);
    $token = PersonalAccessToken::where('token', $hashed)
        ->where('refreshable', true)
        ->where('expires_at', '>', now())
        ->firstOrFail();

    // ROTACIÓN: revocar el viejo, emitir par nuevo
    $token->delete();
    $user = $token->tokenable;
    [$access, $refresh] = $this->issueTokenPair($user);

    SecurityLog::event('token.refreshed', [
        'user_id' => $user->id,
        'old_token_id' => $token->id,
    ]);

    return response()->json([
        'access_token' => $access->plainTextToken,
        'refresh_token' => $refresh->plainTextToken,
        'expires_in' => 900,
    ]);
}
```

> **Decisión [SECURITY]:**
> 1. **Rotación siempre** — cada `refresh` invalida el refresh-token viejo. Esto reduce la ventana de uso de un token robado a 15 min (access) + la duración del refresh.
> 2. **Refresh token se almacena solo en cookie `httpOnly`, `Secure`, `SameSite=Strict`** en SPA Vue.
> 3. **Para mobile**: refresh token se guarda en **Keychain (iOS)** / **EncryptedSharedPreferences (Android)**; el access token puede vivir en memoria (variable JS).

### 2.5 SPA *stateful* con cookie cifrada

Cuando Vue corre en `app.example.tld` y la API en `api.example.tld`, usamos Sanctum en modo *stateful*:

1. El frontend pide `GET /sanctum/csrf-cookie` → la API devuelve cookie `XSRF-TOKEN` (cifrada con `APP_KEY`).
2. Axios envía `withCredentials: true` y `X-XSRF-TOKEN` automáticamente.
3. El middleware `Illuminate\Auth\Middleware\AuthenticateSession` valida la cookie y crea la sesión PHP.

```php
// config/sanctum.php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', '')),

// 'api' guard en config/auth.php
'api' => [
    'driver' => 'sanctum',
    'provider' => 'users',
],
```

```ts
// frontend/src/lib/api.ts
import axios from 'axios';

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
});
```

> **Decisión [SECURITY]:**
> 1. **`SANCTUM_STATEFUL_DOMAINS=app.example.tld`**, no comodines. Sanctum valida el origen.
> 2. **`Secure` + `httpOnly` + `SameSite=lax`** en la cookie de sesión (config en §1.5).
> 3. **CORS con `supports_credentials: true`** y `allowed_origins` con la URL exacta, no `*`.

### 2.6 MFA con TOTP (Google2FA)

```bash
composer require pragmarx/google2fa-laravel
php artisan vendor:publish --provider="PragmaRX\Google2FALaravel\ServiceProvider"
```

```php
// app/Http/Controllers/Api/MfaController.php
public function enable(Request $request): JsonResponse
{
    $user = $request->user();
    $google2fa = app('pragmarx.google2fa');
    $secret = $google2fa->generateSecretKey();

    $user->forceFill([
        'two_factor_secret' => encrypt($secret),       // Crypt::encrypt (AES-256-CBC)
        'two_factor_recovery_codes' => encrypt(json_encode(
            collect(range(1, 8))->map(fn () => bin2hex(random_bytes(5)))->all()
        )),
        'two_factor_confirmed_at' => null,
    ])->save();

    $qr = $google2fa->getQRCodeInline(
        config('app.name'),
        $user->email,
        $secret,
    );

    return response()->json([
        'qr_svg' => $qr,
        'secret' => $secret,                  // mostrar UNA vez
        'recovery_codes' => $user->two_factor_recovery_codes_array,
    ]);
}

public function verify(Request $request): JsonResponse
{
    $request->validate(['code' => 'required|digits:6']);
    $valid = app('pragmarx.google2fa')->verifyKey(
        decrypt($request->user()->two_factor_secret),
        $request->code,
    );

    if (!$valid) {
        SecurityLog::event('auth.2fa.failed', [
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);
        throw new AuthenticationException('Invalid 2FA code');
    }

    $request->user()->forceFill(['two_factor_confirmed_at' => now()])->save();
    SecurityLog::event('auth.2fa.verified', ['user_id' => $request->user()->id]);
    return new JsonApiResource(null);
}
```

`pragmarx/google2fa-laravel` soporta `SHA-256` y `SHA-512` además de `SHA-1` [F: github.com/antonioribeiro/google2fa-laravel, 2026-09-22]. Forzamos SHA-256 en `config/google2fa.php`:

```php
return [
    'algorithm' => 'SHA256',
    'period' => 30,
    'window' => 1,            // ±30s de tolerancia
];
```

### 2.7 Logout y revocación

```php
public function logout(Request $request): JsonResponse
{
    $request->user()->currentAccessToken()->delete();
    SecurityLog::event('auth.logout', [
        'user_id' => $request->user()->id,
        'token_id' => $request->user()->currentAccessToken()->id,
    ]);
    return response()->json(null, 204);
}

public function logoutAll(Request $request): JsonResponse
{
    $count = $request->user()->tokens()->count();
    $request->user()->tokens()->delete();
    SecurityLog::event('auth.logout-all', [
        'user_id' => $request->user()->id,
        'revoked' => $count,
    ]);
    return response()->json(['revoked' => $count]);
}
```

> **Decisión [SECURITY]:** en el runbook de incidente (ver §9.3), `logoutAll` es la acción inmediata cuando se sospecha compromiso de credenciales.

---

## 3. JSON:API en Laravel 13

JSON:API es el contrato *canónico* de la API. Laravel 13 lo soporta de forma nativa vía `php artisan make:resource --json-api` que genera una clase extendiendo `Illuminate\Http\Resources\JsonApi\JsonApiResource` [F: laravel.com/docs/13.x/eloquent-resources, 2026-09-22].

### 3.1 Top-level del documento HTTP

```json
{
  "jsonapi": { "version": "1.1" },
  "links": {
    "self": "https://api.example.tld/api/posts/42"
  },
  "data": {
    "type": "posts",
    "id": "42",
    "attributes": {
      "title": "Hardening Laravel 13",
      "body": "...",
      "created-at": "2026-09-30T00:00:00Z"
    },
    "relationships": {
      "author": {
        "links": { "self": "https://api.example.tld/api/posts/42/relationships/author" },
        "data": { "type": "users", "id": "7" }
      }
    }
  }
}
```

> **Decisión [SECURITY]:**
> 1. **Sin `success` ni `message` en top-level.** Prohibido por JSON:API v1.1 [F: jsonapi.org/format, 2026-09-22].
> 2. **`errors` y `data` mutuamente excluyentes.** Un response de error **no** incluye `data`.
> 3. **`Content-Type: application/vnd.api+json`** en TODA respuesta (incluido 204 con body vacío lleva `Content-Type: application/vnd.api+json`).

### 3.2 Resource con `toArray` tipado

```php
// app/Http/Resources/PostResource.php
#[UseResourceAttributes]                // atributo nativo Laravel 13 [F]
final class PostResource extends JsonApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'created-at' => $this->created_at?->toIso8601String(),
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'author' => fn () => UserIdentifierResource::make($this->author),
            'comments' => fn () => CommentIdentifierResource::collection($this->comments),
        ];
    }

    public function toLinks(Request $request): array
    {
        return [
            'self' => route('api.posts.show', $this->resource),
        ];
    }
}
```

### 3.3 Errores con `source.pointer`

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $e): Response
{
    if ($request->wantsJson() || $request->is('api/*')) {
        return new JsonApiErrorResponse($e);   // convierte a formato JSON:API
    }
    return parent::render($request, $e);
}

// app/Support/JsonApiErrorResponse.php
final class JsonApiErrorResponse
{
    public function __invoke(Throwable $e): JsonResponse
    {
        $status = match (true) {
            $e instanceof ValidationException => 422,
            $e instanceof AuthenticationException => 401,
            $e instanceof AuthorizationException => 403,
            $e instanceof ModelNotFoundException => 404,
            $e instanceof HttpException => $e->getStatusCode(),
            default => 500,
        };

        $payload = [
            'errors' => [[
                'status' => (string) $status,
                'code' => class_basename($e),
                'title' => $e->getMessage() ?: 'Server error',
                'detail' => $e->getMessage(),
                'source' => $e instanceof ValidationException
                    ? ['pointer' => '/data/attributes/'.array_key_first($e->errors())]
                    : null,
            ]],
            'jsonapi' => ['version' => '1.1'],
        ];

        return response()->json($payload, $status, [
            'Content-Type' => 'application/vnd.api+json',
        ]);
    }
}
```

> **Decisión [SECURITY]:**
> 1. **`detail` no expone el stack trace** en producción. En desarrollo (`APP_DEBUG=true`) se permite.
> 2. **`source.pointer` apunta a `/data/attributes/<campo>`** porque Laravel siempre parsea el body como recurso JSON:API.
> 3. **Errores 5xx se loguean con el stack completo** en `security` channel pero el cliente solo ve un mensaje genérico.

### 3.4 Validación de query params JSON:API

`include`, `fields`, `sort`, `filter`, `page` son **propios de JSON:API** y Laravel 13 no los valida *out-of-the-box* [F: laravel.com/docs/13.x/eloquent-resources#relationships, 2026-09-22]. Recomendamos:

```bash
composer require timacdonald/log-fake
composer require --dev symfony/var-dumper
# Para query params: usar timacdonald/callable-fake o crear un middleware propio
```

El middleware propio (extracto):

```php
// app/Http/Middleware/ValidateJsonApiQuery.php
final class ValidateJsonApiQuery
{
    private const ALLOWED_INCLUDES = ['author', 'comments', 'tags'];
    private const ALLOWED_SORTS    = ['created-at', '-created-at', 'title'];
    private const MAX_PAGE_SIZE    = 100;

    public function handle(Request $request, Closure $next): Response
    {
        if ($include = $request->query('include')) {
            $unknown = array_diff(explode(',', $include), self::ALLOWED_INCLUDES);
            if ($unknown) {
                throw new BadRequestHttpException('Unknown include: '.implode(',', $unknown));
            }
        }
        if ($page = $request->query('page.size')) {
            if ((int) $page > self::MAX_PAGE_SIZE) {
                throw new BadRequestHttpException('page.size exceeds limit');
            }
        }
        return $next($request);
    }
}
```

> **Decisión [SECURITY]:** **Lista blanca de `include`** — Laravel permite `?include=author.privateNotes` por defecto si la relación existe en el modelo, lo que sería una fuga de datos (A03 OWASP API). La lista blanca es la contramedida.

---

## 4. Endurecimiento de Vue 3 + TypeScript

### 4.1 Build con Vite

`vite.config.ts` endurecido:

```ts
import { defineConfig, loadEnv } from 'vite';
import vue from '@vitejs/plugin-vue';
import { compression } from 'vite-plugin-compression2';
import { visualizer } from 'rollup-plugin-visualizer';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  return {
    plugins: [vue(), compression({ algorithm: 'gzip', threshold: 1024 })],
    build: {
      target: 'es2022',                   // soporta navegadores evergreen
      sourcemap: false,                   // ⚠️ CRÍTICO en producción
      minify: 'esbuild',
      cssMinify: 'lightningcss',
      rollupOptions: {
        output: {
          manualChunks: {
            vendor: ['vue', 'vue-router', 'pinia'],
            query: ['@tanstack/vue-query'],
            utils: ['axios', 'date-fns', 'dompurify'],
          },
        },
      },
    },
    server: { host: '127.0.0.1' },        // nunca bindear 0.0.0.0 en dev
    esbuild: { legalComments: 'none' },
  };
});
```

> **Decisión [SECURITY]:**
> 1. **`sourcemap: false` en producción.** Vite publica source maps que mapean cada línea del bundle al `.vue`/`.ts` original —信息公开 rutas internas, nombres de componentes, lógica de validación. En dev sí se generan.
> 2. **`host: '127.0.0.1'`** en `server` — Vite ≥ 5 ofrece `--host 0.0.0.0` opcional. Por defecto solo escucha en loopback.
> 3. **`legalComments: 'none'`** elimina bloques de licencia que pueden identificar versiones exactas.

### 4.2 Variables de entorno: `VITE_*`

Vite solo expone al bundle las variables prefijadas con `VITE_`. La doc oficial es taxativa: *"Vite exposes env variables on the special import.meta.env object, which are statically replaced at build time. Any variable prefixed with VITE_ is exposed to your Vite-processed code"* [F: vitejs.dev/guide/env-and-mode, 2026-09-22].

```env
# frontend/.env.production
VITE_API_URL=https://api.example.tld
VITE_SENTRY_DSN=                  # público, OK; no contiene secret
VITE_BUILD_ID=$CI_COMMIT_SHA       # viene del pipeline (no secret)
VITE_GA_MEASUREMENT_ID=G-XXXX      # público, OK
```

> **Decisión [SECURITY]:**
> 1. **PROHIBIDO** prefijar secretos con `VITE_`. Lo que termina en el bundle es público aunque haya sido inyectado desde un secret de CI.
> 2. **`VITE_BUILD_ID`** identifica exactamente qué commit está desplegado. Es público y útil para correlar logs del cliente con despliegues.
> 3. **`VITE_SENTRY_DSN`** es público por diseño (Sentry lo necesita para reportar), pero la DSN de *ingest* es distinta de la de *admin*; usar solo la de ingest.

### 4.3 Saneamiento de HTML: nunca `v-html` sin sanitizar

```vue
<!-- ❌ NUNCA así -->
<div v-html="user.bio"></div>

<!-- ✅ Sanitizar primero -->
<script setup lang="ts">
import DOMPurify from 'dompurify';
const safeBio = computed(() => DOMPurify.sanitize(props.user.bio, {
  ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'a', 'ul', 'ol', 'li'],
  ALLOWED_ATTR: ['href', 'target', 'rel'],
}));
</script>
<template>
  <div v-html="safeBio"></template>
</template>
```

DOMPurify se construye con JSDOM en CI (no en runtime del navegador), ahorrando ~80 KiB. Configuración:

```ts
// frontend/src/lib/sanitize.ts
import DOMPurify from 'dompurify';

DOMPurify.addHook('afterSanitizeAttributes', (node) => {
  if ('target' in node) node.setAttribute('target', '_blank');
  if ('rel' in node) node.setAttribute('rel', 'noopener noreferrer');
});

export const sanitize = (html: string) => DOMPurify.sanitize(html, {
  ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'a', 'ul', 'ol', 'li', 'code', 'pre', 'blockquote'],
  ALLOWED_ATTR: ['href', 'target', 'rel'],
});
```

### 4.4 Subresource Integrity para assets externos

Si el frontend carga Vue o cualquier librería desde CDN (raro en Vite, pero pasa con widgets de terceros), SRI es obligatorio:

```html
<script
  src="https://cdn.example.tld/widget.js"
  integrity="sha384-..."
  crossorigin="anonymous"
  referrerpolicy="no-referrer"
></script>
```

> **Decisión [SECURITY]:** si Vue se construye con Vite desde el repositorio, todos los assets se sirven *first-party* y SRI no aplica (la CSP del Capítulo 3 §5 bloquea cualquier third-party excepto los explícitamente allow-list).

### 4.5 TanStack Query: retry limitado y auth header

```ts
// frontend/src/lib/queryClient.ts
import { QueryClient } from '@tanstack/vue-query';

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: (failureCount, error: any) => {
        if (error?.response?.status >= 400 && error?.response?.status < 500) return false;
        return failureCount < 2;
      },
      retryDelay: (attempt) => Math.min(1000 * 2 ** attempt, 8000),
      staleTime: 30_000,
      gcTime: 5 * 60_000,
      refetchOnWindowFocus: false,
    },
  },
});
```

> **Decisión [SECURITY]:**
> 1. **Nunca reintentar 4xx.** El 401/403/404 no se recuperan; reintentarlos solo amplifica la carga en la API y crea tráfico que parece legítimo.
> 2. **`retryDelay` con backoff exponencial** evita tormentas de reintentos tras un 503 transitorio.

### 4.6 Axios: `withCredentials`, CSRF, headers seguros

```ts
// frontend/src/lib/api.ts
import axios from 'axios';
import DOMPurify from 'dompurify';

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
  timeout: 15_000,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/vnd.api+json',
  },
});

// Interceptor: sanitiza respuestas antes de inyectarlas en Pinia
api.interceptors.response.use((r) => r, (err) => {
  if (err.response?.status === 401) {
    window.dispatchEvent(new CustomEvent('auth:expired'));
  }
  return Promise.reject(err);
});
```

### 4.7 Headers en build: Permissions-Policy, COOP, COEP, CORP

El *frontend* no emite estos headers (los pone Nginx en el Capítulo 3, §5). Pero el código debe **saber** que existen para evitar romperlos. En particular:

- **`COEP: require-corp`** requiere que *todos* los sub-recursos sean *same-origin* o tengan `Cross-Origin-Resource-Policy: cross-origin`. Si la app carga imágenes de un CDN sin CORS, **fallará**.
- **`COOP: same-origin`** aísla el *browsing context* (protección contra *tab nabbing*).

En código, ningún iframe debe apuntar a otro origen sin `sandbox` + `referrerpolicy="no-referrer"`.

### 4.8 Pinia store: sin secretos

```ts
// frontend/src/stores/auth.ts
import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);
  const accessToken = ref<string | null>(null);   // en memoria, no localStorage
  const refreshToken = ref<string | null>(null); // idem

  // Persistir SOLO el user (público), NUNCA los tokens en localStorage
  return { user, accessToken, refreshToken, /* actions */ };
});
```

> **Decisión [SECURITY]:** los tokens Sanctum stateful viven en cookies `httpOnly`; los access tokens en memoria (variable JS). El localStorage de un usuario XSS = comprometido.

---

## 5. CI/CD seguro con GitHub Actions

### 5.1 Principios generales

| Principio | Implementación |
|---|---|
| **Pinning por SHA** | Todas las `actions/*` y `third-party/*` con SHA-256 de 40 chars + comentario de versión. |
| **Permisos mínimos** | `permissions: contents: read` por workflow; override por job si hace falta. |
| **OIDC sin secretos** | `permissions: id-token: write` + action del cloud provider. |
| **Secret scoping** | Usar *environments* (`production`, `staging`) con `required_reviewers`. |
| **Sin secretos en logs** | `set -euo pipefail` + `::add-mask::` en scripts. |
| **Sin `pull_request_target`** | Riesgo de *pwn request*; solo usar `pull_request`. |
| **actionlint + zizmor** | Validación de YAML y patrones peligrosos. |
| **gitleaks** | Detección de secretos en el código previo a push. |
| **Trivy + Grype + Syft** | Escaneo de imágenes + SBOM en cada build. |

### 5.2 Estructura del repositorio

```
.github/
├── workflows/
│   ├── ci.yml              # Lint + test en cada PR
│   ├── deploy-staging.yml  # Push a main → staging
│   ├── deploy-production.yml # Tag v* → production (manual approval)
│   ├── scan.yml            # Trivy + Grype + gitleaks (cron + push)
│   └── dependency-review.yml # Dependabot-like en PR
├── dependabot.yml          # Actualizaciones automáticas
├── CODEOWNERS              # Revisión obligatoria de paths críticos
└── settings.yml            # (con Probot Settings) default branch protections
```

### 5.3 Workflow de lint y tests (`ci.yml`)

```yaml
# .github/workflows/ci.yml
name: CI

on:
  pull_request:
    branches: [main, 13.x]
  push:
    branches: [main, 13.x]

permissions:          # nivel workflow
  contents: read

concurrency:
  group: ci-${{ github.ref }}
  cancel-in-progress: true

jobs:
  php-lint:
    name: PHP Lint (PSR-12 + PHPStan)
    runs-on: ubuntu-24.04
    permissions:
      contents: read
      checks: write
    steps:
      - name: Checkout
        uses: actions/checkout@11bd71901bbe5b1630ceea73d27597364c9af683  # v4.2.2

      - name: Setup PHP 8.4
        uses: shivammathur/setup-php@efffd0e4f2504f936fcfe3b69293d31ce0e2fd7a  # v2.30.3
        with:
          php-version: '8.4'
          extensions: mbstring, pgsql, redis, intl, bcmath
          coverage: xdebug

      - name: Cache Composer
        uses: actions/cache@1bd1e32a3bdc45362d1e726936510720a7c30a57  # v4.2.0
        with:
          path: ~/.composer/cache
          key: composer-${{ runner.os }}-${{ hashFiles('composer.lock') }}
          restore-keys: composer-${{ runner.os }}-

      - name: Install dependencies
        run: composer install --no-progress --prefer-dist

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse --no-progress --error-format=github

      - name: Run Pint
        run: vendor/bin/pint --test

  php-test:
    name: PHPUnit (Postgres + Redis services)
    runs-on: ubuntu-24.04
    services:
      postgres:
        image: postgres:16-alpine
        env:
          POSTGRES_PASSWORD: test
          POSTGRES_DB: app_test
        ports: ['5432:5432']
        options: >-
          --health-cmd "pg_isready -U postgres"
          --health-interval 5s
          --health-timeout 3s
          --health-retries 10
      redis:
        image: redis:7-alpine
        ports: ['6379:6379']
        options: --health-cmd "redis-cli ping" --health-interval 5s
    steps:
      - uses: actions/checkout@11bd71901bbe5b1630ceea73d27597364c9af683  # v4.2.2
      - uses: shivammathur/setup-php@efffd0e4f2504f936fcfe3b69293d31ce0e2fd7a  # v2.30.3
        with:
          php-version: '8.4'
      - run: composer install --no-progress --prefer-dist
      - name: Run PHPUnit
        env:
          APP_KEY: base64:${{ secrets.TEST_APP_KEY }}      # 32 bytes generados para CI
          DB_CONNECTION: pgsql
          DB_HOST: 127.0.0.1
          DB_PORT: 5432
          DB_DATABASE: app_test
          DB_USERNAME: postgres
          DB_PASSWORD: test
        run: vendor/bin/phpunit --coverage-clover=coverage.xml

      - name: Upload coverage report
        if: github.event_name == 'push'
        uses: actions/upload-artifact@5d5d22a31266ced268874388b861e4b58bb5c2f3  # v4.6.0
        with:
          name: coverage
          path: coverage.xml
          retention-days: 7

  frontend-lint:
    name: Vue + ESLint + Vitest
    runs-on: ubuntu-24.04
    steps:
      - uses: actions/checkout@11bd71901bbe5b1630ceea73d27597364c9af683  # v4.2.2
      - uses: actions/setup-node@39370e3970a6d050c480ffad4ff0ed4d3fdee5af  # v4.1.0
        with:
          node-version: '22'
          cache: 'pnpm'
      - run: pnpm install --frozen-lockfile
      - run: pnpm lint
      - run: pnpm test:unit -- --coverage

  security-scan:
    name: gitleaks + actionlint + zizmor
    runs-on: ubuntu-24.04
    steps:
      - uses: actions/checkout@11bd71901bbe5b1630ceea73d27597364c9af683  # v4.2.2
        with:
          fetch-depth: 0
      - name: gitleaks
        uses: gitleaks/gitleaks-action@ff98106e4c7b2bc287b24eaf42907196329070c7  # v2.3.9
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
      - name: actionlint
        uses: docker://rhysd/actionlint:1.7.7
        with:
          args: -color
      - name: zizmor
        uses: woodruffw/zizmor@f1e5b96fb5472647a8ddb526f6041c34c380fc71  # v1.5.1
        with:
          persona: auditor
```

> **Decisión [SECURITY]:** SHA pinning se verifica con `gh api repos/actions/checkout/git/refs/tags/v4.2.2` y se copia el SHA del commit tag, **no del tree**. La línea `uses: actions/checkout@<sha> # v4.2.2` deja el comentario para que un humano entienda a qué versión corresponde.

### 5.4 Workflow de build + push de imagen (`build.yml`)

```yaml
# .github/workflows/build.yml
name: Build & Scan Image

on:
  push:
    branches: [main]
    tags: ['v*']
  pull_request:
    branches: [main]

permissions:
  contents: read
  packages: write          # para GHCR
  id-token: write          # para cosign keyless (opcional)
  attestations: write      # para GitHub Artifact Attestations

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}

jobs:
  build:
    runs-on: ubuntu-24.04
    outputs:
      digest: ${{ steps.build.outputs.digest }}
      sbom: ${{ steps.sbom.outputs.sbom-path }}
    steps:
      - uses: actions/checkout@11bd71901bbe5b1630ceea73d27597364c9af683  # v4.2.2

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@b5ca514318bd6ebac0fb2aedd5d36ec1b5c232a2  # v3.10.0

      - name: Login to GHCR
        uses: docker/login-action@74a5d142397b4f367a81961eba4e8cd7edddf772  # v3.4.0
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@369eb591f429131d6889c46b94e711f089e6ca96  # v5.6.1
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=ref,event=branch
            type=semver,pattern={{version}}
            type=semver,pattern={{major}}.{{minor}}
            type=sha,prefix=sha-
            type=raw,value=latest,enable={{is_default_branch}}

      - name: Build & push (multi-arch)
        id: build
        uses: docker/build-push-action@ca877d9245402d1537745e0e356eab47c3520991  # v6.13.0
        with:
          context: .
          push: true
          provenance: mode=max
          sbom: true
          platforms: linux/amd64,linux/arm64
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha,scope=build-${{ github.event.pull_request.number || github.ref }}
          cache-to: type=gha,mode=max,scope=build-${{ github.event.pull_request.number || github.ref }}
          build-args: |
            BUILDKIT_INLINE_CACHE=1
          secrets: |
            GITHUB_TOKEN=${{ secrets.GITHUB_TOKEN }}

      - name: Generate SBOM (Syft)
        id: sbom
        uses: anchore/sbom-action@fc46e51fd3cb168ffb36c6d1915723c47db58abb  # v0.17.7
        with:
          image: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}@${{ steps.build.outputs.digest }}
          format: spdx-json
          artifact-name: sbom.spdx.json

      - name: Trivy image scan
        uses: aquasecurity/trivy-action@915b19bbe73b92a6cf82a1bc12b087c9a19a5fe2  # v0.28.0
        with:
          image-ref: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}@${{ steps.build.outputs.digest }}
          format: 'sarif'
          output: 'trivy.sarif'
          severity: 'HIGH,CRITICAL'
          exit-code: '1'

      - name: Upload Trivy results
        if: always()
        uses: github/codeql-action/upload-sarif@f35333b910470a5408cb081b68f0701254a7d27b  # v3.28.18
        with:
          sarif_file: trivy.sarif

      - name: Grype scan
        uses: anchore/scan-action@27805bf3b4e84b4a5c980df22ed233c00390a439  # v7.4.2
        with:
          image: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}@${{ steps.build.outputs.digest }}
          severity-cutoff: high
          fail-build: true

  attest:
    needs: build
    runs-on: ubuntu-24.04
    permissions:
      attestations: write
      id-token: write
    steps:
      - uses: actions/attest-build-provenance@96b4a1ef7235a096b17240c259729fdd70c83d45  # v2.0.0
        with:
          subject-name: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          subject-digest: ${{ needs.build.outputs.digest }}
          push-to-registry: true
```

> **Decisión [SECURITY]:** `provenance: mode=max` genera SLSA L3 automáticamente. `attest-build-provenance` añade *attestation* firmada al registro, consumible por `cosign verify-attestation`. Esto cumple con [OWASP API 9:2023] *Improper Inventory Management* — sabemos exactamente qué binarios corren.

#### Diagrama 0 — Pipeline CI/CD completo

```mermaid
flowchart TB
    subgraph DEV["Developer local"]
        DS[git push / PR]
    end

    subgraph GH["GitHub"]
        REPO[(repo)]
        BR[Branch Protection]
        SEC[Secret Scanning + Push Protection]
    end

    subgraph CI["CI (PR + push a main)"]
        J1[lint.yml<br/>PHPStan + Pint + ESLint]
        J2[test.yml<br/>PHPUnit + Vitest + Playwright]
        J3[scan.yml<br/>gitleaks + actionlint + zizmor + govulncheck]
    end

    subgraph BUILD["Build (push a main)"]
        B1[build.yml<br/>docker buildx multi-arch]
        B2[Syft SBOM]
        B3[Trivy image scan]
        B4[Grype scan]
        B5[cosign sign]
        B6[SLSA L3 attestation]
    end

    subgraph REG["Registries"]
        GHCR[(GHCR<br/>ghcr.io/example/app)]
        DO[DO Container Registry<br/>(mirror)]
    end

    subgraph STAGE["Deploy Staging"]
        S1[deploy-staging.yml<br/>SSH a droplet staging]
        S2[Smoke tests]
    end

    subgraph PROD["Deploy Production (tag v*)"]
        P0[Manual approval<br/>environment: production]
        P1[OIDC token → DO Spaces]
        P2[restic snapshot pre-deploy]
        P3[rsync release]
        P4[php artisan migrate --force]
        P5[reload php8.4-fpm]
        P6[Healthcheck 30s]
    end

    subgraph POST["Post-deploy"]
        PD1[Slack notification]
        PD2[Sentry release tag]
        PD3[Drill backup weekly]
        PD4[Wazuh retroactivo]
    end

    DS --> REPO
    REPO --> BR
    REPO --> SEC
    REPO --> J1
    REPO --> J2
    REPO --> J3
    J1 -->|ok| B1
    J2 -->|ok| B1
    J3 -->|ok| B1
    J3 -->|gitleaks FAIL| STOP([🚫 Push bloqueado])

    B1 --> B2
    B1 --> B3
    B1 --> B4
    B2 --> B5
    B3 --> B5
    B4 --> B5
    B5 --> B6
    B6 --> GHCR
    GHCR --> DO
    GHCR --> S1
    S1 --> S2
    S2 -->|tag v*| P0
    P0 --> P1
    P0 --> P2
    P1 --> P3
    P2 --> P3
    P3 --> P4
    P4 --> P5
    P5 --> P6
    P6 -->|healthy| PD1
    P6 -->|healthy| PD2
    P6 -->|healthy| PD3
    P6 -->|healthy| PD4
    P6 -->|unhealthy| ROLLBACK([↩️ Rollback a release anterior])
```

### 5.5 Workflow de deploy a DigitalOcean vía OIDC (`deploy-production.yml`)

```yaml
# .github/workflows/deploy-production.yml
name: Deploy Production

on:
  push:
    tags: ['v*']
  workflow_dispatch:

permissions:
  contents: read
  id-token: write          # obligatorio para OIDC

concurrency:
  group: deploy-prod
  cancel-in-progress: false  # nunca cancelar un deploy a producción en curso

jobs:
  deploy:
    name: Deploy to production droplet
    runs-on: ubuntu-24.04
    environment:
      name: production
      url: https://api.example.tld
    steps:
      - uses: actions/checkout@11bd71901bbe5b1630ceea73d27597364c9af683  # v4.2.2

      - name: Configure SSH (clave de deploy restringida)
        env:
          DEPLOY_SSH_KEY: ${{ secrets.DEPLOY_SSH_PRIVATE_KEY }}
        run: |
          mkdir -p ~/.ssh
          echo "$DEPLOY_SSH_KEY" > ~/.ssh/deploy_ed25519
          chmod 600 ~/.ssh/deploy_ed25519
          ssh-keyscan -H ${{ secrets.DEPLOY_HOST }} >> ~/.ssh/known_hosts
          echo "::add-mask::$DEPLOY_SSH_KEY"

      - name: Sync release to droplet
        env:
          DEPLOY_HOST: ${{ secrets.DEPLOY_HOST }}
          DEPLOY_USER: deployer
          RELEASE_SHA: ${{ github.sha }}
        run: |
          rsync -az --delete \
            --exclude='.git' \
            --exclude='storage/logs/*' \
            --exclude='.env' \
            --exclude='vendor/' \
            -e 'ssh -i ~/.ssh/deploy_ed25519 -o StrictHostKeyChecking=yes' \
            ./ deployer@${DEPLOY_HOST}:/var/www/html/releases/${RELEASE_SHA}/

      - name: Remote deploy commands
        env:
          DEPLOY_HOST: ${{ secrets.DEPLOY_HOST }}
          RELEASE_SHA: ${{ github.sha }}
          APP_KEY: ${{ secrets.APP_KEY }}
          DB_PASSWORD: ${{ secrets.DB_PASSWORD }}
          CF_API_TOKEN: ${{ secrets.CLOUDFLARE_API_TOKEN }}
        run: |
          ssh -i ~/.ssh/deploy_ed25519 deployer@${DEPLOY_HOST} <<'EOF'
          set -euo pipefail
          cd /var/www/html

          # Symlink release → current
          ln -sfn releases/${RELEASE_SHA} current
          cd current

          # Composer install en el servidor (sin dev)
          composer install --no-dev --optimize-autoloader --no-interaction --no-progress

          # Migrate + cache
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan event:cache

          # Purga Opcache
          (echo "curl -s http://127.0.0.1:8080/opcache-purge") || true

          # Reload php-fpm (sin down-time)
          sudo systemctl reload php8.4-fpm

          # Tag release en DO Snapshot (best effort)
          curl -s -X POST \
            -H "Authorization: Bearer ${CF_API_TOKEN}" \
            https://api.digitalocean.com/v2/droplets/${{ secrets.DROPLET_ID }}/snapshots \
            -d "{\"name\":\"release-${RELEASE_SHA}\",\"tags\":[\"prod\"]}" || true

          # Cleanup: mantener últimos 5 releases
          ls -dt releases/* | tail -n +6 | xargs -r rm -rf
          EOF

      - name: Tag release in Sentry
        if: success()
        env:
          SENTRY_AUTH_TOKEN: ${{ secrets.SENTRY_AUTH_TOKEN }}
          SENTRY_ORG: example
          SENTRY_PROJECT: laravel-api
        run: |
          curl -fsS -X POST \
            "https://sentry.io/api/0/projects/${SENTRY_ORG}/${SENTRY_PROJECT}/releases/" \
            -H "Authorization: Bearer ${SENTRY_AUTH_TOKEN}" \
            -H "Content-Type: application/json" \
            -d "{
              \"version\": \"${{ needs.resolve-tag.outputs.tag }}\",
              \"refs\": [{
                \"repository\": \"${{ github.repository }}\",
                \"commit\": \"${{ needs.resolve-tag.outputs.sha }}\"
              }]
            }"

      - name: Notify Slack (deploy status)
        if: always()
        uses: slackapi/slack-github-action@37ebaef184d7626c5f204ab8d3baff4262dd30f0  # v1.27.0
        env:
          SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK_URL }}
        with:
          payload: |
            {
              "text": "${{ job.status == 'success' && '✅' || '❌' }} Deploy *${{ needs.resolve-tag.outputs.tag }}* (${{ needs.resolve-tag.outputs.sha }}) to *production* — ${{ job.status }}"
            }

      - name: Auto-rollback on failure
        if: failure()
        env:
          DEPLOY_HOST: ${{ secrets.DEPLOY_HOST }}
        run: |
          ssh -i ~/.ssh/deploy_ed25519 deployer@${DEPLOY_HOST} <<'REMOTE_EOF'
          set -euo pipefail
          LAST_GOOD=$(ls -dt /var/www/html/releases/*/  | head -n 2 | tail -n 1)
          echo "Rolling back to: $LAST_GOOD"
          ln -sfn "$LAST_GOOD" /var/www/html/current
          cd /var/www/html/current
          php artisan config:cache
          php artisan route:cache
          sudo systemctl reload php8.4-fpm
          REMOTE_EOF
```

> **Decisión [SECURITY]:** la clave SSH de deploy está **restringida** en `~/.ssh/authorized_keys` del usuario `deployer` con directivas `command`, `from`, `no-port-forwarding`, `no-X11-forwarding`, `no-agent-forwarding`, `no-pty`:
>
> ```
> command="/usr/local/bin/deploy-cmd.sh",from="4.148.0.0/14",from="20.0.0.0/8",from="13.64.0.0/11",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty ssh-ed25519 AAAA... gh-deploy
> ```
>
> Las IPs de los GitHub Actions runners cambian frecuentemente y el rango completo ocupa >200 bloques CIDR (verificado contra `https://api.github.com/meta` clave `actions` [F: docs.github.com/en/actions/security-guides/security-harden-deployments, 2026-09-22]). En lugar de hardcodear el rango completo en `authorized_keys`, la práctica recomendada es:
>
> 1. **Whitelist dinámica vía script de bootstrap** ejecutado en el primer arranque del droplet: `curl -s https://api.github.com/meta | jq -r '.actions[]' > /etc/ssh/actions-runners.allowed`.
> 2. **En `sshd_config`** añadir `AllowUsers deployer@*` y bloquear deploy con `iptables -I INPUT -p tcp --dport 22 -m set --match-set actions-runners src -j ACCEPT` antes de la regla drop-all.
> 3. **Regenerar** la lista en cada deploy con un cron semanal (el rango cambia cuando GitHub añade DCs).
>
> `deploy-cmd.sh` solo permite `cd /var/www/html/current && /usr/bin/git checkout` o comandos pre-aprobados; cualquier otra cosa se rechaza.

#### Diagrama 1 — Flujo de deploy GitHub Actions

```mermaid
sequenceDiagram
    autonumber
    actor Dev as Developer
    participant GH as GitHub
    participant CI as Actions Runner
    participant DO as DigitalOcean API
    participant DR as Droplet (deployer user)
    participant RR as Restic Backup

    Dev->>GH: git tag v1.4.2 && git push --tags
    GH->>CI: trigger workflow (push tag v*)
    CI->>CI: ci.yml (lint+test)
    CI->>CI: build.yml (docker buildx multi-arch)
    CI->>CI: Trivy scan + Grype + Syft SBOM
    CI->>GH: push image to GHCR + cosign sign
    CI->>CI: attest-build-provenance (SLSA L3)

    CI->>DO: OIDC token → DO Spaces temp credentials
    CI->>RR: snapshot pre-deploy (best-effort)
    CI->>DR: rsync release (SSH ed25519 restringido)
    DR->>DR: deploy-cmd.sh whitelist
    DR->>DR: composer install --no-dev
    DR->>DR: php artisan migrate --force
    DR->>DR: php artisan config:cache, route:cache
    DR->>DR: symlink current → release/$SHA
    DR->>DR: sudo systemctl reload php8.4-fpm
    DR-->>CI: exit 0
    CI->>GH: notify Slack/Telegram webhook (status: success)
    GH->>Dev: release published
```

### 5.6 Workflow de scan programado (`scan.yml`)

```yaml
# .github/workflows/scan.yml
name: Scheduled Security Scan

on:
  schedule:
    - cron: '17 5 * * *'   # 05:17 UTC diario
  workflow_dispatch:

permissions:
  contents: read
  security-events: write

jobs:
  trivy-fs:
    runs-on: ubuntu-24.04
    steps:
      - uses: actions/checkout@11bd71901bbe5b1630ceea73d27597364c9af683  # v4.2.2
      - uses: aquasecurity/trivy-action@915b19bbe73b92a6cf82a1bc12b087c9a19a5fe2  # v0.28.0
        with:
          scan-type: 'fs'
          scan-ref: '.'
          format: 'sarif'
          output: 'trivy-fs.sarif'
          severity: 'CRITICAL'
      - uses: github/codeql-action/upload-sarif@f35333b910470a5408cb081b68f0701254a7d27b  # v3.28.18
        with:
          sarif_file: trivy-fs.sarif

  govulncheck:
    runs-on: ubuntu-24.04
    steps:
      - uses: actions/checkout@11bd71901bbe5b1630ceea73d27597364c9af683  # v4.2.2
      - uses: actions/setup-go@0aaccfd150d50ccaeb58ebd88d36e91967a5f35b  # v5.4.0
        with:
          go-version: '1.23'
      - run: go install golang.org/x/vuln/cmd/govulncheck@latest
      - run: govulncheck ./...

  npm-audit:
    runs-on: ubuntu-24.04
    steps:
      - uses: actions/checkout@11bd71901bbe5b1630ceea73d27597364c9af683  # v4.2.2
      - uses: actions/setup-node@39370e3970a6d050c480ffad4ff0ed4d3fdee5af  # v4.1.0
      - run: npm audit --audit-level=high --omit=dev
```

### 5.7 Dependabot y revisión de dependencias

```yaml
# .github/dependabot.yml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
      day: "monday"
      time: "06:00"
    open-pull-requests-limit: 10
    labels: [dependencies, php]
    groups:
      laravel:
        patterns: ["laravel/*"]
      security:
        applies-to: security-updates
        schedules:
          - match: { security: true }

  - package-ecosystem: "npm"
    directory: "/frontend/"
    schedule: { interval: "weekly", day: "tuesday" }
    labels: [dependencies, frontend]

  - package-ecosystem: "github-actions"
    directory: "/"
    schedule: { interval: "monthly" }
    labels: [dependencies, ci]
    groups:
      actions:
        patterns: ["*"]
```

> **Decisión [SECURITY]:**
> 1. **Dependabot para actions está en `monthly`** — los SHA ya están pinneados, así que la actualización es segura y rutinaria.
> 2. **`applies-to: security-updates`** fuerza PRs automáticos solo para parches de seguridad de Composer.
> 3. **`open-pull-requests-limit: 10`** previene avalancha de PRs (que pueden ser vectores de *dependency confusion*).

### 5.8 CODEOWNERS y branch protection

```text
# .github/CODEOWNERS
# Cada cambio en código de aplicación requiere revisión de un CODEOWNER.
/app/                @santander/backend-team
/frontend/src/       @santander/frontend-team
/database/migrations/  @santander/dba-team
/.github/workflows/  @santander/devops-team
/docker/             @santander/devops-team
/config/             @santander/backend-team @santander/devops-team
*.env.example        @santander/devops-team
```

Reglas de protección (configurar en *Settings → Branches → Branch protection rules*):

| Regla | Valor |
|---|---|
| `main` | branch por defecto |
| `Require a pull request before merging` | ✅ |
| `Require approvals` | 2 |
| `Dismiss stale pull request approvals when new commits are pushed` | ✅ |
| `Require review from Code Owners` | ✅ |
| `Require status checks to pass before merging` | ✅ (CI, build, scan) |
| `Require branches to be up to date before merging` | ✅ |
| `Require conversation resolution before merging` | ✅ |
| `Require signed commits` | ✅ |
| `Require linear history` | ✅ |
| `Include administrators` | ✅ |
| `Restrict who can push to matching branches` | solo `maintain` team |
| `Do not allow force pushes` | ✅ |
| `Do not allow deletions` | ✅ |

### 5.9 Secret scanning + push protection

Activar en *Settings → Code security and analysis*:

- **Secret scanning:** ON
- **Push protection:** ON (bloquea pushes que contengan secrets conocidos)
- **Dependabot security updates:** ON
- **Dependabot version updates:** ON
- **Code scanning:** ON (CodeQL)

`push protection` usa la base de datos de GitHub Token Scanning, que detecta AWS keys, GitHub PATs, DO tokens, Stripe keys, Slack tokens, etc. [F: docs.github.com/en/code-security/secret-scanning, 2026-09-22].

> **Decisión [SECURITY]:** añadir un *custom pattern* para nuestro `DEPLOY_SSH_PRIVATE_KEY` con la regla:
>
> ```json
> {
>   "patterns": [{
>     "name": "Deploy SSH Private Key",
>     "pattern": "-----BEGIN OPENSSH PRIVATE KEY-----[\\s\\S]+?-----END OPENSSH PRIVATE KEY-----",
>     "multiline": true
>   }]
> }
> ```

---

## 6. Containerización segura

### 6.1 Imagen base: Docker Hardened Images (DHI)

Recomendamos **`dhi.io/php:8.4-fpm-alpine3.22`** o **`dhi.io/php:8.4-cli-alpine3.22`** sobre la imagen *vanilla* `php:8.4-fpm-alpine`. Las DHI son:

- **Sin shell** (`/bin/sh` no existe → reduce superficie de RCE).
- **No-root** (`USER 65532`).
- **SBOM + SLSA L3 + cosign sign** incluidos.
- **CVE baseline ~0** actualizado semanalmente [F: docs.docker.com/dhi, 2026-09-22].

Para fallback, usar **`cgr.dev/chainguard/php-fpm:latest`** (Chainguard), equivalentes en garantías.

### 6.2 Dockerfile multi-stage endurecido

```dockerfile
# syntax=docker/dockerfile:1.7
ARG PHP_VERSION=8.4.6
ARG COMPOSER_VERSION=2.7.7

# ─────────────────────────────────────────────────────────────────
# Stage 1: builder (instala dependencias, se descarta en final)
# ─────────────────────────────────────────────────────────────────
FROM dhi.io/php:8.4-fpm-alpine3.22 AS builder

ENV COMPOSER_ALLOW_SUPERUSER=0 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_HOME=/tmp/composer

# Instalamos composer desde source verificada
COPY --from=dhi.io/composer:2.7 /usr/bin/composer /usr/bin/composer
RUN composer --version

WORKDIR /build

# Cache layer: solo si cambia composer.json/lock
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

# Copiamos el código
COPY . .

# Generamos el autoloader optimizado con classmap authoritative
RUN composer dump-autoload \
        --classmap-authoritative \
        --no-dev

# Precache de configuración Laravel
RUN cp .env.production .env \
 && php artisan key:generate --force \
 && php artisan config:cache \
 && php artisan route:cache \
 && php artisan event:cache \
 && php artisan view:cache

# ─────────────────────────────────────────────────────────────────
# Stage 2: runtime
# ─────────────────────────────────────────────────────────────────
FROM dhi.io/php:8.4-fpm-alpine3.22 AS runtime

# Etiquetas obligatorias
LABEL org.opencontainers.image.title="Laravel 13 API" \
      org.opencontainers.image.description="Laravel 13 API hardened image" \
      org.opencontainers.image.vendor="example.tld" \
      org.opencontainers.image.licenses="MIT" \
      org.opencontainers.image.source="https://github.com/example/app" \
      org.opencontainers.image.documentation="https://github.com/example/app/blob/main/README.md"

# Capas: usuarios no-root
ARG UID=1000
ARG GID=1000

# Paquetes runtime mínimos (ca-certificates, tini, fcgi client para healthcheck)
RUN apk add --no-cache \
        ca-certificates=20241121-r1 \
        tini=0.19.0-r3 \
        fcgi=2024.05.18-r0 \
        tzdata=2025b-r0 \
    && update-ca-certificates \
    && cp /usr/share/zoneinfo/UTC /etc/localtime \
    && echo "UTC" > /etc/timezone

# Copiamos el build de la stage anterior
COPY --from=builder --chown=${UID}:${GID} /build /var/www/html

# Creamos directorios de storage y bootstrap/cache (Laravel necesita escribir)
RUN mkdir -p /var/www/html/storage/framework/{sessions,views,cache,testing} \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache \
 && chown -R ${UID}:${GID} /var/www/html \
 && chmod -R 0755 /var/www/html/storage \
 && chmod -R 0755 /var/www/html/bootstrap/cache

# FPM pool config
COPY docker/fpm-pool.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/php.ini /usr/local/etc/php/php.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf

# Script de arranque: lee secretos de /run/secrets (Docker secrets) y genera .env
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod 0555 /usr/local/bin/entrypoint.sh

# Healthcheck con fcgi (no necesita curl)
COPY docker/healthcheck.sh /usr/local/bin/healthcheck.sh
RUN chmod 0555 /usr/local/bin/healthcheck.sh

# Cambiamos a no-root
USER ${UID}:${GID}

# Volúmenes para logs y cache (writeable)
VOLUME ["/var/www/html/storage", "/var/www/html/bootstrap/cache"]

EXPOSE 9000

ENV PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin \
    PHP_FPM_LISTEN=0.0.0.0:9000 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

# tini para reaping de zombies (PID 1)
ENTRYPOINT ["/sbin/tini", "--", "/usr/local/bin/entrypoint.sh"]

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=20s --retries=3 \
  CMD ["/usr/local/bin/healthcheck.sh"]

CMD ["php-fpm"]
```

> **Decisión [SECURITY]:**
> 1. **`tini` como PID 1.** PHP-FPM no reapa zombies; un worker colgado se quedaría como `<defunct>` consumiendo PID. `tini` es 30 KiB y resuelve esto.
> 2. **`USER 1000:1000`** evita que un RCE en PHP-FPM escriba fuera del árbol de la app.
> 3. **Healthcheck con fcgi client** evita `curl` (binario grande, múltiples CVEs históricos).
> 4. **`APP_DEBUG=false` en ENV del Dockerfile.** No se puede cambiar sin rebuild. Doble red de seguridad junto al check de §1.12.

### 6.3 `php.ini` endurecido

```ini
; docker/php.ini
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /proc/self/fd/2
html_errors = Off

memory_limit = 256M
max_execution_time = 30
max_input_time = 30
max_input_vars = 1500

post_max_size = 20M
upload_max_filesize = 15M
max_file_uploads = 5

; Sessions
session.cookie_httponly = On
session.cookie_secure = On
session.cookie_samesite = "Lax"
session.use_strict_mode = On
session.use_only_cookies = On
session.use_trans_sid = Off
session.gc_maxlifetime = 7200

; Opcache (crítico para FPM)
opcache.enable = 1
opcache.enable_cli = 0
opcache.memory_consumption = 192
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.fast_shutdown = 1
opcache.jit = tracing
opcache.jit_buffer_size = 64M
```

### 6.4 FPM pool

```ini
; docker/fpm-pool.conf
[app]
user = app
group = app
listen = 0.0.0.0:9000

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 2
pm.max_spare_servers = 10
pm.max_requests = 1000       # reciclar workers periódicamente
pm.process_idle_timeout = 30s

request_terminate_timeout = 30s
request_slowlog_timeout = 5s
slowlog = /proc/self/fd/2

catch_workers_output = yes
decorate_workers_output = no

clear_env = no

php_admin_value[error_log] = /proc/self/fd/2
php_admin_flag[log_errors] = on
php_admin_value[memory_limit] = 256M

; security
php_admin_value[disable_functions] = exec,passthru,shell_exec,system,proc_open,popen,curl_multi_exec,parse_ini_file,show_source
php_admin_value[open_basedir] = /var/www/html:/tmp
```

> **Decisión [SECURITY]:**
> 1. **`disable_functions`** bloquea `exec`, `shell_exec`, etc. La aplicación no debería necesitarlos; si los necesita (ej. PDF generation con `wkhtmltopdf`), se mueve a una queue con proceso separado (Capítulo 4, §9).
> 2. **`open_basedir`** limita el filesystem al árbol de la app + `/tmp` (necesario para uploads y sessions).
> 3. **`pm.max_requests = 1000`** recicla workers, mitigando *memory leaks* lentos en extensiones PHP.

### 6.5 Entrypoint con secretos de Docker

```bash
#!/usr/bin/env bash
# docker/entrypoint.sh
set -euo pipefail

cd /var/www/html

# ── Generar .env desde Docker secrets ─────────────────────────────
if [[ ! -f .env && -d /run/secrets ]]; then
    : > .env
    for secret_file in /run/secrets/*; do
        [[ -f "$secret_file" ]] || continue
        key="$(basename "$secret_file" | tr '[:lower:]' '[:upper:]' | tr '-' '_')"
        value="$(cat "$secret_file")"
        printf "%s=%q\n" "$key" "$value" >> .env
    done
    chmod 0600 .env
fi

# ── Validar configuración antes de iniciar ────────────────────────
if [[ "${APP_ENV:-production}" == "production" ]]; then
    php artisan config:clear  # regenerar desde .env
    php -r '
        $env = parse_ini_file("/var/www/html/.env");
        if (($env["APP_DEBUG"] ?? "false") !== "false") {
            fwrite(STDERR, "FATAL: APP_DEBUG=true in production\n");
            exit(1);
        }
        if (empty($env["APP_KEY"] ?? "")) {
            fwrite(STDERR, "FATAL: APP_KEY missing\n");
            exit(1);
        }
    '
fi

# ── Optimizar si hace falta ───────────────────────────────────────
[[ ! -f bootstrap/cache/config.php ]] && php artisan config:cache
[[ ! -f bootstrap/cache/routes-v7.php ]] && php artisan route:cache

exec "$@"
```

### 6.6 Healthcheck

```bash
#!/usr/bin/env bash
# docker/healthcheck.sh
set -euo pipefail
SCRIPT_NAME="${HEALTHCHECK_PATH:-/api/health}"
fcgi-request \
    --server "127.0.0.1:9000" \
    --request "$SCRIPT_NAME" \
    --method GET \
    --header "X-Health-Check: 1" \
    /var/www/html/public/index.php 2>/dev/null \
    | head -n 1 | grep -q "200"
```

> El endpoint `/api/health` (en la app) debe:
> 1. Responder 200 con `{"data":{"type":"health","attributes":{"status":"ok"}}}`.
> 2. NO requerir auth.
> 3. NO escribir a BD (solo ping).
> 4. Ser excluido del rate-limiter (whitelist en `RouteServiceProvider`).

### 6.7 `.dockerignore`

```gitignore
.git
.gitignore
.github
.idea
.vscode
node_modules
frontend/node_modules
frontend/dist
frontend/.env*
docker-compose*.yml
docker-compose*.yaml
README.md
CHANGELOG.md
.env
.env.*
!.env.example
storage/logs/*
storage/framework/cache/data/*
storage/framework/sessions/*
storage/framework/views/*
tests
phpunit.xml
phpstan.neon
.psalm.xml
docs/
artifacts/
*.log
*.tar.gz
```

### 6.8 `docker-compose.yml` endurecido

```yaml
# docker-compose.yml
name: laravel-app

x-logging: &default-logging
  driver: json-file
  options:
    max-size: "10m"
    max-file: "5"

x-security-opts: &default-security-opts
  security_opt:
    - no-new-privileges:true
  cap_drop:
    - ALL
  cap_add:
    - NET_BIND_SERVICE       # solo si el contenedor expone < 1024
  read_only: false           # true en api/worker; false en scheduler que escribe logs a stdout

secrets:
  app_key:
    file: ./secrets/APP_KEY
  db_password:
    file: ./secrets/DB_PASSWORD
  cloudflare_api_token:
    file: ./secrets/CLOUDFLARE_API_TOKEN
  do_api_token:
    file: ./secrets/DO_API_TOKEN
  github_deploy_key:
    file: ./secrets/GITHUB_DEPLOY_KEY

networks:
  backend:
    driver: bridge
    driver_opts:
      com.docker.network.bridge.name: br-app
    ipam:
      config:
        - subnet: 172.28.0.0/24

volumes:
  app_storage:
  pgdata:
  redisdata:

services:
  api:
    image: ghcr.io/example/app:${IMAGE_TAG:-latest}
    container_name: api
    restart: unless-stopped
    <<: *default-logging
    security_opt:
      - no-new-privileges:true
      - seccomp:./seccomp/fpm.json
      - apparmor:api
    cap_drop: [ALL]
    read_only: true
    tmpfs:
      - /tmp:size=64m,mode=1777
      - /var/www/html/storage/framework/cache:size=128m
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
      DB_HOST: postgres
      REDIS_HOST: redis
      LOG_CHANNEL: stderr
      PHP_FPM_LISTEN: 0.0.0.0:9000
    secrets:
      - app_key
      - db_password
    depends_on:
      postgres: { condition: service_healthy }
      redis:    { condition: service_healthy }
    networks:
      - backend
    healthcheck:
      test: ["/usr/local/bin/healthcheck.sh"]
      interval: 30s
      timeout: 3s
      retries: 3
      start_period: 20s
    deploy:
      resources:
        limits:
          cpus: '2.0'
          memory: 512M
        reservations:
          cpus: '0.5'
          memory: 256M

  worker:
    image: ghcr.io/example/app:${IMAGE_TAG:-latest}
    container_name: worker
    restart: unless-stopped
    command: ["php", "artisan", "queue:work", "--tries=3", "--max-time=3600", "--memory=256"]
    <<: *default-logging
    security_opt:
      - no-new-privileges:true
      - seccomp:./seccomp/worker.json
    cap_drop: [ALL]
    read_only: true
    tmpfs:
      - /tmp:size=32m,mode=1777
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
      DB_HOST: postgres
      REDIS_HOST: redis
      QUEUE_CONNECTION: redis
    secrets:
      - app_key
      - db_password
    depends_on:
      postgres: { condition: service_healthy }
      redis:    { condition: service_healthy }
    networks:
      - backend
    deploy:
      resources:
        limits: { cpus: '1.5', memory: 384M }

  scheduler:
    image: ghcr.io/example/app:${IMAGE_TAG:-latest}
    container_name: scheduler
    restart: unless-stopped
    command: ["sh", "-c", "while true; do php artisan schedule:run --no-interaction; sleep 60; done"]
    <<: *default-logging
    cap_drop: [ALL]
    security_opt:
      - no-new-privileges:true
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
    secrets:
      - app_key
      - db_password
    depends_on:
      postgres: { condition: service_healthy }
    networks:
      - backend

  postgres:
    image: postgres:16-alpine
    container_name: postgres
    restart: unless-stopped
    <<: *default-logging
    security_opt:
      - no-new-privileges:true
    cap_drop: [ALL]
    cap_add: [CHOWN, SETUID, SETGID, DAC_OVERRIDE]
    environment:
      POSTGRES_DB: app_prod
      POSTGRES_USER: app_prod
      POSTGRES_PASSWORD_FILE: /run/secrets/db_password
      POSTGRES_INITDB_ARGS: "--encoding=UTF-8 --locale=C"
      PGDATA: /var/lib/postgresql/data/pgdata
    volumes:
      - pgdata:/var/lib/postgresql/data
    secrets:
      - db_password
    networks:
      - backend
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U app_prod -d app_prod"]
      interval: 10s
      timeout: 3s
      retries: 5
    deploy:
      resources:
        limits: { cpus: '2.0', memory: 1G }

  redis:
    image: redis:7-alpine
    container_name: redis
    restart: unless-stopped
    <<: *default-logging
    security_opt:
      - no-new-privileges:true
    cap_drop: [ALL]
    command: >
      redis-server
      --maxmemory 256mb
      --maxmemory-policy allkeys-lru
      --save 900 1
      --save 300 10
      --save 60 10000
      --appendonly yes
      --appendfsync everysec
      --requirepass ${REDIS_PASSWORD:?no REDIS_PASSWORD set}
      --bind 0.0.0.0
    volumes:
      - redisdata:/data
    networks:
      - backend
    healthcheck:
      test: ["CMD", "redis-cli", "-a", "${REDIS_PASSWORD}", "ping"]
      interval: 10s
      timeout: 3s
      retries: 5
    deploy:
      resources:
        limits: { cpus: '0.5', memory: 320M }

  nginx:
    image: nginx:1.29-alpine
    container_name: nginx
    restart: unless-stopped
    <<: *default-logging
    security_opt:
      - no-new-privileges:true
    cap_drop: [ALL]
    cap_add: [CHOWN, SETUID, SETGID, NET_BIND_SERVICE]
    read_only: true
    tmpfs:
      - /var/cache/nginx:size=64m
      - /var/run:size=16m
    volumes:
      - ./docker/nginx.conf:/etc/nginx/nginx.conf:ro
      - ./docker/conf.d:/etc/nginx/conf.d:ro
      - ./docker/certs:/etc/nginx/certs:ro
      - app_storage:/var/www/html/storage:ro
    ports:
      - "127.0.0.1:8080:8080"
    depends_on:
      api: { condition: service_healthy }
    networks:
      - backend
    healthcheck:
      test: ["CMD", "wget", "-q", "--spider", "http://127.0.0.1:8080/health"]
      interval: 30s
      timeout: 3s
      retries: 3
```

> **Decisión [SECURITY]:**
> 1. **`POSTGRES_PASSWORD_FILE`** lee el secret de `/run/secrets/db_password` sin exponerlo en `docker inspect` ni en variables de entorno.
> 2. **`read_only: true`** en api/worker; `tmpfs` para `/tmp` y `storage/framework/cache` (PHP necesita escribir ahí).
> 3. **`cap_drop: [ALL]` + `no-new-privileges:true`** en todos los servicios. Solo `NET_BIND_SERVICE` se añade donde se necesita escuchar en <1024 (Nginx).
> 4. **`seccomp` profiles personalizados** limitan syscalls disponibles (los perfiles se incluyen en `docker/seccomp/`).
> 5. **`127.0.0.1:8080:8080`** bindea Nginx solo en loopback — Cloudflare Tunnel (§7) llega a `127.0.0.1:8080` localmente.
> 6. **No hay `volumes: ["./:/var/www/html"]`** en producción — el código entra por imagen.

---

## 7. Detección de intrusos y monitoreo

### 7.1 Wazuh agent: instalación y configuración

Wazuh es un HIDS (Host-based Intrusion Detection System) que combina *file integrity monitoring*, *log analysis*, *vulnerability detection* y *system inventory* [F: documentation.wazuh.com/current, 2026-09-22].

```bash
# En el droplet
curl -s https://packages.wazuh.com/key/GPG-KEY-WAZUH | gpg --dearmor -o /etc/apt/keyrings/wazuh.gpg
echo "deb [signed-by=/etc/apt/keyrings/wazuh.gpg] https://packages.wazuh.com/4.x/apt/ stable main" \
    > /etc/apt/sources.list.d/wazuh.list
apt-get update
WAZUH_MANAGER="wazuh.example.tld" apt-get install -y wazuh-agent
systemctl daemon-reload
systemctl enable --now wazuh-agent
```

### 7.2 `ossec.conf` (FIM + custom log)

```xml
<!-- /var/ossec/etc/ossec.conf -->
<ossec_config>
  <client_buffer>
    <disabled>no</disabled>
    <queue_size>5000</queue_size>
    <events_per_second>500</events_per_second>
  </client_buffer>

  <syslog>
    <format>cef</format>
  </syslog>

  <!-- File Integrity Monitoring -->
  <syscheck>
    <disabled>no</disabled>
    <frequency>21600</frequency>            <!-- 6 h -->
    <scan_on_start>yes</scan_on_start>
    <alert_new_files>yes</alert_new_files>
    <auto_ignore frequency="10" timeframe="3600">no</auto_ignore>

    <directories whodata="yes" report_changes="yes" check_all="yes"
                 recursion_level="5" tags="critical-config"
                 restrict="\.(conf|cnf|cfg|yml|yaml|json|env)$">
      /etc/nginx
    </directories>

    <directories whodata="yes" report_changes="yes" check_all="yes"
                 recursion_level="3" tags="ssh-config">
      /etc/ssh
    </directories>

    <directories check_all="yes" recursion_level="3" tags="webapp-code">
      /var/www/html/app
    </directories>

    <directories check_all="yes" recursion_level="2" tags="webapp-config">
      /var/www/html/config
    </directories>

    <directories check_all="yes" recursion_level="2" tags="php-fpm-config">
      /etc/php/8.4
    </directories>

    <nodiff>/etc/ssl/private</nodiff>
    <nodiff>/etc/shadow</nodiff>
    <nodiff type="sregex">\.key$</nodiff>

    <ignore type="sregex">/storage/logs/.*\.log$</ignore>
    <ignore type="sregex">/storage/framework/sessions/.*</ignore>
    <ignore type="sregex">/bootstrap/cache/.*\.php$</ignore>

    <skip_nfs>yes</skip_nfs>
    <skip_dev>yes</skip_dev>
    <skip_proc>yes</skip_proc>
    <skip_sys>yes</skip_sys>

    <file_limit>
      <enabled>yes</enabled>
      <entries>200000</entries>
    </file_limit>

    <synchronization>
      <enabled>yes</enabled>
      <interval>5m</interval>
      <max_eps>10</max_eps>
    </synchronization>
  </syscheck>

  <!-- Log monitoring -->
  <localfile>
    <log_format>json</log_format>
    <location>/var/www/html/storage/logs/security.log</location>
    <label key="app">laravel-security</label>
  </localfile>

  <localfile>
    <log_format>syslog</log_format>
    <location>/var/log/auth.log</location>
    <label key="app">sshd</label>
  </localfile>

  <localfile>
    <log_format>syslog</log_format>
    <location>/var/log/nginx/access.log</location>
    <label key="app">nginx</label>
  </localfile>

  <localfile>
    <log_format>syslog</log_format>
    <location>/var/log/nginx/error.log</location>
    <label key="app">nginx</label>
  </localfile>

  <!-- Auditd integration via audisp-remote -->
  <localfile>
    <log_format>audit</log_format>
    <location>/var/log/audit/audit.log</location>
  </localfile>

  <active-response>
    <disabled>no</disabled>
    <command>firewall-drop</command>
    <location>local</location>
    <rules_id>5710,5720,5730</rules_id>
    <timeout>600</timeout>
  </active-response>
</ossec_config>
```

> **Decisión [SECURITY]:**
> 1. **`whodata="yes"`** en `/etc/nginx` y `/etc/ssh` — Wazuh instrumenta auditd para registrar quién y cuándo modificó cada archivo, con qué proceso [F: documentation.wazuh.com/current/user-manual/capabilities/file-integrity, 2026-09-22].
> 2. **`auto_ignore frequency="10"`** evita ruido en archivos que cambian más de 10 veces por hora (logs).
> 3. **`alert_new_files="yes"`** en webapp-code detecta webshells dejados por RCE.
> 4. **`nodiff`** evita enviar a Wazuh el contenido de archivos sensibles.

### 7.3 Reglas custom para Laravel

`/var/ossec/etc/rules/local_rules.xml`:

```xml
<group name="laravel,security">

  <!-- Login fallido -->
  <rule id="100001" level="7">
    <match>auth.login.failed</match>
    <description>Laravel: failed login attempt</description>
    <group>authentication_failures,</group>
  </rule>

  <!-- 5 o más logins fallidos en 10 min: alerta -->
  <rule id="100002" level="12" frequency="5" timeframe="600">
    <if_matched_sid>100001</if_matched_sid>
    <same_field>json.email</same_field>
    <description>Laravel: brute-force credential stuffing detected</description>
    <group>authentication_failures,attacks,</group>
  </rule>

  <!-- Cambio de rol -->
  <rule id="100003" level="10">
    <match>role.changed</match>
    <description>Laravel: role change performed</description>
    <group>policy_violation,</group>
  </rule>

  <!-- RBAC denied -->
  <rule id="100004" level="8">
    <match>rbac.denied</match>
    <description>Laravel: RBAC access denied</description>
    <group>access_denied,</group>
  </rule>

  <!-- Rate limit exceeded -->
  <rule id="100005" level="6" frequency="10" timeframe="60">
    <if_matched_sid>100006</if_matched_sid>
    <description>Laravel: rate limit exceeded (burst)</description>
  </rule>
  <rule id="100006" level="3">
    <match>rate_limit.exceeded</match>
    <description>Laravel: rate limit single hit</description>
  </rule>

  <!-- File upload sospechoso (PHP dentro de un zip, MIME raro, etc.) -->
  <rule id="100007" level="12">
    <match>file.upload.suspicious</match>
    <description>Laravel: suspicious upload blocked</description>
    <group>web,attack,</group>
  </rule>

  <!-- Token emitido con ability admin:* desde IP no corporativa -->
  <rule id="100008" level="10">
    <match>token.issued</match>
    <field name="json.abilities">admin:*</field>
    <description>Laravel: privileged token issued</description>
    <group>policy_violation,</group>
  </rule>

  <!-- Mass assignment attempt -->
  <rule id="100009" level="12">
    <match>mass_assignment.rejected</match>
    <description>Laravel: mass assignment attempt blocked</description>
    <group>web,attack,</group>
  </rule>
</group>
```

> **Decisión [SECURITY]:** los IDs `100001+` están en el rango *custom* (los oficiales son `< 100000`). Esto evita colisión con reglas oficiales de Wazuh.

### 7.4 Alertas: Slack + Telegram + email

Configurar en *Wazuh dashboard → Server management → Settings → Notifications* o vía *integration* custom:

```xml
<!-- /var/ossec/etc/integrations/slack.xml -->
<integration>
  <name>slack</name>
  <hook_url><URL_DEL_WEBHOOK_DE_SLACK></hook_url>
  <level>10</level>
  <alert_format>{json}</alert_format>
</integration>
```

Alternativa Telegram vía custom script (más privado):

```bash
#!/usr/bin/env bash
# /var/ossec/integrations/custom-telegram
ALERT_FILE="$1"
WEBHOOK=$(awk -F'|' '{print $4}' "$ALERT_FILE" | tr -d '\n')
[[ -z "$WEBHOOK" ]] && WEBHOOK="$TELEGRAM_WEBHOOK_DEFAULT"
[[ -f "$WEBHOOK" ]] && WEBHOOK_URL="$(cat $WEBHOOK)"

LEVEL=$(awk -F':' '/level/{print $2}' "$ALERT_FILE" | tr -d '", ' | head -1)
RULE=$(awk -F':' '/rule/{print $2}' "$ALERT_FILE" | tr -d '", ' | head -1)
MSG=$(awk -F':' '/description/{print $2}' "$ALERT_FILE" | tr -d '",' | head -1)

# Redactar tokens conocidos
MSG=$(echo "$MSG" | sed -E 's/(token|password|key)=[^&]*/\1=[REDACTED]/g')

curl -s -X POST "$WEBHOOK_URL" \
    -d "{\"text\":\"🚨 Wazuh L${LEVEL} rule ${RULE}: ${MSG}\"}" >/dev/null
exit 0
```

### 7.5 auditd → Wazuh

`/etc/audit/rules.d/wazuh.rules`:

```text
# Buffer
-b 8192
-f 1
--backlog_wait_time 60000

# Fail2Ban jail visibility
-w /var/log/fail2ban.log -p wa -k fail2ban

# Authentication
-w /etc/pam.d/ -p wa -k pam
-w /etc/nsswitch.conf -p wa -k nss
-w /etc/ssh/sshd_config -p wa -k sshd_config

# Cron
-w /etc/cron.allow -p wa -k cron
-w /etc/cron.deny -p wa -k cron
-w /etc/crontab -p wa -k cron
-w /var/spool/cron/ -p wa -k cron

# Sudoers
-w /etc/sudoers -p wa -k sudoers
-w /etc/sudoers.d/ -p wa -k sudoers

# System binaries (cambios no deben ocurrir)
-w /usr/bin/ -p wa -k system_binaries
-w /usr/sbin/ -p wa -k system_binaries
-w /usr/local/bin/ -p wa -k system_binaries
-w /bin/ -p wa -k system_binaries
-w /sbin/ -p wa -k system_binaries

# Containers
-w /etc/docker -p wa -k docker
-w /usr/bin/docker -p x -k docker
-w /var/run/docker.sock -p wa -k docker
```

Plug audisp-remote:

```text
# /etc/audisp/plugins.d/au-remote.conf
active = yes
direction = out
path = /sbin/audisp-remote
type = always
```

```text
# /etc/audisp/audisp-remote.conf
remote_server = wazuh.example.tld
port = 1514
transport = tcp
# Si Wazuh manager está en otra red, usar RELAY
```

> **Decisión [SECURITY]:** `-w /usr/bin/ -p wa` genera **muchos** eventos; se filtra en Wazuh con `<ignore type="sregex">^/usr/bin/(dpkg|apt|...)</ignore>` o se excluyen reglas específicas que generan ruido. Auditar cambios en binarios del sistema detecta *rootkits* pero requiere análisis posterior.

### 7.6 Métricas: Prometheus + exporters

```bash
# Node exporter
useradd -r -s /usr/sbin/nologin node_exporter
wget https://github.com/prometheus/node_exporter/releases/download/v1.8.2/node_exporter-1.8.2.linux-amd64.tar.gz
tar xzf node_exporter-*.tar.gz -C /opt/
cat > /etc/systemd/system/node_exporter.service <<'EOF'
[Unit]
Description=Node Exporter
After=network.target

[Service]
User=node_exporter
ExecStart=/opt/node_exporter-1.8.2.linux-amd64/node_exporter \
    --web.listen-address=127.0.0.1:9100 \
    --collector.filesystem.mount-points-exclude='^/(sys|proc|dev|host|etc)($$|/)'
Restart=on-failure

[Install]
WantedBy=multi-user.target
EOF
systemctl enable --now node_exporter
```

`/etc/prometheus/prometheus.yml` (en el manager central):

```yaml
global:
  scrape_interval: 15s
  evaluation_interval: 30s

scrape_configs:
  - job_name: 'droplet-app'
    static_configs:
      - targets: ['droplet-app.example.tld:9100']
    relabel_configs:
      - source_labels: [__address__]
        target_label: instance
        replacement: 'app-prod-1'

  - job_name: 'postgres'
    static_configs:
      - targets: ['droplet-app.example.tld:9187']

  - job_job: 'redis'
    static_configs:
      - targets: ['droplet-app.example.tld:9121']

  - job_name: 'nginx'
    static_configs:
      - targets: ['droplet-app.example.tld:9113']

  - job_name: 'php-fpm'
    static_configs:
      - targets: ['droplet-app.example.tld:9253']
```

> **Decisión [SECURITY]:** los exporters escuchan en `127.0.0.1` y Prometheus los scrapea vía SSH tunnel o servicio interno en VPC, nunca暴露 a internet. El firewall del Capítulo 2 cierra el acceso.

### 7.7 Dashboards Grafana

Paneles mínimos:

1. **Host overview**: CPU, RAM, disco, network, load average, boot time.
2. **Disk & inodes**: `/var` no > 80 %; `/var/log` rotación.
3. **Postgres**: connections (max 100), transactions/s, replication lag.
4. **Redis**: memory used, evicted keys, ops/s.
5. **Nginx**: requests/s por status code (5xx en rojo), top IPs, top URLs.
6. **PHP-FPM**: active workers, queue length, slow requests (>5s).
7. **Wazuh alerts**: top 10 rules fired últimas 24h, geolocalización de SSH brute-force.

JSON para importar en Grafana disponible en `artifacts/grafana/dashboard.json` (no incluido por brevedad, ver Apéndice A).

### 7.8 Uptime monitoring externo

- **UptimeRobot** (free tier): monitoriza `https://api.example.tld/health` cada 5 min; alerta por email/Slack si 2 checks fallan.
- **Healthchecks.io**: monitoriza *cron jobs* (`/api/cron/ping/...` con UUID) y avisa si un *scheduled task* no se ejecutó en la ventana esperada. Se documenta en el runbook de §9.6.
- **Sentry** (frontend y backend): captura excepciones y performance. DSN es público (solo ingest).

#### Diagrama 2 — Arquitectura de monitoreo

```mermaid
flowchart LR
    subgraph Droplet["Droplet 24.04 LTS"]
        NGINX[Nginx 1.29]
        PHP[PHP 8.4 FPM]
        PG[Postgres 16]
        RD[Redis 7]
        AUDITD[auditd]
        FILES[/etc, /var/www, /etc/nginx/]
        SECLOG[/var/www/html/storage/logs/security.log/]
    end

    WAGENT[Wazuh Agent]
    NODE[Node Exporter :9100]
    PGPROM[postgres_exporter :9187]
    REDPROM[redis_exporter :9121]
    NGXPROM[nginx-prometheus-exporter :9113]
    FPMPROM[php-fpm-exporter :9253]

    subgraph Central["Manager Central (VPC)"]
        WMANAGER[Wazuh Manager]
        PROM[Prometheus]
        GRAF[Grafana]
        ALERT[Alertmanager]
    end

    EXT[UptimeRobot + Healthchecks.io]
    SLACK[Slack / Telegram Webhook]
    PD[PagerDuty]
    SENTRY[Sentry.io]
    MAIL[Email SMTP]

    FILES -->|FIM| WAGENT
    SECLOG -->|localfile json| WAGENT
    AUDITD -->|audisp-remote| WAGENT
    WAGENT -->|TCP 1514| WMANAGER
    WMANAGER -->|rules/decoders| WMANAGER
    WMANAGER -->|alert L>=10| SLACK
    WMANAGER -->|alert L>=12| PD

    NGINX --> NGXPROM
    PHP --> FPMPROM
    PG --> PGPROM
    RD --> REDPROM
    NODE
    NGXPROM --> PROM
    FPMPROM --> PROM
    PGPROM --> PROM
    REDPROM --> PROM
    NODE --> PROM
    PROM --> GRAF
    PROM --> ALERT
    ALERT --> SLACK

    NGINX -->|HTTPS GET /health| EXT
    EXT -->|fail 2x| MAIL

    PHP -->|exceptions| SENTRY
```

### 7.9 Fail2ban

```ini
# /etc/fail2ban/filter.d/nginx-login.conf
[Definition]
failregex = ^<HOST> .* "(GET|POST) /api/login HTTP/\d+\.\d+" 401
            ^<HOST> .* "(GET|POST) /sanctum/csrf-cookie HTTP/\d+\.\d+" 429
ignoreregex =

# /etc/fail2ban/jail.d/api.conf
[api-login]
enabled  = true
filter   = nginx-login
logpath  = /var/log/nginx/access.log
port     = http,https
findtime = 600
maxretry = 10
bantime  = 3600
action   = iptables-multiport[name=api, port="http,https", protocol=tcp]

[sshd]
enabled  = true
port     = ssh
filter   = sshd
logpath  = /var/log/auth.log
maxretry = 5
findtime = 600
bantime  = 3600
```

Wazuh lee `/var/log/fail2ban.log` (audit rule de §7.5) y crea alerta de nivel 6 cuando una IP es baneada.

---

## 8. Backups cifrados

### 8.1 Política 3-2-1-1-0

Adoptamos la regla **3-2-1-1-0**:

- **3** copias del dato (producción + 2 backups).
- **2** medios diferentes (disco local + object storage).
- **1** copia offsite (DO Spaces en región diferente).
- **1** copia inmutable / offline (Snapshots DO marcados `immutable=true`).
- **0** errores verificados: `restic check` semanal sin errores.

### 8.2 restic a DO Spaces

#### Inicialización

```bash
# Generar passphrase fuerte (32 bytes base64)
openssl rand -base64 32 > /etc/restic/passphrase
chmod 0400 /etc/restic/passphrase
chown root:root /etc/restic/passphrase

# Variables de entorno para S3-compatible
cat > /etc/restic/restic.env <<'EOF'
AWS_ACCESS_KEY_ID=<ResticSpacesKey>
AWS_SECRET_ACCESS_KEY=<ResticSpacesSecret>
RESTIC_REPOSITORY=s3:s3.nyc3.digitaloceanspaces.com/example-prod-backups
RESTIC_PASSWORD_FILE=/etc/restic/passphrase
EOF
chmod 0400 /etc/restic/restic.env

restic -E /etc/restic/restic.env init
```

`restic` cifra con **AES-256-CTR + Poly1305-AES** y deriva la clave maestra con **scrypt** [F: restic.readthedocs.io/en/stable/100_references.html, 2026-09-22]. No existe la opción de *no cifrar*: por diseño, todo restic repo es cifrado.

#### Script de backup

```bash
#!/usr/bin/env bash
# /opt/backup/run.sh
set -euo pipefail
source /etc/restic/restic.env

export RESTIC_CACHE_DIR=/var/cache/restic
mkdir -p "$RESTIC_CACHE_DIR"

TS=$(date -u +%Y%m%dT%H%M%SZ)
HOSTNAME=$(hostname -s)
TAG="auto-${HOSTNAME}-${TS}"

# Pre-flight
restic check --read-data-subset=5% >/var/log/restic/check.log 2>&1 || {
    logger -t restic "restic check failed; aborting backup"
    echo "ALERT: restic check failed" | mail -s "[BACKUP] check failed on $HOSTNAME" alerts@example.tld
    exit 2
}

# Backup con tags y excludes
restic backup \
    --tag "$TAG" \
    --exclude-file=/etc/restic/excludes.txt \
    --exclude-caches \
    --one-file-system \
    --verbose \
    /etc \
    /var/www/html \
    /var/lib/restic-keys \
    /etc/ossec \
    /etc/audit \
    /root/.ssh \
    /var/log \
    2>&1 | tee -a /var/log/restic/backup-${TS}.log

# Retention: mantener últimos 7 daily, 4 weekly, 6 monthly
restic forget \
    --tag "$TAG" \
    --keep-daily 7 \
    --keep-weekly 4 \
    --keep-monthly 6 \
    --prune

logger -t restic "Backup $TAG completed"
```

`/etc/restic/excludes.txt`:

```text
*.tmp
*.swp
/var/www/html/storage/logs/*.log
/var/www/html/storage/framework/cache/data/*
/var/www/html/storage/framework/sessions/*
/var/www/html/bootstrap/cache/*.php
/var/www/html/node_modules
/var/www/html/vendor
*.sock
```

#### Cron

```cron
# /etc/cron.d/restic
17 2 * * *  root  /opt/backup/run.sh >/var/log/restic/cron.log 2>&1
```

> **Decisión [SECURITY]:** el job corre a las 02:17 UTC (distinto de los demás jobs del Capítulo 4) para evitar picos de I/O y para que el *snapshot* de DO sea posterior.

### 8.3 Snapshot DO inmutable

```bash
doctl compute snapshot create droplet-app-prod \
    --tag-name "release" \
    --description "Weekly immutable snapshot"
```

Para hacerlo realmente inmutable, usar *Spaces versioning* en el bucket `example-prod-backups` con *Object Lock* (modo *Compliance* — el bucket root debe ser creado con `x-amz-object-lock-mode: COMPLIANCE` y el lock de 30 días mínimo). DO Spaces soporta Object Lock desde 2023 [F: docs.digitalocean.com/products/spaces, 2026-09-22].

### 8.4 borgbackup como segunda capa (opcional)

Para datos que requieren *append-only mode* (por compliance RGPD), un repositorio borg en otro servidor:

```bash
borg init --encryption=repokey-blake2 /srv/borg/example-prod
borg create /srv/borg/example-prod::'{now}' /var/www/html /etc
```

`repokey-blake2` cifra con AES-256-CTR + HMAC-SHA256 (BLAKE2b hash) [F: borgbackup.readthedocs.io, 2026-09-22].

### 8.5 Drill de restauración semanal

```bash
#!/usr/bin/env bash
# /opt/backup/drill.sh  (cron semanal, domingo 04:17 UTC)
set -euo pipefail
source /etc/restic/restic.env
export RESTIC_CACHE_DIR=/var/cache/restic

DRILL_DIR=/var/tmp/restic-drill
rm -rf "$DRILL_DIR"
mkdir -p "$DRILL_DIR"

# Verificar integridad completa (10 % de los datos)
restic check --read-data-subset=10%

# Restaurar 3 archivos críticos como spot-check
restic restore latest \
    --target "$DRILL_DIR" \
    --include /etc/nginx \
    --include /var/www/html/.env.example \
    --include /var/www/html/config/app.php

# Diff contra el sistema real (debe ser idéntico en config)
diff -r /etc/nginx "$DRILL_DIR/etc/nginx" || {
    echo "DRILL FAIL: /etc/nginx diverges"; exit 1
}

# Métricas
SIZE=$(du -sh "$DRILL_DIR" | cut -f1)
COUNT=$(find "$DRILL_DIR" -type f | wc -l)
logger -t restic "Drill OK: restored $COUNT files ($SIZE) to $DRILL_DIR"

rm -rf "$DRILL_DIR"
```

```cron
17 4 * * 0  root  /opt/backup/drill.sh >/var/log/restic/drill.log 2>&1
```

Resultado del drill se publica automáticamente a Slack `#backups` con `curl` al webhook.

### 8.6 Cifrado en reposo: dónde vive la passphrase

| Opción | Seguridad | Operatividad |
|---|---|---|
| `/etc/restic/passphrase` con `chmod 0400` | Media | Alto (mismo host) |
| Hashicorp Vault (Transit engine) | Alta | Media (latencia + ops) |
| SOPS + age (en repo Git, descifrado por CI) | Alta | Media |
| Passphrase en dropbear initramfs (unlock remoto) | Muy alta | Baja (DR compleja) |

Recomendación del proyecto: **Vault con Transit + unseal keys en 3 personas** (cada una con su propio secreto) + **copia offline en papel en caja fuerte**. La passphrase de restic nunca se almacena en GitHub.

---

## 9. Runbook de respuesta a incidentes

### 9.1 Marco NIST SP 800-61r3 aplicado

Adoptamos las cuatro fases de NIST SP 800-61r3 [NIST, 2025-04-03]:

1. **Preparation** — capítulo 1 al 8 ya cubren el grueso (IDS, backups, MFA, secretos).
2. **Detection & Analysis** — Wazuh, auditd, métricas.
3. **Containment, Eradication & Recovery** — §9.2 a §9.6.
4. **Post-Incident Activity** — §9.7 post-mortem.

### 9.2 Roles y contactos

| Rol | Persona | Contacto |
|---|---|---|
| Incident Commander (IC) | <nombre> | +34 XXX XXX XXX, ic@example.tld |
| Tech Lead | <nombre> | +34 XXX, tech@example.tld |
| DevOps on-call | rotación PagerDuty | pd-oncall@example.tld |
| DBA | <nombre> | dba@example.tld |
| Legal (RGPD/breach) | <abogado> | legal@example.tld |
| DigitalOcean support | — | https://cloud.digitalocean.com/support |
| Cloudflare support | — | Enterprise dashboard |

### 9.3 Caso A: SSH comprometido

#### Señales típicas

- Wazuh rule 5710 (SSHD authentication failure): 30+ fallos/min desde una IP.
- `journalctl -u ssh` muestra login exitoso de usuario `deployer` desde IP no corporativa.
- `who` muestra una sesión activa no reconocida.

#### Diagrama 3 — Runbook SSH comprometido

```mermaid
flowchart TD
    A[Alerta Wazuh: SSH brute-force exitoso] --> B{Auto-bloqueo<br/>Fail2Ban funcionó?}
    B -- Sí --> C[IP en iptables DROP<br/>Verificar who, w, last]
    B -- No --> D[Manual: iptables -I INPUT -s IP -j DROP]

    C --> E[Containment<br/>1. Bloquear IP en Cloudflare<br/>2. fail2ban ban manual]
    D --> E
    E --> F[Evidencia<br/>cp /var/log/auth.log.* /secure/evidence/]

    F --> G{¿LLave SSH usada<br/>por el atacante?}
    G -- Sí --> H[Identificar fingerprint<br/>ssh-keygen -l -f /root/.ssh/known_hosts]
    H --> I[Revocar llave pública<br/>en authorized_keys de TODOS los usuarios]

    G -- No --> J[¿Fuerza bruta<br/>de contraseña?]
    J -- Sí --> K[Verificar PasswordAuthentication no<br/>debería ser posible; bug si lo es]

    F --> L[Rotación de secretos<br/>1. APP_KEY (nueva, previa en APP_PREVIOUS_KEYS 7d)<br/>2. DB passwords<br/>3. Tokens Sanctum: TRUNCATE personal_access_tokens<br/>4. Deploy keys SSH (regenerar ed25519)<br/>5. GitHub PATs/PAT for deploy<br/>6. Cloudflare API token<br/>7. DO API token]

    L --> M[Invalidar sesiones<br/>DELETE FROM sessions<br/>DELETE FROM personal_access_tokens]

    M --> N[Auditoría forense<br/>1. Wazuh retroactivo: últimos 14 días<br/>2. last, lastb, lastlog<br/>3. journalctl _COMM=sudo<br/>4. Buscar web shells: find /var/www -name '*.php' -newer /var/log/auth.log<br/>5. Listar procesos: ps auxf<br/>6. Netstat: ss -tunap]

    N --> O[¿Acceso a root?}
    O -- Sí --> P[ASUMIR rootkit<br/>Reinstalar droplet desde snapshot pre-incidente<br/>Cambiar TODAS las claves SSH]
    O -- No --> Q[Endurecer<br/>1. Cambiar Port (cap. 1)<br/>2. Revisar sudoers<br/>3. Auditd retroactivo<br/>4. Aumentar fail2ban maxretry]

    P --> R[Recovery<br/>1. Restaurar DB desde restic snapshot pre-incidente<br/>2. Verificar integridad: SELECT count(*) FROM users;<br/>3. Re-desplegar última versión limpia]

    Q --> R

    R --> S[Post-mortem<br/>Documentar incidente<br/>Identificar root cause<br/>Asignar remediation tasks<br/>Actualizar runbook]

    S --> T[Cerrar ticket PagerDuty<br/>Notificar Legal si datos afectados<br/>Comunicación stakeholders]
```

#### Lista de comandos concreta

```bash
# 1. Contención inmediata
sudo fail2ban-client set sshd banip <IP>
sudo iptables -I INPUT -s <IP> -j DROP
sudo ufw deny from <IP>
# Bloqueo perimetral (Cloudflare API)
curl -X POST "https://api.cloudflare.com/client/v4/firewall/access_rules/rules" \
    -H "Authorization: Bearer $CF_API_TOKEN" \
    -H "Content-Type: application/json" \
    --data "{\"mode\":\"block\",\"configuration\":{\"target\":\"ip\",\"value\":\"<IP>\"},\"notes\":\"incident-<ticket>\"}"

# 2. Identificar vector
last -i | head -20
lastb -i | head -20
journalctl -u ssh --since "7 days ago"

# 3. Evidencia (preservar antes de cualquier cambio)
sudo mkdir -p /secure/evidence/$(date -u +%Y%m%dT%H%M%SZ)/
sudo cp /var/log/{auth.log*,syslog*,fail2ban.log} /secure/evidence/$(date -u +%Y%m%dT%H%M%SZ)/
sudo cp -r /var/ossec/logs/archives /secure/evidence/$(date -u +%Y%m%dT%H%M%SZ)/
sudo chmod -R 0400 /secure/evidence/$(date -u +%Y%m%dT%H%M%SZ)/

# 4. Rotación de secretos
php artisan tinker --execute='\App\Models\User::query()->each(fn($u)=>$u->tokens()->delete());'
TRUNCATE personal_access_tokens;
NEW_APP_KEY=$(php artisan key:generate --show)
# Editar .env, APP_PREVIOUS_KEYS=$OLD_KEY, APP_KEY=$NEW_APP_KEY
# Cambiar DB_PASSWORD en DO Managed DB y en /run/secrets/db_password

# 5. Regenerar deploy key
ssh-keygen -t ed25519 -C "deploy@$(date +%s)" -f /tmp/new_deploy_key -N ''
# Añadir a GitHub Deploy keys con read-only
# Reemplazar en droplet authorized_keys

# 6. Análisis forense
sudo ausearch -m USER_LOGIN,USER_AUTH,EXECVE -ts recent
sudo find /var/www -name '*.php' -newermt "7 days ago" -ls
sudo find / -perm -4000 -type f -ls     # nuevos SUID
sudo ps auxf | less

# 7. Notificación
echo "Incident <ticket>: SSH compromise at $(date)" | mail -s "[INCIDENT] SSH" alerts@example.tld
```

### 9.4 Caso B: Aplicación comprometida (RCE / web shell)

#### Señales

- Wazuh rule 100007 (file.upload.suspicious) — subida rechazada pero con contenido PHP.
- Wazuh FIM: nuevo `.php` en `/var/www/html/storage/`.
- Nginx log: requests con payloads `<?php system($_GET['c'])`.
- `cpu` al 100 % sostenido por un worker PHP-FPM desconocido.

#### Respuesta

1. **Containment**: poner el sitio en *maintenance mode* (`php artisan down --secret=<token>`).
2. **Captura forense**: snapshot DO inmediato (aunque bloquee deploys).
3. **Identificar vector**: revisar logs nginx últimos 7 días; correlacionar con Wazuh FIM events.
4. **Rotación de APP_KEY** + DB password + Sanctum tokens (como §9.3 punto 4).
5. **Limpieza**: si hay web shell confirmado, NO borrar inmediatamente — copiar a evidencia primero.
6. **Recovery**: rollback a release anterior con `ln -sfn releases/<prev-ok-sha> current`.
7. **Post-incidente**: bug fix que evite el vector (input validation, file upload hardening, *uploaded MIME sniff*).

#### Hardening adicional inmediato

```php
// Reforzar upload de archivos (post-incidente)
$request->validate([
    'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
]);
$file = $request->file('file');
if ($file->getMimeType() !== $file->getMimeTypeFromExtension()) {
    SecurityLog::event('file.upload.suspicious', [
        'claimed' => $file->getClientMimeType(),
        'real' => $file->getMimeType(),
        'user' => $request->user()->id,
    ]);
    abort(422, 'MIME mismatch');
}
$path = $file->storeAs('uploads', Str::uuid(), 'private');
// NUNCA en /public/ directo
```

### 9.5 Caso C: DDoS

#### Señales

- UptimeRobot reporta >5xx masivos.
- Grafana `nginx_requests_total{status="5xx"}` se dispara.
- Wazuh rule 9500+ (HTTP flood).

#### Respuesta

1. **Activar Cloudflare "Under Attack Mode"** vía API:
   ```bash
   curl -X PATCH "https://api.cloudflare.com/client/v4/zones/$CF_ZONE/settings/security_level" \
       -H "Authorization: Bearer $CF_API_TOKEN" \
       -H "Content-Type: application/json" \
       --data '{"value":"under_attack"}'
   ```
2. **Rate-limit agresivo** en WAF rules: 10 req/min por IP global.
3. **Bloquear ASN** identificado (ej.ASN de un proveedor cloud específico).
4. **Escalar a DO support** si el ataque es >10 Gbps (afecta uplink del DC).
5. **NO** deshabilitar la CDN pensando "ahorra CPU" — la CDN es la primera línea.
6. **Documentar** duración, picos (Gbps/Mpps), reglas WAF añadidas.

### 9.6 Caso D: Ransomware

#### Señales

- Wazuh FIM: modificación masiva de archivos `.php`, `.jpg`, `.docx` → extensión `.locked`.
- Postgres log: conexión desde IP inusual, *truncate* o *drop* de tablas.
- Backups locales cifrados o eliminados.

#### Respuesta

1. **Aislar**: tirar firewall del droplet (cerrar TODOS los puertos).
2. **NO pagar** (NIST IR Guide §4.3).
3. **No apagar** el droplet — el estado en memoria puede contener claves de descifrado.
4. **Recolectar evidencia**: snapshot DO completo a bucket separado con Object Lock.
5. **Restaurar**: provisionar nuevo droplet desde `release-<last-clean-snapshot>` tag de DO.
6. **Cambiar TODAS las credenciales** que el droplet comprometido podía ver.
7. **Notificar Legal** en 72h (RGPD Art. 33 [EUR-Lex, 2016-04-27]).
8. **Post-mortem**: ¿por qué los backups no estaban en immutable storage? ¿cómo entró el ransomware?

### 9.7 Plantilla de post-mortem

```markdown
# Post-mortem: <título>

**Fecha del incidente:** YYYY-MM-DD
**Detectado:** HH:MM UTC
**Resuelto:** HH:MM UTC
**Severidad:** SEV1 | SEV2 | SEV3
**Incident Commander:** @nombre
**Reporter:** @nombre
**Status:** Draft | Reviewed | Final

## Resumen ejecutivo
<2-3 frases; qué pasó, qué se vio afectado, qué se hizo>

## Timeline (UTC)
| Hora | Evento |
|------|--------|
| HH:MM | Alerta Wazuh disparó |
| HH:MM | On-call paged |
| HH:MM | Contención completada |
| HH:MM | Root cause identificado |
| HH:MM | Mitigación desplegada |

## Impact
- **Usuarios afectados:** N / %
- **Downtime:** H horas
- **Datos exfiltrados:** sí/no/por confirmar
- **Coste estimado:** $X

## Root cause (5 Whys)
1. Why 1: ...
2. Why 2: ...
3. Why 3: ...
4. Why 4: ...
5. Why 5: ...

## What went well
- ...

## What went wrong
- ...

## Action items
- [ ] @owner — Acción concreta — fecha límite
- [ ] @owner — ...

## Lessons learned
- ...
```

> **Decisión [SECURITY]:** post-mortem es **blameless**. El objetivo es mejorar el sistema, no señalar personas. Documentado en el runbook del equipo y referenciado en el onboarding.

---

## 10. Política de secretos

### 10.1 Principios

1. **No secrets en código fuente.** `.env.example` con placeholders; `.env` en `.gitignore`.
2. **No secrets en logs.** Filtros `Monolog` + `add-mask::` en Actions + sanitización en `SecurityLog`.
3. **No secrets en tickets / Slack / email.** Política firmada por el equipo; incumplimiento = acción disciplinaria.
4. **Permisos `600`, owner `deployer:deployer` o `root:root`**, nunca world-readable.
5. **Rotación trimestral** de:
   - `APP_KEY` (con `APP_PREVIOUS_KEYS` por 90 días).
   - DB passwords (DO Managed Postgres rotación nativa).
   - Cloudflare API token.
   - DO API token.
   - Deploy keys SSH (ed25519).
   - GitHub PATs.
6. **Secret scanning + push protection** activados en GitHub (ver §5.9).
7. **Scaneo retroactivo** mensual con `gitleaks detect --no-git --redact` sobre todo el repo.

### 10.2 Almacenamiento por entorno

| Entorno | Almacenamiento | Acceso |
|---|---|---|
| Dev local | `.env` local (gitignored), git-crypt opcional | Solo el dev |
| CI/CD | GitHub Actions Secrets + Environments | Workflows con `permissions:` mínimos |
| Staging | Docker secrets en `secrets/staging/`, chmod 600 | Deployers + rotation policy |
| Producción | Docker secrets + SOPS en backup vault | Deployers + IC + Vault admin |
| Disaster recovery | Vault + copia offline en caja fuerte | 2 personas con 2FA |

### 10.3 Inventario de secretos

Ver Apéndice A.

### 10.4 SOPS + age (para IaC)

Si en algún momento la configuración se gestiona con Terraform/Ansible:

```bash
# age keypair
age-keygen -o keys/dev.age
AGE_PUBKEY=$(cat keys/dev.age | grep 'public key:' | awk '{print $3}')

# Encrypt
sops --age "$AGE_PUBKEY" --encrypt --in-place secrets/db.yaml

# Decrypt (en CI, key via env)
export SOPS_AGE_KEY_FILE=/path/to/key
sops --decrypt secrets/db.yaml | kubectl apply -f -
```

Age usa X25519 + ChaCha20-Poly1305 + HKDF-SHA-256 + scrypt [F: github.com/FiloSottile/age, 2026-09-22]. Mejor que PGP: sin servidores de claves, sin red, sin曲线参数不明.

### 10.5 HashiCorp Vault (alternativa enterprise)

```hcl
# policies/api.hcl
path "secret/data/app/*" {
  capabilities = ["read"]
}
path "auth/token/renew-self" {
  capabilities = ["update"]
}
path "auth/token/revoke-self" {
  capabilities = ["update"]
}
```

```bash
# Vault agent en el droplet lee y escribe a /run/secrets/
vault agent -config=/etc/vault/agent.hcl
```

Ventajas:
- Rotación automática de secretos dinámicos (DB credentials con TTL de 1h).
- Audit trail completo de quién leyó qué.
- AWS/GCP/Azure secrets engine.

Desventajas:
- Coste operativo (un servicio más que mantener).
- Single point of failure: requiere HA + unseal keys distribuidas.

> **Decisión [SECURITY]:** para una VPS única, **SOPS + age + cifrado en Git** es suficiente. Vault se justifica con >3 servicios o >5 secretos dinámicos.

### 10.6 Checklist operativa trimestral

| Tarea | Frecuencia | Responsable |
|---|---|---|
| Rotar `APP_KEY` con `APP_PREVIOUS_KEYS` | Trimestral | DevOps on-call |
| Rotar DB password | Trimestral | DBA |
| Rotar Cloudflare API token | Trimestral | DevOps on-call |
| Rotar DO API token | Trimestral | DevOps on-call |
| Regenerar deploy SSH key | Trimestral | DevOps on-call |
| Auditar `personal_access_tokens` activos | Mensual | Tech Lead |
| Revisar logs de gitleaks | Semanal | DevOps on-call |
| `restic check --read-data-subset=10%` | Semanal (automático) | Backup owner |
| `fail2ban` ban count > 0 en Slack | Diario | DevOps on-call |
| Tablero Wazuh: top 10 reglas disparadas | Semanal | IC |
| Post-mortem último incidente | Post-incidente | IC + Tech Lead |
| Penetration test externo | Anual | Third-party + IC |

---

## 11. Apéndice A — Inventario de secretos

| Nombre (referencia) | Tipo | Uso | Rotación | Almacenamiento |
|---|---|---|---|---|
| `APP_KEY` | Laravel encryption (AES-256-CBC base64) | Cookies, signed URLs, Crypt::encrypt | Trimestral | GitHub Secret + /run/secrets/app_key + Vault |
| `DB_PASSWORD` | Postgres user `app_prod` | Conexión DB | Trimestral | GitHub Secret + DO Managed DB + /run/secrets/db_password |
| `DB_PASSWORD_TEST` | Postgres user `app_test` (CI) | Tests en CI | Bajo demanda | GitHub Secret (solo en repo) |
| `REDIS_PASSWORD` | Redis requirepass | Conexión Redis | Trimestral | /run/secrets/redis_password |
| `CLOUDFLARE_API_TOKEN` | Cloudflare API token | WAF, Origin CA, purge cache | Trimestral | GitHub Secret |
| `CLOUDFLARE_ZONE_ID` | Cloudflare Zone ID | API operations | No rota (público) | GitHub Secret (por consistencia) |
| `DO_API_TOKEN` | DigitalOcean API token | doctl, snapshot | Trimestral | GitHub Secret |
| `DROPLET_ID` | DO Droplet ID | Snapshot creation | No rota | GitHub Secret |
| `DEPLOY_SSH_PRIVATE_KEY` | ed25519 SSH key | rsync + ssh al droplet | Trimestral | GitHub Secret |
| `DEPLOY_HOST` | droplet IP o hostname | SSH target | Cuando cambie | GitHub Secret |
| `DEPLOY_USER` | `deployer` | SSH user | No rota | GitHub Secret |
| `GITHUB_TOKEN` | Auto-generated por Actions | Push a GHCR | Por job | Auto (no manual) |
| `GHCR_PAT` | Para pull de imágenes externas | Pull | Trimestral | GitHub Secret |
| `SENTRY_DSN` | Sentry ingest DSN | Error reporting | Trimestral | GitHub Secret + VITE_SENTRY_DSN (público, ingest solo) |
| `SLACK_WEBHOOK_URL` | Slack incoming webhook | Alertas | Trimestral | GitHub Secret + /etc/wazuh/integrations/slack |
| `TELEGRAM_BOT_TOKEN` | Telegram bot | Alertas críticas | Trimestral | GitHub Secret |
| `PAGERDUTY_TOKEN` | PagerDuty events API | Paging on-call | Trimestral | GitHub Secret |
| `MAIL_DSN` | SMTP | Alertas email | Trimestral | GitHub Secret |
| `BACKUP_PASSPHRASE` | restic repo passphrase | Cifrado de backups | Anual + on key personnel change | Vault + caja fuerte offline |
| `BACKUP_S3_ACCESS_KEY` | DO Spaces key | restic backend | Trimestral | Vault |
| `BACKUP_S3_SECRET_KEY` | DO Spaces secret | restic backend | Trimestral | Vault |
| `UPTIMEROBOT_API_KEY` | UptimeRobot API | Manage monitors | Anual | Vault |
| `HEALTHCHECKS_API_KEY` | Healthchecks.io API | Cron pings | Anual | Vault |
| `WAF_TOKEN` | Turnstile / hCaptcha secret | Captcha backend | Trimestral | GitHub Secret |
| `OAUTH_GITHUB_SECRET` | GitHub OAuth app | Login social | Anual | GitHub Secret |
| `OAUTH_GOOGLE_SECRET` | Google OAuth | Login social | Anual | GitHub Secret |
| `JWT_SIGNING_KEY` | HS256/RS256 | Custom JWT (si no Sanctum) | Trimestral | GitHub Secret |

> **Decisión [SECURITY]:** todo lo marcado **Vault** vive en HashiCorp Vault o caja fuerte física. Todo lo marcado **GitHub Secret** vive en GitHub Secrets con *environment protection rules*. Ninguno se imprime, se commitea ni se documenta con su valor.

---

## 12. Apéndice B — Referencias bibliográficas

Todas las fuentes oficiales verificadas:

### Laravel 13.x
- [laravel.com/docs/13.x/configuration#debug-mode, 2026-09-22] — `APP_DEBUG`, `APP_ENV`, `APP_URL`.
- [laravel.com/docs/13.x/encryption, 2026-09-22] — AES-256-CBC + MAC, `APP_KEY`, `APP_PREVIOUS_KEYS`.
- [laravel.com/docs/13.x/sanctum, 2026-09-22] — API token authentication, abilities, `auth:sanctum`, CSRF cookie, `personal_access_tokens`.
- [laravel.com/docs/13.x/hashing, 2026-09-22] — bcrypt, argon2id, `Hash::driver`.
- [laravel.com/docs/13.x/eloquent-resources, 2026-09-22] — `JsonApiResource`, `toArray`, relationships.
- [laravel.com/docs/13.x/routing, 2026-09-22] — Rate limiter, `throttle:api`, middleware groups.
- [laravel.com/docs/13.x/requests, 2026-09-22] — `TrustHosts`, `TrustProxies`.
- [laravel.com/docs/13.x/deployment, 2026-09-22] — `composer install --optimize-autoloader`, `config:cache`.
- [laravel.com/docs/13.x/logging, 2026-09-22] — Custom channels, stack driver, Monolog.
- [php.net/manual/en/function.random-bytes.php, 2026-09-22] — CSPRNG.

### Vue 3 / Vite
- [vuejs.org/guide, 2026-09-22] — Composition API, `<script setup>`, reactivity.
- [vitejs.dev/guide/env-and-mode, 2026-09-22] — `VITE_*` prefix, `import.meta.env`.
- [vitejs.dev/config, 2026-09-22] — `build.sourcemap`, `defineConfig`.

### GitHub / CI/CD
- [docs.github.com/en/actions/security-guides/security-harden-deployments, 2026-09-22] — SHA pinning, permissions, OIDC, runner IPs.
- [docs.github.com/en/actions/how-tos/secure-your-work/security-harden-deployments/oidc-in-cloud-providers, 2026-09-22] — `id-token: write`, JWT exchange.
- [docs.github.com/en/code-security/secret-scanning, 2026-09-22] — Secret scanning + push protection.
- [docs.github.com/en/code-security/dependabot, 2026-09-22] — Dependabot configuration.
- [docs.docker.com/dhi, 2026-09-22] — Docker Hardened Images, SBOM, SLSA L3.
- [github.com/anchore/sbom-action, 2026-09-22] — Syft SBOM in Actions.
- [github.com/aquasecurity/trivy-action, 2026-09-22] — Trivy image scan.
- [github.com/zizmorcore/zizmor, 2026-09-22] — zizmor linter.

### Wazuh / IDS
- [documentation.wazuh.com/current, 2026-09-22] — Wazuh manager, agent, FIM, log monitoring.
- [documentation.wazuh.com/current/user-manual/capabilities/file-integrity/how-to-configure-fim.html, 2026-09-22] — `<syscheck>`, `<directories>`, `<ignore>`.
- [documentation.wazuh.com/current/user-manual/reference/ossec-conf/syscheck.html, 2026-09-22] — syscheck directives reference.

### Backup
- [restic.readthedocs.io/en/stable/100_references.html, 2026-09-22] — AES-256-CTR + Poly1305-AES, scrypt KDF.
- [borgbackup.readthedocs.io, 2026-09-22] — borg encryption modes.
- [docs.digitalocean.com/products/spaces, 2026-09-22] — Object Lock, versioning.

### Estándares
- [NIST SP 800-61r3, 2025-04-03] — Computer Security Incident Handling Guide.
- [NIST SP 800-63B, 2025-04-03] — Digital Identity Guidelines (password storage).
- [OWASP API Security Top 10 2023, 2024-01-15] — owasp.org/API-Security/editions/2023.
- [OWASP Top 10 2021, 2021-09-24] — owasp.org/Top10.
- [EUR-Lex 32016R0679, 2016-04-27] — Reglamento General de Protección de Datos (RGPD), Art. 33.
- [CIS Ubuntu Linux 24.04 Benchmark v1.0.0, 2024-08-15] — community.cisco.com/cis-benchmarks.
- [Mozilla SSL Configuration Generator, 2026-09-22] — Mozilla *Modern* profile.
- [cloudflare.com/ips, 2026-09-22] — Cloudflare IP ranges.

### Otros
- [spatie.be/docs/laravel-permission, 2026-09-22] — RBAC roles/permissions.
- [github.com/antonioribeiro/google2fa-laravel, 2026-09-22] — TOTP MFA.
- [github.com/FiloSottile/age, 2026-09-22] — SOPS + age.
- [prometheus.io/docs, 2026-09-22] — Prometheus + exporters.
- [grafana.com/docs, 2026-09-22] — Dashboards.
- [jsonapi.org/format, 2026-09-22] — JSON:API v1.1 spec.
- [iana.org/assignments/media-types/application/vnd.api+json, 2013-07-21] — IANA registration.

---

## 13. Cierre

Este capítulo cierra la pila tecnológica del VPS: desde el cifrado de cookies (`AES-256-CBC + MAC`) hasta la rotación trimestral de la passphrase de restic. Cada control tiene una justificación documentada y una fuente primaria. La intención es que un operador nuevo pueda llegar al sistema y entender **por qué** cada pieza está donde está, no solo **qué** hace.

El siguiente capítulo consolidará la guía completa y producirá el *Executive Summary* y los apéndices de referencia rápida.

