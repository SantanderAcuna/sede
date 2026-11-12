<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** El trámite viene de la fuente oficial: borrarlo no serviría, la ingesta lo traería de vuelta. */
final class TramiteNoSePuedeEliminar extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Este trámite viene del catálogo oficial (SUIT) y no se puede eliminar: la carga automática lo volvería a traer. Para retirarlo del sitio, despublíquelo.');
    }
}
