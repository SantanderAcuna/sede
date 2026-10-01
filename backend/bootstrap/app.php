<?php

declare(strict_types=1);

use App\Support\Api\Respuesta;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        //
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
        // Las dos primeras devuelven el mismo texto a propósito: distinguir «no
        // existe» de «no está publicado» revelaría qué trámites tiene la Entidad
        // sin publicar.
        $exceptions->render(function (ModelNotFoundException $excepcion, Request $peticion): ?JsonResponse {
            return $peticion->is('api/*') ? Respuesta::error('El recurso no fue encontrado.', 404) : null;
        });

        $exceptions->render(function (NotFoundHttpException $excepcion, Request $peticion): ?JsonResponse {
            return $peticion->is('api/*') ? Respuesta::error('El recurso no fue encontrado.', 404) : null;
        });

        $exceptions->render(function (ValidationException $excepcion, Request $peticion): ?JsonResponse {
            return $peticion->is('api/*')
                ? Respuesta::error('Error de validación.', 422, $excepcion->errors())
                : null;
        });
    })->create();
