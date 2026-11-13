<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\NoticiaServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListarNoticiasRequest;
use App\Http\Resources\NoticiaResource;
use App\Support\Api\ApiResponse;
use App\Support\Api\RespuestaDeImagen;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Las noticias publicadas (M01-6, RF-01-013). Públicas.
 *
 * @tags Noticias
 */
final class NoticiaController extends Controller
{
    public function __construct(private readonly NoticiaServiceInterface $noticias) {}

    /**
     * Las noticias publicadas, de la más reciente a la más antigua. El home pide
     * `per_page=6`. Sin noticias responde una lista vacía (CT-01): el sitio decide
     * qué decir, la API no inventa contenido.
     *
     * @operationId listarNoticias
     */
    public function index(ListarNoticiasRequest $peticion): JsonResponse
    {
        $pagina = $this->noticias->publicadas(
            porPagina: (int) $peticion->query('per_page', 10),
            destacada: $peticion->has('destacada') ? $peticion->boolean('destacada') : null,
        );

        return ApiResponse::coleccion(NoticiaResource::collection($pagina), $pagina);
    }

    /**
     * Una noticia publicada, con su cuerpo.
     *
     * @operationId obtenerNoticia
     */
    public function show(string $slug): JsonResponse
    {
        $noticia = $this->noticias->publicadaPorSlug($slug);

        return $noticia === null
            ? ApiResponse::notFound('La noticia no existe.')
            : ApiResponse::objeto((new NoticiaResource($noticia))->conCuerpo());
    }

    /**
     * La imagen de una noticia publicada, en una dirección estable.
     *
     * Se transmite desde el disco donde quedó guardada. No hay URL firmada: caduca
     * a los 15 minutos y una imagen de una página pública no puede caducar. El ETag
     * cambia al subir otra imagen, así que la caché del navegador nunca la deja vieja.
     *
     * @operationId obtenerImagenNoticia
     */
    public function imagen(string $slug): Response
    {
        $noticia = $this->noticias->publicadaPorSlug($slug);
        $imagen = $noticia?->imagenPrincipal;

        abort_if($imagen === null, 404);

        return RespuestaDeImagen::desde($imagen, 'public, max-age=300');
    }
}
