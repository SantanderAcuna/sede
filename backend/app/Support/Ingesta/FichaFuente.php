<?php

declare(strict_types=1);

namespace App\Support\Ingesta;

/**
 * Todo lo que la fuente declara sobre un trámite, ya reunido.
 *
 * Es la frontera entre la red y el catálogo. Lo que hay aquí viene de la API de
 * contenido de GOV.CO tal cual, sin interpretar: los campos que la Entidad publica
 * se deciden después, en `MapeoFicha`, y ese reparto es lo que permite probar el
 * mapeo —incluido el descarte de datos personales— sin tocar la red.
 *
 * Las listas se guardan crudas porque cada respuesta tiene su forma y normalizarlas
 * aquí repartiría el conocimiento de la fuente entre dos clases. Se conserva el
 * nombre original de cada campo en los bloques que se copian literales, para que
 * el mapeo se pueda auditar contra la respuesta.
 *
 * `faltantes` no es una lista de errores: son los bloques que la fuente **no
 * declara** para este trámite. La API responde 200 con una lista vacía cuando no
 * tiene el dato, así que sin esta lista un trámite sin normativa y un trámite cuya
 * normativa no se pudo pedir se verían igual, y no son lo mismo.
 */
final readonly class FichaFuente
{
    /**
     * @param  list<array<string, mixed>>  $perfiles
     * @param  list<array<string, mixed>>  $puntosAtencion
     * @param  list<array<string, mixed>>  $normativa
     * @param  list<array<string, mixed>>  $seguimientoPersonal
     * @param  list<array<string, mixed>>  $seguimientoNoPersonal
     * @param  list<AccionFuente>  $acciones
     * @param  list<string>  $faltantes
     */
    public function __construct(
        public string $codigo,
        public ?string $nombre,
        public ?string $proposito,
        public ?string $tipoTramite,
        public ?string $urlTramiteEnLinea,
        public ?string $paginaWeb,
        public ?string $costoDeclarado,
        public ?string $tiempoObtencion,
        public ?string $resultadoObtiene,
        public ?string $observacionTiempo,
        public array $perfiles,
        public array $puntosAtencion,
        public array $normativa,
        public array $seguimientoPersonal,
        public array $seguimientoNoPersonal,
        public array $acciones,
        public array $faltantes,
    ) {}
}
