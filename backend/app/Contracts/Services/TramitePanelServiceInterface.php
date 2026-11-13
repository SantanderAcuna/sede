<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Exceptions\TramiteIncompleto;
use App\Models\Tramite;
use App\Support\Tramites\FiltrosTramitePanel;
use Illuminate\Pagination\LengthAwarePaginator;

interface TramitePanelServiceInterface
{
    /** @return LengthAwarePaginator<int, Tramite> */
    public function listar(int $porPagina, FiltrosTramitePanel $filtros): LengthAwarePaginator;

    public function porUuid(string $uuid): ?Tramite;

    /**
     * @param  array<string, mixed>  $datos
     *
     * @throws TramiteIncompleto
     */
    public function crear(array $datos): Tramite;

    /**
     * @param  array<string, mixed>  $datos
     *
     * @throws TramiteIncompleto
     */
    public function actualizar(Tramite $tramite, array $datos): Tramite;

    /** Envía el elemento a la papelera (soft delete). Todo elemento es borrable. */
    public function eliminar(Tramite $tramite): void;
}
