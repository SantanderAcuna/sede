<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\FileMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

/**
 * Contrato para el servicio de gestión de archivos subidos.
 *
 * R-52: FilesMedia polymorphic — abstracción centralizada para todos los
 * archivos del sistema.
 */
interface FilesMediaServiceInterface
{
    /**
     * Guarda un archivo subido asociado a un modelo.
     *
     * @param  UploadedFile  $file  Archivo recibido en la petición
     * @param  string  $collection  Nombre de la colección (ej: 'logos', 'documentos', 'fotos')
     * @param  object  $model  Modelo propietario del archivo
     * @param  array<string, mixed>|null  $metadata  Metadatos opcionales
     */
    public function upload(
        UploadedFile $file,
        string $collection,
        object $model,
        ?array $metadata = null,
    ): FileMedia;

    /**
     * Obtiene todos los archivos de una colección para un modelo.
     *
     * @return Collection<int, FileMedia>
     */
    public function getByCollection(object $model, string $collection): Collection;

    /**
     * Obtiene un archivo por su UUID.
     */
    public function findByUuid(string $uuid): ?FileMedia;

    /**
     * Elimina un archivo por su UUID (archivo físico + registro).
     */
    public function delete(string $uuid): void;

    /**
     * Elimina todos los archivos de una colección para un modelo.
     */
    public function deleteByCollection(object $model, string $collection): void;
}
