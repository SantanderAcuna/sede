<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\FilesMediaServiceInterface;
use App\Models\FileMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Servicio centralizado de gestión de archivos subidos.
 *
 * Implementa el patrón polimórfico: un archivo puede asociarse a cualquier modelo
 * sin necesidad de tables intermedias. El archivo físico se almacena en el disco
 * por defecto de la configuración (`FILESYSTEM_DISK`) y los metadatos + la
 * referencia viven en `file_media`; cada fila recuerda en qué disco quedó, así
 * que cambiar de disco no deja huérfanos los archivos anteriores.
 *
 * R-52: FilesMedia polymorphic.
 */
final class FilesMediaService implements FilesMediaServiceInterface
{
    /**
     * Extensiones que se aceptan al guardar. Debe coincidir con `mimes:` de
     * `SubirArchivoRequest`; lo que no esté aquí se guarda como `.bin`.
     *
     * SVG no está: es XML con scripts posibles y, servido desde el mismo origen,
     * ejecuta código en la sesión de quien lo abre (H-13).
     */
    private const EXTENSIONES = [
        'pdf', 'png', 'jpg', 'gif', 'webp',
        'doc', 'docx', 'odt', 'xls', 'xlsx', 'ppt', 'pptx',
        'zip', 'rar',
    ];

    /**
     * {@inheritDoc}
     */
    public function upload(
        UploadedFile $file,
        string $collection,
        object $model,
        ?array $metadata = null,
    ): FileMedia {
        $filename = $this->generateFilename($file);

        $path = $this->buildPath($model, $collection, $filename);

        // El disco configurado, no uno fijo: antes era siempre `public` aunque
        // `FILESYSTEM_DISK` dijera otra cosa, y los archivos quedaban donde nadie
        // los servía (H-13).
        $disco = (string) config('filesystems.default');

        Storage::disk($disco)->put($path, $file->getContent());

        return FileMedia::create([
            'model_type' => $model::class,
            'model_id' => $this->getModelId($model),
            'model_uuid' => $this->getModelUuid($model),
            'collection' => $collection,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => (int) $file->getSize(),
            'disk' => $disco,
            'path' => $path,
            'metadata' => $metadata,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getByCollection(object $model, string $collection): Collection
    {
        $query = FileMedia::where('model_type', $model::class)
            ->where('collection', $collection);

        $modelId = $this->getModelId($model);
        $modelUuid = $this->getModelUuid($model);

        // Buscar por model_id Y model_uuid a la vez (el caso normal con Eloquent real),
        // o solo por model_uuid cuando model_id es null (compatibilidad con factories legacy).
        if ($modelId !== null && $modelUuid !== null) {
            $query->where(function (Builder $q) use ($modelId, $modelUuid): void {
                $q->where('model_id', $modelId)
                    ->where('model_uuid', $modelUuid);
            });
        } elseif ($modelUuid !== null) {
            $query->where('model_uuid', $modelUuid);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findByUuid(string $uuid): ?FileMedia
    {
        return FileMedia::where('uuid', $uuid)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $uuid): void
    {
        $file = $this->findByUuid($uuid);

        if ($file !== null) {
            $file->deleteFully();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deleteByCollection(object $model, string $collection): void
    {
        $files = $this->getByCollection($model, $collection);

        foreach ($files as $file) {
            $file->deleteFully();
        }
    }

    /**
     * Genera un nombre de archivo único.
     *
     * La extensión sale del CONTENIDO (`guessExtension()` mira los bytes), no del
     * nombre que manda el cliente: con `getClientOriginalExtension()` un PNG
     * válido llamado `x.php` se guardaba como `x.php`. Si el contenido no es de
     * un tipo permitido, queda `.bin`, que ningún servidor ejecuta ni interpreta.
     */
    private function generateFilename(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->guessExtension());

        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        if (! in_array($extension, self::EXTENSIONES, true)) {
            $extension = 'bin';
        }

        return Str::uuid()->toString().'.'.$extension;
    }

    /**
     * Construye la ruta de almacenamiento: `file_media/{model_type}/{collection}/{filename}`.
     */
    private function buildPath(object $model, string $collection, string $filename): string
    {
        $modelSlug = $this->slugifyClassName($model::class);

        return "file_media/{$modelSlug}/{$collection}/{$filename}";
    }

    /**
     * Convierte el nombre de clase en un slug seguro para rutas.
     */
    private function slugifyClassName(string $class): string
    {
        return Str::slug(str_replace('\\', '_', $class), '_');
    }

    /**
     * Obtiene el id numérico del modelo, si lo tiene.
     */
    private function getModelId(object $model): ?int
    {
        if (method_exists($model, 'getAttribute')) {
            /** @var mixed $id */
            $id = $model->getAttribute('id');

            return is_int($id) || is_numeric($id) ? (int) $id : null;
        }

        if (property_exists($model, 'id')) {
            return (int) $model->id;
        }

        return null;
    }

    /**
     * Obtiene el uuid del modelo, si lo tiene.
     */
    private function getModelUuid(object $model): ?string
    {
        if (method_exists($model, 'getAttribute')) {
            /** @var mixed $uuid */
            $uuid = $model->getAttribute('uuid');
        } elseif (property_exists($model, 'uuid')) {
            /** @var mixed $uuid */
            $uuid = $model->uuid;
        } else {
            return null;
        }

        return is_string($uuid) ? $uuid : null;
    }
}
