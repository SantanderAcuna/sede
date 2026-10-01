<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Canal por el que se inicia el trámite: cuarto atributo obligatorio de los seis.
 *
 * La distinción que el contrato pide es la que le importa al ciudadano: si el
 * trámite se inicia en un canal de la Entidad o en un portal del Estado que no
 * es suyo. En el primer caso la Sede puede acompañarlo; en el segundo sale del
 * dominio de la Entidad y debe decirlo antes de que pulse.
 */
enum CanalInicioTramite: string
{
    case PROPIO = 'propio';
    case PORTAL_NACIONAL = 'portal_nacional';

    /** Nombre legible, para el panel y para cualquier ficha que lo muestre. */
    public function nombre(): string
    {
        return match ($this) {
            self::PROPIO => 'Canal propio de la Entidad',
            self::PORTAL_NACIONAL => 'Portal nacional',
        };
    }
}
