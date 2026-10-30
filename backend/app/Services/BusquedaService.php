<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\BusquedaRepositoryInterface;
use App\Contracts\Services\BusquedaServiceInterface;
use App\DTOs\Busqueda\ResultadoBusqueda;
use App\Support\Busqueda\TextoBuscable;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Los casos de uso de la búsqueda de la sede.
 */
final class BusquedaService implements BusquedaServiceInterface
{
    /** El contrato exige tres caracteres como mínimo; se vuelve a comprobar tras normalizar. */
    private const MINIMO = 3;

    public function __construct(private readonly BusquedaRepositoryInterface $busqueda) {}

    public function sugerir(string $texto, int $limite): array
    {
        $consulta = TextoBuscable::paraConsulta($texto);

        // «___» o «%%%» pasan la validación (tienen tres caracteres) y quedan vacíos
        // al normalizar. No es un error del ciudadano: simplemente no hay nada que
        // buscar, y la respuesta correcta es una lista vacía, no el catálogo entero.
        if (mb_strlen($consulta) < self::MINIMO) {
            return [];
        }

        return $this->busqueda->sugerir($consulta, $this->palabras($consulta), $limite);
    }

    public function buscar(string $texto, ?string $tipo, int $pagina, int $porPagina): LengthAwarePaginator
    {
        $consulta = TextoBuscable::paraConsulta($texto);

        if (mb_strlen($consulta) < self::MINIMO) {
            /** @var LengthAwarePaginator<int, ResultadoBusqueda> $vacia */
            $vacia = new LengthAwarePaginator([], 0, $porPagina, $pagina);

            return $vacia;
        }

        return $this->busqueda->buscar($consulta, $this->palabras($consulta), $tipo, $pagina, $porPagina);
    }

    /**
     * @return list<string>
     */
    private function palabras(string $consulta): array
    {
        return TextoBuscable::palabras(
            $consulta,
            (int) config('busqueda.palabra_minima'),
            (int) config('busqueda.palabras_maximas'),
        );
    }
}
