<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\FileMedia;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Imágenes que el editor del panel inserta en el contenido (noticias, políticas, entidad).
 */
interface ImagenContenidoServiceInterface
{
    public const COLECCION = 'contenido';

    /** Re-codifica la imagen y la guarda con `FilesMediaService`; el propietario es quien la sube. */
    public function subir(User $autor, UploadedFile $imagen, string $alt): FileMedia;

    /** Sólo las de la colección «contenido»: ningún otro archivo se publica por aquí. */
    public function publica(string $uuid): ?FileMedia;

    /** La URL pública estable con la que se inserta en el HTML. */
    public function urlPublica(FileMedia $imagen): string;
}
