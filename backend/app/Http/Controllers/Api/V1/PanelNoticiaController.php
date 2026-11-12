<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\NoticiaServiceInterface;
use App\Exceptions\NoticiaIncompleta;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarNoticiaRequest;
use App\Http\Requests\SubirImagenNoticiaRequest;
use App\Http\Resources\NoticiaPanelResource;
use App\Models\Noticia;
use App\Support\Api\ApiResponse;
use App\Support\Api\RespuestaDeImagen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gestión de las noticias desde el panel (M01-6). Requiere `noticias.gestionar`.
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
     * Todas las noticias —borradores y programadas incluidas—, las más recientes primero.
     *
     * @operationId listarNoticiasPanel
     */
    public function index(Request $peticion): JsonResponse
    {
        Gate::authorize('viewAny', Noticia::class);

        $pagina = $this->noticias->todas(max(1, min(100, (int) $peticion->query('per_page', 20))));

        $coleccion = NoticiaPanelResource::collection($pagina);

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

        try {
            $noticia = $this->noticias->crear($peticion->validated(), $peticion->user()?->id);
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

        try {
            $noticia = $this->noticias->actualizar($noticia, $peticion->validated());
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

        $this->noticias->eliminar($noticia);

        return ApiResponse::ok(null, 'Noticia eliminada.');
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
