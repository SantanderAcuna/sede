<?php

declare(strict_types=1);

use App\Http\Controllers\PoliticaDescargaController;
use App\Http\Controllers\PoliticaDocumentoOficialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Descargas públicas
|--------------------------------------------------------------------------
|
| La descarga de cada política en ODT y PDF/A (T-20, AC-20.5). Su dirección
| pública es `/politicas/<slug>.odt|pdf`, la que enlaza la página de cada
| política, y nginx la envía a la aplicación; por eso no vive bajo `/api`.
|
| Se carga en `bootstrap/app.php`, fuera del grupo `web`, igual que las sondas
| de salud: el grupo `web` inicia una sesión y pondría una cookie en una
| descarga pública, y la política de cookies del sitio afirma que no instala
| ninguna para funcionar.
|
*/

Route::get('/politicas/{slug}.{formato}', PoliticaDescargaController::class)
    ->where('slug', '[a-z0-9-]+')
    ->where('formato', 'odt|pdf')
    ->name('politicas.descarga');

// El documento oficial (acto administrativo o texto aprobado) que se adjunta a la
// política desde el panel, guardado en la tabla común de archivos.
Route::get('/politicas/{slug}/documento', PoliticaDocumentoOficialController::class)
    ->where('slug', '[a-z0-9-]+')
    ->name('politicas.documento');
