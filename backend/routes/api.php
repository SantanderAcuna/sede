<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EntidadController;
use App\Http\Controllers\Api\V1\IdentidadController;
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

    // Identidad: top-bar, footer y menú — públicos
    Route::get('/identidad/top-bar', [IdentidadController::class, 'topBar'])->name('identidad.top-bar');
    Route::get('/identidad/footer', [IdentidadController::class, 'footer'])->name('identidad.footer');
    Route::get('/identidad/menu', [IdentidadController::class, 'menu'])->name('identidad.menu');

    // El catálogo de trámites. Las dos operaciones son públicas —el contrato las
    // declara con `security: []`— y por eso no llevan middleware de sesión.
    Route::get('/tramites', [TramiteController::class, 'listar'])->name('tramites.listar');
    Route::get('/tramites/{slug}', [TramiteController::class, 'mostrar'])->name('tramites.mostrar');

    // Panel — Auth (requiere autenticación Sanctum)
    Route::prefix('panel')->name('panel.')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth:sanctum');
        Route::get('/perfil', [AuthController::class, 'perfil'])->name('perfil')->middleware('auth:sanctum');
    });
});
