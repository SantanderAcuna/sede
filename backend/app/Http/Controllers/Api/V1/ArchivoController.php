<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\FilesMediaServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubirArchivoRequest;
use App\Http\Resources\FileMediaResource;
use App\Models\Entidad;
use App\Models\Politica;
use App\Support\Api\ApiResponse;
use Illuminate\Database\Eloquent\Model;
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
     * El modelo al que se adjuntan los archivos, por su UUID público: la entidad o
     * una política. La ruta se llama `/entidad/{uuid}/archivos` por historia; el
     * recurso real es «cualquier modelo con archivos».
     */
    private function resolverModelo(string $uuid): ?Model
    {
        return Entidad::where('uuid', $uuid)->first() ?? Politica::where('uuid', $uuid)->first();
    }

    /**
     * Adjuntar o quitar archivos de una política exige, además del permiso de
     * archivos, poder gestionar políticas: el documento oficial se publica.
     */
    private function puedeTocar(Request $request, Model|string|null $propietario): bool
    {
        $esPolitica = $propietario instanceof Politica || $propietario === Politica::class;

        return ! $esPolitica || $request->user()?->can('politicas.gestionar') === true;
    }

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
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        try {
            $archivo = $this->filesService->findByUuid($uuid);

            if ($archivo === null) {
                return ApiResponse::notFound('Archivo no encontrado.');
            }

            if (! $this->puedeTocar($request, $archivo->model_type)) {
                return ApiResponse::forbidden();
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
            $model = $this->resolverModelo($modelUuid);

            if ($model === null) {
                return ApiResponse::notFound('Entidad no encontrada.');
            }

            if (! $this->puedeTocar($request, $model)) {
                return ApiResponse::forbidden();
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
            $model = $this->resolverModelo($modelUuid);

            if ($model === null) {
                return ApiResponse::notFound('Entidad no encontrada.');
            }

            if (! $this->puedeTocar($request, $model)) {
                return ApiResponse::forbidden();
            }

            // A una política sólo se le adjunta el documento oficial.
            if ($model instanceof Politica && $request->validated('collection') !== Politica::COLECCION_DOCUMENTO) {
                return ApiResponse::validationError([
                    'collection' => ['A una política sólo se le adjunta la colección «'.Politica::COLECCION_DOCUMENTO.'».'],
                ]);
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
