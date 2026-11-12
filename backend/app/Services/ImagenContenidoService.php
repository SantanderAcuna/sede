<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\FilesMediaServiceInterface;
use App\Contracts\Services\ImagenContenidoServiceInterface;
use App\Models\FileMedia;
use App\Models\User;
use App\Support\Imagenes\RecodificadorDeImagen;
use Illuminate\Http\UploadedFile;

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
            return $this->archivos->upload(
                file: $limpia,
                collection: self::COLECCION,
                model: $autor,
                metadata: ['alt' => $alt],
            );
        } finally {
            @unlink($limpia->getPathname());
        }
    }

    public function publica(string $uuid): ?FileMedia
    {
        $archivo = $this->archivos->findByUuid($uuid);

        return $archivo !== null && $archivo->collection === self::COLECCION ? $archivo : null;
    }

    public function urlPublica(FileMedia $imagen): string
    {
        return '/api/v1/contenido/imagenes/'.$imagen->uuid;
    }
}
