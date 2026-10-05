<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Http\Resources\EntidadResource;

/**
 * Contrato para el servicio de Entidad.
 */
interface EntidadServiceInterface
{
    /**
     * Obtiene los datos de la entidad para mostrar en el sitio.
     *
     * @throws \RuntimeException si no hay entidad configurada
     */
    public function obtenerEntidad(): EntidadResource;

    /**
     * Actualiza los datos de la entidad desde el panel de administración.
     *
     * @param  array<string, mixed>  $datos  Datos validados por ActualizarEntidadRequest
     *
     * @throws \RuntimeException si no hay entidad configurada
     */
    public function actualizar(array $datos): EntidadResource;
}
