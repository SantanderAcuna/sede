# Informe literal — `docs-security/capitulo-05.md`

> **Alcance:** lectura íntegra de `capitulo-05.md` (3603 líneas) por tramos con `read` (offsets 1, 120, 519, 918, 1317, 1716, 2115, 2514, 2913, 3312) hasta EOF. Índice en `capitulo-05.md:14-31`.
> **Regla de este informe:** solo se reporta lo que el documento dice, con cita `capitulo-05.md:línea`. Donde el capítulo no trata el punto, se escribe **no consta**.

---

## 0. Identificación y encabezado

- Título: *"Capítulo 5 — Capa de Aplicación, CI/CD, IDS, Backups y Respuesta a Incidentes"* — `capitulo-05.md:1`
- **Pila objetivo declarada en el capítulo:** "Laravel 13.x (API JSON:API), Vue 3 + TypeScript (SPA desacoplada), Nginx 1.29, **PHP 8.4-FPM**, **PostgreSQL 16**, Redis 7, Wazuh 4.x, GitHub Actions, DigitalOcean Droplet + Spaces" — `capitulo-05.md:3`
- Convenciones: referencias `[URL, YYYY-MM-DD]`, `[F]` fuente primaria oficial, `[A]` autoritativa secundaria; secretos nunca impresos; acciones GitHub **siempre** pinneadas por SHA-256 de 40 caracteres; marcadores `[ADD] [CHANGE] [REMOVE] [FIX] [SECURITY]` — `capitulo-05.md:5-10`
- Modelo de amenaza: NIST SP 800-61r3 con cuatro fases — *preparation, detection/analysis, containment/eradication/recovery, post-incident activity* — `capitulo-05.md:33`
- Tabla OWASP API Top-10 A01–A10 con mitigación primaria por riesgo — `capitulo-05.md:37-48`
- Índice con 12 entradas (§1–§12) — `capitulo-05.md:16-27`; existe además §0 Prólogo (`:31`) y §13 Cierre (`:3598`), **no listadas en el índice**.

---

## 1. Hardening de Laravel 13

### 1.1 Filosofía (cuatro planos)
Runtime (`php.ini`, FPM pool, `opcache`), framework (`config/*.php`, `bootstrap/app.php`), código (`app/`, middleware, policies), despliegue (Dockerfile, secrets, CI) — `capitulo-05.md:58-63`.

### 1.2 Comandos exactos de instalación/despliegue en producción
`capitulo-05.md:71-86`, literal:

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

- `--optimize-autoloader` genera classmap que evita el `include` dinámico PSR-0/PSR-4; `--classmap-authoritative` prohíbe el *fallback* — acelera arranque de FPM ~30 % `[F: getcomposer.org/doc, 2026-08-12]` — `capitulo-05.md:88`
- **Decisión [SECURITY]:** se prohíbe `--prefer-source` en CI/CD; solo `--prefer-dist` — `capitulo-05.md:90`

### 1.3 Variables de entorno críticas (`/var/www/html/.env`)

Permisos `600`, propietario `deployer:deployer`; `.env.example` commiteado solo con placeholders — `capitulo-05.md:94`. Bloque literal `capitulo-05.md:96-139`:

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

Variables usadas en otros puntos del capítulo y **ausentes** de ese bloque: `REDIS_HOST` (`:2303`, `:2346`), `QUEUE_CONNECTION` (`:2347`), `MAIL_*` (solo aparece `MAIL_DSN` en el inventario de secretos, `:3521`), `CACHE_STORE`, `FILESYSTEM_DISK`, `BROADCAST_*`, `SANCTUM_TOKEN_PREFIX` sí consta (`:127`). **No consta** `LOG_DAILY_DAYS`, `HASH_DRIVER`, `BCRYPT_ROUNDS`, `ARGON_*`, `SESSION_ENCRYPT`, `SESSION_EXPIRE_ON_CLOSE`, `APP_MAINTENANCE_DRIVER`.

- `APP_DEBUG=false` justificado con cita textual de la doc de Laravel — `capitulo-05.md:143`; se valida en arranque con *configuration gate* (`:145`)
- `APP_ENV=production` — `:147-149`
- `APP_URL=https://api.dominio.tld` (el encabezado usa `api.dominio.tld`, el bloque `.env` usa `api.example.tld`) — `:151-159`

### 1.4 `APP_KEY`: generación, almacenamiento y rotación
- Cifrado `AES-256-CBC` + MAC `HMAC-SHA256`; la clave maestra (32 bytes base64) "se deriva con PBKDF2/scrypt interno" y cifra cookies, sesiones, *signed URLs* y `Crypt::encryptString()` — `capitulo-05.md:163`
- Generación (literal) — `:167-171`:
  ```bash
  # NUNCA pegar manualmente una clave; SIEMPRE usar key:generate
  php artisan key:generate --show
  # → base64:XXXXXXX...    (no la imprimimos en logs)
  ```
  `key:generate` "will use PHP's secure random bytes generator…" `[F: laravel.com/docs/13.x/encryption, 2026-09-22]`; `random_bytes()` mapea a `getrandom(2)` `[F: php.net]` — `:173`
- Rotación con `APP_PREVIOUS_KEYS` (procedimiento numerado exacto) — `:179-189`:
  1. Generar `NEW_KEY = php artisan key:generate --show`
  2. En `.env`: `APP_KEY=base64:NEW_KEY` / `APP_PREVIOUS_KEYS=base64:OLD_KEY`
  3. Desplegar (Laravel cifra con nueva, descifra legacy con vieja)
  4. Esperar al menos una ventana de retención (**90 días recomendado**) y vaciar `APP_PREVIOUS_KEYS`
  5. Invalidar sesiones y tokens: `DELETE FROM personal_access_tokens;`
- Almacenamiento permitido/prohibido — tabla `:193-200`: `.env` en servidor ✅ (600, owner `deployer`); GitHub Actions Secrets ✅ (rotación trimestral); Código fuente ❌; `.env.example` ❌ (solo `APP_KEY=` vacío); Logs/tickets/Slack ❌; **DO App Platform env vars ✅**

### 1.5 Cookies y sesión
Sesión sobre Redis con TTL 120 min — `:204`. Tabla `:206-211`: `SESSION_SECURE_COOKIE=true`; `SESSION_HTTP_ONLY=true`; `SESSION_SAME_SITE=lax` ("Si la API no usa GET con side-effects… considerar `strict`"); `SESSION_DOMAIN=.example.tld`. **Decisión:** `SESSION_DRIVER=redis` y nunca `file` — `:213`.

### 1.6 `TRUSTED_PROXIES` y riesgo de *header forgery*
- Si se deja vacío, Laravel ignora todos los headers → URLs `http://` y CSRF mismatch — `:217`
- Riesgo: atacante que alcance el puerto 80/443 saltándose Cloudflare inyecta `X-Forwarded-Proto: https` y `X-Forwarded-For: 127.0.0.1` — `:221`
- Mitigación: (1) listar proxies explícitamente en `bootstrap/app.php`; (2) Cloudflare Authenticated Origin Pulls (Cap. 3 §4); (3) usar `Request::ip()` solo si la IP está en `TRUSTED_PROXIES` — `:225-227`
- Bloque literal con 22 rangos de Cloudflare + `10.0.0.0/8` para DO LB — `:229-261`
- **Decisión [SECURITY]:** "**NO** usar `TRUSTED_PROXIES=*` (es la configuración 'rápida' pero rompe toda la protección contra *header forgery*)" — `:263`
- **Con Cloudflare delante, el valor operativo que ordena el capítulo es la lista explícita de IPs de Cloudflare + DO LB** (`:234-259`); el `TRUSTED_PROXIES=*` del bloque `.env` (`:130`) queda contradicho por `:263`.

### 1.7 `TrustHosts`
Bloque `:269-277` con `$middleware->trustHosts(at: ['api.example.tld','app.example.tld'])`; devuelve 400 ante `Host: evil.example.com` — `:267-279`.

### 1.8 CORS estricto — literal `:283-298`
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
- `allowed_origins_patterns` vacío (regex de origen = bypass clásico); si se necesita wildcard: `^https://[a-z0-9-]+\.example\.tld$` — `:301`
- `allowed_headers` excluye `X-Requested-With` — `:302`
- `max_age=600` (10 min) — `:303`

### 1.9 CSRF: ruta web stateless vs API token
- `VerifyCsrfToken` global sobre rutas web; `routes/api.php` se monta en el grupo `api` que excluye ese middleware por defecto (`'api' => ['throttle:api', SubstituteBindings::class]`) — `:307`
- **Rutas web (Blade):** CSRF activo, Sanctum *stateful* con cookie. **Rutas API (Vue SPA + mobile):** token `Authorization: Bearer …`, sin CSRF — `:309-310`
- Ejemplo literal de `routes/api.php` con `auth:sanctum` + `ability:read:posts` / `ability:write:posts` — `:312-321`; `ability` (no `scope`) es el middleware nativo de Sanctum 13.x — `:323`

### 1.10 Rate limiting
Los *rate limiters* se declaran en `app/Providers/AppServiceProvider.php` (no en `routes/api.php`; en los ejemplos de `routes/api.php` del capítulo no aparece ningún `throttle:` explícito). Literal `:329-351`:

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
Números: `api` 120/min por usuario autenticado, 30/min por IP si anónimo; `login` 5/min por `email|ip` + 20/min por IP; `export` 2/min por usuario o IP — `:337-349`. El capítulo afirma que Laravel 13 "incluye dos *rate limiters* por defecto (`'api'` y `'login'`)" — `:327`.

### 1.11 Hashing: bcrypt vs argon2id
- Laravel 13 soporta `bcrypt`, `argon2i`, `argon2id` vía `Hash::driver('argon2id')`; NIST SP 800-63B §5.1.1.2 recomienda KDFs *memory-hard*; `argon2id` gana PHC 2015 — `:357`
- Config literal `:359-368`:
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
- **Recomendación:** `argon2id` por defecto; `bcrypt` solo *fallback* legacy con *rehash-on-login*; `memory=64 MiB`; `verify=true` reduce ~30 % throughput — `:372-374`

### 1.12 Mass assignment — regla exacta
"`$fillable` estricto, *sin* `$guarded`". La práctica `$guarded = []` "la prohibimos" — `:376-378`. Literal `:380-393`:

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
Campos sensibles (`role`, `is_admin`, `email_verified_at`) con método explícito tras `Gate::authorize('users.change-role', $user)` y log `Log::channel('security')->warning('role.changed', …)` — `:395-409`.

### 1.13 RBAC: Gates + Policies + Spatie Permission
Stack — `:416-418`: **Gates** (`AuthServiceProvider`) para decisiones globales (`view-dashboard-admin`, `impersonate`); **Policies** (`php artisan make:policy PostPolicy`) para CRUD por modelo; **`spatie/laravel-permission` v6.x** para roles y permissions en BD.

`PostPolicy` literal — `:420-433` (`viewAny` → `$user->can('read:posts')`; `update` → `$user->can('write:posts') && ($user->id === $post->user_id || $user->hasRole('admin'))`).

`Gate::before` — `:438-442`:
```php
Gate::before(function (User $user, string $ability) {
    return $user->hasRole('super-admin') ? true : null;
});
```
Reglas — `:444-446`: (1) `Gate::before` solo para `super-admin`, único y registrado en código (no en BD); (2) **Spatie Permission no se usa para abilities de Sanctum** (abilities = scopes sobre token; permissions Spatie = roles sobre usuario; mezclar = bugs).

### 1.14 Logging de seguridad — canal dedicado
Canal `security` que escribe a `/var/log/laravel/security.log` en JSON y hace *forward* a Wazuh vía syslog — `:450`. Config literal `:452-475`:

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
Helper `app/Support/SecurityLog.php` con redacción de `['password','passwd','token','authorization','api_key','app_key']` → `[REDACTED]` — `:479-497`.

Eventos obligatorios (tabla `:500-518`): `auth.login.success` info; `auth.login.failed` warning; `auth.logout` info; `auth.2fa.challenge` info; `auth.2fa.failed` warning; `token.issued` info; `token.revoked` info; `password.changed` warning; `password.reset.requested` info; `role.changed` warning; `permission.granted` warning; `rbac.denied` warning; `file.upload` info; `export.requested` info; `rate_limit.exceeded` warning.

### 1.15 Verificación de configuración al arranque
Literal `:522-538`:

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
"Esto se ejecuta **una sola vez** durante `config:cache`, fallando rápido en el primer *build* del pipeline" — `:540`.

---

## 2. Resto de secciones (§2–§13)

### §2 Sanctum y autenticación API (`:544-845`)
- **2.1 abilities, no scopes** (`:546-614`): cita de doc; emisión de tokens literal en `TokenController::issue` con `Gate::authorize('tokens.issue')` y validación `'abilities.*' => 'string|in:read:posts,write:posts,read:users,admin:*'`, `'expires_at' => 'nullable|date|after:now'` — `:554-586`. Reglas: *plain text* se devuelve UNA vez (columna guarda hash SHA-256); `expires_at` por token; `name` único `cli-` + 8 hex — `:589-592`. Middleware: `ability:read:posts`, `ability:write:posts`, `ability:admin:*` — `:596-608`. Wildcards solo al final y solo para admin; test de regresión `AuthAbilitiesTest` verifica 403 con `[]` — `:612-614`
- **2.2 hashing de tokens** (`:616-640`): migración `personal_access_tokens` con `token VARCHAR(64) NOT NULL UNIQUE` comentado `-- SHA-256 hash`; índice `(tokenable_type, tokenable_id)`; decisión de añadir índice único `(name, tokenable_id)` y *trigger* Postgres que rechaza `expires_at < created_at` (documentado en Cap. 4 §6) — `:622-640`
- **2.3 `last_used_at` y rotación** (`:642-671`): comando `tokens:prune {--days=90}`; cron `$schedule->command('tokens:prune --days=90')->dailyAt('03:17')->onOneServer();` en `app/Console\Kernel.php` — `:649-669`
- **2.4 refresh tokens** (`:673-714`): access TTL **15 min**, refresh TTL **30 días** con ability `refresh:token` y flag `refreshable=true`; rotación obligatoria; `expires_in => 900`; refresh token solo en cookie `httpOnly`, `Secure`, `SameSite=Strict` en SPA; mobile en Keychain / EncryptedSharedPreferences — `:677-714`
- **2.5 SPA stateful** (`:716-750`): `GET /sanctum/csrf-cookie` → cookie `XSRF-TOKEN` cifrada con `APP_KEY`; Axios `withCredentials: true` + `X-XSRF-TOKEN`; `config/sanctum.php` `'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS',''))`; guard `api` driver `sanctum` — `:720-733`
- **2.6 MFA TOTP** (`:752-818`): `composer require pragmarx/google2fa-laravel` + `vendor:publish`; `enable()` con `encrypt($secret)`, 8 recovery codes `bin2hex(random_bytes(5))`, `getQRCodeInline`; `verify()` con `'code' => 'required|digits:6'`; config forzada `'algorithm' => 'SHA256'`, `'period' => 30`, `'window' => 1` — `:754-818`
- **2.7 Logout** (`:820-845`): `currentAccessToken()->delete()` y `tokens()->delete()` (logoutAll); en runbook `logoutAll` es la acción inmediata ante sospecha de compromiso — `:845`

### §3 JSON:API en Laravel 13 (`:849-1006`)
- Soporte nativo vía `php artisan make:resource --json-api` generando clase que extiende `Illuminate\Http\Resources\JsonApi\JsonApiResource` — `:851`
- **3.1 Top-level** (`:855-877`): ejemplo con `jsonapi.version = "1.1"`, `links.self`, `data.type/id/attributes/relationships`; atributos en kebab (`created-at`). Decisiones: sin `success` ni `message` en top-level; `errors` y `data` mutuamente excluyentes; `Content-Type: application/vnd.api+json` en toda respuesta, incluido 204 — `:879-882`
- **3.2 Resource tipado** (`:886-914`): `#[UseResourceAttributes]`, `toArray`, `toRelationships`, `toLinks` con `self => route('api.posts.show', …)`
- **3.3 Errores con `source.pointer`** (`:919-960`): `app/Exceptions/Handler.php` con `render()` que devuelve `new JsonApiErrorResponse($e)`; mapa de status 422/401/403/404/`HttpException`/500; payload `errors[0]` con `status`, `code` (`class_basename($e)`), `title`, `detail`, `source.pointer = '/data/attributes/'.array_key_first($e->errors())`; decisiones: `detail` no expone stack en producción, errores 5xx se loguean completos en canal `security` — `:963-966`
- **3.4 Validación de query params** (`:968-1006`): `include/fields/sort/filter/page` "son propios de JSON:API y Laravel 13 no los valida *out-of-the-box*"; middleware `ValidateJsonApiQuery` con `ALLOWED_INCLUDES = ['author','comments','tags']`, `ALLOWED_SORTS = ['created-at','-created-at','title']`, `MAX_PAGE_SIZE = 100`; lista blanca de `include` como contramedida a fuga de datos (A03) — `:970-1006`
- **Paginación:** no consta más allá de `page.size` max 100 (`:986`) y del rechazo de `page.size` superior (`:996-999`). No hay `page[number]`, `page[offset]`, links de paginación ni `meta` de paginación. **No consta.**
- **Shape de errores JSON:API:** §3.3 (`:943-954`). **`meta`, `included`, `links.first/last/prev/next`: no consta.**

### §4 Endurecimiento de Vue 3 + TypeScript (`:1010-1199`)
- **4.1 Vite** (`:1016-1044`): `target: 'es2022'`, `sourcemap: false` (⚠️ CRÍTICO), `minify: 'esbuild'`, `cssMinify: 'lightningcss'`, `manualChunks` vendor/query/utils, `server: { host: '127.0.0.1' }`, `esbuild: { legalComments: 'none' }`, plugin `compression({ algorithm: 'gzip', threshold: 1024 })`
- **4.2 `VITE_*`** (`:1056-1061`): `VITE_API_URL=https://api.example.tld`; `VITE_SENTRY_DSN=` (público); `VITE_BUILD_ID=$CI_COMMIT_SHA`; `VITE_GA_MEASUREMENT_ID=G-XXXX`. **PROHIBIDO** prefijar secretos con `VITE_` — `:1065`
- **4.3 Saneamiento HTML** (`:1071-1102`): `<div v-html="user.bio">` ❌; DOMPurify con `ALLOWED_TAGS: ['p','br','strong','em','a','ul','ol','li']` / `ALLOWED_ATTR: ['href','target','rel']`; hook `afterSanitizeAttributes` fuerza `target="_blank"` y `rel="noopener noreferrer"`; DOMPurify "se construye con JSDOM en CI (no en runtime del navegador), ahorrando ~80 KiB" — `:1088`
- **4.4 SRI** (`:1105-1118`): ejemplo `integrity="sha384-..." crossorigin="anonymous" referrerpolicy="no-referrer"`; si Vue se construye con Vite, SRI no aplica
- **4.5 TanStack Query** (`:1122-1139`): nunca reintentar 4xx (`return false` para 400–499), `retryDelay: Math.min(1000 * 2 ** attempt, 8000)`, `staleTime: 30_000`, `gcTime: 5 * 60_000`, `refetchOnWindowFocus: false`
- **4.6 Axios** (`:1148-1171`): `withCredentials: true`, `xsrfCookieName: 'XSRF-TOKEN'`, `xsrfHeaderName: 'X-XSRF-TOKEN'`, `timeout: 15_000`, headers `X-Requested-With: XMLHttpRequest` y `Accept: application/vnd.api+json`
- **4.7 Headers de build** (`:1174-1181`): el frontend no emite; los pone Nginx (Cap. 3 §5). `COEP: require-corp` y `COOP: same-origin`; ningún iframe a otro origen sin `sandbox` + `referrerpolicy="no-referrer"`
- **4.8 Pinia** (`:1185-1196`): tokens en memoria, **nunca** `localStorage`; persistir solo `user`
- **CSP nonce:** **no consta** en el capítulo (solo menciones a "CSP del Capítulo 3 §5" y a CSPRNG `:1048`, `:1118`, `:3550`)

### §5 CI/CD — ver sección 4 de este informe (detalle completo)

### §6 Containerización — ver sección 3 de este informe (detalle literal)

### §7 Detección de intrusos y monitoreo (`:2483-2959`)
- **7.1 Wazuh agent** (`:2489-2497`): instalación por `apt` con `curl … GPG-KEY-WAZUH | gpg --dearmor -o /etc/apt/keyrings/wazuh.gpg`, repo `https://packages.wazuh.com/4.x/apt/ stable main`, `WAZUH_MANAGER="wazuh.example.tld" apt-get install -y wazuh-agent`, `systemctl enable --now wazuh-agent`
- **7.2 `ossec.conf`** (`:2502-2609`): `client_buffer` queue 5000 / 500 eps; `<syslog><format>cef</format>`; `<syscheck>` con `frequency 21600` (6 h), `scan_on_start`, `alert_new_files`, `auto_ignore frequency="10" timeframe="3600"`, directorios con `whodata="yes"` en `/etc/nginx` (recursion 5) y `/etc/ssh` (recursion 3), `check_all` en `/var/www/html/app` (3), `/var/www/html/config` (2), `/etc/php/8.4` (2); `nodiff` en `/etc/ssl/private`, `/etc/shadow`, `\.key$`; `ignore` para `/storage/logs/*.log`, `/storage/framework/sessions/*`, `/bootstrap/cache/*.php`; `skip_nfs/dev/proc/sys`; `file_limit 200000`; `synchronization` 5m/10 eps. `<localfile>` json en `/var/www/html/storage/logs/security.log` con label `laravel-security`; syslog de `/var/log/auth.log`, `/var/log/nginx/access.log`, `/var/log/nginx/error.log`; audit en `/var/log/audit/audit.log`. `<active-response>` `firewall-drop` local, `rules_id 5710,5720,5730`, `timeout 600`
- **7.3 Reglas custom** (`:2622-2686`): IDs 100001 (login fallido, level 7), 100002 (level 12, frequency 5, timeframe 600, `same_field json.email`), 100003 (role.changed, level 10), 100004 (rbac.denied, level 8), 100005 (level 6, freq 10, 60 s, `if_matched_sid 100006`), 100006 (rate_limit.exceeded, level 3), 100007 (`file.upload.suspicious`, level 12), 100008 (`token.issued` con `field name="json.abilities"` = `admin:*`, level 10), 100009 (`mass_assignment.rejected`, level 12). IDs ≥ 100000 son el rango custom — `:2688`
- **7.4 Alertas** (`:2694-2723`): integración Slack con `<hook_url><URL_DEL_WEBHOOK_DE_SLACK></hook_url>`, `<level>10</level>`, `<alert_format>{json}</alert_format>`; script custom Telegram `/var/ossec/integrations/custom-telegram` que redacta `(token|password|key)=[^&]*`
- **7.5 auditd → Wazuh** (`:2730-2782`): `-b 8192`, `-f 1`, `--backlog_wait_time 60000`; watches sobre fail2ban.log, pam, nsswitch, sshd_config, cron, sudoers, `/usr/bin/`, `/usr/sbin/`, `/usr/local/bin/`, `/bin/`, `/sbin/`, `/etc/docker`, `/usr/bin/docker`, `/var/run/docker.sock`; plug `audisp-remote` con `remote_server = wazuh.example.tld`, `port = 1514`, `transport = tcp`
- **7.6 Prometheus** (`:2789-2845`): `node_exporter` 1.8.2 en `/opt/`, usuario `node_exporter`, `--web.listen-address=127.0.0.1:9100`, unidad systemd con `Restart=on-failure`; `prometheus.yml` con `scrape_interval: 15s`, `evaluation_interval: 30s`, jobs `droplet-app:9100`, `postgres:9187`, `redis:9121` (`job_job` — ver §6 defectos), `nginx:9113`, `php-fpm:9253`; los exporters escuchan en `127.0.0.1` y se scrapean vía túnel SSH o VPC — `:2845`
- **7.7 Grafana** (`:2849-2859`): 7 paneles mínimos (host overview; disk & inodes `/var` no >80 %; Postgres connections max 100, transactions/s, replication lag; Redis memory used/evicted/ops; Nginx requests/s por status, top IPs/URLs; PHP-FPM active workers, queue length, slow requests >5 s; Wazuh top 10 reglas 24 h + geolocalización SSH); JSON en `artifacts/grafana/dashboard.json` "(no incluido por brevedad, ver Apéndice A)"
- **7.8 Uptime** (`:2863-2865`): UptimeRobot free tier cada 5 min sobre `https://api.example.tld/health`, alerta si 2 checks fallan; Healthchecks.io para cron jobs con UUID en `/api/cron/ping/...`; Sentry frontend y backend
- **7.9 Fail2ban** (`:2931-2959`): filtro `nginx-login` con failregex `^<HOST> .* "(GET|POST) /api/login HTTP/\d+\.\d+" 401` y `/sanctum/csrf-cookie … 429`; jail `api-login` `findtime 600`, `maxretry 10`, `bantime 3600`, `action iptables-multiport`; jail `sshd` `maxretry 5`, `findtime 600`, `bantime 3600`; Wazuh lee `/var/log/fail2ban.log` y alerta nivel 6 cuando una IP es baneada — `:2959`
- **Diagrama 2 — Arquitectura de monitoreo** — `:2869-2927`

### §8 Backups cifrados (`:2963-3143`)
- **8.1 Política 3-2-1-1-0** (`:2967-2973`): 3 copias; 2 medios; 1 offsite (DO Spaces en región distinta); 1 inmutable/offline ("Snapshots DO marcados `immutable=true`"); 0 errores verificados (`restic check` semanal sin errores)
- **8.2 restic a DO Spaces** (`:2979-3069`): `openssl rand -base64 32 > /etc/restic/passphrase` (chmod 0400, root:root); `/etc/restic/restic.env` con `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `RESTIC_REPOSITORY=s3:s3.nyc3.digitaloceanspaces.com/example-prod-backups`, `RESTIC_PASSWORD_FILE=/etc/restic/passphrase` (chmod 0400); `restic -E /etc/restic/restic.env init`. Cifra **AES-256-CTR + Poly1305-AES**, clave maestra con **scrypt** `[F: restic.readthedocs.io]` — `:2997`. Script `/opt/backup/run.sh` con pre-flight `restic check --read-data-subset=5%`, `restic backup --tag "$TAG" --exclude-file=/etc/restic/excludes.txt --exclude-caches --one-file-system` sobre `/etc`, `/var/www/html`, `/var/lib/restic-keys`, `/etc/ossec`, `/etc/audit`, `/root/.ssh`, `/var/log`; `restic forget --keep-daily 7 --keep-weekly 4 --keep-monthly 6 --prune`. `excludes.txt` con 9 patrones. Cron: `17 2 * * * root /opt/backup/run.sh` (02:17 UTC, distinto de los jobs del Cap. 4) — `:3069`
- **8.3 Snapshot DO inmutable** (`:3073-3079`): `doctl compute snapshot create droplet-app-prod --tag-name "release" --description "Weekly immutable snapshot"`; inmutabilidad real vía Spaces versioning + Object Lock modo **Compliance**, lock mínimo **30 días**; DO Spaces soporta Object Lock desde 2023
- **8.4 borgbackup opcional** (`:3085-3090`): `borg init --encryption=repokey-blake2 /srv/borg/example-prod`; `repokey-blake2` usa AES-256-CTR + HMAC-SHA256 (BLAKE2b)
- **8.5 Drill semanal** (`:3094-3132`): `/opt/backup/drill.sh`, cron `17 4 * * 0` (domingo 04:17 UTC); `restic check --read-data-subset=10%`; `restic restore latest` con `--include /etc/nginx`, `/var/www/html/.env.example`, `/var/www/html/config/app.php`; `diff -r /etc/nginx "$DRILL_DIR/etc/nginx"`; métricas por `logger`; resultado a Slack `#backups` con `curl`
- **8.6 Dónde vive la passphrase** (`:3136-3143`): tabla con 4 opciones (chmod 0400 media/alta; Vault Transit alta/media; SOPS+age alta/media; dropbear initramfs muy alta/baja). "Recomendación del proyecto: **Vault con Transit + unseal keys en 3 personas** … + copia offline en papel en caja fuerte. La passphrase de restic nunca se almacena en GitHub."

### §9 Runbook — ver sección 5 de este informe (detalle completo)

### §10 Política de secretos (`:3399-3496`)
- **10.1 Principios** (`:3401-3415`): 1 no secrets en código (`.env` gitignored); 2 no secrets en logs (Monolog + `::add-mask::` + `SecurityLog`); 3 no secrets en tickets/Slack/email (incumplimiento = acción disciplinaria); 4 permisos `600`, owner `deployer:deployer` o `root:root`; 5 rotación trimestral de APP_KEY (con `APP_PREVIOUS_KEYS` por 90 días), DB passwords, Cloudflare API token, DO API token, deploy keys SSH ed25519, GitHub PATs; 6 secret scanning + push protection; 7 `gitleaks detect --no-git --redact` mensual retroactivo
- **10.2 Almacenamiento por entorno** (`:3419-3425`): Dev local `.env` gitignored (+ git-crypt opcional); CI/CD GitHub Actions Secrets + Environments; Staging Docker secrets en `secrets/staging/` chmod 600; Producción Docker secrets + SOPS en backup vault; DR Vault + copia offline en caja fuerte (2 personas con 2FA)
- **10.3**: remite a Apéndice A — `:3429`
- **10.4 SOPS + age** (`:3435-3448`): `age-keygen -o keys/dev.age`; `sops --age "$AGE_PUBKEY" --encrypt --in-place secrets/db.yaml`; `export SOPS_AGE_KEY_FILE`; `sops --decrypt secrets/db.yaml | kubectl apply -f -`. Age = X25519 + ChaCha20-Poly1305 + HKDF-SHA-256 + scrypt
- **10.5 HashiCorp Vault** (`:3452-3479`): política `policies/api.hcl` (`secret/data/app/*` read; `auth/token/renew-self` y `revoke-self` update); `vault agent -config=/etc/vault/agent.hcl` escribe en `/run/secrets/`. Ventajas: credenciales dinámicas DB con TTL **1 h**, audit trail, secrets engines AWS/GCP/Azure. Desventajas: coste operativo, SPOF. **Decisión:** "para una VPS única, **SOPS + age + cifrado en Git** es suficiente. Vault se justifica con >3 servicios o >5 secretos dinámicos"
- **10.6 Checklist trimestral** (`:3483-3496`): 12 tareas con frecuencia y responsable (rotaciones trimestrales; auditar `personal_access_tokens` mensual/Tech Lead; revisar logs gitleaks semanal; `restic check --read-data-subset=10%` semanal automático; fail2ban ban count >0 diario; top 10 reglas Wazuh semanal/IC; post-mortem post-incidente; pentest externo anual)

### §11 Apéndice A — Inventario de secretos (`:3500-3532`)
Tabla de 27 secretos con nombre, tipo, uso, rotación y almacenamiento: `APP_KEY`, `DB_PASSWORD`, `DB_PASSWORD_TEST`, `REDIS_PASSWORD`, `CLOUDFLARE_API_TOKEN`, `CLOUDFLARE_ZONE_ID` (no rota), `DO_API_TOKEN`, `DROPLET_ID` (no rota), `DEPLOY_SSH_PRIVATE_KEY`, `DEPLOY_HOST`, `DEPLOY_USER`, `GITHUB_TOKEN` (por job), `GHCR_PAT`, `SENTRY_DSN`, `SLACK_WEBHOOK_URL`, `TELEGRAM_BOT_TOKEN`, `PAGERDUTY_TOKEN`, `MAIL_DSN`, `BACKUP_PASSPHRASE` (anual + cambio de personal), `BACKUP_S3_ACCESS_KEY`, `BACKUP_S3_SECRET_KEY`, `UPTIMEROBOT_API_KEY` (anual), `HEALTHCHECKS_API_KEY` (anual), `WAF_TOKEN`, `OAUTH_GITHUB_SECRET` (anual), `OAUTH_GOOGLE_SECRET` (anual), `JWT_SIGNING_KEY`. Rotación por defecto: trimestral. Almacenamiento: GitHub Secret, `/run/secrets/*`, Vault o caja fuerte — `:3502-3532`.
> El capítulo llama a esto "Apéndice A — Inventario de secretos" (`:3500`), mientras en el árbol del repositorio de trabajo el archivo `docs-security/apendice-A.md` se titula "Apéndice A — Scripts completos". Divergencia de contenido entre documentos (ver §6).

### §12 Apéndice B — Referencias bibliográficas (`:3536-3594`)
Seis bloques: Laravel 13.x (10 refs), Vue 3/Vite (3), GitHub/CI/CD (8), Wazuh/IDS (3), Backup (3), Estándares (8), Otros (7). Estándares citados: NIST SP 800-61r3 `[2025-04-03]`, NIST SP 800-63B, OWASP API Top 10 2023 `[2024-01-15]`, OWASP Top 10 2021, EUR-Lex 32016R0679 (RGPD Art. 33) `[2016-04-27]`, CIS Ubuntu Linux 24.04 Benchmark v1.0.0 `[2024-08-15]`, Mozilla SSL Configuration Generator, cloudflare.com/ips — `:3578-3585`.

### §13 Cierre (`:3598-3602`)
Cierra la pila del VPS "desde el cifrado de cookies (`AES-256-CBC + MAC`) hasta la rotación trimestral de la passphrase de restic". El siguiente capítulo consolidará la guía completa con *Executive Summary* y apéndices de referencia rápida.

### Temas pedidos que **no constan** en el capítulo
| Tema pedido | Estado |
|---|---|
| Colas/Horizon | Horizon **no consta**. Solo `queue:work --tries=3 --max-time=3600 --memory=256` (`:2333`) y `QUEUE_CONNECTION: redis` (`:2347`) |
| WebSockets/Reverb | **no consta** (ninguna aparición de Reverb, websocket, broadcast) |
| CSP con nonce | **no consta** (solo referencias a "CSP del Capítulo 3 §5") |
| Paginación (shape/links/meta) | **no consta** salvo `page.size` ≤ 100 (`:986-999`) |
| `included` / `meta` / links de paginación JSON:API | **no consta** |
| Almacenamiento abstracto (disks, S3, `FILESYSTEM_DISK`) | **no consta** en `.env`; solo `storeAs('uploads', Str::uuid(), 'private')` (`:3297`) |
| Octane | **no consta** |
| MinIO | **no consta** |
| Redis como cache de aplicación (`CACHE_STORE`) | **no consta** en `.env`; solo `restic`-cache y locks de `onOneServer()` (`:671`) |
| Blue/green o canary deploy | **no consta** |
| Matriz de aprobaciones de `required_reviewers` | **no consta** el YAML; solo el principio y `environment: production` |

---

## 3. Docker (reproducción literal)

### 3.1 Imagen base — DHI
`capitulo-05.md:1920-1927`:

> Recomendamos **`dhi.io/php:8.4-fpm-alpine3.22`** o **`dhi.io/php:8.4-cli-alpine3.22`** sobre la imagen *vanilla* `php:8.4-fpm-alpine`. Las DHI son:
> - **Sin shell** (`/bin/sh` no existe → reduce superficie de RCE).
> - **No-root** (`USER 65532`).
> - **SBOM + SLSA L3 + cosign sign** incluidos.
> - **CVE baseline ~0** actualizado semanalmente `[F: docs.docker.com/dhi, 2026-09-22]`.
>
> Para fallback, usar **`cgr.dev/chainguard/php-fpm:latest`** (Chainguard), equivalentes en garantías.

### 3.2 Dockerfile multi-stage endurecido — literal `capitulo-05.md:1931-2048`

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

Decisiones `:2051-2055`: (1) `tini` como PID 1 (PHP-FPM no reapa zombies), "es 30 KiB"; (2) **`USER 1000:1000`**; (3) healthcheck con fcgi client en vez de `curl`; (4) `APP_DEBUG=false` en ENV "no se puede cambiar sin rebuild".

### 3.3 `php.ini` endurecido — literal `capitulo-05.md:2059-2096`

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

### 3.4 FPM pool — literal `capitulo-05.md:2100-2131`

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
Decisiones `:2133-2136`: `disable_functions` bloquea `exec`, `shell_exec`, etc.; `open_basedir` limita el filesystem a app + `/tmp`; `pm.max_requests = 1000` recicla workers.

### 3.5 Entrypoint con Docker secrets — literal `capitulo-05.md:2140-2180`

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

### 3.6 Healthcheck — literal `capitulo-05.md:2184-2196`

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
Requisitos del endpoint `/api/health` `:2198-2202`: 1) responder 200 con `{"data":{"type":"health","attributes":{"status":"ok"}}}`; 2) NO requerir auth; 3) NO escribir a BD; 4) excluido del rate-limiter (whitelist en `RouteServiceProvider`).

### 3.7 `.dockerignore` — literal `capitulo-05.md:2206-2235`

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

### 3.8 `docker-compose.yml` endurecido — literal `capitulo-05.md:2239-2471`

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

Decisiones `:2473-2479`: (1) `POSTGRES_PASSWORD_FILE` sin exponer en `docker inspect`; (2) `read_only: true` en api/worker con `tmpfs` para `/tmp` y `storage/framework/cache`; (3) `cap_drop: [ALL]` + `no-new-privileges:true` en todos los servicios, solo `NET_BIND_SERVICE` donde se escucha en <1024 (Nginx); (4) *seccomp profiles* personalizados "se incluyen en `docker/seccomp/`"; (5) `127.0.0.1:8080:8080` bindea Nginx solo en loopback — "Cloudflare Tunnel (§7) llega a `127.0.0.1:8080` localmente"; (6) "No hay `volumes: ["./:/var/www/html"]` en producción — el código entra por imagen".

### 3.9 Resumen de lo exigido por §6
- **Imágenes base:** `dhi.io/php:8.4-fpm-alpine3.22` / `dhi.io/php:8.4-cli-alpine3.22` (`:1920`), fallback `cgr.dev/chainguard/php-fpm:latest` (`:1927`), composer `dhi.io/composer:2.7` (`:1946`), runtime `apk`: `ca-certificates=20241121-r1`, `tini=0.19.0-r3`, `fcgi=2024.05.18-r0`, `tzdata=2025b-r0` (`:1995-1998`); compose: `postgres:16-alpine`, `redis:7-alpine`, `nginx:1.29-alpine`
- **Usuario no root / UID:** §6.1 dice `USER 65532` (`:1923`); §6.2 usa `ARG UID=1000` / `ARG GID=1000` / `USER ${UID}:${GID}` (`:1990-1991`, `:2028`) y la decisión dice "**`USER 1000:1000`**" (`:2053`)
- **`read_only`:** `api: true` (`:2295`), `worker: true` (`:2339`), `nginx: true` (`:2451`); `scheduler` sin `read_only`; ancla `x-security-opts` declara `read_only: false` (`:2256`)
- **`cap_drop`:** `[ALL]` en api, worker, scheduler, postgres, redis, nginx (`:2294, 2338, 2366, 2387, 2417, 2449`); `cap_add` solo `CHOWN/SETUID/SETGID/DAC_OVERRIDE` en postgres (`:2388`) y `CHOWN/SETUID/SETGID/NET_BIND_SERVICE` en nginx (`:2450`); el ancla declara `cap_add: NET_BIND_SERVICE` (`:2255`)
- **Límites:** api `cpus 2.0 / memory 512M`, reservas `0.5 / 256M` (`:2320-2327`); worker `1.5 / 384M` (`:2358`); postgres `2.0 / 1G` (`:2408`); redis `0.5 / 320M` (`:2440`); **scheduler sin límites**; **nginx sin límites**; **no hay límite de `pids` en ningún servicio** (`pids_limit`/`--pids-limit`: **no consta**)
- **Healthchecks:** Dockerfile `HEALTHCHECK --interval=30s --timeout=3s --start-period=20s --retries=3` (`:2045-2046`); compose api 30/3/3/20 s (`:2314-2319`); postgres `pg_isready` 10/3/5 (`:2401-2405`); redis `redis-cli ping` 10/3/5 (`:2433-2437`); nginx `wget --spider http://127.0.0.1:8080/health` 30/3/3 (`:2466-2470`); worker y scheduler **sin healthcheck**
- **Multi-stage:** dos stages, `builder` y `runtime`, ambos sobre la misma imagen DHI (`:1939`, `:1979`); el builder se descarta
- **Regla de que la imagen final no conserve herramientas de construcción:** **enunciada en el comentario del Dockerfile** ("Stage 1: builder (instala dependencias, se descarta en final)" — `:1937`) y por el uso de `COPY --from=builder` (`:2004`). **No hay ninguna afirmación literal del tipo "la imagen final no conserva herramientas de construcción" ni un `RUN apk del …` / limpieza de `build-base`.** En el stage `runtime` sí se ejecuta `apk add` (`:1994`), es decir, el runtime conserva `apk` y shell si la base los tuviera. **Parcialmente consta.**

---

## 4. CI/CD (GitHub Actions)

### 4.1 Principios generales — `capitulo-05.md:1207-1217`
| Principio | Implementación (literal) |
|---|---|
| Pinning por SHA | Todas las `actions/*` y `third-party/*` con SHA-256 de 40 chars + comentario de versión |
| Permisos mínimos | `permissions: contents: read` por workflow; override por job si hace falta |
| OIDC sin secretos | `permissions: id-token: write` + action del cloud provider |
| Secret scoping | Usar *environments* (`production`, `staging`) con `required_reviewers` |
| Sin secretos en logs | `set -euo pipefail` + `::add-mask::` en scripts |
| Sin `pull_request_target` | Riesgo de *pwn request*; solo usar `pull_request` |
| actionlint + zizmor | Validación de YAML y patrones peligrosos |
| gitleaks | Detección de secretos en el código previo a push |
| Trivy + Grype + Syft | Escaneo de imágenes + SBOM en cada build |

### 4.2 Estructura del repositorio — `:1221-1232`
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
En `capitulo-05.md` los workflows con contenido completo son `ci.yml` (`:1236-1362`), `build.yml` (`:1369-1480`) y `deploy-production.yml` (`:1580-1716`) y `scan.yml` (`:1769-1813`). **`deploy-staging.yml` y `dependency-review.yml` aparecen en el árbol pero su contenido no consta.** `build.yml` está documentado pero **no figura en el árbol**.

### 4.3 `ci.yml` — jobs y condiciones (`:1236-1362`)
- Disparadores: `pull_request` y `push` a `[main, 13.x]` (`:1240-1244`); `permissions: contents: read` a nivel workflow (`:1246-1247`); `concurrency.group: ci-${{ github.ref }}`, `cancel-in-progress: true` (`:1249-1251`)
- Jobs: **`php-lint`** (runs-on `ubuntu-24.04`, `permissions: contents: read` + `checks: write`, PHP 8.4 con `extensions: mbstring, pgsql, redis, intl, bcmath`, `coverage: xdebug`, cache Composer, `composer install --no-progress --prefer-dist`, `vendor/bin/phpstan analyse --no-progress --error-format=github`, `vendor/bin/pint --test`) — `:1254-1285`
- **`php-test`** (servicios `postgres:16-alpine` con `POSTGRES_PASSWORD: test`, `POSTGRES_DB: app_test`, healthcheck `pg_isready` 5/3/10; `redis:7-alpine` con healthcheck `redis-cli ping`; `vendor/bin/phpunit --coverage-clover=coverage.xml`; subida de artefacto **solo si `github.event_name == 'push'`**, `retention-days: 7`) — `:1287-1329`
- **`frontend-lint`** (Node 22, `cache: 'pnpm'`, `pnpm install --frozen-lockfile`, `pnpm lint`, `pnpm test:unit -- --coverage`) — `:1331-1342`
- **`security-scan`** (`fetch-depth: 0`, gitleaks, actionlint, zizmor) — `:1344-1362`
- SHA pinning se verifica con `gh api repos/actions/checkout/git/refs/tags/v4.2.2` copiando "el SHA del commit tag, **no del tree**" — `:1365`

### 4.4 `build.yml` — jobs y condiciones (`:1369-1480`)
- `on: push` a `[main]` + `tags: ['v*']`, y `pull_request` a `[main]` (`:1373-1378`)
- `permissions` a nivel workflow: `contents: read`, `packages: write`, `id-token: write` (cosign keyless opcional), `attestations: write` (`:1380-1384`)
- `env: REGISTRY: ghcr.io`, `IMAGE_NAME: ${{ github.repository }}` (`:1386-1388`)
- Job **`build`**: outputs `digest`, `sbom`; pasos: checkout → `docker/setup-buildx-action` → login GHCR con `github.actor` + `secrets.GITHUB_TOKEN` → `docker/metadata-action` con tags `type=ref,event=branch`, `type=semver,pattern={{version}}`, `type=semver,pattern={{major}}.{{minor}}`, `type=sha,prefix=sha-`, `type=raw,value=latest,enable={{is_default_branch}}` → build&push con `push: true`, `provenance: mode=max`, `sbom: true`, `platforms: linux/amd64,linux/arm64`, cache `type=gha,mode=max`, `build-args BUILDKIT_INLINE_CACHE=1`, `secrets GITHUB_TOKEN` → SBOM Syft (`format: spdx-json`, `artifact-name: sbom.spdx.json`) → Trivy → upload-sarif (`if: always()`) → Grype — `:1390-1467`
- Job **`attest`** (`needs: build`, `permissions: attestations: write` + `id-token: write`) con `actions/attest-build-provenance`, `subject-name`, `subject-digest: ${{ needs.build.outputs.digest }}`, `push-to-registry: true` — `:1469-1481`
- `provenance: mode=max` "genera SLSA L3 automáticamente"; consumible por `cosign verify-attestation`; cumple OWASP API 9:2023 — `:1483`
- **Diagrama 0 — Pipeline CI/CD completo** (`flowchart TB`), con subgrafos Developer/GitHub/CI/Build/Registries/Deploy Staging/Deploy Production/Post-deploy, `P1[OIDC token → DO Spaces]`, `P4[php artisan migrate --force]`, `P5[reload php8.4-fpm]`, `P6[Healthcheck 30s]`, y `P6 -->|unhealthy| ROLLBACK([↩️ Rollback a release anterior])` — `:1487-1576`

### 4.5 Actions y pinning exactos

| Action | SHA (40 chars) | Comentario en el doc |
|---|---|---|
| `actions/checkout` | `11bd71901bbe5b1630ceea73d27597364c9af683` | `# v4.2.2` |
| `shivammathur/setup-php` | `efffd0e4f2504f936fcfe3b69293d31ce0e2fd7a` | `# v2.30.3` |
| `actions/cache` | `1bd1e32a3bdc45362d1e726936510720a7c30a57` | `# v4.2.0` |
| `actions/upload-artifact` | `5d5d22a31266ced268874388b861e4b58bb5c2f3` | `# v4.6.0` |
| `actions/setup-node` | `39370e3970a6d050c480ffad4ff0ed4d3fdee5af` | `# v4.1.0` |
| `gitleaks/gitleaks-action` | `ff98106e4c7b2bc287b24eaf42907196329070c7` | `# v2.3.9` |
| `woodruffw/zizmor` | `f1e5b96fb5472647a8ddb526f6041c34c380fc71` | `# v1.5.1` |
| `docker/setup-buildx-action` | `b5ca514318bd6ebac0fb2aedd5d36ec1b5c232a2` | `# v3.10.0` |
| `docker/login-action` | `74a5d142397b4f367a81961eba4e8cd7edddf772` | `# v3.4.0` |
| `docker/metadata-action` | `369eb591f429131d6889c46b94e711f089e6ca96` | `# v5.6.1` |
| `docker/build-push-action` | `ca877d9245402d1537745e0e356eab47c3520991` | `# v6.13.0` |
| `anchore/sbom-action` | `fc46e51fd3cb168ffb36c6d1915723c47db58abb` | `# v0.17.7` |
| `aquasecurity/trivy-action` | `915b19bbe73b92a6cf82a1bc12b087c9a19a5fe2` | `# v0.28.0` |
| `github/codeql-action/upload-sarif` | `f35333b910470a5408cb081b68f0701254a7d27b` | `# v3.28.18` |
| `anchore/scan-action` | `27805bf3b4e84b4a5c980df22ed233c00390a439` | `# v7.4.2` |
| `actions/attest-build-provenance` | `96b4a1ef7235a096b17240c259729fdd70c83d45` | `# v2.0.0` |
| `slackapi/slack-github-action` | `37ebaef184d7626c5f204ab8d3baff4262dd30f0` | `# v1.27.0` |
| `actions/setup-go` | `0aaccfd150d50ccaeb58ebd88d36e91967a5f35b` | `# v5.4.0` |
| `rhysd/actionlint` | `docker://rhysd/actionlint:1.7.7` — **etiqueta, no SHA** | `1.7.7` |

Los 18 SHA tienen exactamente 40 caracteres (verificado). Catorce coinciden con la etiqueta comentada; cuatro no (ver §6).

### 4.6 Escáneres y umbrales que rompen el build
| Escáner | Workflow | Parámetros | ¿Rompe el build? |
|---|---|---|---|
| Trivy (imagen) | `build.yml:1447-1454` | `severity: 'HIGH,CRITICAL'`, `exit-code: '1'`, `format sarif` | **Sí** — HIGH y CRITICAL |
| Grype | `build.yml:1462-1467` | `severity-cutoff: high`, `fail-build: true` | **Sí** — high y superiores |
| Trivy (filesystem) | `scan.yml:1783-1796` | `severity: 'CRITICAL'`, **sin `exit-code`** | No (sin `exit-code` no falla) |
| gitleaks | `ci.yml:1351-1354` | — | Sí (el paso falla y el diagrama lo marca: `J3 -->|gitleaks FAIL| STOP([🚫 Push bloqueado])`, `:1550`) |
| actionlint | `ci.yml:1355-1358` | `args: -color` | Sí |
| zizmor | `ci.yml:1359-1362` | `persona: auditor` | Sí |
| govulncheck | `scan.yml:1798-1806` | `govulncheck ./...` | Sí |
| npm audit | `scan.yml:1808-1813` | `--audit-level=high --omit=dev` | Sí |
| Syft (SBOM) | `build.yml:1439-1445` | `format: spdx-json` | No aplica (genera artefacto) |

### 4.7 OIDC a DigitalOcean
- Título de §5.5: "Workflow de deploy a DigitalOcean vía OIDC (`deploy-production.yml`)" — `:1578`
- `permissions: contents: read` + `id-token: write  # obligatorio para OIDC` — `:1589-1591`
- Diagrama 0: `P1[OIDC token → DO Spaces]` — `:1526`; Diagrama 1: `CI->>DO: OIDC token → DO Spaces temp credentials` — `:1753`
- **La configuración OIDC propiamente dicha (trust relationship en DigitalOcean, `digitalocean/action-doctl` con credencial federada, `DO_OIDC_*`) no consta en el capítulo.** El `deploy` real usa `secrets.DEPLOY_SSH_PRIVATE_KEY` (`:1609`), `secrets.DEPLOY_HOST` (`:1614`), `secrets.DROPLET_ID` (`:1665`) y `secrets.CF_API_TOKEN` (`:1637`): **credenciales estáticas**. El `id-token: write` declarado nunca se consume en ningún paso. Ver §6.
- Referencias bibliográficas sobre OIDC: `docs.github.com/.../security-harden-deployments` y `.../oidc-in-cloud-providers` — `:3558-3559`

### 4.8 Reglas de entornos y aprobaciones
- Principio: "*Secret scoping*: Usar *environments* (`production`, `staging`) con `required_reviewers`" — `:1212`
- Árbol: `deploy-production.yml # Tag v* → production (manual approval)` — `:1226`
- `deploy` job: `environment: name: production`, `url: https://api.example.tld` — `:1601-1603`
- `concurrency: group: deploy-prod`, `cancel-in-progress: false  # nunca cancelar un deploy a producción en curso` — `:1593-1595`
- Diagrama 0: `P0[Manual approval<br/>environment: production]` — `:1525`
- **El YAML concreto de `required_reviewers` / protection rules del environment: no consta.**

### 4.9 Clave SSH de deploy restringida — `:1719-1731`
Directivas en `authorized_keys`: `command="/usr/local/bin/deploy-cmd.sh"`, `from="4.148.0.0/14"`, `from="20.0.0.0/8"`, `from="13.64.0.0/11"`, `no-port-forwarding`, `no-X11-forwarding`, `no-agent-forwarding`, `no-pty`. Se constata que las IPs de los runners cambian y el rango completo ocupa ">200 bloques CIDR" verificado contra `https://api.github.com/meta` clave `actions`. Práctica recomendada: (1) whitelist dinámica con `curl -s https://api.github.com/meta | jq -r '.actions[]' > /etc/ssh/actions-runners.allowed`; (2) en `sshd_config` `AllowUsers deployer@*` + `iptables … -m set --match-set actions-runners src -j ACCEPT`; (3) regenerar con cron semanal. `deploy-cmd.sh` "solo permite `cd /var/www/html/current && /usr/bin/git checkout` o comandos pre-aprobados; cualquier otra cosa se rechaza".

### 4.10 Dependabot, CODEOWNERS y protección de rama
- **Dependabot** (`:1818-1855`): composer semanal lunes 06:00, `open-pull-requests-limit: 10`, grupos `laravel` (`laravel/*`) y `security` (`applies-to: security-updates`); npm en `/frontend/` semanal martes; `github-actions` **mensual** con grupo `actions` (`["*"]`)
- **CODEOWNERS** (`:1859-1868`): `/app/`, `/frontend/src/`, `/database/migrations/`, `/.github/workflows/`, `/docker/`, `/config/`, `*.env.example` con equipos `@santander/*`
- **Branch protection** (`:1871-1888`): PR obligatorio; **2 aprobaciones**; descartar aprobaciones obsoletas; revisión de Code Owners; status checks (CI, build, scan); ramas al día; resolución de conversaciones; commits firmados; historial lineal; incluir administradores; push restringido a `maintain`; sin force push; sin borrado
- **Secret scanning** (`:1892-1911`): Secret scanning ON, Push protection ON, Dependabot security updates ON, Dependabot version updates ON, Code scanning (CodeQL) ON; patrón custom para `DEPLOY_SSH_PRIVATE_KEY` con regex `-----BEGIN OPENSSH PRIVATE KEY-----[\s\S]+?-----END OPENSSH PRIVATE KEY-----` y `"multiline": true`

### 4.11 Deploy a producción en detalle (`:1580-1716`)
Pasos: checkout → configurar SSH (escribe la clave en `~/.ssh/deploy_ed25519`, `chmod 600`, `ssh-keyscan -H`, `::add-mask::`) → `rsync -az --delete` excluyendo `.git`, `storage/logs/*`, `.env`, `vendor/` hacia `deployer@host:/var/www/html/releases/${RELEASE_SHA}/` con `-e 'ssh -i … -o StrictHostKeyChecking=yes'` → comandos remotos por heredoc: symlink `current`, `composer install --no-dev --optimize-autoloader --no-interaction --no-progress`, `php artisan migrate --force`, `config:cache`, `route:cache`, `event:cache`, purga de Opcache, `sudo systemctl reload php8.4-fpm`, snapshot DO, `ls -dt releases/* | tail -n +6 | xargs -r rm -rf` (mantener últimos 5) → tag en Sentry → notificación Slack (`if: always()`) → auto-rollback (`if: failure()`): `LAST_GOOD=$(ls -dt /var/www/html/releases/*/ | head -n 2 | tail -n 1)`, `ln -sfn "$LAST_GOOD" /var/www/html/current`, `config:cache`, `route:cache`, `systemctl reload php8.4-fpm`

---

## 5. Runbook (NIST SP 800-61r3)

### 5.1 Marco adoptado — `capitulo-05.md:3151-3156`
"Siguiendo esa misma lógica" (§0, `:33`): cuatro fases de **NIST SP 800-61r3** `[NIST, 2025-04-03]`:
1. **Preparation** — capítulos 1 a 8 cubren el grueso (IDS, backups, MFA, secretos)
2. **Detection & Analysis** — Wazuh, auditd, métricas
3. **Containment, Eradication & Recovery** — §9.2 a §9.6
4. **Post-Incident Activity** — §9.7 post-mortem

### 5.2 Roles y contactos — `:3160-3168`
Incident Commander (IC) `ic@example.tld`; Tech Lead `tech@example.tld`; DevOps on-call "rotación PagerDuty" `pd-oncall@example.tld`; DBA `dba@example.tld`; Legal (RGPD/breach) `legal@example.tld`; DigitalOcean support `https://cloud.digitalocean.com/support`; Cloudflare support "Enterprise dashboard". Teléfonos con prefijo **+34**.

### 5.3 Caso A — SSH comprometido (`:3170-3260`)
**Señales** `:3172-3176`: Wazuh rule 5710 con 30+ fallos/min desde una IP; `journalctl -u ssh` con login exitoso de `deployer` desde IP no corporativa; `who` con sesión no reconocida.
**Diagrama 3 — Runbook SSH comprometido** (`flowchart TD`) `:3180-3214`, con nodos y decisiones exactos: auto-bloqueo Fail2Ban → containment (bloquear IP en Cloudflare + `fail2ban ban manual`) → evidencia (`cp /var/log/auth.log.* /secure/evidence/`) → ¿llave SSH usada? (fingerprint `ssh-keygen -l -f /root/.ssh/known_hosts` → revocar llave pública en `authorized_keys` de TODOS los usuarios) / ¿fuerza bruta de contraseña? → rotación de secretos (7 ítems numerados) → invalidar sesiones (`DELETE FROM sessions`, `DELETE FROM personal_access_tokens`) → auditoría forense (Wazuh retroactivo 14 días; `last,lastb,lastlog`; `journalctl _COMM=sudo`; `find /var/www -name '*.php' -newer /var/log/auth.log`; `ps auxf`; `ss -tunap`) → ¿acceso a root? Sí ⇒ "ASUMIR rootkit: Reinstalar droplet desde snapshot pre-incidente + Cambiar TODAS las claves SSH"; No ⇒ endurecer (cambiar puerto, revisar sudoers, auditd retroactivo, aumentar fail2ban maxretry) → Recovery (restaurar DB desde restic snapshot pre-incidente; `SELECT count(*) FROM users;`; re-desplegar última versión limpia) → Post-mortem → cerrar ticket PagerDuty / notificar Legal / comunicación stakeholders.
**Comandos concretos** `:3218-3260`, en 7 bloques:
1. Contención: `fail2ban-client set sshd banip <IP>`, `iptables -I INPUT -s <IP> -j DROP`, `ufw deny from <IP>`, bloqueo perimetral Cloudflare API (`POST /client/v4/firewall/access_rules/rules` con `mode: block`)
2. Identificar vector: `last -i | head -20`, `lastb -i | head -20`, `journalctl -u ssh --since "7 days ago"`
3. Evidencia: `mkdir -p /secure/evidence/$(date -u +%Y%m%dT%H%M%SZ)/`, `cp /var/log/{auth.log*,syslog*,fail2ban.log} …`, `cp -r /var/ossec/logs/archives …`, `chmod -R 0400 …`
4. Rotación: tinker borrando tokens, `TRUNCATE personal_access_tokens;`, `NEW_APP_KEY=$(php artisan key:generate --show)`, comentario "Editar .env, APP_PREVIOUS_KEYS=$OLD_KEY, APP_KEY=$NEW_APP_KEY", cambiar DB_PASSWORD en DO Managed DB y `/run/secrets/db_password`
5. Regenerar deploy key: `ssh-keygen -t ed25519 -C "deploy@$(date +%s)" -f /tmp/new_deploy_key -N ''`
6. Forense: `ausearch -m USER_LOGIN,USER_AUTH,EXECVE -ts recent`, `find /var/www -name '*.php' -newermt "7 days ago" -ls`, `find / -perm -4000 -type f -ls`, `ps auxf | less`
7. Notificación por `mail -s "[INCIDENT] SSH"`

### 5.4 Caso B — Aplicación comprometida (RCE / web shell) (`:3262-3299`)
**Señales** `:3264-3269`: Wazuh 100007; FIM con nuevo `.php` en `/var/www/html/storage/`; requests con payloads `<?php system($_GET['c'])`; CPU al 100 % sostenido por worker desconocido.
**Respuesta** (7 pasos numerados) `:3271-3279`: 1 Containment con `php artisan down --secret=<token>`; 2 Captura forense con snapshot DO inmediato (aunque bloquee deploys); 3 Identificar vector (logs nginx 7 días + Wazuh FIM); 4 Rotación de APP_KEY + DB password + Sanctum tokens; 5 Limpieza: "si hay web shell confirmado, NO borrar inmediatamente — copiar a evidencia primero"; 6 Recovery con `ln -sfn releases/<prev-ok-sha> current`; 7 Post-incidente: bug fix que evite el vector.
**Hardening adicional inmediato** `:3283-3299` (literal):
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

### 5.5 Caso C — DDoS (`:3301-3322`)
**Señales** `:3303-3307`: UptimeRobot con >5xx masivos; Grafana `nginx_requests_total{status="5xx"}`; Wazuh rule 9500+ (HTTP flood).
**Respuesta** (6 pasos) `:3309-3322`: 1 activar Cloudflare "Under Attack Mode" vía `PATCH /client/v4/zones/$CF_ZONE/settings/security_level` con `{"value":"under_attack"}`; 2 rate-limit agresivo en WAF rules **10 req/min por IP global**; 3 bloquear ASN; 4 escalar a DO support si >10 Gbps; 5 **NO** deshabilitar la CDN; 6 documentar duración, picos (Gbps/Mpps) y reglas WAF añadidas.

### 5.6 Caso D — Ransomware (`:3324-3341`)
**Señales** `:3326-3330`: FIM con modificación masiva `.php/.jpg/.docx` → `.locked`; Postgres log con conexión desde IP inusual o *truncate/drop*; backups locales cifrados o eliminados.
**Respuesta** (8 pasos) `:3332-3341`: 1 tirar firewall del droplet (cerrar TODOS los puertos); 2 **NO pagar** (NIST IR Guide §4.3); 3 **No apagar** el droplet (estado en memoria puede contener claves); 4 snapshot DO completo a bucket separado con Object Lock; 5 restaurar con nuevo droplet desde `release-<last-clean-snapshot>`; 6 cambiar TODAS las credenciales; 7 notificar Legal en **72 h** (RGPD Art. 33 `[EUR-Lex, 2016-04-27]`); 8 post-mortem sobre backups inmutables y vector de entrada.

### 5.7 Plantilla post-mortem — `:3345-3395`
Markdown con: Fecha del incidente, Detectado/Resuelto (HH:MM UTC), Severidad (SEV1|SEV2|SEV3), Incident Commander, Reporter, Status (Draft|Reviewed|Final); Resumen ejecutivo (2-3 frases); Timeline (UTC) con 5 hitos (alerta Wazuh, on-call paged, contención, root cause, mitigación); Impact (usuarios afectados N/%, downtime horas, datos exfiltrados sí/no/por confirmar, coste $X); Root cause (5 Whys); What went well; What went wrong; Action items (`- [ ] @owner — Acción — fecha límite`); Lessons learned. "post-mortem es **blameless**" — `:3395`.

---

## 6. Defectos y contradicciones

### 6.1 Contradicciones internas directas (dos afirmaciones incompatibles)

| # | Contradicción | Líneas |
|---|---|---|
| 1 | `.env` fija `TRUSTED_PROXIES=*`, pero la decisión dice literalmente "**NO** usar `TRUSTED_PROXIES=*`" y ordena una lista explícita de IPs | `:130` vs `:263`, `:234-259` |
| 2 | Logging: el texto dice `/var/log/laravel/security.log`; la config real escribe en `storage_path('logs/security.log')`; Wazuh vigila `/var/www/html/storage/logs/security.log` | `:450` vs `:464` vs `:2574`, `:2878` |
| 3 | CORS `allowed_headers = ['Authorization','Content-Type','Accept']` (excluye deliberadamente `X-Requested-With`) pero Axios envía `'X-Requested-With': 'XMLHttpRequest'` ⇒ todo preflight cross-origin (SPA en `app.example.tld` → API en `api.example.tld`) será rechazado | `:293`, `:302` vs `:1160` |
| 4 | DHI "No-root (`USER 65532`)" vs Dockerfile `ARG UID=1000` / `USER ${UID}:${GID}` y decisión "`USER 1000:1000`" | `:1923` vs `:1990-1991`, `:2028`, `:2053` |
| 5 | DHI "Sin shell (`/bin/sh` no existe)" vs el Dockerfile usa `RUN` en el stage runtime (`apk add`, `mkdir`, `chmod`), el stage builder usa `RUN composer …` y el servicio `scheduler` ejecuta `["sh","-c", …]` | `:1922` vs `:1939-1974`, `:1994-2025`, `:2364` |
| 6 | `ENV LOG_CHANNEL=stderr` en la imagen y `LOG_CHANNEL: stderr` en compose vs `.env` con `LOG_CHANNEL=stack` + `LOG_STACK=single,security`: en contenedor el canal `security` (y su forward a Wazuh y sus reglas 100001-100009) nunca se escribe | `:2039`, `:2304` vs `:111-112`, `:500-518`, `:2574` |
| 7 | El Dockerfile hace `cp .env.production .env` y `php artisan key:generate --force` en build (APP_KEY horneada en la imagen), pero el entrypoint solo genera `.env` "if [[ ! -f .env …]]" y §1.4/§6.5 dicen que la clave viene de secretos ⇒ el entrypoint **nunca** vuelca los Docker secrets | `:1969-1970` vs `:2148`, `:196`, `:2019` |
| 8 | `.dockerignore` excluye `.env.*` (sin re-incluir `.env.production`) ⇒ `COPY . .` no aporta `.env.production` y el `cp` de la línea `:1969` falla el build | `:2221-2222` vs `:1969` |
| 9 | Se copian **dos** configuraciones de pool FPM que escuchan en el mismo `0.0.0.0:9000`: `fpm-pool.conf` → `zz-app.conf` y `www.conf` → `www.conf` ⇒ conflicto de dirección al arrancar | `:2015`, `:2017`, `:2105` |
| 10 | El pool declara `user = app` / `group = app` pero el contenedor corre como `USER 1000:1000`; en una imagen DHI no existe el usuario `app` | `:2103-2104` vs `:2028` |
| 11 | `pm.max_children = 50` × `memory_limit = 256M` = 12,8 GiB teóricos contra un límite de contenedor de `512M` ⇒ OOM kill; el documento no ajusta ninguno de los dos | `:2108`, `:2126` vs `:2324` |
| 12 | `disable_functions` incluye `parse_ini_file`, y el entrypoint (mismo árbol de config) usa `parse_ini_file(...)` en su validación de arranque | `:2129` vs `:2163` |
| 13 | `read_only: true` en `api`, pero el entrypoint ejecuta `php artisan config:clear` y `config:cache`/`route:cache` que escriben en `bootstrap/cache` (no hay `tmpfs` para esa ruta en el servicio `api`) | `:2295`, `:2296-2298` vs `:2161`, `:2176-2177` |
| 14 | `VOLUME ["/var/www/html/storage", …]` en el Dockerfile y `tmpfs` sobre `/var/www/html/storage/framework/cache` en compose: el `tmpfs` cae dentro de una ruta ya declarada como volumen | `:2031` vs `:2298` |
| 15 | El ancla `x-security-opts` (con `read_only: false` y `cap_add: NET_BIND_SERVICE`) se define pero **no se usa en ningún servicio**; su comentario "true en api/worker; false en scheduler" contradice los servicios reales (api/worker sí `read_only: true`, nginx también, scheduler sin `read_only`) | `:2249-2256` vs `:2285-2471` |
| 16 | Los perfiles seccomp se referencian como `./seccomp/fpm.json` / `./seccomp/worker.json` (relativo al compose) pero la decisión dice que "se incluyen en `docker/seccomp/`" | `:2292`, `:2337` vs `:2477` |
| 17 | §8.6 recomienda "**Vault con Transit + unseal keys en 3 personas**"; §10.5 decide "para una VPS única, **SOPS + age + cifrado en Git** es suficiente" | `:3143` vs `:3479` |
| 18 | Rotación de `APP_KEY`: §1.4 paso 4 y §10.1 dicen retención de `APP_PREVIOUS_KEYS` de **90 días**; el runbook §9.3 usa "previa en `APP_PREVIOUS_KEYS` **7d**" | `:188`, `:3408` vs `:3197` |
| 19 | El runbook ordena `DELETE FROM sessions` pero el driver de sesión es Redis ⇒ no existe tabla `sessions` | `:3199` vs `:117`, `:213` |
| 20 | Tres topologías de base de datos incompatibles: `DB_HOST=10.0.0.5` (`.env`), `DB_HOST: postgres` (compose) y "DO Managed DB" en el runbook | `:134` vs `:2302`, `:2345` vs `:3245` |
| 21 | Dos arquitecturas de despliegue incompatibles: §5.5 despliega con `rsync` + `composer install` en el host + `sudo systemctl reload php8.4-fpm` + `/var/www/html/releases`, mientras §6 despliega todo en Docker con imagen inmutable y `read_only`, y prohíbe montar `./:/var/www/html` | `:1617-1670`, `:2479` vs `:2286`, `:2479` |
| 22 | §6.8 afirma "No hay `volumes: ["./:/var/www/html"]` en producción — el código entra por imagen", pero §7.2 hace FIM sobre `/var/www/html/app`, `/var/www/html/config` y `/var/www/html/storage/logs/security.log` **en el host**, rutas que no existen si el código vive en la imagen | `:2479` vs `:2534-2544`, `:2574` |
| 23 | §7 instala el agente Wazuh, auditd, fail2ban y los exporters **en el droplet por apt**, mientras toda la aplicación corre en contenedores con `read_only`; fail2ban lee `/var/log/nginx/access.log` y Wazuh lee `/var/log/nginx/*.log` que en el contenedor están en un `read_only` sin volumen de logs al host | `:2491-2497`, `:2942`, `:2586-2593` vs `:2443-2454` |
| 24 | El healthcheck se invoca con tres rutas distintas: `/api/health` (script), `/health` (compose nginx), `https://api.example.tld/health` (UptimeRobot) | `:2188` vs `:2467` vs `:2863` |
| 25 | La imagen DHI se declara "no-root" y el pool FPM añade `security_opt: apparmor:api`, perfil que el capítulo no define; sin él el contenedor no arranca | `:1923`, `:2293` |
| 26 | §4.7 dice que el frontend no emite COOP/COEP/CORP y que los pone Nginx, pero el capítulo nunca muestra el `nginx.conf` (solo se referencia `./docker/nginx.conf` en compose) | `:1176` vs `:2456` |
| 27 | §1.4 permite `APP_KEY` en "DO App Platform env vars" cuando la arquitectura es un Droplet con Docker secrets | `:200` vs `:3424` |
| 28 | §1.1 declara el plano runtime (`php.ini`, FPM pool, opcache) "fuera de alcance de este capítulo", y §6.3/§6.4 lo especifican por completo | `:60` vs `:2059-2130` |
| 29 | §1.13 prohíbe mezclar permisos de Spatie con abilities de Sanctum, pero su propia `PostPolicy` usa `$user->can('read:posts')` / `$user->can('write:posts')`, nombres de ability de Sanctum | `:446` vs `:426`, `:431` |
| 30 | §1.12 exige que campos críticos nunca estén en `$fillable` y se asignen tras `Gate::authorize('users.change-role', …)`, pero ese Gate no se define en §1.13 (que solo menciona `view-dashboard-admin` e `impersonate`) | `:390-401` vs `:416` |
| 31 | §1.3 `SESSION_LIFETIME=120` (minutos) vs `php.ini` `session.gc_maxlifetime = 7200` (2 h) | `:118` vs `:2084` |
| 32 | §1.3 `SESSION_SAME_SITE=lax` vs la decisión de §2.4 "Refresh token… `SameSite=Strict`" (cookies distintas, pero el capítulo no las distingue al configurar) | `:121` vs `:713` |
| 33 | §5.5 se titula "vía OIDC" y declara `id-token: write`, pero ningún paso consume un token OIDC; el acceso a DO usa `secrets.DEPLOY_SSH_PRIVATE_KEY`, `secrets.DROPLET_ID` y `secrets.CF_API_TOKEN` | `:1578`, `:1591` vs `:1609`, `:1665`, `:1637` |
| 34 | §5.4/§5.6 usan `anchor: severity HIGH,CRITICAL` + `exit-code 1` en el build; el `scan.yml` usa `severity: CRITICAL` y **sin** `exit-code`, de modo que el escaneo programado nunca rompe el build | `:1453-1454` vs `:1793` |
| 35 | §5.1 prohíbe *pinning* por etiqueta ("Todas las … con SHA-256 de 40 chars"), pero `actionlint` se invoca como `docker://rhysd/actionlint:1.7.7` (etiqueta mutable) | `:1209`, `:9` vs `:1356` |
| 36 | §5.1 fija `permissions: contents: read` por workflow con override por job, pero `build.yml` declara `packages: write`, `id-token: write` y `attestations: write` a nivel **workflow**, heredados por el job `build` | `:1210` vs `:1380-1384` |
| 37 | El árbol de `.github/workflows/` lista `ci.yml`, `deploy-staging.yml`, `scan.yml`, `dependency-review.yml` pero no `build.yml`; y el Diagrama 0 nombra `lint.yml` y `test.yml` que no existen en el árbol | `:1223-1228` vs `:1500-1502`, `:1370` |
| 38 | §6.8 dice que Cloudflare Tunnel llega a `127.0.0.1:8080`, pero Cloudflare Tunnel no se describe en ningún §7 (que es IDS y monitoreo) | `:2478` vs `:2483-2959` |
| 39 | §8.1 exige "1 copia inmutable / offline (**Snapshots DO marcados `immutable=true`**)", pero el comando `doctl compute snapshot create` no tiene ningún flag de inmutabilidad y §8.3 delega la inmutabilidad a Object Lock de Spaces | `:2972` vs `:3074-3077`, `:3079` |
| 40 | §8.3 tag `--tag-name "release"` vs §9.6 `release-<last-clean-snapshot>` | `:3075` vs `:3338` |
| 41 | §7.6 dice que los exporters escuchan en `127.0.0.1` y se scrapean por túnel SSH/VPC, pero `prometheus.yml` apunta a `droplet-app.example.tld:9100` (nombre público), no al extremo del túnel | `:2802`, `:2845` vs `:2822` |
| 42 | §7.3 regla 100008 se describe como "Token emitido con ability `admin:*` **desde IP no corporativa**", pero el XML solo tiene `<match>token.issued</match>` + `<field name="json.abilities">admin:*</field>`: no hay ninguna condición de IP | `:2671-2677` |
| 43 | §7.3 define reglas para eventos que el capítulo nunca emite (`mass_assignment.rejected`), y el código de §2.6 sí emite `auth.2fa.verified` que no está en la tabla de eventos obligatorios de §1.14 (igual que `token.refreshed`, `tokens.pruned`, `auth.logout-all`) | `:2680-2684`, `:805`, `:698`, `:660`, `:837` vs `:500-518` |
| 44 | El Diagrama 2 asigna `alert L>=12 → PD` (PagerDuty) y el inventario lista `PAGERDUTY_TOKEN`, pero no hay ninguna integración PagerDuty configurada en §7.4 | `:2907`, `:3520` vs `:2694-2723` |
| 45 | §1.9 dice que las rutas API usan token Bearer "sin CSRF", pero §7.9 define una jail fail2ban sobre `POST /api/login` con 401, un flujo de login por API que §1.9 no contempla | `:310` vs `:2934` |
| 46 | §1.3 usa el dominio de ejemplo `api.example.tld` y el apartado 1.3.3 titula `APP_URL=https://api.dominio.tld` | `:102` vs `:151` |
| 47 | El capítulo se dirige a una sede colombiana (Ley 1581 sería lo natural) pero el runbook habla de **RGPD** europeo, notificación en 72 h, teléfonos con prefijo **+34** y "Legal (RGPD/breach)" | `:3162-3166`, `:3340`, `:3483` |

### 6.2 Comandos o scripts que no funcionarían tal cual

| # | Problema | Línea |
|---|---|---|
| 1 | `(echo "curl -s http://127.0.0.1:8080/opcache-purge") \|\| true` **imprime** la cadena en lugar de ejecutar `curl`: la purga de OPcache nunca ocurre | `:1657` |
| 2 | El snapshot DO se crea con `-H "Authorization: Bearer ${CF_API_TOKEN}"` contra `api.digitalocean.com`: se envía el token de **Cloudflare** a la API de **DigitalOcean** | `:1663-1666` |
| 3 | En el heredoc remoto `<<'EOF'`, `${RELEASE_SHA}` se expande en el **host remoto**, donde la variable no existe ⇒ `ln -sfn releases/${RELEASE_SHA} current` crea `releases/` vacío; las variables declaradas en `env:` (`APP_KEY`, `DB_PASSWORD`, `CF_API_TOKEN`) nunca llegan al remoto | `:1639-1644`, `:1632-1637` |
| 4 | Los pasos de Sentry y Slack referencian `needs.resolve-tag.outputs.tag` y `needs.resolve-tag.outputs.sha`, pero el workflow **no tiene job `resolve-tag`** ⇒ expresiones vacías y fallo/etiquetas en blanco | `:1684`, `:1687`, `:1699` |
| 5 | El auto-rollback no revierte migraciones ni restaura el `.env`/config cache del release anterior; solo recambia el symlink y regenera caches | `:1702-1716` |
| 6 | `app/Console\Kernel.php` usa barra invertida (ruta inválida) y Laravel 11+/13 no tiene `app/Console/Kernel.php`: la programación va en `routes/console.php` o `bootstrap/app.php` `->withSchedule()` | `:665` |
| 7 | `app/Exceptions/Handler.php` con `render()` no existe en Laravel 11+/13 (se sustituye por `bootstrap/app.php` `->withExceptions()`) | `:920` |
| 8 | `new JsonApiErrorResponse($e)` se devuelve como `Response` desde `render()`, pero la clase solo define `__invoke()`: no es un objeto `Response` | `:924` vs `:930-960` |
| 9 | `RuntimeException` se lanza sin `use RuntimeException;` en `AppServiceProvider` ⇒ resuelve a `App\Providers\RuntimeException` y provoca error fatal | `:522-537` |
| 10 | §1.15 afirma que el bloque "se ejecuta una sola vez durante `config:cache`", pero `boot()` se ejecuta en **cada** petición | `:540` vs `:524` |
| 11 | `RateLimiter::for(...)` sin `use Illuminate\Support\Facades\RateLimiter;` (solo se importan `Limit` y `Request`) | `:330-334` |
| 12 | El limitador `login` limita por `email|ip`, pero §7.9 espera 401 a `/api/login`, endpoint que no se define en ninguna parte | `:341-346` vs `:2934` |
| 13 | `PersonalAccessToken::where('token', hash('sha256', $plain))`: Sanctum almacena el hash del token completo (`id|plaintext`), no del texto plano; además usa la columna `refreshable` que **no existe** en la migración mostrada en §2.2 | `:687-691` vs `:624-635` |
| 14 | El comando de poda usa `orWhere` sin agrupar y nunca elimina tokens con `last_used_at IS NULL` (nunca usados) | `:655-658` |
| 15 | `$user->two_factor_recovery_codes_array` no es un accesor definido en el código mostrado | `:784` |
| 16 | `Illuminate\Auth\Middleware\AuthenticateSession` no es el middleware que valida la cookie CSRF/stateful de Sanctum (esos son `EnsureFrontendRequestsAreStateful` + validación CSRF) | `:722` |
| 17 | Componente Vue inválido: `<div v-html="safeBio"></template>` con cierre `</div>` desemparejado y `</template>` sobrante | `:1083-1085` |
| 18 | `vite.config.ts` importa `visualizer` de `rollup-plugin-visualizer` y **no lo usa**; `cssMinify: 'lightningcss'` requiere el paquete `lightningcss` que no se declara | `:1020`, `:1025`, `:1030` |
| 19 | `ci.yml` usa `pnpm` (cache pnpm, `pnpm install`, `pnpm lint`) sin instalar pnpm ni `corepack enable` ⇒ `pnpm: command not found` | `:1339-1342` |
| 20 | `build.yml` con `push: true` también en `pull_request`, y login a GHCR con `GITHUB_TOKEN` (read-only en PR de fork) ⇒ fallo en PRs | `:1377`, `:1426`, `:1407` |
| 21 | `github/codeql-action/upload-sarif` necesita `security-events: write`, permiso ausente en `build.yml` ⇒ 403 al subir el SARIF | `:1456-1460` vs `:1380-1384` |
| 22 | `npm audit --audit-level=high --omit=dev` se ejecuta sin `setup-node` ni instalación previa: no hay `package-lock.json` (el proyecto usa pnpm) | `:1808-1813` vs `:1340` |
| 23 | `govulncheck ./...` sobre un proyecto PHP/Vue no tiene nada que analizar; además `go install …@latest` está sin pinnear | `:1798-1806` |
| 24 | Dependabot: `applies-to: security-updates` con `schedules:` anidado dentro de `groups` y entrada `- match: { security: true }` no corresponden al esquema de `dependabot.yml` | `:1833-1836` |
| 25 | El script `healthcheck.sh` invoca `fcgi-request`, binario que **no** proporciona el paquete Alpine `fcgi` (que aporta `cgi-fcgi`); con `2>/dev/null` el fallo se oculta | `:2189`, `:1997` |
| 26 | El entrypoint usa `printf "%s=%q\n"` (formato `%q`, específico de `printf` de bash) ⇒ produce valores comillados que no son dotenv válido para valores con caracteres especiales | `:2154` |
| 27 | `[[ ! -f bootstrap/cache/routes-v7.php ]]` asume el nombre de fichero del route-cache de Laravel 11; no consta su vigencia en Laravel 13 | `:2177` |
| 28 | Prometheus: `job_job: 'redis'` es una clave inválida (debería ser `job_name`); ese scrape job queda roto | `:2832` |
| 29 | El pre-flight del backup escribe en `/var/log/restic/check.log` sin crear el directorio (solo se hace `mkdir` del cache de restic) ⇒ fallo con `set -euo pipefail` | `:3008` vs `:3015` |
| 30 | `restic forget --tag "$TAG"` con `TAG="auto-${HOSTNAME}-${TS}"` único por ejecución ⇒ el filtro por tag solo alcanza el snapshot recién creado y la retención 7/4/6 nunca se aplica a los anteriores | `:3012`, `:3038-3043` |
| 31 | `ssh-keygen -l -f /root/.ssh/known_hosts` no es el uso correcto (fingerprint de una clave, no de `known_hosts`) | `:3191` |
| 32 | `TRUNCATE personal_access_tokens;` aparece como línea de shell dentro de un bloque bash: se ejecuta como comando y falla | `:3242` |
| 33 | `$file->getMimeTypeFromExtension()` no es un método de `UploadedFile`/Symfony en Laravel: la validación post-incidente lanzaría error | `:3289` |
| 34 | Diagrama Mermaid 3 con bracket desemparejado: `O[¿Acceso a root?}` | `:3203` |
| 35 | `node_exporter` se descarga con `wget` **sin verificar checksum** y `govulncheck@latest` sin pinnear, contradiciendo el propio principio de *supply-chain* del §5.1 | `:2792`, `:1805` |
| 36 | Los perfiles seccomp y el AppArmor `api` se referencian pero no se aportan; el capítulo dice que están en `docker/seccomp/` sin incluirlos | `:2292-2293`, `:2477` |

### 6.3 Referencias cruzadas erróneas (a la sección equivocada)

| # | Referencia textual | Dice | Debería apuntar a |
|---|---|---|---|
| 1 | "Canal `security` añadido (ver **§1.11**)" | `:112` | §1.14 (logging) — §1.11 es hashing |
| 2 | "*configuration gate* (ver **§1.12**)" | `:145` | §1.15 (verificación al arranque) — §1.12 es mass assignment |
| 3 | "Filtro en `security` channel (**§1.11**)" | `:199` | §1.14 |
| 4 | "Doble red de seguridad junto al check de **§1.12**" | `:2055` | §1.15 |
| 5 | "se reproduce como checklist de cierre en **§11**" | `:50` | §11 es el Apéndice A de inventario de secretos; **no hay checklist OWASP en §11** → referencia a contenido inexistente |
| 6 | "hace *forward* a Wazuh vía syslog (ver **§7.4**)" | `:450` | §7.4 son alertas Slack/Telegram; la integración syslog/config del agente está en §7.1-§7.2 |
| 7 | "Cloudflare Tunnel (**§7**)" | `:2478` | §7 no menciona Cloudflare Tunnel |
| 8 | "dashboard.json … (no incluido por brevedad, ver **Apéndice A**)" | `:2859` | El Apéndice A es el inventario de secretos; el JSON de Grafana no está en ningún apéndice |
| 9 | "Se documenta en el runbook de **§9.6**" (Healthchecks.io) | `:2864` | §9.6 es "Caso D: Ransomware"; Healthchecks.io no aparece en el runbook |
| 10 | `deploy-production.yml` "SSH a droplet staging" | — | `:1520` (Diagrama 0) atribuye el SSH a staging |
| 11 | §1.14 "ver §7.4" y §7.2 vigilan rutas distintas de `security.log` | `:450` vs `:2574` | Inconsistencia además de referencia |
| 12 | "Integración `auditd` vía audisp-remote" pero el `localfile` de Wazuh lee `/var/log/audit/audit.log` **además** del plugin remoto | `:2596-2600` vs `:2767-2783` | Doble canal no reconciliado |

### 6.4 Archivos y rutas referenciados que no existen en el repositorio de trabajo

El capítulo referencia estos artefactos. En `/home/sacunapolo/Documentos/sede` **no existen** ni `docker/`, ni `.github/`, ni `artifacts/`, ni `secrets/`, ni `frontend/` (el workspace solo contiene documentación: `docs/`, `docs-security/`, `GUIA-MAESTRA-COMPLETA.md`, informes y `.scratch/`). No hay repositorio git. Por tanto son **rutas planificadas, no verificables**:

- `docker/fpm-pool.conf` (`:2015`), `docker/php.ini` (`:2016`), `docker/www.conf` (`:2017`), `docker/entrypoint.sh` (`:2020`), `docker/healthcheck.sh` (`:2024`), `docker/nginx.conf` y `docker/conf.d` y `docker/certs` (`:2456-2458`), `docker/seccomp/fpm.json` y `worker.json` (`:2292`, `:2337`, `:2477`)
- `.github/workflows/{ci,build,deploy-staging,deploy-production,scan,dependency-review}.yml` (`:1223-1228`), `.github/dependabot.yml` (`:1819`), `.github/CODEOWNERS` (`:1860`), `.github/settings.yml` (`:1231`)
- `artifacts/grafana/dashboard.json` (`:2859`) — además explícitamente "no incluido por brevedad"
- `secrets/APP_KEY`, `secrets/DB_PASSWORD`, `secrets/CLOUDFLARE_API_TOKEN`, `secrets/DO_API_TOKEN`, `secrets/GITHUB_DEPLOY_KEY` (`:2259-2268`)
- `frontend/.env.production`, `frontend/src/lib/api.ts`, `frontend/src/lib/queryClient.ts`, `frontend/src/lib/sanitize.ts`, `frontend/src/stores/auth.ts`, `vite.config.ts` (`:1057`, `:1149`, `:1123`, `:1091`, `:1186`, `:1014`)
- `app/Support/SecurityLog.php` (`:480`), `app/Support/JsonApiErrorResponse.php` (`:929`), `app/Http/Middleware/ValidateJsonApiQuery.php` (`:981`), `app/Console/Commands/RevokeStaleTokens.php` (`:650`), `app/Policies/PostPolicy.php` (`:421`), `app/Http/Controllers/Api/{TokenController,AuthController,MfaController}.php`
- Host/droplet: `/usr/local/bin/deploy-cmd.sh` (`:1722`), `/etc/ssh/actions-runners.allowed` (`:1727`), `/opt/backup/run.sh` y `/opt/backup/drill.sh` (`:3003`, `:3096`), `/etc/restic/restic.env` y `/etc/restic/excludes.txt` (`:2986`, `:3024`), `/var/ossec/etc/rules/local_rules.xml` (`:2620`), `/var/ossec/integrations/custom-telegram` (`:2708`), `/etc/audit/rules.d/wazuh.rules` (`:2728`), `/etc/fail2ban/filter.d/nginx-login.conf` y `jail.d/api.conf` (`:2932`, `:2938`), `keys/dev.age` y `secrets/db.yaml` (`:3437`, `:3441`), `./secrets/*` de compose (`:2258-2268`)
- Rutas de log divergentes que no pueden coexistir: `/var/log/laravel/security.log` (`:450`) vs `/var/www/html/storage/logs/security.log` (`:464`, `:2574`)

### 6.5 Versiones e incoherencias de pila

| Observación | Línea |
|---|---|
| El capítulo fija **PHP 8.4**, **PostgreSQL 16** y **Nginx 1.29**; el plan solicitado es PHP-FPM **8.5**, PostgreSQL **18**, Laravel 13 + Redis 7 | `:3`, `:1264`, `:1292`, `:1933`, `:2381`, `:2443` |
| `ARG PHP_VERSION=8.4.6` y `ARG COMPOSER_VERSION=2.7.7` se declaran en el Dockerfile pero **nunca se usan** (las imágenes van por etiqueta fija) | `:1933-1934` |
| `COPY --from=dhi.io/composer:2.7` usa una **etiqueta de dos componentes sin digest**, rompiendo la disciplina de pinning del propio capítulo | `:1946` vs `:9`, `:1209` |
| `actions/upload-artifact@5d5d22a…` está comentada como `# v4.6.0` pero ese SHA es el de **v4.3.1** (verificado contra la API de GitHub) | `:1325` |
| `github/codeql-action@f35333b…` comentada `# v3.28.18`: el SHA es el del **objeto de tag anotado** de v3.28.18, no el del commit (`ff0a06e83cb2de871e5a09832bc6a81e7276941f`), lo que contradice la regla de §5.3 "se copia el SHA del commit tag, **no del tree**" | `:1458`, `:1794` vs `:1365` |
| `actions/attest-build-provenance@96b4a1ef…` comentada `# v2.0.0`: el SHA es el del **objeto de tag anotado de `v2`** (tagger 2025-06-11), no de `v2.0.0` | `:1476` |
| `shivammathur/setup-php@efffd0e4…` comentada `# v2.30.3` cuando la etiqueta real es `2.30.3` (sin `v`) | `:1265`, `:1308` |
| `woodruffw/zizmor` (YAML) vs `github.com/zizmorcore/zizmor` (bibliografía): el repositorio se trasladó de organización y las dos referencias no coinciden | `:1360` vs `:3565` |
| La versión de Wazuh es "4.x" en el índice y `wazuh-agent` "4.x/apt" en el repo, sin fijar la versión exacta | `:3`, `:2492` |
| `SANCTUM_TOKEN_PREFIX=` se presenta como variable de entorno de Sanctum; no es una clave de configuración de Sanctum 13.x | `:127` |
| §1.11 referenciada como "§1.11" para el canal de logging cuando esa sección es la de hashing (ya en 6.3) | `:112`, `:199` |
| `[CIS Ubuntu Linux 24.04 Benchmark v1.0.0, 2024-08-15] — community.cisco.com/cis-benchmarks`: la URL no corresponde al CIS Benchmark (Cisco no aloja benchmarks CIS) | `:3583` |
| Los marcadores de cambio `[ADD]`, `[CHANGE]`, `[REMOVE]`, `[FIX]` se declaran como convención en `:10` y **no se usan ni una vez** en las 3603 líneas | `:10` |
| El índice no incluye §0 (Prólogo) ni §13 (Cierre), y el documento tiene 13 secciones numeradas más apéndices, pese a anunciar 12 entradas | `:16-27` vs `:31`, `:3598` |
| El cuerpo contiene texto en **chino** sin traducir: "信息公开" (`:1048`) y "nunca暴露 a internet" (`:2845`); también "sin曲线参数不明" (`:3448`) | `:1048`, `:2845`, `:3448` |
| El cierre afirma que el capítulo va "desde el cifrado de cookies … hasta la rotación trimestral de la passphrase de restic", pero §8/§10 fijan rotación **anual** para `BACKUP_PASSPHRASE` | `:3600` vs `:3522` |
| Fechas de verificación de referencias: todas `2026-09-22`, posteriores al `2025-04-03` de NIST y al `2024-01-15` de OWASP; se cita `laravel.com/docs/13.x/deployment, 2026-09-22` y `getcomposer.org/doc, 2026-08-12` como fuentes ya verificadas | `:88`, `:90`, `:3541-3550` |
| `dhi.io/php:8.4-fpm-alpine3.22` y `dhi.io/composer:2.7`: **no verificable** sin credenciales del registro DHI; el catálogo DHI público publica rutas del tipo `php/alpine-3.24/8.2-fpm`, lo que no confirma la etiqueta exacta que usa el capítulo | `:1920`, `:1946` |
| `#[UseResourceAttributes]` y `php artisan make:resource --json-api` se presentan como nativos de Laravel 13 `[F]`; **no verificable** con las fuentes citadas en el propio capítulo | `:851`, `:888` |
| `APP_KEY` "se deriva con PBKDF2/scrypt interno": Laravel no deriva la clave de cifrado con PBKDF2/scrypt (se usa tal cual tras decodificar base64) | `:163` |
| "Laravel 13 incluye dos *rate limiters* por defecto (`'api'` y `'login'`)" — el limiter `login` es una convención de aplicación, no un limitador incluido por el framework | `:327` |

---

## 7. Hechos verificados (literal, cita `capitulo-05.md:línea`)

1. La pila objetivo del capítulo es "Laravel 13.x (API JSON:API), Vue 3 + TypeScript (SPA desacoplada), Nginx 1.29, PHP 8.4-FPM, PostgreSQL 16, Redis 7, Wazuh 4.x, GitHub Actions, DigitalOcean Droplet + Spaces" — `capitulo-05.md:3`
2. Las acciones de GitHub "**siempre** pinneadas por SHA-256 de 40 caracteres" — `capitulo-05.md:9`
3. El comando de producción exige `composer install --no-dev --no-interaction --no-progress --optimize-autoloader --classmap-authoritative --prefer-dist` seguido de `config:cache`, `route:cache`, `event:cache`, `view:cache` — `capitulo-05.md:71-86`
4. Se prohíbe `--prefer-source` en CI/CD; "Solo `--prefer-dist` se acepta" — `capitulo-05.md:90`
5. `.env` de producción: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://api.example.tld`, `APP_TIMEZONE=UTC`, `APP_LOCALE=es`, `LOG_CHANNEL=stack`, `LOG_STACK=single,security`, `LOG_LEVEL=warning` — `capitulo-05.md:99-114`
6. `.env`: `SESSION_DRIVER=redis`, `SESSION_LIFETIME=120`, `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`, `SESSION_DOMAIN=.example.tld` — `capitulo-05.md:117-122`
7. `.env`: `SANCTUM_STATEFUL_DOMAINS=app.example.tld`, `SANCTUM_GUARD=web`, `TRUSTED_PROXIES=*` (contradicho en `:263`), `DB_CONNECTION=pgsql`, `DB_HOST=10.0.0.5`, `DB_PORT=5432`, `DB_DATABASE=app_prod`, `DB_USERNAME=app_prod` — `capitulo-05.md:125-138`
8. Generación de clave: `php artisan key:generate --show`, "NUNCA pegar manualmente una clave; SIEMPRE usar key:generate" — `capitulo-05.md:167-171`
9. Rotación de `APP_KEY` en 5 pasos con `APP_PREVIOUS_KEYS`, con ventana de retención recomendada de 90 días y `DELETE FROM personal_access_tokens;` al final — `capitulo-05.md:179-189`
10. `APP_KEY` prohibida en código fuente, `.env.example`, logs/tickets/Slack; permitida en `.env` (600), GitHub Actions Secrets (rotación trimestral) y DO App Platform — `capitulo-05.md:193-200`
11. "**NO** usar `TRUSTED_PROXIES=*` (es la configuración 'rápida' pero rompe toda la protección contra *header forgery*)" — `capitulo-05.md:263`
12. `bootstrap/app.php` lista 22 CIDR de Cloudflare (IPv4+IPv6) más `10.0.0.0/8` para el DO Load Balancer en `trustProxies(at: […])` — `capitulo-05.md:234-259`
13. `TrustHosts` se configura con `$middleware->trustHosts(at: ['api.example.tld','app.example.tld'])` y hace responder 400 ante un `Host` ajeno — `capitulo-05.md:267-277`
14. CORS estricto: `allowed_origins = ['https://app.example.tld','capacitor://localhost']`, `allowed_origins_patterns = []`, `allowed_headers = ['Authorization','Content-Type','Accept']`, `max_age = 600`, `supports_credentials = true` — `capitulo-05.md:286-296`
15. Rutas API con token Bearer "sin CSRF"; rutas web con CSRF y Sanctum stateful — `capitulo-05.md:307-310`
16. Limitador `api`: 120/min por `$user->id`, 30/min por IP si anónimo — `capitulo-05.md:334-339`
17. Limitador `login`: `Limit::perMinute(5)->by($request->input('email').'|'.$request->ip())` y `Limit::perMinute(20)->by($request->ip())` — `capitulo-05.md:341-346`
18. Limitador `export`: `Limit::perMinute(2)` por usuario o IP — `capitulo-05.md:348-350`
19. Hashing: `'driver' => 'argon2id'` con `memory => 65536`, `threads => 1`, `time => 4`, `verify => true` — `capitulo-05.md:361-368`
20. `bcrypt` queda "solo como fallback para usuarios pre-existentes" con *rehash-on-login* — `capitulo-05.md:372`
21. Mass assignment: `$fillable = ['name','email','password']`, `$hidden = ['password','remember_token','two_factor_secret']`, y `role`/`is_admin` "NUNCA en `$fillable`" — `capitulo-05.md:384-391`
22. RBAC: Gates en `AuthServiceProvider` para `view-dashboard-admin`/`impersonate`, Policies por modelo, `spatie/laravel-permission` **v6.x** para roles y permisos en BD — `capitulo-05.md:416-418`
23. `Gate::before` devuelve `true` solo para `hasRole('super-admin')` y ese rol debe ser único y estar registrado en código — `capitulo-05.md:438-445`
24. `spatie/laravel-permission` no se usa para abilities de Sanctum: "Mezclar ambos es una fuente clásica de bugs" — `capitulo-05.md:446`
25. Canal `security` = stack de `security-file` + `security-syslog`; el fichero usa `RotatingFileHandler` con `maxFiles => 30` y `JsonFormatter`; el syslog usa `facility => LOG_LOCAL5` y `level => warning` — `capitulo-05.md:454-474`
26. Quince eventos de seguridad obligatorios, incluidos `auth.login.failed`, `role.changed`, `rbac.denied`, `rate_limit.exceeded` y `file.upload` — `capitulo-05.md:500-518`
27. `AppServiceProvider::boot()` lanza `RuntimeException` en producción si `app.debug` es true, si `app.key` está vacío o si `app.url` no empieza por `https://` — `capitulo-05.md:524-537`
28. Sanctum usa **abilities**, no scopes, y en CI un test `AuthAbilitiesTest` genera tokens con `[]` y verifica 403 — `capitulo-05.md:546-614`
29. El *plain text* token se devuelve UNA sola vez; `personal_access_tokens.token` guarda solo el hash SHA-256 — `capitulo-05.md:590`
30. Refresh tokens: access TTL 15 min, refresh TTL 30 días con ability `refresh:token`, `expires_in => 900`, rotación en cada refresh; el refresh token vive en cookie `httpOnly`+`Secure`+`SameSite=Strict` — `capitulo-05.md:677-713`
31. MFA TOTP con `pragmarx/google2fa-laravel`, `config/google2fa.php` con `'algorithm' => 'SHA256'`, `'period' => 30`, `'window' => 1` — `capitulo-05.md:754-817`
32. JSON:API con `jsonapi.version = "1.1"`, `Content-Type: application/vnd.api+json` en toda respuesta y `errors`/`data` mutuamente excluyentes — `capitulo-05.md:855-882`
33. `VALIDATE JSON:API`: `MAX_PAGE_SIZE = 100` y listas blancas `ALLOWED_INCLUDES = ['author','comments','tags']`, `ALLOWED_SORTS = ['created-at','-created-at','title']` — `capitulo-05.md:984-986`
34. Vite en producción: `target: 'es2022'`, `sourcemap: false` (⚠️ CRÍTICO), `minify: 'esbuild'`, `cssMinify: 'lightningcss'`, `host: '127.0.0.1'`, `legalComments: 'none'` — `capitulo-05.md:1027-1042`
35. TanStack Query: nunca reintentar 4xx, `retryDelay: Math.min(1000 * 2 ** attempt, 8000)`, `staleTime: 30_000`, `gcTime: 5 * 60_000` — `capitulo-05.md:1129-1136`
36. Trivy rompe el build con `severity: 'HIGH,CRITICAL'` y `exit-code: '1'`; Grype con `severity-cutoff: high` y `fail-build: true` — `capitulo-05.md:1453-1454`, `:1466-1467`
37. `concurrency: group: deploy-prod`, `cancel-in-progress: false  # nunca cancelar un deploy a producción en curso` — `capitulo-05.md:1593-1595`
38. La clave SSH de deploy se restringe con `command=`, `from=` (3 CIDR), `no-port-forwarding`, `no-X11-forwarding`, `no-agent-forwarding`, `no-pty` — `capitulo-05.md:1722`
39. Branch protection: 2 aprobaciones, commits firmados, historial lineal, incluir administradores, sin force push, sin borrado, push restringido a `maintain` — `capitulo-05.md:1873-1888`
40. Docker: `cap_drop: [ALL]` en los seis servicios; `read_only: true` en api, worker y nginx; `no-new-privileges:true` en todos; solo postgres recibe `CHOWN/SETUID/SETGID/DAC_OVERRIDE` y nginx `CHOWN/SETUID/SETGID/NET_BIND_SERVICE` — `capitulo-05.md:2290-2470`
41. Límites de recursos: api `cpus 2.0 / memory 512M` (reservas 0.5/256M), worker `1.5/384M`, postgres `2.0/1G`, redis `0.5/320M` — `capitulo-05.md:2320-2440`
42. `php.ini`: `memory_limit = 256M`, `upload_max_filesize = 15M`, `post_max_size = 20M`, `session.use_strict_mode = On`, `opcache.validate_timestamps = 0`, `opcache.jit = tracing` — `capitulo-05.md:2068-2095`
43. FPM pool: `pm.max_children = 50`, `pm.max_requests = 1000`, `request_terminate_timeout = 30s`, `open_basedir = /var/www/html:/tmp`, `disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_multi_exec,parse_ini_file,show_source` — `capitulo-05.md:2107-2130`
44. Healthcheck del contenedor: `HEALTHCHECK --interval=30s --timeout=3s --start-period=20s --retries=3` — `capitulo-05.md:2045-2046`
45. Endpoint `/api/health` debe devolver `{"data":{"type":"health","attributes":{"status":"ok"}}}`, no requerir auth, no escribir a BD y estar excluido del rate-limiter — `capitulo-05.md:2198-2202`
46. Wazuh FIM: `frequency 21600` (6 h), `alert_new_files` sí, `whodata="yes"` en `/etc/nginx` y `/etc/ssh`, `file_limit 200000`, sincronización cada 5 m con `max_eps 10` — `capitulo-05.md:2516-2568`
47. `active-response` de Wazuh: `firewall-drop` local con `rules_id 5710,5720,5730` y `timeout 600` — `capitulo-05.md:2602-2608`
48. Reglas custom: 100002 = level 12, `frequency 5`, `timeframe 600`; 100004 = level 8; 100007 = level 12; 100009 = level 12 — `capitulo-05.md:2633-2684`
49. Prometheus: `scrape_interval: 15s`, `evaluation_interval: 30s`, exporters en 9100 (node), 9187 (postgres), 9121 (redis), 9113 (nginx), 9253 (php-fpm) — `capitulo-05.md:2816-2842`
50. Fail2ban: jail `api-login` con `findtime 600`, `maxretry 10`, `bantime 3600`; jail `sshd` con `maxretry 5`, `findtime 600`, `bantime 3600` — `capitulo-05.md:2939-2956`
51. Backups: política **3-2-1-1-0**; restic con **AES-256-CTR + Poly1305-AES** y derivación **scrypt**; retención `--keep-daily 7 --keep-weekly 4 --keep-monthly 6`; cron `17 2 * * *` — `capitulo-05.md:2967-2973`, `:2997`, `:3038-3043`, `:3066`
52. Drill semanal: dominio `17 4 * * 0` con `restic check --read-data-subset=10%` y restauración de 3 rutas críticas — `capitulo-05.md:3106-3129`
53. Runbook: cuatro fases NIST SP 800-61r3 y cuatro casos (A SSH comprometido, B RCE/web shell, C DDoS, D Ransomware) — `capitulo-05.md:3151-3156`, `:3170`, `:3262`, `:3301`, `:3324`
54. Caso C: WAF con "10 req/min por IP global" y escalado a DO support "si el ataque es >10 Gbps" — `capitulo-05.md:3318-3320`
55. Caso D: "**NO pagar**", no apagar el droplet, notificar a Legal en 72 h (RGPD Art. 33) — `capitulo-05.md:3335-3340`
56. Post-mortem **blameless** con severidad SEV1|SEV2|SEV3 y root cause por *5 Whys* — `capitulo-05.md:3351`, `:3374-3379`, `:3395`
57. Rotación trimestral de `APP_KEY` (con `APP_PREVIOUS_KEYS` por 90 días), DB passwords, Cloudflare API token, DO API token, deploy keys SSH y GitHub PATs — `capitulo-05.md:3407-3413`
58. Inventario de 27 secretos con rotación y almacenamiento; `BACKUP_PASSPHRASE` rota "Anual + on key personnel change" — `capitulo-05.md:3502-3530`
59. Estándares citados como base: NIST SP 800-61r3, NIST SP 800-63B, OWASP API Security Top 10 2023, OWASP Top 10 2021, RGPD Art. 33, CIS Ubuntu Linux 24.04 Benchmark v1.0.0 — `capitulo-05.md:3578-3583`
60. Los 18 SHA de acciones tienen exactamente 40 caracteres hexadecimales (verificado por conteo sobre el fichero) — `capitulo-05.md:1262-1802`

### Verificaciones externas realizadas sobre el contenido del capítulo
- `actions/checkout@11bd71901bbe5b1630ceea73d27597364c9af683` **sí** corresponde a `v4.2.2` (API de GitHub) — el pin de `capitulo-05.md:1262` es correcto.
- `actions/upload-artifact@5d5d22a31266ced268874388b861e4b58bb5c2f3` corresponde en realidad a **`v4.3.1`**, no a `v4.6.0` como dice el comentario — `capitulo-05.md:1325`.
- `github/codeql-action@f35333b910470a5408cb081b68f0701254a7d27b` es el SHA del **objeto de tag anotado** de `v3.28.18` (commit real `ff0a06e83cb2de871e5a09832bc6a81e7276941f`) — `capitulo-05.md:1458`, `:1794`.
- `actions/attest-build-provenance@96b4a1ef7235a096b17240c259729fdd70c83d45` es el SHA del **objeto de tag `v2`** (tagger 2025-06-11; commit real `e8998f949152b193b063cb0ec769d69d929409be`), no de `v2.0.0` — `capitulo-05.md:1476`.
- `gitleaks/gitleaks-action`, `zizmorcore/zizmor`, `anchore/scan-action` (`v7.4.2`), `slackapi/slack-github-action`, `docker/*`, `aquasecurity/trivy-action`, `actions/cache`, `actions/setup-node`, `actions/setup-go` y `shivammathur/setup-php` (etiqueta `2.30.3`) coinciden con sus etiquetas comentadas.
