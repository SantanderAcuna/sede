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

    /**
     * Actualiza la primera entidad con los datos proporcionados.
     *
     * @param  array<string, mixed>  $datos
     *
     * @throws \RuntimeException si no existe la entidad
     */
    public function update(array $datos): Entidad
    {
        $entidad = Entidad::first();

        if ($entidad === null) {
            throw new \RuntimeException('No existe la entidad configurada.');
        }

        $entidad->update($datos);

        return $entidad->fresh() ?? $entidad;
    }
}
