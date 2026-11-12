<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\BannerServiceInterface;
use App\Exceptions\BannerIncompleto;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarBannerRequest;
use App\Http\Requests\OrdenarBannersRequest;
use App\Http\Requests\SubirImagenBannerRequest;
use App\Http\Resources\BannerPanelResource;
use App\Models\Banner;
use App\Models\User;
use App\Support\Api\ApiResponse;
use App\Support\Api\RespuestaDeImagen;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gestión del carrusel de la portada desde el panel. Requiere `banners.gestionar`.
 *
 * Una diapositiva puede existir como borrador con solo el título; para publicarla hace falta
 * imagen con texto alternativo (lo decide el servicio).
 *
 * @tags Panel — Carrusel
 */
final class PanelBannerController extends Controller
{
    public function __construct(private readonly BannerServiceInterface $banners) {}

    /**
     * Todas las diapositivas, en el orden elegido, con su estado y lo que les falta.
     *
     * @operationId listarBannersPanel
     */
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Banner::class);

        return ApiResponse::ok(BannerPanelResource::collection($this->banners->todos())->resolve());
    }

    /**
     * Una diapositiva.
     *
     * @operationId obtenerBannerPanel
     */
    public function show(string $uuid): JsonResponse
    {
        $banner = $this->banners->porUuid($uuid);
        if ($banner === null) {
            return ApiResponse::notFound('La diapositiva no existe.');
        }
        Gate::authorize('view', $banner);

        return ApiResponse::ok((new BannerPanelResource($banner))->resolve());
    }

    /**
     * Crea una diapositiva (basta el título). Nace al final del carrusel.
     *
     * @operationId crearBanner
     */
    public function store(GuardarBannerRequest $peticion): JsonResponse
    {
        Gate::authorize('create', Banner::class);

        try {
            $banner = $this->banners->crear($this->actor(), $peticion->validated());
        } catch (BannerIncompleto $e) {
            return ApiResponse::validationError(['banner' => $e->problemas]);
        }

        return ApiResponse::created((new BannerPanelResource($banner))->resolve(), 'Diapositiva creada correctamente.');
    }

    /**
     * Cambia una diapositiva. Publicarla (`activo: true`) exige imagen con texto alternativo.
     *
     * @operationId actualizarBanner
     */
    public function update(GuardarBannerRequest $peticion, string $uuid): JsonResponse
    {
        $banner = $this->banners->porUuid($uuid);
        if ($banner === null) {
            return ApiResponse::notFound('La diapositiva no existe.');
        }
        Gate::authorize('update', $banner);

        try {
            $banner = $this->banners->actualizar($this->actor(), $banner, $peticion->validated());
        } catch (BannerIncompleto $e) {
            return ApiResponse::validationError(['banner' => $e->problemas]);
        }

        return ApiResponse::ok((new BannerPanelResource($banner))->resolve(), 'Diapositiva actualizada correctamente.');
    }

    /**
     * Sube o reemplaza la imagen, con su texto alternativo.
     *
     * @operationId subirImagenBanner
     */
    public function subirImagen(SubirImagenBannerRequest $peticion, string $uuid): JsonResponse
    {
        $banner = $this->banners->porUuid($uuid);
        if ($banner === null) {
            return ApiResponse::notFound('La diapositiva no existe.');
        }
        Gate::authorize('update', $banner);

        $banner = $this->banners->guardarImagen($this->actor(), $banner, $peticion->validated('imagen'), (string) $peticion->validated('alt'));

        return ApiResponse::ok((new BannerPanelResource($banner))->resolve(), 'Imagen guardada correctamente.');
    }

    /**
     * La imagen, esté la diapositiva publicada o no (con sesión y sin caché compartida).
     *
     * @operationId obtenerImagenBannerPanel
     */
    public function imagen(string $uuid): Response
    {
        $banner = $this->banners->porUuid($uuid);
        abort_if($banner === null, 404);
        Gate::authorize('view', $banner);

        $imagen = $banner->imagenPrincipal;
        abort_if($imagen === null, 404);

        return RespuestaDeImagen::desde($imagen, 'private, no-store');
    }

    /**
     * Fija el orden del carrusel: la lista completa de diapositivas, la primera de la lista es la primera que se ve.
     *
     * @operationId ordenarBanners
     */
    public function ordenar(OrdenarBannersRequest $peticion): JsonResponse
    {
        Gate::authorize('reorder', Banner::class);

        $this->banners->reordenar($this->actor(), $peticion->uuids());

        return ApiResponse::ok(BannerPanelResource::collection($this->banners->todos())->resolve(), 'Orden guardado.');
    }

    /**
     * Elimina una diapositiva (borrado lógico).
     *
     * @operationId eliminarBanner
     */
    public function destroy(string $uuid): JsonResponse
    {
        $banner = $this->banners->porUuid($uuid);
        if ($banner === null) {
            return ApiResponse::notFound('La diapositiva no existe.');
        }
        Gate::authorize('delete', $banner);

        $this->banners->eliminar($this->actor(), $banner);

        return ApiResponse::ok(null, 'Diapositiva eliminada.');
    }

    private function actor(): User
    {
        /** @var User $actor */
        $actor = auth()->user();

        return $actor;
    }
}
