<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Si el trámite tiene costo: segundo atributo obligatorio de los seis.
 *
 * Se guarda como enumeración y no como booleano porque el contrato lo publica
 * como `gratuito` o `con_costo` y porque un booleano acaba imprimiéndose como
 * `true` en alguna ficha, que es justo lo que el ciudadano no debe leer para
 * saber si tiene que pagar.
 *
 * El importe, cuando lo hay, viaja aparte en `costo`: son dos datos distintos y
 * confundirlos obliga a interpretar una cadena vacía como «gratis».
 */
enum CostoTramite: string
{
    case GRATUITO = 'gratuito';
    case CON_COSTO = 'con_costo';

    /** Nombre legible, para el panel y para cualquier ficha que lo muestre. */
    public function nombre(): string
    {
        return match ($this) {
            self::GRATUITO => 'Gratuito',
            self::CON_COSTO => 'Con costo',
        };
    }
}
