<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\TramiteRepositoryInterface;
use App\Models\Tramite;
use App\Support\Tramites\FiltrosTramite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * El catálogo de trámites, sobre Eloquent.
 */
final class TramiteRepository implements TramiteRepositoryInterface
{
    public function paginar(FiltrosTramite $filtros): LengthAwarePaginator
    {
        $consulta = $this->publicados();

        if ($filtros->tipo !== null) {
            // El filtro de modalidad acota el catálogo a uno de los tres grupos
            // del Anexo 2.1. Sin él, la consulta devuelve las tres modalidades.
            $consulta->where('type', $filtros->tipo);
        }

        if ($filtros->categoria !== null) {
            $consulta->where('categoria_slug', $filtros->categoria);
        }

        if ($filtros->buscar !== null) {
            // Se busca contra el texto normalizado —sin tildes, en minúsculas— y
            // no contra el nombre: es lo que hace que quien escribe «tramite»
            // encuentre «trámite».
            $consulta->where('busqueda', 'like', '%'.Tramite::normalizar($filtros->buscar).'%');
        }

        // El orden es por nombre y no por fecha porque es el que espera quien
        // recorre el catálogo; y es un orden total, así que dos páginas seguidas
        // no repiten ni se saltan filas.
        return $consulta
            ->orderBy('nombre')
            ->paginate(perPage: $filtros->porPagina, page: $filtros->pagina);
    }

    public function porSlug(string $slug): ?Tramite
    {
        return $this->publicados()->where('slug', $slug)->first();
    }

    /**
     * Los trámites que el ciudadano puede ver.
     *
     * La regla de publicación —los seis atributos obligatorios del §5.1.3 de la
     * Guía, la ficha en GOV.CO y el alta de la Entidad— vive en
     * `Tramite::scopePublicados`, porque la comparte con la búsqueda: dos copias
     * acaban discrepando.
     *
     * @return Builder<Tramite>
     */
    private function publicados(): Builder
    {
        return Tramite::query()->publicados();
    }
}
