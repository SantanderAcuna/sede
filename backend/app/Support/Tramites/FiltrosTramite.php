<?php

declare(strict_types=1);

namespace App\Support\Tramites;

/**
 * Los filtros del catálogo público, ya validados.
 *
 * Existe para que el servicio y el repositorio no reciban un arreglo sin forma: con `array`
 * cualquiera de los dos puede leer una clave que nadie escribió y el análisis estático no lo ve.
 * Aquí cada valor que el contrato admite es una propiedad tipada, y quien los construye es el
 * `FormRequest`, que es el único que conoce la petición HTTP.
 *
 * Los filtros facetados (modalidad, costo, dependencia, solicitante) son los que exige el
 * RF-B1-022 —búsqueda por nombre, modalidad, costo y tipo de solicitante— y los que la referencia
 * de presentación (directorio con facetas) pide para acotar el catálogo de un vistazo.
 */
final readonly class FiltrosTramite
{
    public function __construct(
        public int $pagina,
        public int $porPagina,
        public ?string $buscar,
        /** Slug de la temática (`tramite_categorias.slug`). */
        public ?string $categoria,
        /**
         * Grupo del catálogo: `tramites`, `opa` o `consultas`. Nulo se trata como `tramites`, que
         * es el grupo por defecto del Anexo 2.1.
         */
        public ?string $tipo,
        /** Modalidad: `en_linea`, `parcialmente_en_linea` o `presencial`. */
        public ?string $modalidad,
        /** Costo: `gratuito` o `con_costo`. */
        public ?string $costo,
        /** Id de la dependencia responsable. */
        public ?int $dependencia,
        /** Tipo de solicitante (grupo de audiencia): `ciudadano`, `extranjeros`, … */
        public ?string $solicitante,
    ) {}

    /** El grupo efectivo sobre el que se cuentan las facetas y se filtra (nulo = `tramites`). */
    public function grupo(): string
    {
        return $this->tipo ?? 'tramites';
    }
}
