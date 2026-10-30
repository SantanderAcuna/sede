<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ArchivoController;
use App\Http\Controllers\Api\V1\AuditoriaController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BusquedaController;
use App\Http\Controllers\Api\V1\ConsentimientoController;
use App\Http\Controllers\Api\V1\EntidadController;
use App\Http\Controllers\Api\V1\IdentidadController;
use App\Http\Controllers\Api\V1\PanelPoliticaController;
use App\Http\Controllers\Api\V1\PoliticaController;
use App\Http\Controllers\Api\V1\TramiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API de la sede — versión 1
|--------------------------------------------------------------------------
|
| Ninguna ruta vive aquí sin su operación declarada en `contract/openapi.yaml`:
| el contrato es la fuente de verdad del intercambio HTTP y la prueba de deriva
| comprueba que no sobre ni falte ninguna.
|
| El prefijo `/v1` y el nombre de grupo ya los aplica el arranque de la
| aplicación; aquí sólo se declaran las rutas dentro de la versión.
|
*/

Route::prefix('v1')->name('v1.')->group(function (): void {
    // Datos institucionales — pública (sin autenticación)
    Route::get('/entidad', [EntidadController::class, 'mostrar'])
        ->name('entidad.mostrar')
        ->withoutMiddleware('auth:sanctum');

    // Registro del consentimiento de cookies — público (lo llama el navegador del
    // ciudadano) y con límite propio por IP: no guarda nada identificable, pero
    // sigue siendo una escritura abierta a internet.
    Route::post('/consentimientos', [ConsentimientoController::class, 'store'])
        ->name('consentimientos.store')
        ->withoutMiddleware('auth:sanctum')
        ->middleware('throttle:consentimientos');

    // Políticas de la sede — públicas. El texto vive en base de datos y se edita desde el panel.
    Route::get('/politicas', [PoliticaController::class, 'index'])
        ->name('politicas.index')
        ->withoutMiddleware('auth:sanctum');
    Route::get('/politicas/{slug}', [PoliticaController::class, 'show'])
        ->name('politicas.show')
        ->withoutMiddleware('auth:sanctum');

    // Identidad: top-bar, footer y menú — públicos
    Route::get('/identidad/top-bar', [IdentidadController::class, 'topBar'])->name('identidad.top-bar');
    Route::get('/identidad/footer', [IdentidadController::class, 'footer'])->name('identidad.footer');
    Route::get('/identidad/menu', [IdentidadController::class, 'menu'])->name('identidad.menu');

    // El catálogo de trámites. Las dos operaciones son públicas —el contrato las
    // declara con `security: []`— y por eso no llevan middleware de sesión.
    Route::get('/tramites', [TramiteController::class, 'listar'])->name('tramites.listar');
    Route::get('/tramites/{slug}', [TramiteController::class, 'mostrar'])->name('tramites.mostrar');

    // La búsqueda de la sede (T-22). Las dos operaciones son públicas y comparten un
    // límite por IP: se llaman mientras el ciudadano escribe, así que son las más
    // expuestas a un uso abusivo. Lo escrito no se registra en la aplicación.
    Route::get('/busqueda/sugerencias', [BusquedaController::class, 'sugerir'])
        ->name('busqueda.sugerir')
        ->withoutMiddleware('auth:sanctum')
        ->middleware('throttle:busqueda');
    Route::get('/buscar', [BusquedaController::class, 'buscar'])
        ->name('busqueda.buscar')
        ->withoutMiddleware('auth:sanctum')
        ->middleware('throttle:busqueda');

    // Panel — Auth (requiere autenticación Sanctum)
    Route::prefix('panel')->name('panel.')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login'])
            ->name('login')
            ->middleware('throttle:login');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth:sanctum');
        Route::get('/perfil', [AuthController::class, 'perfil'])->name('perfil')->middleware('auth:sanctum');

        // Auditoría — log de auditoría (requiere autenticación + permiso). Sin el
        // permiso, cualquier cuenta autenticada podía leer quién hizo qué (H-07).
        Route::get('/auditoria', [AuditoriaController::class, 'index'])
            ->name('auditoria.index')
            ->middleware('auth:sanctum')
            ->middleware('can:auditoria.ver');

        // Entidad — gestión de datos institucionales (requiere autenticación + permiso)
        Route::patch('/entidad', [EntidadController::class, 'actualizar'])
            ->name('entidad.actualizar')
            ->middleware('auth:sanctum')
            ->middleware('can:entidad.gestionar');

        // Políticas — edición del texto (requiere autenticación + permiso)
        Route::get('/politicas', [PanelPoliticaController::class, 'index'])
            ->name('politicas.index')
            ->middleware('auth:sanctum')
            ->middleware('can:politicas.gestionar');
        Route::patch('/politicas/{slug}', [PanelPoliticaController::class, 'update'])
            ->name('politicas.update')
            ->middleware('auth:sanctum')
            ->middleware('can:politicas.gestionar');

        // Archivos — gestión de archivos subidos (requiere autenticación + permiso)
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/entidad/{modelUuid}/archivos', [ArchivoController::class, 'index'])
                ->name('archivos.index')
                ->middleware('can:archivos.ver');
            Route::post('/entidad/{modelUuid}/archivos', [ArchivoController::class, 'store'])
                ->name('archivos.store')
                ->middleware('can:archivos.crear');
            Route::get('/archivos/{uuid}', [ArchivoController::class, 'show'])
                ->name('archivos.show')
                ->middleware('can:archivos.ver');
            Route::delete('/archivos/{uuid}', [ArchivoController::class, 'destroy'])
                ->name('archivos.destroy')
                ->middleware('can:archivos.eliminar');
        });
    });
});
