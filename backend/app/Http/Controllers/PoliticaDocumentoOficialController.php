<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Politica;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga del documento oficial adjunto a una política (T-20).
 *
 * El archivo vive en la tabla común `file_media` y se sube desde el panel. Esta
 * dirección es estable (`/politicas/<slug>/documento`): la URL firmada de
 * `FileMedia::url()` caduca a los 15 minutos y no sirve para enlazarla desde una
 * página pública. El contenido se transmite desde el disco donde quedó guardado.
 */
final class PoliticaDocumentoOficialController extends Controller
{
    public function __invoke(string $slug): StreamedResponse
    {
        $politica = Politica::query()->where('slug', $slug)->firstOrFail();
        $archivo = $politica->documentoOficial();

        abort_if($archivo === null, 404);

        $disco = Storage::disk($archivo->disk);

        abort_unless($disco->exists($archivo->path), 404);

        $flujo = $disco->readStream($archivo->path);
        abort_if($flujo === null, 404);

        // Un PDF se lee en el navegador; el resto (ODT, DOCX…) se descarga.
        $disposicion = $archivo->mime_type === 'application/pdf'
            ? HeaderUtils::DISPOSITION_INLINE
            : HeaderUtils::DISPOSITION_ATTACHMENT;

        // El nombre original lo puso quien subió el archivo: la versión ASCII que
        // exige la cabecera no puede llevar `%`, `/` ni `\`.
        $respaldo = str_replace(['%', '/', '\\', '"'], '_', Str::ascii($archivo->original_filename));

        return response()->stream(
            static function () use ($flujo): void {
                fpassthru($flujo);
                fclose($flujo);
            },
            200,
            [
                'Content-Type' => $archivo->mime_type,
                'Content-Length' => (string) $archivo->size,
                'Content-Disposition' => HeaderUtils::makeDisposition($disposicion, $archivo->original_filename, $respaldo),
                'Cache-Control' => 'public, max-age=300',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
