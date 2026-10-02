<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Entidad;

/**
 * Contrato para el repositorio de Entidad.
 *
 * Define las operaciones de acceso a datos para la entidad institucional.
 */
interface EntidadRepositoryInterface
{
    /**
     * Obtiene la primera entidad configurada.
     */
    public function findFirst(): ?Entidad;
}
