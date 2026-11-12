<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\NoticiaRepositoryInterface;
use App\Enums\EstadoNoticia;
use App\Models\FileMedia;
use App\Models\Noticia;
use App\Support\Noticias\FiltrosNoticiasPanel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

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

    public function paginarTodas(int $porPagina, FiltrosNoticiasPanel $filtros): LengthAwarePaginator
    {
        $consulta = Noticia::query()->with('imagenPrincipal');

        if ($filtros->titulo !== null) {
            // `%`, `_` y `\` son comodines de LIKE: se escapan para buscar el texto tal cual
            // («100%» no es «100 y lo que sea»). PostgreSQL usa la barra invertida como escape.
            $literal = addcslashes($filtros->titulo, '%_\\');
            $consulta->where('titulo', 'ilike', '%'.$literal.'%');
        }

        match ($filtros->estado) {
            EstadoNoticia::BORRADOR => $consulta->where('activo', false),
            EstadoNoticia::PROGRAMADA => $consulta->where('activo', true)->where('fecha_publicacion', '>', now()),
            EstadoNoticia::PUBLICADA => $consulta->where('activo', true)->where('fecha_publicacion', '<=', now()),
            null => null,
        };

        // La columna sale de una lista cerrada (`FiltrosNoticiasPanel::ORDENES`), nunca del cliente.
        $columna = $filtros->ordenarPor === FiltrosNoticiasPanel::ORDEN_TITULO ? 'titulo' : 'fecha_publicacion';
        $sentido = $filtros->direccion === 'asc' ? 'asc' : 'desc';

        // `id` desempata: con la misma fecha o el mismo título, dos páginas seguidas no repiten ni saltan filas.
        return $consulta->orderBy($columna, $sentido)->orderBy('id', $sentido)->paginate($porPagina);
    }

    public function porUuid(string $uuid): ?Noticia
    {
        // Un identificador que no es un UUID (p. ej. «undefined») no existe: sin esto
        // PostgreSQL rechaza la consulta y el ciudadano ve un 500 en lugar de un 404.
        if (! Str::isUuid($uuid)) {
            return null;
        }

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
