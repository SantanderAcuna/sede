<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\TramiteRepositoryInterface;
use App\Models\Tramite;
use App\Support\Tramites\FiltrosTramite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * El catálogo de trámites, sobre Eloquent.
 *
 * La regla de publicación vive en `Tramite::scopePublicados` (la comparte con la búsqueda); aquí
 * se aplican los filtros facetados y se cargan las relaciones que la tarjeta y la ficha necesitan.
 */
final class TramiteRepository implements TramiteRepositoryInterface
{
    public function paginar(FiltrosTramite $filtros): LengthAwarePaginator
    {
        return $this->filtrado($filtros)
            // La dependencia y el tema viajan en cada tarjeta; se cargan por lote para no disparar
            // una consulta por fila (N+1).
            ->with(['dependencia', 'categoria'])
            // El orden es por nombre —el que espera quien recorre un directorio— y es total, así que
            // dos páginas seguidas no repiten ni se saltan filas.
            ->orderBy('nombre')
            ->paginate(perPage: $filtros->porPagina, page: $filtros->pagina);
    }

    public function porSlug(string $slug): ?Tramite
    {
        return Tramite::query()
            ->publicados()
            ->with(['dependencia', 'categoria', ...Tramite::RELACIONES_FICHA])
            ->where('slug', $slug)
            ->first();
    }

    public function facetas(FiltrosTramite $filtros): array
    {
        return [
            'tipos' => $this->contarPorColumna($this->grupoBase($filtros, conTipo: false), 'type'),
            'modalidades' => $this->contarPorColumna($this->grupoBase($filtros), 'modalidad'),
            'costos' => $this->contarPorColumna($this->grupoBase($filtros), 'tiene_costo'),
            'categorias' => $this->contarCategorias($this->grupoBase($filtros)),
            'dependencias' => $this->contarDependencias($this->grupoBase($filtros)),
            'solicitantes' => $this->contarSolicitantes($this->grupoBase($filtros)),
        ];
    }

    /**
     * La consulta del listado: el grupo activo, la búsqueda y las facetas seleccionadas.
     *
     * @return Builder<Tramite>
     */
    private function filtrado(FiltrosTramite $filtros): Builder
    {
        $consulta = $this->grupoBase($filtros);

        if ($filtros->modalidad !== null) {
            $consulta->where('modalidad', $filtros->modalidad);
        }
        if ($filtros->costo !== null) {
            $consulta->where('tiene_costo', $filtros->costo);
        }
        if ($filtros->categoria !== null) {
            $consulta->whereHas('categoria', fn (Builder $q) => $q->where('slug', $filtros->categoria));
        }
        if ($filtros->dependencia !== null) {
            $consulta->where('dependencia_id', $filtros->dependencia);
        }
        if ($filtros->solicitante !== null) {
            $consulta->whereHas('audiencias', fn (Builder $q) => $q->where('grupo', $filtros->solicitante));
        }

        return $consulta;
    }

    /**
     * La base de las facetas: los publicados del grupo activo y la búsqueda, **sin** las facetas
     * seleccionadas, para que cada recuento muestre todas las opciones y elegir una no borre las
     * demás.
     *
     * @return Builder<Tramite>
     */
    private function grupoBase(FiltrosTramite $filtros, bool $conTipo = true): Builder
    {
        $consulta = Tramite::query()->publicados();

        if ($conTipo) {
            // El grupo (tramites/opa/consultas) acota todas las facetas salvo la del propio tipo.
            $consulta->where('type', $filtros->grupo());
        }
        if ($filtros->buscar !== null) {
            $consulta->where('busqueda', 'like', '%'.Tramite::normalizar($filtros->buscar).'%');
        }

        return $consulta;
    }

    /**
     * @param  Builder<Tramite>  $consulta
     * @return list<array{valor: string, total: int}>
     */
    private function contarPorColumna(Builder $consulta, string $columna): array
    {
        return array_values($consulta
            ->getQuery()
            ->select($columna, DB::raw('count(*) as total'))
            ->whereNotNull($columna)
            ->groupBy($columna)
            ->orderBy($columna)
            ->get()
            ->map(static fn (object $fila): array => [
                'valor' => (string) $fila->{$columna},
                'total' => (int) $fila->total,
            ])
            ->all());
    }

    /**
     * @param  Builder<Tramite>  $consulta
     * @return list<array{slug: string, nombre: string, total: int}>
     */
    private function contarCategorias(Builder $consulta): array
    {
        return array_values($consulta
            ->getQuery()
            ->join('tramite_categorias', 'tramites.categoria_id', '=', 'tramite_categorias.id')
            ->select('tramite_categorias.slug', 'tramite_categorias.nombre', DB::raw('count(*) as total'))
            ->groupBy('tramite_categorias.slug', 'tramite_categorias.nombre', 'tramite_categorias.orden')
            ->orderBy('tramite_categorias.orden')
            ->get()
            ->map(static fn (object $fila): array => [
                'slug' => (string) $fila->slug,
                'nombre' => (string) $fila->nombre,
                'total' => (int) $fila->total,
            ])
            ->all());
    }

    /**
     * @param  Builder<Tramite>  $consulta
     * @return list<array{id: int, nombre: string, total: int}>
     */
    private function contarDependencias(Builder $consulta): array
    {
        return array_values($consulta
            ->getQuery()
            ->join('dependencias', 'tramites.dependencia_id', '=', 'dependencias.id')
            ->select('dependencias.id', 'dependencias.nombre', DB::raw('count(*) as total'))
            ->groupBy('dependencias.id', 'dependencias.nombre', 'dependencias.orden')
            ->orderBy('dependencias.orden')
            ->get()
            ->map(static fn (object $fila): array => [
                'id' => (int) $fila->id,
                'nombre' => (string) $fila->nombre,
                'total' => (int) $fila->total,
            ])
            ->all());
    }

    /**
     * El tipo de solicitante sale de la tabla de audiencias: se cuenta cuántos trámites del grupo
     * tienen cada grupo de audiencia.
     *
     * @param  Builder<Tramite>  $consulta
     * @return list<array{valor: string, total: int}>
     */
    private function contarSolicitantes(Builder $consulta): array
    {
        return array_values($consulta
            ->getQuery()
            ->join('tramite_audiencias', 'tramites.id', '=', 'tramite_audiencias.tramite_id')
            ->select('tramite_audiencias.grupo', DB::raw('count(distinct tramites.id) as total'))
            ->groupBy('tramite_audiencias.grupo')
            ->orderBy('tramite_audiencias.grupo')
            ->get()
            ->map(static fn (object $fila): array => [
                'valor' => (string) $fila->grupo,
                'total' => (int) $fila->total,
            ])
            ->all());
    }
}
