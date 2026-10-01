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
     * La regla es la del §5.1.3 de la Guía: **no se publica un trámite al que le
     * falte uno de sus seis atributos obligatorios**. Vive aquí y no en el modelo
     * ni en el controlador porque este repositorio es la única puerta por la que
     * el ciudadano llega al catálogo: si la regla estuviera repartida, bastaría con
     * añadir una consulta nueva para saltársela sin que nada fallara.
     *
     * Las tres condiciones son la misma regla vista desde tres lados:
     *
     * - `publicado_en`: la Entidad lo ha dado de alta.
     * - `url_ficha_gov_co`: tiene ficha en GOV.CO, que es el destino del clic en el
     *   nombre según el Anexo 2.1. Un trámite sin ficha puede estar guardado
     *   mientras la Entidad la consigue; mientras tanto no se ofrece.
     * - los seis atributos: desde que la ingesta de la ficha oficial puede guardar
     *   un trámite incompleto —para que la Entidad vea qué le falta en vez de
     *   perderlo—, la comprobación dejó de ser implícita en el esquema y tiene que
     *   ser explícita aquí. Sin ella, un trámite al que la fuente no le declara la
     *   modalidad se publicaría con la modalidad en nulo, que es justo lo que la
     *   Guía prohíbe.
     *
     * @return Builder<Tramite>
     */
    private function publicados(): Builder
    {
        return Tramite::query()
            ->whereNotNull('publicado_en')
            ->whereNotNull('url_ficha_gov_co')
            ->whereNotNull('modalidad')
            ->whereNotNull('tiene_costo')
            ->whereNotNull('tiempo_solucion_dias')
            ->whereNotNull('canal_inicio')
            ->whereNotNull('consulta_estado')
            ->whereNotNull('requisitos');
    }
}
