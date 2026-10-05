<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\FilesMediaServiceInterface;
use App\Models\FileMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Servicio centralizado de gestión de archivos subidos.
 *
 * Implementa el patrón polimórfico: un archivo puede asociarse a cualquier modelo
 * sin necesidad de tables intermedias. El archivo físico se almacena en el disco
 * configurado (por defecto `public`) y los metadatos + la referencia viven en
 * `file_media`.
 *
 * R-52: FilesMedia polymorphic.
 */
final class FilesMediaService implements FilesMediaServiceInterface
{
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

        // Guardar el archivo físico en el disco configurado
        Storage::disk('public')->put($path, $file->getContent());

        return FileMedia::create([
            'model_type' => $model::class,
            'model_id' => $this->getModelId($model),
            'model_uuid' => $this->getModelUuid($model),
            'collection' => $collection,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize() ?? 0,
            'disk' => 'public',
            'path' => $path,
            'metadata' => $metadata,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getByCollection(object $model, string $collection): Collection
    {
        return FileMedia::where('model_type', $model::class)
            ->where('model_id', $this->getModelId($model))
            ->where('collection', $collection)
            ->orderBy('created_at', 'desc')
            ->get();
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
     * Genera un nombre de archivo único preservando la extensión.
     */
    private function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();

        return Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
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
        return property_exists($model, 'id') ? (int) $model->id : null;
    }

    /**
     * Obtiene el uuid del modelo, si lo tiene.
     */
    private function getModelUuid(object $model): ?string
    {
        return property_exists($model, 'uuid') ? $model->uuid : null;
    }
}
