<?php

declare(strict_types=1);

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
    // El catálogo de trámites. Las dos operaciones son públicas —el contrato las
    // declara con `security: []`— y por eso no llevan middleware de sesión.
    Route::get('/tramites', [TramiteController::class, 'listar'])->name('tramites.listar');
    Route::get('/tramites/{slug}', [TramiteController::class, 'mostrar'])->name('tramites.mostrar');
});
