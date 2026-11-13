<?php

declare(strict_types=1);

namespace App\Support\Api;

use App\Models\FileMedia;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Transmite una imagen guardada en disco como respuesta HTTP.
 *
 * Lo comparten la ruta pública de la imagen de una noticia y la del panel: la única
 * diferencia es la política de caché, que decide quien llama.
 */
final class RespuestaDeImagen
{
    public static function desde(FileMedia $imagen, string $cacheControl): Response
    {
        $disco = Storage::disk($imagen->disk);
        abort_unless($disco->exists($imagen->path), 404);

        $flujo = $disco->readStream($imagen->path);
        abort_if($flujo === null, 404);

        $respuesta = response()->stream(
            static function () use ($flujo): void {
                fpassthru($flujo);
                fclose($flujo);
            },
            200,
            [
                'Content-Type' => $imagen->mime_type,
                'Content-Length' => (string) $imagen->size,
                'Cache-Control' => $cacheControl,
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
        $respuesta->setEtag(sha1($imagen->uuid));

        return $respuesta;
    }
}
