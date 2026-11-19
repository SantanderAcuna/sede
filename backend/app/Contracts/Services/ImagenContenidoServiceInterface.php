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
    /**
     * Re-codifica la imagen y la guarda con `FilesMediaService` (uso `contenido`; `subido_por` es quien la sube).
     * El texto alternativo no se guarda con el archivo: vive en el `<img>` del cuerpo donde se usa.
     */
    public function subir(User $autor, UploadedFile $imagen): FileMedia;

    /**
     * Sólo las de la colección «contenido»: ningún otro archivo se publica por aquí.
     *
     * Quien edita contenido (`$comoEditor`) ve cualquiera, para previsualizar borradores; el público solo ve la
     * que alguna noticia YA PUBLICADA incrusta (activa, con fecha cumplida y sin eliminar).
     */
    public function publica(string $uuid, bool $comoEditor = false): ?FileMedia;

    /** La URL pública estable con la que se inserta en el HTML. */
    public function urlPublica(FileMedia $imagen): string;
}
