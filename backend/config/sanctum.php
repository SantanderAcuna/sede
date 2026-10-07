<?php

declare(strict_types=1);

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    // La conversión es explícita a propósito: env() devuelve bool|string y un valor
    // mal formado en el entorno rompería la aplicación en tiempo de ejecución
    // en lugar de al analizarla.
    'stateful' => explode(',', (string) env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort(),
        // Sanctum::currentRequestHost(),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be checked when
    | Sanctum is trying to authenticate a request. If none of these guards
    | are able to authenticate the request, Sanctum will use the bearer
    | token that's present on an incoming request for authentication.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. This will override any values set in the token's
    | "expires_at" attribute, but first-party sessions are not affected.
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    |
    | Sanctum can prefix new tokens in order to take advantage of numerous
    | security scanning initiatives maintained by open source platforms
    | that notify developers if they commit tokens into repositories.
    |
    | See: https://docs.github.com/en/code-security/secret-scanning/about-secret-scanning
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When authenticating your first-party SPA with Sanctum you may need to
    | customize some of the middleware Sanctum uses while processing the
    | request. You may change the middleware listed below as required.
    |
    */

    'middleware' => [
        // 'authenticate_session' desactivado intencionalmente.
        //
        // El middleware original (\Laravel\Sanctum\Http\Middleware\AuthenticateSession)
        // almacena un hash de la contraseña del usuario en la sesión y lo valida
        // en cada petición: si la contraseña cambió (o si Sanctum cree que cambió),
        // invalida la sesión lanzando AuthenticationException y llamando
        // logoutCurrentDevice() en el guard configurado.
        //
        // En Laravel 13 + Sanctum 4 con `auth.defaults.guard = web`, este middleware
        // falla porque internamente hace Auth::logoutCurrentDevice() que delega
        // al `RequestGuard` de tokens (que no tiene `logout()`), generando
        // BadMethodCallException en cada F5. Peor aún: si Sanctum rota el session_id
        // entre peticiones (comportamiento observado en pruebas), el navegador queda
        // con un session_id que ya no existe en la BD, devolviendo 401.
        //
        // Para una SPA con cookie de sesión, este comportamiento de revocación por
        // cambio de contraseña no compensa la inestabilidad introducida. Si en el
        // futuro se necesita detectar contraseñas cambiadas, se prefiere comparar
        // el hash en un middleware de aplicación explícito que no toque el session_id.
        'authenticate_session' => null,

        'encrypt_cookies' => EncryptCookies::class,
        'validate_csrf_token' => ValidateCsrfToken::class,
    ],

];
