<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Se quiso publicar un trámite al que le faltan datos obligatorios. */
final class TramiteIncompleto extends RuntimeException
{
    /** @param list<string> $problemas */
    public function __construct(public readonly array $problemas)
    {
        parent::__construct('El trámite no se puede publicar: le faltan datos.');
    }
}
