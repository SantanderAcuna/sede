<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\TramitePanelRepositoryInterface;
use App\Models\Tramite;
use App\Support\Busqueda\TextoBuscable;
use App\Support\Tramites\EscritorFichaRelacional;
use App\Support\Tramites\FiltrosTramitePanel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * El acceso a los trámites desde el panel: lista, lectura, guardado con su ficha relacional y
 * borrado a la papelera.
 */
final class TramitePanelRepository implements TramitePanelRepositoryInterface
{
    public function paginar(int $porPagina, FiltrosTramitePanel $filtros): LengthAwarePaginator
    {
        $consulta = Tramite::query();

        if ($filtros->texto !== null) {
            $literal = addcslashes(TextoBuscable::normalizar($filtros->texto), '%_\\');
            $codigo = addcslashes($filtros->texto, '%_\\');
            $consulta->where(function (Builder $q) use ($literal, $codigo): void {
                $q->where('busqueda_titulo', 'like', '%'.$literal.'%')
                    ->orWhere('codigo', 'ilike', '%'.$codigo.'%');
            });
        }

        if ($filtros->tipo !== null) {
            $consulta->where('type', $filtros->tipo);
        }

        match ($filtros->estado) {
            'publicado' => $consulta->publicados(),
            // «Borrador» es todo lo que el ciudadano no ve, incluido lo publicado con datos que faltan.
            'borrador' => $consulta->whereNotIn('id', Tramite::query()->publicados()->select('id')),
            default => null,
        };

        $columna = $filtros->ordenarPor === FiltrosTramitePanel::ORDEN_NOMBRE ? 'nombre' : 'updated_at';
        $sentido = $filtros->direccion === 'asc' ? 'asc' : 'desc';

        // `withCount` deja que la fila calcule si está completa sin una consulta por trámite (N+1).
        return $consulta->with('dependencia')->withCount('requisitos')->orderBy($columna, $sentido)->orderBy('id', $sentido)->paginate($porPagina);
    }

    public function porUuid(string $uuid): ?Tramite
    {
        // Un identificador que no es UUID («undefined») no existe: sin esto PostgreSQL rechaza la consulta.
        if (! Str::isUuid($uuid)) {
            return null;
        }

        return Tramite::query()
            ->with(['categoria', 'dependencia', ...Tramite::RELACIONES_FICHA])
            ->where('uuid', $uuid)
            ->first();
    }

    public function existeSlug(string $slug): bool
    {
        // Incluye los enviados a la papelera: su `slug` sigue ocupando el índice único.
        return Tramite::withTrashed()->where('slug', $slug)->exists();
    }

    public function siguienteCodigo(string $tipo): string
    {
        $prefijo = Tramite::PREFIJO_CODIGO[$tipo] ?? 'T';
        $ultimo = Tramite::withTrashed()
            ->where('codigo', 'like', $prefijo.'%')
            ->pluck('codigo')
            ->map(static fn (string $c): int => ctype_digit(substr($c, strlen($prefijo))) ? (int) substr($c, strlen($prefijo)) : 0)
            ->max() ?? 0;

        return $prefijo.str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT);
    }

    public function guardar(Tramite $tramite, ?array $ficha = null): Tramite
    {
        return DB::transaction(function () use ($tramite, $ficha): Tramite {
            $tramite->save();

            if ($ficha !== null) {
                // Mismo escritor que la carga inicial: el formulario envía la ficha entera, se borra
                // lo anterior y se reinserta.
                EscritorFichaRelacional::escribir($tramite, $ficha);
            }

            return $tramite;
        });
    }

    public function eliminar(Tramite $tramite): void
    {
        // A la papelera (soft delete): las filas hijas quedan con el trámite para poder restaurarlo.
        $tramite->delete();
    }
}
