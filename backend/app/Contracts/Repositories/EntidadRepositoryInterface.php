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

    /**
     * Actualiza la primera entidad con los datos proporcionados.
     *
     * @param  array<string, mixed>  $datos
     *
     * @throws \RuntimeException si no existe la entidad
     */
    public function update(array $datos): Entidad;
}
