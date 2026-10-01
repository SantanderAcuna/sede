<?php

declare(strict_types=1);

namespace App\Support\Ingesta;

/**
 * Una acción que la ficha oficial exige para adelantar un trámite.
 *
 * «Acción» es la palabra de la fuente y no una traducción libre: la API devuelve
 * `TipoAccionCondicion` con valores como `DOCUMENTO`, `VERIFICACION_INST`,
 * `SOLICITUD` o `PAGO`, y ese nombre se conserva aquí para que el mapeo se pueda
 * auditar contra la respuesta sin traducir nada de memoria.
 *
 * `cruda` guarda la acción tal cual vino. Se guarda entera y sin normalizar a
 * propósito: cada naturaleza trae campos distintos —un documento lleva cantidad y
 * unidad, una solicitud lleva canal y URL, un pago lleva moneda y cuentas— y
 * partirla en propiedades tipadas obligaría a un campo nulo por cada campo que no
 * aplique, que es cómo se acaba publicando una cantidad de cero en un pago.
 */
final readonly class AccionFuente
{
    /**
     * @param  array<string, mixed>  $cruda
     */
    public function __construct(
        public string $tipo,
        public int $orden,
        public ?string $momento,
        public array $cruda,
    ) {}
}
