<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\EntidadRepositoryInterface;
use App\Models\Entidad;

/**
 * Implementación Eloquent del repositorio de Entidad.
 */
final class EntidadRepository implements EntidadRepositoryInterface
{
    /**
     * Obtiene la primera entidad configurada.
     */
    public function findFirst(): ?Entidad
    {
        return Entidad::first();
    }
}
