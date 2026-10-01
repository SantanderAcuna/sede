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
     * Las dos condiciones son la misma regla vista desde dos lados: no se publica
     * lo que la Entidad no ha publicado —`publicado_en`— ni lo que no tiene ficha
     * en GOV.CO, que es el destino del clic en el nombre según el Anexo 2.1. Un
     * trámite sin ficha puede estar guardado mientras la Entidad la consigue;
     * mientras tanto no se ofrece.
     *
     * @return Builder<Tramite>
     */
    private function publicados(): Builder
    {
        return Tramite::query()
            ->whereNotNull('publicado_en')
            ->whereNotNull('url_ficha_gov_co');
    }
}
