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
 * El servicio existe para que el controlador no decida nada: traduce una petición ya validada en
 * una consulta y convierte «no hay nada con ese slug» en el error que el contrato declara. La regla
 * de qué se publica no está aquí a propósito, sino en el modelo (`Tramite::scopePublicados`).
 */
interface TramiteServiceInterface
{
    /**
     * @return LengthAwarePaginator<int, Tramite>
     */
    public function listar(FiltrosTramite $filtros): LengthAwarePaginator;

    /**
     * El recuento de cada faceta dentro del grupo y la búsqueda vigentes.
     *
     * @return array{
     *     tipos: list<array{valor: string, total: int}>,
     *     modalidades: list<array{valor: string, total: int}>,
     *     costos: list<array{valor: string, total: int}>,
     *     categorias: list<array{slug: string, nombre: string, total: int}>,
     *     dependencias: list<array{id: int, nombre: string, total: int}>,
     *     solicitantes: list<array{valor: string, total: int}>
     * }
     */
    public function facetas(FiltrosTramite $filtros): array;

    /**
     * @throws TramiteNoEncontrado
     */
    public function porSlug(string $slug): Tramite;
}
