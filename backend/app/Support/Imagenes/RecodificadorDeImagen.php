<?php

declare(strict_types=1);

namespace App\Support\Imagenes;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

/**
 * Vuelve a dibujar una imagen subida: se decodifica a píxeles y se codifica de nuevo.
 *
 * Lo que no son píxeles —EXIF/GPS, comentarios, bytes añadidos al final, un PHP
 * pegado tras un PNG válido— no sobrevive al viaje. El formato de salida es el mismo
 * de entrada (PNG, JPG o WEBP), decidido por el CONTENIDO y no por el nombre.
 */
final class RecodificadorDeImagen
{
    public function recodificar(UploadedFile $archivo): UploadedFile
    {
        $contenido = (string) $archivo->getContent();
        $info = @getimagesizefromstring($contenido);
        $origen = @imagecreatefromstring($contenido);

        if ($info === false || $origen === false) {
            throw new InvalidArgumentException('El archivo no es una imagen válida.');
        }

        [$formato, $extension] = match ($info[2]) {
            IMAGETYPE_PNG => ['png', 'png'],
            IMAGETYPE_JPEG => ['jpg', 'jpg'],
            IMAGETYPE_WEBP => ['webp', 'webp'],
            default => throw new InvalidArgumentException('Formato de imagen no permitido.'),
        };

        if ($formato === 'png' || $formato === 'webp') {
            imagealphablending($origen, false);
            imagesavealpha($origen, true);
        }

        ob_start();
        match ($formato) {
            'png' => imagepng($origen, null, 6),
            'jpg' => imagejpeg($origen, null, 85),
            'webp' => imagewebp($origen, null, 82),
        };
        $salida = (string) ob_get_clean();

        $ruta = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($ruta, $salida);

        return new UploadedFile($ruta, 'imagen.'.$extension, null, null, true);
    }
}
