<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ArchivoController;
use App\Http\Controllers\Api\V1\AuditoriaController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\Api\V1\BusquedaController;
use App\Http\Controllers\Api\V1\ConsentimientoController;
use App\Http\Controllers\Api\V1\EntidadController;
use App\Http\Controllers\Api\V1\IdentidadController;
use App\Http\Controllers\Api\V1\ImagenContenidoController;
use App\Http\Controllers\Api\V1\NoticiaController;
use App\Http\Controllers\Api\V1\PanelBannerController;
use App\Http\Controllers\Api\V1\PanelNoticiaController;
use App\Http\Controllers\Api\V1\PanelPoliticaController;
use App\Http\Controllers\Api\V1\PanelRolController;
use App\Http\Controllers\Api\V1\PanelTramiteController;
use App\Http\Controllers\Api\V1\PanelUsuarioController;
use App\Http\Controllers\Api\V1\PoliticaController;
use App\Http\Controllers\Api\V1\TramiteController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

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
    // Las lecturas públicas —entidad, políticas, identidad y trámites— no usan
    // `throttle:api` (60 por minuto) sino `throttle:lecturas-publicas`: el sitio pide
    // dos o tres por cada página que sirve, así que 60 eran 20 a 30 páginas por minuto
    // para todos los que salen por una misma IP. Se quita el general de cada una porque,
    // con dos limitadores, manda el más estricto (ver `config/lecturas.php`).

    // Todo lo público va sin el middleware de «frontend con estado» de Sanctum: el sitio y el panel
    // comparten dominio y, con él, una lectura anónima abría sesión y ponía XSRF-TOKEN y la cookie de
    // sesión antes de que la persona eligiera nada en el banner (AC-26.2). Solo el panel necesita estado.
    Route::withoutMiddleware(EnsureFrontendRequestsAreStateful::class)->group(function (): void {
        // Datos institucionales — pública (sin autenticación)
        Route::get('/entidad', [EntidadController::class, 'mostrar'])
            ->name('entidad.mostrar')
            ->withoutMiddleware(['auth:sanctum', 'throttle:api'])
            ->middleware('throttle:lecturas-publicas');

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
            ->withoutMiddleware(['auth:sanctum', 'throttle:api'])
            ->middleware('throttle:lecturas-publicas');
        Route::get('/politicas/{slug}', [PoliticaController::class, 'show'])
            ->name('politicas.show')
            ->withoutMiddleware(['auth:sanctum', 'throttle:api'])
            ->middleware('throttle:lecturas-publicas');

        // Identidad: top-bar, footer y menú — públicos
        Route::get('/identidad/top-bar', [IdentidadController::class, 'topBar'])
            ->name('identidad.top-bar')
            ->withoutMiddleware('throttle:api')
            ->middleware('throttle:lecturas-publicas');
        Route::get('/identidad/footer', [IdentidadController::class, 'footer'])
            ->name('identidad.footer')
            ->withoutMiddleware('throttle:api')
            ->middleware('throttle:lecturas-publicas');
        Route::get('/identidad/menu', [IdentidadController::class, 'menu'])
            ->name('identidad.menu')
            ->withoutMiddleware('throttle:api')
            ->middleware('throttle:lecturas-publicas');

        // Noticias del home (M01-6, RF-01-013): lecturas públicas. La imagen tiene su
        // propio límite: cada visita al home pide seis y no debe gastar el cupo de las
        // lecturas de datos.
        Route::get('/noticias', [NoticiaController::class, 'index'])
            ->name('noticias.index')
            ->withoutMiddleware(['auth:sanctum', 'throttle:api'])
            ->middleware('throttle:lecturas-publicas');
        Route::get('/noticias/{slug}', [NoticiaController::class, 'show'])
            ->name('noticias.mostrar')
            ->withoutMiddleware(['auth:sanctum', 'throttle:api'])
            ->middleware('throttle:lecturas-publicas');
        // Carrusel de la portada: solo diapositivas vigentes. Misma política de lectura pública que las noticias.
        Route::get('/banners', [BannerController::class, 'listar'])
            ->name('banners.listar')
            ->withoutMiddleware(['auth:sanctum', 'throttle:api'])
            ->middleware('throttle:lecturas-publicas');
        Route::get('/banners/{uuid}/imagen', [BannerController::class, 'imagen'])
            ->name('banners.imagen')
            ->whereUuid('uuid')
            ->withoutMiddleware(['auth:sanctum', 'throttle:api'])
            ->middleware('throttle:imagenes-publicas');

        Route::get('/noticias/{slug}/imagen', [NoticiaController::class, 'imagen'])
            ->name('noticias.imagen')
            ->withoutMiddleware(['auth:sanctum', 'throttle:api'])
            ->middleware('throttle:imagenes-publicas');

        // Imágenes insertadas en el contenido editado en el panel: públicas, con su propio límite.
        Route::get('/contenido/imagenes/{uuid}', [ImagenContenidoController::class, 'show'])
            ->whereUuid('uuid')
            ->name('contenido.imagenes.ver')
            ->withoutMiddleware(['auth:sanctum', 'throttle:api'])
            ->middleware('throttle:imagenes-publicas');

        // El catálogo de trámites. Las dos operaciones son públicas —el contrato las
        // declara con `security: []`— y por eso no llevan middleware de sesión.
        Route::get('/tramites', [TramiteController::class, 'listar'])
            ->name('tramites.listar')
            ->withoutMiddleware('throttle:api')
            ->middleware('throttle:lecturas-publicas');
        Route::get('/tramites/{slug}', [TramiteController::class, 'mostrar'])
            ->name('tramites.mostrar')
            ->withoutMiddleware('throttle:api')
            ->middleware('throttle:lecturas-publicas');
        // La valoración de una ficha — escritura pública con su propio límite por IP.
        Route::post('/tramites/{slug}/valoracion', [TramiteController::class, 'valorar'])
            ->name('tramites.valorar')
            ->withoutMiddleware('throttle:api')
            ->middleware('throttle:valoraciones');

        // La búsqueda de la sede (T-22). Las dos operaciones son públicas y comparten un
        // límite por IP: se llaman mientras el ciudadano escribe, así que son las más
        // expuestas a un uso abusivo. Lo escrito no se registra en la aplicación.
        //
        // `throttle:api` (60 por minuto) se quita de estas dos rutas: el grupo `api` lo trae
        // y, sumado al límite propio, dejaba el tope real de la búsqueda en 60 y no en los 120
        // documentados —se midió en staging: 200 hasta la petición 60 y 429 desde la 61—.
        // Con dos limitadores, el más estricto manda y el configurable no sirve de nada.
        Route::get('/busqueda/sugerencias', [BusquedaController::class, 'sugerir'])
            ->name('busqueda.sugerir')
            ->withoutMiddleware(['auth:sanctum', 'throttle:api'])
            ->middleware('throttle:busqueda');
        Route::get('/buscar', [BusquedaController::class, 'buscar'])
            ->name('busqueda.buscar')
            ->withoutMiddleware(['auth:sanctum', 'throttle:api'])
            ->middleware('throttle:busqueda');
    });

    // Panel — Auth (requiere autenticación Sanctum)
    Route::prefix('panel')->name('panel.')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login'])
            ->name('login')
            ->middleware('throttle:login');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth:sanctum');
        Route::get('/perfil', [AuthController::class, 'perfil'])->name('perfil')->middleware('auth:sanctum');

        // Cada ruta exige el permiso de SU acción (`modulo.accion`): «ver» no da «editar» ni «eliminar».
        // Las Policies de cada modelo lo vuelven a comprobar en el controlador.
        Route::middleware('auth:sanctum')->group(function (): void {
            // Auditoría — quién hizo qué (H-07: sin el permiso, cualquier cuenta lo leía).
            Route::get('/auditoria', [AuditoriaController::class, 'index'])
                ->name('auditoria.index')->middleware('can:auditoria.ver');

            // Entidad — datos institucionales.
            Route::patch('/entidad', [EntidadController::class, 'actualizar'])
                ->name('entidad.actualizar')->middleware('can:entidad.editar');

            // Políticas — edición del texto.
            Route::get('/politicas', [PanelPoliticaController::class, 'index'])
                ->name('politicas.index')->middleware('can:politicas.ver');
            Route::patch('/politicas/{slug}', [PanelPoliticaController::class, 'update'])
                ->name('politicas.update')->middleware('can:politicas.editar');

            // Trámites — alta, edición, eliminación y publicación.
            Route::get('/tramites', [PanelTramiteController::class, 'index'])
                ->name('panel.tramites.index')->middleware('can:tramites.ver');
            Route::post('/tramites', [PanelTramiteController::class, 'store'])
                ->name('panel.tramites.store')->middleware('can:tramites.crear');
            Route::get('/tramites/{uuid}', [PanelTramiteController::class, 'show'])
                ->name('panel.tramites.show')->middleware('can:tramites.ver');
            Route::patch('/tramites/{uuid}', [PanelTramiteController::class, 'update'])
                ->name('panel.tramites.update')->middleware('can:tramites.editar');
            Route::delete('/tramites/{uuid}', [PanelTramiteController::class, 'destroy'])
                ->name('panel.tramites.destroy')->middleware('can:tramites.eliminar');
            // Las dependencias responsables, para el selector del formulario de trámites.
            Route::get('/dependencias', [PanelTramiteController::class, 'dependencias'])
                ->name('panel.tramites.dependencias')->middleware('can:tramites.ver');

            // Carrusel de la portada (banners).
            Route::get('/banners', [PanelBannerController::class, 'index'])
                ->name('panel.banners.index')->middleware('can:banners.ver');
            Route::post('/banners', [PanelBannerController::class, 'store'])
                ->name('panel.banners.store')->middleware('can:banners.crear');
            Route::put('/banners/orden', [PanelBannerController::class, 'ordenar'])
                ->name('panel.banners.orden')->middleware('can:banners.editar');
            Route::get('/banners/{uuid}', [PanelBannerController::class, 'show'])
                ->name('panel.banners.show')->middleware('can:banners.ver');
            Route::patch('/banners/{uuid}', [PanelBannerController::class, 'update'])
                ->name('panel.banners.update')->middleware('can:banners.editar');
            Route::delete('/banners/{uuid}', [PanelBannerController::class, 'destroy'])
                ->name('panel.banners.destroy')->middleware('can:banners.eliminar');
            Route::post('/banners/{uuid}/imagen', [PanelBannerController::class, 'subirImagen'])
                ->name('panel.banners.imagen.subir')->middleware('can:banners.editar');
            Route::get('/banners/{uuid}/imagen', [PanelBannerController::class, 'imagen'])
                ->name('panel.banners.imagen')->middleware('can:banners.ver');

            // Usuarios, roles y permisos. Las reglas contra la escalada de privilegios viven en los servicios.
            Route::get('/usuarios', [PanelUsuarioController::class, 'index'])
                ->name('panel.usuarios.index')->middleware('can:usuarios.ver');
            Route::post('/usuarios', [PanelUsuarioController::class, 'store'])
                ->name('panel.usuarios.store')->middleware('can:usuarios.crear');
            Route::get('/usuarios/{uuid}', [PanelUsuarioController::class, 'show'])
                ->name('panel.usuarios.show')->middleware('can:usuarios.ver');
            Route::patch('/usuarios/{uuid}', [PanelUsuarioController::class, 'update'])
                ->name('panel.usuarios.update')->middleware('can:usuarios.editar');
            Route::get('/permisos', [PanelRolController::class, 'permisos'])
                ->name('panel.permisos.index')->middleware('can:roles.ver');
            Route::get('/roles', [PanelRolController::class, 'index'])
                ->name('panel.roles.index')->middleware('can:roles.ver');
            Route::post('/roles', [PanelRolController::class, 'store'])
                ->name('panel.roles.store')->middleware('can:roles.crear');
            Route::get('/roles/{id}', [PanelRolController::class, 'show'])->whereNumber('id')
                ->name('panel.roles.show')->middleware('can:roles.ver');
            Route::patch('/roles/{id}', [PanelRolController::class, 'update'])->whereNumber('id')
                ->name('panel.roles.update')->middleware('can:roles.editar');
            Route::delete('/roles/{id}', [PanelRolController::class, 'destroy'])->whereNumber('id')
                ->name('panel.roles.destroy')->middleware('can:roles.eliminar');

            // Noticias — alta, edición, eliminación y publicación.
            Route::get('/noticias', [PanelNoticiaController::class, 'index'])
                ->name('noticias.index')->middleware('can:noticias.ver');
            Route::post('/noticias', [PanelNoticiaController::class, 'store'])
                ->name('noticias.store')->middleware('can:noticias.crear');
            Route::get('/noticias/{uuid}', [PanelNoticiaController::class, 'show'])
                ->name('noticias.show')->middleware('can:noticias.ver');
            Route::patch('/noticias/{uuid}', [PanelNoticiaController::class, 'update'])
                ->name('noticias.update')->middleware('can:noticias.editar');
            Route::delete('/noticias/{uuid}', [PanelNoticiaController::class, 'destroy'])
                ->name('noticias.destroy')->middleware('can:noticias.eliminar');
            Route::get('/noticias/{uuid}/imagen', [PanelNoticiaController::class, 'imagen'])
                ->name('noticias.imagen.ver')->middleware('can:noticias.ver');
            Route::post('/noticias/{uuid}/imagen', [PanelNoticiaController::class, 'subirImagen'])
                ->name('noticias.imagen')->middleware('can:noticias.editar');

            // Imágenes del editor (noticias, políticas o entidad: la petición valida que quien sube
            // pueda editar el contenido al que va la imagen).
            Route::post('/contenido/imagenes', [ImagenContenidoController::class, 'store'])
                ->name('contenido.imagenes.store');

            // Archivos — gestión de archivos subidos.
            Route::get('/entidad/{modelUuid}/archivos', [ArchivoController::class, 'index'])
                ->name('archivos.index')->middleware('can:archivos.ver');
            Route::post('/entidad/{modelUuid}/archivos', [ArchivoController::class, 'store'])
                ->name('archivos.store')->middleware('can:archivos.crear');
            Route::get('/archivos/{uuid}', [ArchivoController::class, 'show'])
                ->name('archivos.show')->middleware('can:archivos.ver');
            Route::delete('/archivos/{uuid}', [ArchivoController::class, 'destroy'])
                ->name('archivos.destroy')->middleware('can:archivos.eliminar');
        });
    });
});
