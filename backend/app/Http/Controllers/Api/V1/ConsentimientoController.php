<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarConsentimientoRequest;
use App\Models\Consentimiento;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Registro de consentimientos de cookies.
 *
 * @operationId registrarConsentimiento
 *
 * @tags Consentimientos
 */
final class ConsentimientoController extends Controller
{
    /**
     * Guarda un evento de consentimiento: categorías, versión de la política y
     * fecha del servidor. No guarda IP, agente de usuario ni identificador alguno.
     */
    public function store(RegistrarConsentimientoRequest $peticion): JsonResponse
    {
        try {
            /** @var array{evento: string, categorias: list<string>, version_politica: int} $datos */
            $datos = $peticion->validated();

            $consentimiento = Consentimiento::query()->create([
                'evento' => $datos['evento'],
                'categorias' => array_values($datos['categorias']),
                'version_politica' => $datos['version_politica'],
                // La hora es la del servidor, puesta aquí y no por el navegador ni por la base.
                'registrado_en' => now(),
            ]);

            return ApiResponse::created([
                'id' => $consentimiento->id,
                'type' => 'consentimiento',
                'evento' => $consentimiento->evento,
                'categorias' => $consentimiento->categorias,
                'version_politica' => $consentimiento->version_politica,
                'registrado_en' => $consentimiento->registrado_en->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al registrar el consentimiento', ['exception' => $e]);

            return ApiResponse::error('Error interno al registrar el consentimiento.', 500);
        }
    }
}
