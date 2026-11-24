<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\FilesMediaServiceInterface;
use App\Exceptions\ArchivoEnUso;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubirArchivoRequest;
use App\Http\Resources\FileMediaResource;
use App\Models\FileMedia;
use App\Models\Politica;
use App\Services\PoliticaDocumentoService;
use App\Support\Api\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        private readonly PoliticaDocumentoService $documentos,
    ) {}

    /**
     * La política a la que se adjuntan los documentos, por su UUID público. La ruta se llama
     * `/entidad/{uuid}/archivos` por historia, pero solo acepta políticas: los archivos de Entidad se
     * retiraron en D9 (si el módulo Entidad necesita adjuntos, se diseñan con una relación explícita).
     */
    private function resolverModelo(string $uuid): ?Politica
    {
        return Politica::where('uuid', $uuid)->first();
    }

    /**
     * Adjuntar o quitar archivos de una política exige, además del permiso de
     * archivos, poder editar políticas: el documento oficial se publica.
     */
    private function puedeTocar(Request $request, Model|string|null $propietario): bool
    {
        $esPolitica = $propietario instanceof Politica || $propietario === Politica::class;

        return ! $esPolitica || $request->user()?->can('politicas.editar') === true;
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
     * @response 409
     */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        try {
            $archivo = $this->filesService->findByUuid($uuid);

            if ($archivo === null) {
                return ApiResponse::notFound('Archivo no encontrado.');
            }

            // Un documento de política NUNCA se borra desde la API (Ley 594/2000, a la espera de jurídica):
            // «quitar» solo lo desvincula y el archivo queda en la mediateca.
            $politicaId = DB::table('politica_documentos')->where('file_media_uuid', $archivo->uuid)->value('politica_id');
            if ($politicaId !== null || $archivo->uso === FileMedia::USO_DOCUMENTO) {
                if (! $this->puedeTocar($request, Politica::class)) {
                    return ApiResponse::forbidden();
                }
                if ($politicaId === null) {
                    return ApiResponse::conflict('Los documentos oficiales no se eliminan del sistema.');
                }

                $politica = Politica::query()->findOrFail((int) $politicaId);
                $this->documentos->quitar($politica, $archivo, $request->user() ?? abort(401));

                return ApiResponse::ok(null, 'Documento quitado. El archivo se conserva.');
            }

            $this->filesService->delete($uuid);

            return ApiResponse::ok(null, 'Archivo eliminado.');
        } catch (ArchivoEnUso $e) {
            // Antes del `catch (\Throwable)`: si no, el conflicto saldría como un 500.
            return ApiResponse::conflict($e->getMessage());
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

            // Solo existe una colección de documentos por política; cualquier otra está vacía.
            $archivos = $collection === Politica::COLECCION_DOCUMENTO
                ? $this->documentos->listar($model)
                : collect();

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
            if ($request->validated('collection') !== Politica::COLECCION_DOCUMENTO) {
                return ApiResponse::validationError([
                    'collection' => ['A una política sólo se le adjunta la colección «'.Politica::COLECCION_DOCUMENTO.'».'],
                ]);
            }

            $archivo = $this->documentos->adjuntar(
                $model,
                $request->validated('archivo'),
                $request->user() ?? abort(401),
            );

            return ApiResponse::ok(new FileMediaResource($archivo), 'Archivo subido correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al subir archivo', ['model' => $modelUuid, 'exception' => $e]);

            return ApiResponse::error('Error interno al subir el archivo.', 500);
        }
    }
}
