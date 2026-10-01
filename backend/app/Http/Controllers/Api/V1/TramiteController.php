<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\TramiteServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListarTramitesRequest;
use App\Http\Resources\TramiteResource;
use App\Support\Api\Respuesta;
use Illuminate\Http\JsonResponse;

/**
 * El catálogo de trámites de la Entidad.
 *
 * Dos operaciones y ninguna decisión: el controlador valida la petición con su
 * `FormRequest`, se la pasa al servicio y envuelve el resultado en el sobre del
 * contrato. La regla de qué se publica vive en el repositorio, no aquí, porque
 * aquí no se ve y lo que no se ve se salta sin querer en la siguiente consulta.
 */
final class TramiteController extends Controller
{
    public function __construct(private readonly TramiteServiceInterface $tramites) {}

    /**
     * El catálogo paginado.
     *
     * Cada elemento viaja completo —con sus seis atributos obligatorios— aunque
     * el ciudadano sólo vea unos pocos en la galería: el contrato declara un solo
     * esquema para el listado y para la ficha, y una versión recortada sería un
     * segundo contrato que habría que mantener en silencio.
     */
    public function listar(ListarTramitesRequest $peticion): JsonResponse
    {
        $pagina = $this->tramites->listar($peticion->filtros());

        return Respuesta::coleccion(TramiteResource::collection($pagina), $pagina);
    }

    /**
     * La ficha completa de un trámite.
     */
    public function mostrar(string $slug): JsonResponse
    {
        return Respuesta::objeto(new TramiteResource($this->tramites->porSlug($slug)));
    }
}
