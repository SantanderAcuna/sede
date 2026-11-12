<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\NoticiaRepositoryInterface;
use App\Contracts\Services\FilesMediaServiceInterface;
use App\Contracts\Services\NoticiaServiceInterface;
use App\Exceptions\NoticiaIncompleta;
use App\Models\Noticia;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final class NoticiaService implements NoticiaServiceInterface
{
    public function __construct(
        private readonly NoticiaRepositoryInterface $noticias,
        private readonly FilesMediaServiceInterface $archivos,
    ) {}

    public function publicadas(int $porPagina, ?bool $destacada): LengthAwarePaginator
    {
        return $this->noticias->paginarPublicadas($porPagina, $destacada);
    }

    public function publicadaPorSlug(string $slug): ?Noticia
    {
        return $this->noticias->publicadaPorSlug($slug);
    }

    public function todas(int $porPagina): LengthAwarePaginator
    {
        return $this->noticias->paginarTodas($porPagina);
    }

    public function porUuid(string $uuid): ?Noticia
    {
        return $this->noticias->porUuid($uuid);
    }

    public function crear(array $datos, ?int $autorId): Noticia
    {
        unset($datos['imagen_alt']);

        $noticia = new Noticia($datos);
        $noticia->slug = $this->slugUnico((string) $datos['titulo']);
        $noticia->activo = (bool) ($datos['activo'] ?? false);
        $noticia->destacada = (bool) ($datos['destacada'] ?? false);
        $noticia->autor_id = $autorId;

        $this->exigirCompleta($noticia);

        return $this->noticias->guardar($noticia)->refresh();
    }

    public function actualizar(Noticia $noticia, array $datos): Noticia
    {
        $alt = $datos['imagen_alt'] ?? null;
        unset($datos['imagen_alt']);

        $noticia->fill($datos);

        $imagen = $noticia->imagenPrincipal;
        if ($alt !== null && $imagen !== null) {
            $imagen->metadata = ['alt' => trim((string) $alt)] + ($imagen->metadata ?? []);
        }

        // Antes de guardar nada: si queda publicada e incompleta, no cambia ni el texto ni el alt.
        $this->exigirCompleta($noticia);

        $this->noticias->guardar($noticia);
        if ($alt !== null && $imagen !== null) {
            $this->noticias->guardarImagen($imagen);
        }

        return $noticia->refresh();
    }

    public function guardarImagen(Noticia $noticia, UploadedFile $imagen, string $alt): Noticia
    {
        foreach ($this->archivos->getByCollection($noticia, Noticia::COLECCION_IMAGEN) as $anterior) {
            $this->archivos->delete($anterior->uuid);
        }

        $this->archivos->upload(
            file: $imagen,
            collection: Noticia::COLECCION_IMAGEN,
            model: $noticia,
            metadata: ['alt' => $alt],
        );

        return $noticia->refresh();
    }

    public function eliminar(Noticia $noticia): void
    {
        $this->noticias->eliminar($noticia);
    }

    /**
     * @throws NoticiaIncompleta
     */
    private function exigirCompleta(Noticia $noticia): void
    {
        if (! $noticia->activo) {
            return;
        }

        $problemas = $noticia->problemas();
        if ($problemas !== []) {
            throw new NoticiaIncompleta($problemas);
        }
    }

    /** Un slug que no choque con otro —ni con uno eliminado: la restricción única lo cuenta—. */
    private function slugUnico(string $titulo): string
    {
        $base = Str::limit(Str::slug($titulo), 190, '') ?: 'noticia';
        $slug = $base;

        for ($n = 2; $this->noticias->existeSlug($slug); $n++) {
            $slug = $base.'-'.$n;
        }

        return $slug;
    }
}
