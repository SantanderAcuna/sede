<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\ImagenContenidoServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubirImagenContenidoRequest;
use App\Support\Api\ApiResponse;
use App\Support\Api\RespuestaDeImagen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            // El alt RECIBIDO: si el archivo se reutilizó, el guardado puede ser el de otra subida.
            'alt' => (string) $peticion->validated('alt'),
        ], 'Imagen subida correctamente.');
    }

    /**
     * La imagen de un contenido publicado. Pública, con límite de peticiones; sólo sirve
     * la colección «contenido» y, sin sesión de editor, solo la de una noticia ya publicada.
     *
     * @operationId obtenerImagenContenido
     */
    public function show(Request $peticion, string $uuid): Response
    {
        // Sesión opcional: la ruta es pública, pero quien edita contenido ve también las imágenes aún sin publicar.
        $usuario = $peticion->user('sanctum');
        $comoEditor = false;
        foreach (SubirImagenContenidoRequest::PERMISOS as $permiso) {
            if ($usuario?->can($permiso) === true) {
                $comoEditor = true;
                break;
            }
        }

        $imagen = $this->imagenes->publica($uuid, $comoEditor);
        abort_if($imagen === null, 404);

        // Lo que solo ve el editor no debe quedar en la caché compartida del proxy.
        return RespuestaDeImagen::desde($imagen, $comoEditor ? 'private, no-store' : 'public, max-age=86400, immutable');
    }
}
