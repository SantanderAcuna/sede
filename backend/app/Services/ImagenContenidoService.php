<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\FilesMediaServiceInterface;
use App\Contracts\Services\ImagenContenidoServiceInterface;
use App\Models\FileMedia;
use App\Models\Noticia;
use App\Models\User;
use App\Support\Imagenes\RecodificadorDeImagen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class ImagenContenidoService implements ImagenContenidoServiceInterface
{
    public function __construct(
        private readonly FilesMediaServiceInterface $archivos,
        private readonly RecodificadorDeImagen $recodificador,
    ) {}

    public function subir(User $autor, UploadedFile $imagen, string $alt): FileMedia
    {
        $limpia = $this->recodificador->recodificar($imagen);

        try {
            $reutilizado = $this->reutilizar(hash('sha256', $limpia->getContent()));
            if ($reutilizado !== null) {
                return $reutilizado;
            }

            $archivo = $this->archivos->upload(
                file: $limpia,
                collection: self::COLECCION,
                model: $autor,
                metadata: ['alt' => $alt],
            );
            // En «contenido» el dueño ES quien subió; la columna nueva lo dice sin depender de model_type.
            $archivo->forceFill(['subido_por' => $autor->id])->save();

            return $archivo;
        } finally {
            @unlink($limpia->getPathname());
        }
    }

    /**
     * Si ya hay una imagen de contenido con esta misma huella (la re-codificación es determinista) y su archivo
     * sigue en el disco, se reutiliza. Se renueva `updated_at` con la hora de PHP en el mismo UPDATE que la
     * devuelve (atómico): la limpieza de las 03:30 no se la puede llevar entre la búsqueda y el uso, y si ya la
     * borró el UPDATE no devuelve nada y se sube una nueva.
     */
    private function reutilizar(string $huella): ?FileMedia
    {
        $candidatos = FileMedia::query()
            ->where('collection', self::COLECCION)->where('hash_sha256', $huella)
            ->orderBy('created_at')->orderBy('uuid')->limit(5)->get();

        foreach ($candidatos as $candidato) {
            if (! Storage::disk($candidato->disk)->exists($candidato->path)) {
                continue;
            }

            $filas = DB::select(
                'update file_media set updated_at = ? where uuid = ? and collection = ? returning uuid',
                [now(), $candidato->uuid, self::COLECCION],
            );
            if ($filas !== []) {
                return FileMedia::query()->where('uuid', $candidato->uuid)->first();
            }
        }

        return null;
    }

    public function publica(string $uuid, bool $comoEditor = false): ?FileMedia
    {
        $archivo = $this->archivos->findByUuid($uuid);

        if ($archivo === null || $archivo->collection !== self::COLECCION) {
            return null;
        }

        return $comoEditor || $this->laUsaUnaNoticiaPublicada($archivo) ? $archivo : null;
    }

    private function laUsaUnaNoticiaPublicada(FileMedia $archivo): bool
    {
        // `publicadas()` ordena para los listados; para un `exists` el ORDER BY sobra.
        return Noticia::query()->publicadas()->reorder()
            ->whereIn('id', DB::table('noticia_imagenes')->select('noticia_id')->where('file_media_uuid', $archivo->uuid))
            ->exists();
    }

    public function urlPublica(FileMedia $imagen): string
    {
        return '/api/v1/contenido/imagenes/'.$imagen->uuid;
    }
}
