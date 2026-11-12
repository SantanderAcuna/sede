<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\TramiteServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarValoracionRequest;
use App\Http\Requests\ListarTramitesRequest;
use App\Http\Resources\TramiteFichaResource;
use App\Http\Resources\TramiteResource;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * El catálogo de trámites de la Entidad.
 *
 * Dos operaciones y ninguna decisión: el controlador valida la petición con su `FormRequest`, se
 * la pasa al servicio y envuelve el resultado en el sobre del contrato. La regla de qué se publica
 * vive en el modelo y el repositorio, no aquí.
 */
final class TramiteController extends Controller
{
    public function __construct(private readonly TramiteServiceInterface $tramites) {}

    /**
     * El catálogo paginado, en forma de tarjetas, con las facetas del grupo en `meta` para la barra
     * de filtros.
     */
    public function listar(ListarTramitesRequest $peticion): JsonResponse
    {
        $filtros = $peticion->filtros();
        $pagina = $this->tramites->listar($filtros);

        return ApiResponse::coleccion(
            TramiteResource::collection($pagina),
            $pagina,
            ['facetas' => $this->tramites->facetas($filtros)],
        );
    }

    /**
     * La ficha completa de un trámite.
     */
    public function mostrar(string $slug): JsonResponse
    {
        return ApiResponse::objeto(new TramiteFichaResource($this->tramites->porSlug($slug)));
    }

    /**
     * La valoración pública de una ficha: «¿Cómo fue tu experiencia?» (FÁCIL/DIFÍCIL) y la encuesta
     * de 1 a 5 estrellas. Sólo sobre fichas publicadas: `porSlug` devuelve 404 para el resto.
     */
    public function valorar(GuardarValoracionRequest $peticion, string $slug): JsonResponse
    {
        $tramite = $this->tramites->porSlug($slug);
        /** @var array{valor?: string|null, estrellas?: int|null, comentario?: string|null} $datos */
        $datos = $peticion->validated();

        $tramite->valoraciones()->create([
            'valor' => $datos['valor'] ?? null,
            'estrellas' => $datos['estrellas'] ?? null,
            'comentario' => $datos['comentario'] ?? null,
        ]);

        return ApiResponse::created(null, 'Gracias por su valoración.');
    }
}
