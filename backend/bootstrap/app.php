<?php

declare(strict_types=1);

use App\Http\Middleware\CabecerasDeSeguridad;
use App\Support\Api\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Las sondas viven fuera de la API versionada y sin el grupo de
            // middleware de la API: no son parte del contrato público.
            Route::group([], base_path('routes/salud.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Las cabeceras de seguridad se añaden a todas las respuestas —también a
        // las sondas— porque un `nosniff` que sólo proteja unas rutas deja el
        // resto a merced del navegador, y el coste de la cabecera de más es cero.
        $middleware->append(CabecerasDeSeguridad::class);

        // El limitador `api` se registra en `AppServiceProvider`, pero Laravel 11+
        // no lo aplica al grupo de la API por su cuenta: sin esta llamada el
        // limitador existiría y no limitaría nada.
        $middleware->throttleApi();

        // Habilita Sanctum para autenticación con cookie en SPAs (panel Vue).
        // Registra EnsureFrontendRequestsAreStateful en el grupo api: las peticiones
        // desde orígenes en SANCTUM_STATEFUL_DOMAINS reciben una cookie CSRF y pueden
        // usar autenticación por sesión. Sin esto, todo request desde el panel
        // devuelve 401 aunque el token sea válido.
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // El contrato declara el error como una respuesta con la misma forma que
        // las buenas —`{success, message, data, errors}`—, no como el cuerpo
        // suelto que Laravel devuelve por defecto. Se traduce en un solo sitio
        // para que cualquier operación futura herede la forma y no haya dos
        // maneras de responder mal según quién escriba el controlador.
        //
        // La guarda `api/*` es deliberada: fuera de la API la aplicación no
        // responde JSON —las sondas de salud incluidas—, y devolver un sobre aquí
        // convertiría un error de una página en algo que su cliente no sabe leer.
        //
        // Las dos primeras devuelven el mismo texto a propósito: distinguir «no
        // existe» de «no está publicado» revelaría qué trámites tiene la Entidad
        // sin publicar.
        $exceptions->render(function (ModelNotFoundException $excepcion, Request $peticion): ?JsonResponse {
            return $peticion->is('api/*') ? ApiResponse::notFound('El recurso no fue encontrado.') : null;
        });

        $exceptions->render(function (NotFoundHttpException $excepcion, Request $peticion): ?JsonResponse {
            return $peticion->is('api/*') ? ApiResponse::notFound('El recurso no fue encontrado.') : null;
        });

        $exceptions->render(function (ValidationException $excepcion, Request $peticion): ?JsonResponse {
            return $peticion->is('api/*')
                ? ApiResponse::validationError($excepcion->errors())
                : null;
        });

        // Los tres estados que la guía pide y que faltaban: autenticación,
        // autorización y límite de peticiones. Sin ellos, un `401`, un `403` o un
        // `429` salían con el cuerpo por defecto de Laravel y el cliente tenía que
        // adivinar el sobre según el código, que es justo lo que el contrato evita.
        $exceptions->render(function (AuthenticationException $excepcion, Request $peticion): ?JsonResponse {
            return $peticion->is('api/*') ? ApiResponse::unauthorized() : null;
        });

        $exceptions->render(function (AuthorizationException $excepcion, Request $peticion): ?JsonResponse {
            return $peticion->is('api/*') ? ApiResponse::forbidden() : null;
        });

        $exceptions->render(function (TooManyRequestsHttpException $excepcion, Request $peticion): ?JsonResponse {
            if (! $peticion->is('api/*')) {
                return null;
            }

            // Las cabeceras traen `Retry-After`, y el contrato las expone en CORS
            // justamente para que el cliente sepa cuándo volver. Un render propio
            // las perdería: Laravel no las copia de la excepción a la respuesta
            // que devuelve el callback.
            return ApiResponse::tooManyRequests()->withHeaders($excepcion->getHeaders());
        });
    })->create();
