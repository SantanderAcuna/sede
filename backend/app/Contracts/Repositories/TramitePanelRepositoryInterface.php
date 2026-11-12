<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Tramite;
use App\Support\Tramites\FiltrosTramitePanel;
use Illuminate\Pagination\LengthAwarePaginator;

interface TramitePanelRepositoryInterface
{
    /** @return LengthAwarePaginator<int, Tramite> */
    public function paginar(int $porPagina, FiltrosTramitePanel $filtros): LengthAwarePaginator;

    public function porUuid(string $uuid): ?Tramite;

    public function existeSlug(string $slug): bool;

    /** El siguiente código libre para el tipo dado: `T0001` (trámite), `O0001` (OPA), `C0001` (consulta). */
    public function siguienteCodigo(string $tipo): string;

    /**
     * Guarda el trámite y, cuando se recibe la ficha convertida, reemplaza sus filas hijas en una
     * transacción.
     *
     * @param  array<string, mixed>|null  $ficha
     */
    public function guardar(Tramite $tramite, ?array $ficha = null): Tramite;

    public function eliminar(Tramite $tramite): void;
}
