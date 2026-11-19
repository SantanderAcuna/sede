<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Se intentó borrar un archivo que una noticia, un banner o un documento de política todavía usa.
 *
 * La base lo impide con claves foráneas `ON DELETE RESTRICT` (PostgreSQL 18 lo reporta como `23001` y la 16
 * como `23503`); esta excepción es la forma de dominio, para que el controlador responda 409 y no 500.
 */
final class ArchivoEnUso extends RuntimeException
{
    public function __construct(public readonly string $uuid, ?Throwable $anterior = null)
    {
        parent::__construct('El archivo está en uso y no se puede eliminar.', 0, $anterior);
    }
}
