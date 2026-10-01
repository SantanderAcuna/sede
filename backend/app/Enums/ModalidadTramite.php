<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Modalidad de un trámite: primer atributo obligatorio de los seis que exige la
 * Guía §5.1.3 de la Resolución 2893.
 *
 * Los valores son los del contrato, no una elección interna: lo que se guarda
 * aquí es lo que viaja en `modalidad`, y traducirlo en la capa de salida
 * abriría la puerta a que el catálogo y el contrato digan cosas distintas.
 *
 * La distinción entre `EN_LINEA` y `PARCIALMENTE_EN_LINEA` no es de matiz: el
 * ciudadano decide con ella si puede terminar el trámite sin desplazarse, y por
 * eso la fuente oficial la declara trámite por trámite en vez de deducirse.
 */
enum ModalidadTramite: string
{
    case EN_LINEA = 'en_linea';
    case PARCIALMENTE_EN_LINEA = 'parcialmente_en_linea';
    case PRESENCIAL = 'presencial';

    /** Nombre legible, para el panel y para cualquier ficha que lo muestre. */
    public function nombre(): string
    {
        return match ($this) {
            self::EN_LINEA => 'En línea',
            self::PARCIALMENTE_EN_LINEA => 'Parcialmente en línea',
            self::PRESENCIAL => 'Presencial',
        };
    }
}
