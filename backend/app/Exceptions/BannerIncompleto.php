<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Se pidió publicar una diapositiva a la que le falta algo (imagen o texto alternativo). */
final class BannerIncompleto extends RuntimeException
{
    /** @param list<string> $problemas */
    public function __construct(public readonly array $problemas)
    {
        parent::__construct(implode(' ', $problemas));
    }
}
