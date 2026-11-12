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

    /** El siguiente código libre para un trámite del panel: `P0001`, `P0002`… */
    public function siguienteCodigo(): string;

    public function guardar(Tramite $tramite): Tramite;

    public function eliminar(Tramite $tramite): void;
}
