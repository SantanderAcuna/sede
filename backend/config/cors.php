<?php

declare(strict_types=1);

use App\Support\Cors\OrigenesPermitidos;

/*
 * CORS.
 *
 * El cliente de la sede vive en otro origen que la API —el sitio y el panel se
 * sirven aparte—, así que sin esta lista el navegador descarta las respuestas
 * antes de que JavaScript pueda verlas, aunque el servidor las haya enviado.
 *
 * Los orígenes se listan, nunca con `*`: `supports_credentials` está en `true`
 * porque la sesión viaja en cookie, y la especificación prohíbe combinar
 * credenciales con un comodín. Un `*` aquí no abriría la API «un poco», la
 * dejaría abierta a cualquier origen que pida con cookies.
 *
 * `exposed_headers` declara los encabezados de límite de tasa: son los únicos
 * que JavaScript puede leer de la respuesta, y sin ellos el cliente no sabe
 * cuándo volver después de un `429`.
 */
return [

    // Sólo la API y el punto donde Sanctum deja la cookie CSRF. El resto de las
    // rutas —sondas de salud incluidas— no se expone a otros orígenes.
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Ver `App\Support\Cors\OrigenesPermitidos`: en producción solo los orígenes
    // declarados en SEDE_ORIGENES_PERMITIDOS / FRONTEND_URL; los de desarrollo
    // (`localhost`, `0.0.0.0`) solo en `local` y `testing`.
    'allowed_origins' => OrigenesPermitidos::para(
        (string) env('APP_ENV', 'production'),
        (string) env('SEDE_ORIGENES_PERMITIDOS', ''),
        (string) env('FRONTEND_URL', ''),
    ),

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'Retry-After'],

    'supports_credentials' => true,

    // Sin caché de preflight: la lista de orígenes puede cambiar entre
    // despliegues y una respuesta cacheada seguiría autorizando el origen viejo.
    'max_age' => 0,

];
