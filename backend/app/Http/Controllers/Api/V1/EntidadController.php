<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\EntidadServiceInterface;
use App\Http\Controllers\Controller;
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

            return response()->json([
                'success' => true,
                'message' => null,
                'data' => $entidadResource,
                'errors' => null,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'errors' => null,
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Error al obtener entidad', [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al obtener los datos de la entidad.',
                'data' => null,
                'errors' => null,
            ], 500);
        }
    }
}
