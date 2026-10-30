<?php

declare(strict_types=1);

namespace App\DTOs\Busqueda;

/**
 * Un resultado de la búsqueda, ya sea trámite o política.
 *
 * Es un objeto de valor y no un modelo a propósito: un resultado reúne campos de
 * dos tablas distintas bajo un mismo nombre (`titulo`, `fecha`, `extracto`) y no
 * es una fila de ninguna. Los dos recursos del contrato —sugerencia y
 * resultado— se construyen desde él; `extracto` y `fecha` sólo los usa el segundo.
 */
final readonly class ResultadoBusqueda
{
    public function __construct(
        public string $tipoRecurso,
        public int $id,
        public string $titulo,
        public string $categoria,
        public ?string $extracto,
        public ?string $fecha,
        public string $url,
        public float $score,
    ) {}
}
