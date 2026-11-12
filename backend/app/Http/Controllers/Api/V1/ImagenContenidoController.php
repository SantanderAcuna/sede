<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\ImagenContenidoServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubirImagenContenidoRequest;
use App\Support\Api\ApiResponse;
use App\Support\Api\RespuestaDeImagen;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Imágenes del contenido que se edita en el panel (noticias, políticas, entidad).
 *
 * @tags Panel — Imágenes del contenido
 */
final class ImagenContenidoController extends Controller
{
    public function __construct(private readonly ImagenContenidoServiceInterface $imagenes) {}

    /**
     * Sube una imagen para insertarla en el contenido. Se re-codifica antes de guardarse.
     *
     * @operationId subirImagenContenido
     */
    public function store(SubirImagenContenidoRequest $peticion): JsonResponse
    {
        $imagen = $this->imagenes->subir(
            $peticion->user() ?? abort(401),
            $peticion->validated('imagen'),
            (string) $peticion->validated('alt'),
        );

        return ApiResponse::created([
            'uuid' => $imagen->uuid,
            'url' => $this->imagenes->urlPublica($imagen),
            'alt' => (string) ($imagen->metadata['alt'] ?? ''),
        ], 'Imagen subida correctamente.');
    }

    /**
     * La imagen de un contenido publicado. Pública, con límite de peticiones; sólo sirve
     * la colección «contenido».
     *
     * @operationId obtenerImagenContenido
     */
    public function show(string $uuid): Response
    {
        $imagen = $this->imagenes->publica($uuid);
        abort_if($imagen === null, 404);

        return RespuestaDeImagen::desde($imagen, 'public, max-age=86400, immutable');
    }
}
