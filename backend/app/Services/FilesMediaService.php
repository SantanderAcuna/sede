<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\FilesMediaServiceInterface;
use App\Models\FileMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Servicio centralizado de gestión de archivos subidos.
 *
 * El archivo físico se almacena en el disco por defecto de la configuración (`FILESYSTEM_DISK`) y su
 * referencia vive en `file_media`; cada fila recuerda en qué disco quedó, así que cambiar de disco no
 * deja huérfanos los archivos anteriores. Quién usa cada archivo lo dicen las relaciones explícitas
 * de noticias, banners y políticas, no este servicio.
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
    public function upload(UploadedFile $file, string $uso, ?int $subidoPor = null): FileMedia
    {
        if (! in_array($uso, [FileMedia::USO_IMAGEN, FileMedia::USO_CONTENIDO, FileMedia::USO_DOCUMENTO], true)) {
            throw new InvalidArgumentException("Uso de archivo desconocido: {$uso}");
        }

        $filename = $this->generateFilename($file);
        $path = "file_media/{$uso}/{$filename}";
        $contenido = $file->getContent();

        // El disco configurado, no uno fijo: antes era siempre `public` aunque
        // `FILESYSTEM_DISK` dijera otra cosa, y los archivos quedaban donde nadie
        // los servía (H-13).
        $disco = (string) config('filesystems.default');
        Storage::disk($disco)->put($path, $contenido);

        return FileMedia::create([
            'uso' => $uso,
            'subido_por' => $subidoPor,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => (int) $file->getSize(),
            'disk' => $disco,
            'path' => $path,
            'hash_sha256' => hash('sha256', $contenido),
        ]);
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
}
