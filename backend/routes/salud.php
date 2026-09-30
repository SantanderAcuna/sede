<?php

declare(strict_types=1);

use App\Http\Controllers\Api\SaludController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sondas de salud
|--------------------------------------------------------------------------
|
| Deliberadamente FUERA de la API versionada y sin middleware de sesión ni de
| límite de tasa: las llama el orquestador cada pocos segundos y no son parte
| del contrato público.
|
| No se exponen a Internet. El punto de entrada sólo las atiende desde la red
| interna; publicar el estado interno de la infraestructura es regalar
| reconocimiento.
|
*/

// Vida: responde aunque una dependencia esté caída. Si esto falla, el proceso
// está muerto y hay que reiniciarlo.
Route::get('/health', [SaludController::class, 'vida'])->name('salud.vida');

// Preparación: responde sólo si la base, la caché y el almacenamiento responden.
// Si esto falla, el proceso está vivo pero no puede atender.
Route::get('/ready', [SaludController::class, 'preparacion'])->name('salud.preparacion');
