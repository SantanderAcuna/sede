<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\NoticiaRepositoryInterface;
use App\Models\FileMedia;
use App\Models\Noticia;
use Illuminate\Pagination\LengthAwarePaginator;

final class NoticiaRepository implements NoticiaRepositoryInterface
{
    public function paginarPublicadas(int $porPagina, ?bool $destacada): LengthAwarePaginator
    {
        $consulta = Noticia::query()->publicadas()->with('imagenPrincipal');

        if ($destacada !== null) {
            $consulta->where('destacada', $destacada);
        }

        return $consulta->paginate($porPagina);
    }

    public function publicadaPorSlug(string $slug): ?Noticia
    {
        return Noticia::query()->publicadas()->with('imagenPrincipal')->where('slug', $slug)->first();
    }

    public function paginarTodas(int $porPagina): LengthAwarePaginator
    {
        return Noticia::query()->with('imagenPrincipal')
            ->orderByDesc('fecha_publicacion')->orderByDesc('id')
            ->paginate($porPagina);
    }

    public function porUuid(string $uuid): ?Noticia
    {
        return Noticia::query()->with('imagenPrincipal')->where('uuid', $uuid)->first();
    }

    public function existeSlug(string $slug): bool
    {
        return Noticia::withTrashed()->where('slug', $slug)->exists();
    }

    public function guardar(Noticia $noticia): Noticia
    {
        $noticia->save();

        return $noticia;
    }

    public function eliminar(Noticia $noticia): void
    {
        $noticia->delete();
    }

    public function guardarImagen(FileMedia $imagen): void
    {
        $imagen->save();
    }
}
