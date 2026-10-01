<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Exceptions\TramiteNoEncontrado;
use App\Models\Tramite;
use App\Support\Tramites\FiltrosTramite;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Los casos de uso del catálogo de trámites.
 *
 * El servicio existe para que el controlador no decida nada: traduce una
 * petición ya validada en una consulta y convierte «no hay nada con ese slug» en
 * el error que el contrato declara. La regla de qué se publica no está aquí a
 * propósito, sino en el repositorio: dos sitios comprobando lo mismo acaban
 * discrepando.
 */
interface TramiteServiceInterface
{
    /**
     * @return LengthAwarePaginator<int, Tramite>
     */
    public function listar(FiltrosTramite $filtros): LengthAwarePaginator;

    /**
     * @throws TramiteNoEncontrado
     */
    public function porSlug(string $slug): Tramite;
}
