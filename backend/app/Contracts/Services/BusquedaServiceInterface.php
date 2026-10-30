<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\DTOs\Busqueda\ResultadoBusqueda;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Los casos de uso de la búsqueda.
 *
 * El servicio traduce lo que escribió el ciudadano —que puede traer tildes mal
 * puestas, comodines o barras— en un término comparable, y decide cuándo no hay
 * nada que buscar. El repositorio no vuelve a mirar el texto crudo.
 */
interface BusquedaServiceInterface
{
    /**
     * @return list<ResultadoBusqueda>
     */
    public function sugerir(string $texto, int $limite): array;

    /**
     * @return LengthAwarePaginator<int, ResultadoBusqueda>
     */
    public function buscar(string $texto, ?string $tipo, int $pagina, int $porPagina): LengthAwarePaginator;
}
