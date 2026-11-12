<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\TramitePanelServiceInterface;
use App\Exceptions\TramiteIncompleto;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarTramiteRequest;
use App\Http\Requests\ListarTramitesPanelRequest;
use App\Http\Resources\TramitePanelFilaResource;
use App\Http\Resources\TramitePanelResource;
use App\Models\Dependencia;
use App\Models\Tramite;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Gestión de los trámites desde el panel. Requiere `tramites.gestionar`.
 *
 * Un trámite puede existir incompleto (borrador); solo se publica con la ficha de GOV.CO y los
 * seis atributos que el sitio exige. Al editar uno del catálogo oficial queda marcado como
 * del panel, para que la carga automática no pise el cambio.
 *
 * @tags Panel — Trámites
 */
final class PanelTramiteController extends Controller
{
    public function __construct(private readonly TramitePanelServiceInterface $tramites) {}

    /**
     * Trámites —borradores incluidos— para la tabla del panel. Se busca por nombre o código
     * (`q`), se filtra por `estado` (`publicado` | `borrador`) y se ordena.
     *
     * @operationId listarTramitesPanel
     */
    public function index(ListarTramitesPanelRequest $peticion): JsonResponse
    {
        Gate::authorize('viewAny', Tramite::class);

        $pagina = $this->tramites->listar($peticion->porPagina(), $peticion->filtros());

        return ApiResponse::coleccion(TramitePanelFilaResource::collection($pagina), $pagina);
    }

    /**
     * Un trámite completo con su estado y lo que le falta para publicarse.
     *
     * @operationId obtenerTramitePanel
     */
    public function show(string $uuid): JsonResponse
    {
        $tramite = $this->tramites->porUuid($uuid);
        if ($tramite === null) {
            return ApiResponse::notFound('El trámite no existe.');
        }
        Gate::authorize('view', $tramite);

        return ApiResponse::ok((new TramitePanelResource($tramite))->resolve());
    }

    /**
     * Crea un trámite (basta el nombre). El código y la dirección los asigna el sistema.
     *
     * @operationId crearTramite
     */
    public function store(GuardarTramiteRequest $peticion): JsonResponse
    {
        Gate::authorize('create', Tramite::class);

        try {
            $tramite = $this->tramites->crear($peticion->datos());
        } catch (TramiteIncompleto $e) {
            return ApiResponse::validationError(['tramite' => $e->problemas]);
        }

        return ApiResponse::created((new TramitePanelResource($tramite))->resolve(), 'Trámite creado correctamente.');
    }

    /**
     * Edita un trámite. El código y la dirección no cambian.
     *
     * @operationId actualizarTramite
     */
    public function update(GuardarTramiteRequest $peticion, string $uuid): JsonResponse
    {
        $tramite = $this->tramites->porUuid($uuid);
        if ($tramite === null) {
            return ApiResponse::notFound('El trámite no existe.');
        }
        Gate::authorize('update', $tramite);

        try {
            $tramite = $this->tramites->actualizar($tramite, $peticion->datos());
        } catch (TramiteIncompleto $e) {
            return ApiResponse::validationError(['tramite' => $e->problemas]);
        }

        return ApiResponse::ok((new TramitePanelResource($tramite))->resolve(), 'Trámite actualizado correctamente.');
    }

    /**
     * Envía un elemento del catálogo a la papelera. Todo elemento es borrable (soft delete).
     *
     * @operationId eliminarTramite
     */
    public function destroy(string $uuid): Response|JsonResponse
    {
        $tramite = $this->tramites->porUuid($uuid);
        if ($tramite === null) {
            return ApiResponse::notFound('El trámite no existe.');
        }
        Gate::authorize('delete', $tramite);

        $this->tramites->eliminar($tramite);

        return response()->noContent();
    }

    /**
     * Las dependencias responsables, para poblar el selector del formulario.
     *
     * @operationId listarDependencias
     */
    public function dependencias(): JsonResponse
    {
        Gate::authorize('viewAny', Tramite::class);

        $dependencias = Dependencia::query()
            ->orderBy('orden')
            ->get(['id', 'nombre'])
            ->map(static fn (Dependencia $d): array => ['id' => $d->id, 'nombre' => $d->nombre])
            ->all();

        return ApiResponse::ok($dependencias);
    }
}
