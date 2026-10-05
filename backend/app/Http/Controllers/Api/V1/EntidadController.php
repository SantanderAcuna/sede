<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\EntidadServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarEntidadRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para el recurso Entidad.
 *
 * @operationId obtenerEntidad
 *
 * @tags Entidad
 *
 * @response 200 {
 *   "success": true,
 *   "message": null,
 *   "data": { ... },
 *   "errors": null
 * }
 */
final class EntidadController extends Controller
{
    public function __construct(
        private readonly EntidadServiceInterface $entidadService,
    ) {}

    /**
     * Devuelve los datos institucionales de la entidad.
     *
     * Este endpoint alimenta la cabecera y el pie de página de la sede.
     * El pie debe publicar los ocho datos institucionales y enlazar las
     * cinco políticas obligatorias.
     */
    public function mostrar(): JsonResponse
    {
        try {
            $entidadResource = $this->entidadService->obtenerEntidad();

            return ApiResponse::objeto($entidadResource);
        } catch (\RuntimeException $e) {
            return ApiResponse::notFound($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Error al obtener entidad', [
                'exception' => $e,
            ]);

            return ApiResponse::error(
                'Error interno al obtener los datos de la entidad.',
                500
            );
        }
    }

    /**
     * Actualiza los datos de la entidad desde el panel de administración.
     *
     * @operationId actualizarEntidad
     *
     * @tags Panel — Entidad
     */
    public function actualizar(ActualizarEntidadRequest $request): JsonResponse
    {
        try {
            $entidadResource = $this->entidadService->actualizar(
                $request->validated()
            );

            return ApiResponse::ok($entidadResource, 'Entidad actualizada correctamente.');
        } catch (\RuntimeException $e) {
            return ApiResponse::notFound($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Error al actualizar entidad', [
                'exception' => $e,
            ]);

            return ApiResponse::error(
                'Error interno al actualizar la entidad.',
                500
            );
        }
    }
}
