<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Se pidió publicar una noticia a la que le falta algo: título, descripción,
 * contenido, imagen o el texto alternativo de la imagen (RF-01-013).
 *
 * Lleva la lista de lo que falta para que el panel la muestre tal cual: el
 * controlador la convierte en un 422 con `errors.noticia`.
 */
final class NoticiaIncompleta extends RuntimeException
{
    /**
     * @param  list<string>  $problemas
     */
    public function __construct(public readonly array $problemas)
    {
        parent::__construct('La noticia está incompleta y no se puede publicar.');
    }
}
