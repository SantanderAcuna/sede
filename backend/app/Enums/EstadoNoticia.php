<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * En qué punto está una noticia, tal como lo ve quien la gestiona en el panel.
 *
 * No es una columna: sale de `activo` y de la fecha, comparada con el reloj del
 * SERVIDOR. Lo decide el servidor para que la etiqueta de la tabla, el filtro por
 * estado y lo que el sitio realmente muestra no puedan discrepar.
 *
 *  - borrador:   sin marcar como publicada. No se ve en el sitio, sea cual sea la fecha.
 *  - programada: publicada, pero su fecha aún no llega. Se verá sola cuando llegue.
 *  - publicada:  publicada y con la fecha cumplida. Es la que el sitio muestra.
 */
enum EstadoNoticia: string
{
    case BORRADOR = 'borrador';
    case PROGRAMADA = 'programada';
    case PUBLICADA = 'publicada';
}
