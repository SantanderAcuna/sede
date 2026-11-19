<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\FileMedia;
use Illuminate\Http\UploadedFile;

/**
 * Contrato para el servicio de gestión de archivos subidos: abstracción centralizada para todos los
 * archivos del sistema.
 */
interface FilesMediaServiceInterface
{
    /**
     * Guarda un archivo subido. No tiene dueño: quien lo usa lo vincula después con su relación explícita.
     *
     * @param  UploadedFile  $file  Archivo recibido en la petición
     * @param  string  $uso  `FileMedia::USO_*`: imagen, contenido o documento
     * @param  int|null  $subidoPor  Id del usuario que lo subió (solo el editor de contenido lo registra)
     */
    public function upload(UploadedFile $file, string $uso, ?int $subidoPor = null): FileMedia;

    /**
     * Obtiene un archivo por su UUID.
     */
    public function findByUuid(string $uuid): ?FileMedia;

    /**
     * Elimina un archivo por su UUID (archivo físico + registro).
     */
    public function delete(string $uuid): void;
}
