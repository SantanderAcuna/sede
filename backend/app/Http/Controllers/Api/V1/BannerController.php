<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\BannerServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\BannerPublicoResource;
use App\Models\Banner;
use App\Support\Api\ApiResponse;
use App\Support\Api\RespuestaDeImagen;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * El carrusel de la portada, lectura pública. Solo las diapositivas vigentes, en el orden elegido.
 *
 * @tags Banners
 */
final class BannerController extends Controller
{
    public function __construct(private readonly BannerServiceInterface $banners) {}

    /**
     * Las diapositivas vigentes (activas y dentro de su fecha), con su imagen y texto alternativo.
     *
     * @operationId listarBanners
     */
    public function listar(): JsonResponse
    {
        return ApiResponse::ok(BannerPublicoResource::collection($this->banners->vigentes())->resolve());
    }

    /**
     * La imagen de una diapositiva vigente.
     *
     * @operationId obtenerImagenBanner
     */
    public function imagen(string $uuid): Response
    {
        $banner = Banner::query()->vigentes()->with('imagenPrincipal')->where('uuid', $uuid)->first();
        $imagen = $banner?->imagenPrincipal;
        abort_if($imagen === null, 404);

        return RespuestaDeImagen::desde($imagen, 'public, max-age=300');
    }
}
