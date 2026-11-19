<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Quien reordena el carrusel partió de una lista que ya no es la actual: otra persona la cambió
 * (reordenó, creó o eliminó una diapositiva) mientras la tenía abierta. Guardar sin avisar la pisaría.
 */
final class OrdenDeBannersDesactualizado extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('El carrusel cambió mientras usted lo editaba. Se recargó la lista: revise el orden e inténtelo de nuevo.');
    }
}
