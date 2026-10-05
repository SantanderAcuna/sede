<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\FilesMediaServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubirArchivoRequest;
use App\Http\Resources\FileMediaResource;
use App\Models\Entidad;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Gestión de archivos subidos.
 *
 * @tags Panel — Archivos
 */
final class ArchivoController extends Controller
{
    public function __construct(
        private readonly FilesMediaServiceInterface $filesService,
    ) {}

    /**
     * @operationId obtenerArchivo
     *
     * @response 200 { "success": true, "data": { FileMediaItem } }
     * @response 404
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $archivo = $this->filesService->findByUuid($uuid);

            if ($archivo === null) {
                return ApiResponse::notFound('Archivo no encontrado.');
            }

            return ApiResponse::objeto(new FileMediaResource($archivo));
        } catch (\Throwable $e) {
            Log::error('Error al obtener archivo', ['uuid' => $uuid, 'exception' => $e]);

            return ApiResponse::error('Error interno al obtener el archivo.', 500);
        }
    }

    /**
     * @operationId eliminarArchivo
     *
     * @response 200 { "success": true, "message": "Archivo eliminado" }
     * @response 404
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $archivo = $this->filesService->findByUuid($uuid);

            if ($archivo === null) {
                return ApiResponse::notFound('Archivo no encontrado.');
            }

            $this->filesService->delete($uuid);

            return ApiResponse::ok(null, 'Archivo eliminado.');
        } catch (\Throwable $e) {
            Log::error('Error al eliminar archivo', ['uuid' => $uuid, 'exception' => $e]);

            return ApiResponse::error('Error interno al eliminar el archivo.', 500);
        }
    }

    /**
     * @operationId listarArchivos
     *
     * @response 200 { "success": true, "data": list<FileMediaItem> }
     */
    public function index(Request $request, string $modelUuid): JsonResponse
    {
        try {
            $collection = $request->query('collection');
            $model = Entidad::where('uuid', $modelUuid)->first();

            if ($model === null) {
                return ApiResponse::notFound('Entidad no encontrada.');
            }

            if ($collection === null) {
                return ApiResponse::error('El parámetro collection es obligatorio.', 422);
            }

            $archivos = $this->filesService->getByCollection($model, $collection);

            return ApiResponse::ok(FileMediaResource::collection($archivos));
        } catch (\Throwable $e) {
            Log::error('Error al listar archivos', ['model' => $modelUuid, 'exception' => $e]);

            return ApiResponse::error('Error interno al listar archivos.', 500);
        }
    }

    /**
     * @operationId subirArchivo
     *
     * @response 200 { "success": true, "data": FileMediaItem }
     * @response 422
     */
    public function store(SubirArchivoRequest $request, string $modelUuid): JsonResponse
    {
        try {
            $model = Entidad::where('uuid', $modelUuid)->first();

            if ($model === null) {
                return ApiResponse::notFound('Entidad no encontrada.');
            }

            $archivo = $this->filesService->upload(
                file: $request->validated('archivo'),
                collection: $request->validated('collection'),
                model: $model,
                metadata: $request->validated('metadata'),
            );

            return ApiResponse::ok(new FileMediaResource($archivo), 'Archivo subido correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al subir archivo', ['model' => $modelUuid, 'exception' => $e]);

            return ApiResponse::error('Error interno al subir el archivo.', 500);
        }
    }
}
