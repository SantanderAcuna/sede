<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\IdentidadServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\ObtenerMenuRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @tags Identidad
 */
final class IdentidadController extends Controller
{
    public function __construct(
        private readonly IdentidadServiceInterface $identidadService,
    ) {}

    /**
     * @operationId obtenerTopBar
     *
     * @response 200 {
     *   "success": true,
     *   "data": { "id", "type": "top-bar", "logo_path", "url_govco", "altura_px", "idioma_default" }
     * }
     */
    public function topBar(): JsonResponse
    {
        try {
            return ApiResponse::objeto($this->identidadService->obtenerTopBar());
        } catch (ModelNotFoundException $e) {
            return ApiResponse::notFound($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Error al obtener top-bar', ['exception' => $e]);

            return ApiResponse::error('Error interno al obtener el top bar.', 500);
        }
    }

    /**
     * @operationId obtenerFooter
     *
     * @response 200 {
     *   "success": true,
     *   "data": { "id", "type": "footer", ... }
     * }
     */
    public function footer(): JsonResponse
    {
        try {
            return ApiResponse::objeto($this->identidadService->obtenerFooter());
        } catch (ModelNotFoundException $e) {
            return ApiResponse::notFound($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Error al obtener footer', ['exception' => $e]);

            return ApiResponse::error('Error interno al obtener el footer.', 500);
        }
    }

    /**
     * @operationId obtenerMenu
     *
     * @response 200 {
     *   "success": true,
     *   "data": [{ "id", "type": "menu-item", "slug", "etiqueta", ... }]
     * }
     */
    public function menu(ObtenerMenuRequest $request): JsonResponse
    {
        try {
            $rol = $request->validated()['rol'] ?? null;
            $menu = $this->identidadService->obtenerMenu($rol);

            return ApiResponse::ok($menu);
        } catch (\Throwable $e) {
            Log::error('Error al obtener menú', ['exception' => $e]);

            return ApiResponse::error('Error interno al obtener el menú.', 500);
        }
    }
}
