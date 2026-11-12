<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\TramitePanelRepositoryInterface;
use App\Models\Tramite;
use App\Support\Busqueda\TextoBuscable;
use App\Support\Tramites\FiltrosTramitePanel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final class TramitePanelRepository implements TramitePanelRepositoryInterface
{
    public function paginar(int $porPagina, FiltrosTramitePanel $filtros): LengthAwarePaginator
    {
        $consulta = Tramite::query();

        if ($filtros->texto !== null) {
            // El nombre ya está normalizado (sin tildes, en minúsculas) en `busqueda_titulo`.
            // `%`, `_` y `\` son comodines de LIKE: se escapan para buscar el texto tal cual.
            $literal = addcslashes(TextoBuscable::normalizar($filtros->texto), '%_\\');
            $codigo = addcslashes($filtros->texto, '%_\\');
            $consulta->where(function ($q) use ($literal, $codigo): void {
                $q->where('busqueda_titulo', 'like', '%'.$literal.'%')
                    ->orWhere('codigo', 'ilike', '%'.$codigo.'%');
            });
        }

        match ($filtros->estado) {
            'publicado' => $consulta->publicados(),
            // «Borrador» es todo lo que el ciudadano no ve, incluido lo publicado con datos que faltan.
            'borrador' => $consulta->whereNotIn('id', Tramite::query()->publicados()->select('id')),
            default => null,
        };

        $columna = $filtros->ordenarPor === FiltrosTramitePanel::ORDEN_NOMBRE ? 'nombre' : 'updated_at';
        $sentido = $filtros->direccion === 'asc' ? 'asc' : 'desc';

        return $consulta->orderBy($columna, $sentido)->orderBy('id', $sentido)->paginate($porPagina);
    }

    public function porUuid(string $uuid): ?Tramite
    {
        // Un identificador que no es UUID («undefined») no existe: sin esto PostgreSQL rechaza la consulta.
        if (! Str::isUuid($uuid)) {
            return null;
        }

        return Tramite::query()->where('uuid', $uuid)->first();
    }

    public function existeSlug(string $slug): bool
    {
        return Tramite::query()->where('slug', $slug)->exists();
    }

    public function siguienteCodigo(): string
    {
        $prefijo = Tramite::PREFIJO_CODIGO_PANEL;
        $ultimo = Tramite::query()
            ->where('codigo', 'like', $prefijo.'%')
            ->pluck('codigo')
            ->map(static fn (string $c): int => ctype_digit(substr($c, 1)) ? (int) substr($c, 1) : 0)
            ->max() ?? 0;

        return $prefijo.str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT);
    }

    public function guardar(Tramite $tramite): Tramite
    {
        $tramite->save();

        return $tramite;
    }

    public function eliminar(Tramite $tramite): void
    {
        $tramite->delete();
    }
}
