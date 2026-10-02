<?php

declare(strict_types=1);

namespace App\Support\Tramites;

/**
 * Los filtros del catálogo público, ya validados.
 *
 * Existe para que el servicio y el repositorio no reciban un arreglo sin forma:
 * con `array` cualquiera de los dos puede leer una clave que nadie escribió y
 * el análisis estático no lo ve. Aquí los cuatro valores que el contrato admite
 * son propiedades tipadas, y quien los construye es el `FormRequest`, que es el
 * único que conoce la petición HTTP.
 */
final readonly class FiltrosTramite
{
    public function __construct(
        public int $pagina,
        public int $porPagina,
        public ?string $buscar,
        public ?string $categoria,
        /**
         * Modalidad del catálogo: `tramites`, `opa` o `consultas`. Nula devuelve
         * las tres modalidades a la vez, que es el comportamiento del catálogo
         * completo.
         */
        public ?string $tipo,
    ) {}
}
