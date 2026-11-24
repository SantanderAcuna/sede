<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\NoticiaServiceInterface;
use App\Exceptions\NoticiaIncompleta;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarNoticiaRequest;
use App\Http\Requests\ListarNoticiasPanelRequest;
use App\Http\Requests\SubirImagenNoticiaRequest;
use App\Http\Resources\NoticiaPanelFilaResource;
use App\Http\Resources\NoticiaPanelResource;
use App\Models\Noticia;
use App\Support\Acceso\Huella;
use App\Support\Api\ApiResponse;
use App\Support\Api\RespuestaDeImagen;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gestión de las noticias desde el panel (M01-6). Cada acción exige su permiso (ver `CatalogoPermisos`).
 *
 * Una noticia nace como borrador y sólo se publica con título, descripción,
 * contenido, imagen y texto alternativo de la imagen (RF-01-013, Res. 1519/2020).
 * Ese «sólo se publica» lo decide el servicio; aquí se valida la petición, se
 * autoriza con `NoticiaPolicy` y se contesta.
 *
 * @tags Panel — Noticias
 */
final class PanelNoticiaController extends Controller
{
    public function __construct(private readonly NoticiaServiceInterface $noticias) {}

    /**
     * Todas las noticias —borradores y programadas incluidas—, sin su cuerpo, para la tabla
     * del panel. Se puede buscar por título (`q`), filtrar por `estado` y ordenar
     * (`ordenar_por`, `direccion`); por defecto, las más recientes primero.
     *
     * @operationId listarNoticiasPanel
     */
    public function index(ListarNoticiasPanelRequest $peticion): JsonResponse
    {
        Gate::authorize('viewAny', Noticia::class);

        $pagina = $this->noticias->todas($peticion->porPagina(), $peticion->filtros());

        $coleccion = NoticiaPanelFilaResource::collection($pagina);

        return ApiResponse::coleccion($coleccion, $pagina);
    }

    /**
     * Una noticia con su estado de publicación.
     *
     * @operationId obtenerNoticiaPanel
     */
    public function show(string $uuid): JsonResponse
    {
        $noticia = $this->noticias->porUuid($uuid);
        if ($noticia === null) {
            return ApiResponse::notFound('La noticia no existe.');
        }
        Gate::authorize('view', $noticia);

        return ApiResponse::ok(self::elemento($noticia));
    }

    /**
     * Crea una noticia. Si se pide publicarla ya debe estar completa; como la imagen
     * se sube después, en la práctica nace siempre como borrador.
     *
     * @operationId crearNoticia
     */
    public function store(GuardarNoticiaRequest $peticion): JsonResponse
    {
        Gate::authorize('create', Noticia::class);
        // Publicar es un permiso aparte: crear no basta para dejar una noticia a la vista del público.
        if ($peticion->boolean('activo')) {
            Gate::authorize('publish', Noticia::class);
        }

        try {
            $noticia = DB::transaction(function () use ($peticion) {
                $creada = $this->noticias->crear($peticion->validated(), $peticion->user()?->id);
                Huella::deSesion('noticia_creada', 'noticias', $creada->id, ['titulo' => $creada->titulo, 'estado' => self::estado($creada)]);

                return $creada;
            });
        } catch (NoticiaIncompleta $e) {
            return ApiResponse::validationError(['noticia' => $e->problemas]);
        }

        return ApiResponse::created(self::elemento($noticia), 'Noticia creada correctamente.');
    }

    /**
     * Edita una noticia. La dirección (`slug`) no cambia al editar el título: un
     * enlace ya compartido no puede romperse.
     *
     * @operationId actualizarNoticia
     */
    public function update(GuardarNoticiaRequest $peticion, string $uuid): JsonResponse
    {
        $noticia = $this->noticias->porUuid($uuid);
        if ($noticia === null) {
            return ApiResponse::notFound('La noticia no existe.');
        }
        Gate::authorize('update', $noticia);
        // Publicar o retirar (cambiar `activo`) exige el permiso de publicar; guardar igual que está, no.
        if ($peticion->has('activo') && $peticion->boolean('activo') !== (bool) $noticia->activo) {
            Gate::authorize('publish', $noticia);
        }

        try {
            $antes = self::instantanea($noticia);
            $noticia = DB::transaction(function () use ($noticia, $peticion, $antes) {
                $guardada = $this->noticias->actualizar($noticia, $peticion->validated());
                Huella::deSesion('noticia_actualizada', 'noticias', $guardada->id, [
                    'titulo' => $guardada->titulo,
                    'campos' => Huella::diferencias($antes, self::instantanea($guardada)),
                    'estado' => self::estado($guardada),
                ]);

                return $guardada;
            });
        } catch (NoticiaIncompleta $e) {
            return ApiResponse::validationError(['noticia' => $e->problemas]);
        }

        return ApiResponse::ok(self::elemento($noticia), 'Noticia actualizada correctamente.');
    }

    /**
     * Sube (o reemplaza) la imagen de la noticia con su texto alternativo.
     *
     * @operationId subirImagenNoticia
     */
    public function subirImagen(SubirImagenNoticiaRequest $peticion, string $uuid): JsonResponse
    {
        $noticia = $this->noticias->porUuid($uuid);
        if ($noticia === null) {
            return ApiResponse::notFound('La noticia no existe.');
        }
        Gate::authorize('update', $noticia);

        $noticia = $this->noticias->guardarImagen(
            $noticia,
            $peticion->validated('imagen'),
            (string) $peticion->validated('alt'),
        );

        Huella::deSesion('noticia_imagen_cambiada', 'noticias', $noticia->id, ['titulo' => $noticia->titulo]);

        return ApiResponse::ok(self::elemento($noticia), 'Imagen guardada correctamente.');
    }

    /**
     * La imagen de una noticia, esté publicada o no.
     *
     * La ruta pública sólo entrega las publicadas; el panel necesita ver también la de
     * un borrador. Va con sesión y permiso, y sin caché compartida (`private, no-store`):
     * un borrador no debe quedar en una caché intermedia.
     *
     * @operationId obtenerImagenNoticiaPanel
     */
    public function imagen(string $uuid): Response
    {
        $noticia = $this->noticias->porUuid($uuid);
        abort_if($noticia === null, 404);
        Gate::authorize('view', $noticia);

        $imagen = $noticia->imagenPrincipal;
        abort_if($imagen === null, 404);

        return RespuestaDeImagen::desde($imagen, 'private, no-store');
    }

    /**
     * Elimina una noticia. Se archiva (borrado lógico), no se destruye.
     *
     * @operationId eliminarNoticia
     */
    public function destroy(string $uuid): JsonResponse
    {
        $noticia = $this->noticias->porUuid($uuid);
        if ($noticia === null) {
            return ApiResponse::notFound('La noticia no existe.');
        }
        Gate::authorize('delete', $noticia);

        $titulo = $noticia->titulo;
        $id = $noticia->id;
        DB::transaction(function () use ($noticia, $id, $titulo): void {
            $this->noticias->eliminar($noticia);
            Huella::deSesion('noticia_eliminada', 'noticias', $id, ['titulo' => $titulo]);
        });

        return ApiResponse::ok(null, 'Noticia eliminada.');
    }

    /**
     * Los campos que la persona puede editar, como están ahora (para saber cuáles cambiaron).
     *
     * @return array<string, mixed>
     */
    private static function instantanea(Noticia $noticia): array
    {
        return [
            'titulo' => $noticia->titulo,
            'descripcion_corta' => $noticia->descripcion_corta,
            'contenido' => $noticia->contenido,
            'fecha_publicacion' => $noticia->fecha_publicacion->toIso8601String(),
            'destacada' => (bool) $noticia->destacada,
            'activo' => (bool) $noticia->activo,
            'imagen_alt' => $noticia->imagen_principal_alt,
        ];
    }

    private static function estado(Noticia $noticia): string
    {
        return $noticia->activo ? 'publicada' : 'borrador';
    }

    /**
     * La noticia tal como la ve el panel (ver `NoticiaPanelResource`).
     *
     * @return array<string, mixed>
     */
    private static function elemento(Noticia $noticia): array
    {
        // Tras subir una imagen la relación cacheada quedó vieja: se vuelve a leer.
        $noticia->unsetRelation('imagenPrincipal')->load('imagenPrincipal');

        return (new NoticiaPanelResource($noticia))->resolve();
    }
}
