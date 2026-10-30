<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\BusquedaServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\BuscarRequest;
use App\Http\Requests\SugerirBusquedaRequest;
use App\Http\Resources\ResultadoBusquedaResource;
use App\Http\Resources\SugerenciaBusquedaResource;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * La búsqueda de la sede (T-22).
 *
 * Dos operaciones, ninguna decisión: validar, delegar y envolver. Lo que el
 * ciudadano escribió **no se registra aquí ni en ninguna capa de la aplicación**: ni
 * `Log::`, ni auditoría, ni contador por término. La consulta puede traer un número
 * de documento, y un dato personal que nadie necesita no se guarda (Ley 1581 de
 * 2012, principio de finalidad).
 */
final class BusquedaController extends Controller
{
    public function __construct(private readonly BusquedaServiceInterface $busqueda) {}

    /**
     * Hasta diez sugerencias mientras se escribe.
     */
    public function sugerir(SugerirBusquedaRequest $peticion): JsonResponse
    {
        $sugerencias = $this->busqueda->sugerir($peticion->texto(), $peticion->limite());

        return ApiResponse::ok(SugerenciaBusquedaResource::collection($sugerencias)->resolve());
    }

    /**
     * Los resultados completos, por pertinencia y paginados.
     */
    public function buscar(BuscarRequest $peticion): JsonResponse
    {
        $pagina = $this->busqueda->buscar(
            $peticion->texto(),
            $peticion->tipo(),
            $peticion->pagina(),
            $peticion->porPagina(),
        );

        return ApiResponse::coleccion(ResultadoBusquedaResource::collection($pagina), $pagina);
    }
}
