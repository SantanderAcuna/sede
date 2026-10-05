<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\EntidadRepositoryInterface;
use App\Contracts\Services\EntidadServiceInterface;
use App\Http\Resources\EntidadResource;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para la entidad institucional.
 *
 * Implementa la lógica de negocio para obtener los datos de la entidad
 * que alimentan la cabecera y el pie de página de la sede.
 */
final class EntidadService implements EntidadServiceInterface
{
    public function __construct(
        private readonly EntidadRepositoryInterface $entidadRepository,
    ) {}

    /**
     * Obtiene los datos de la entidad para mostrar en el sitio.
     *
     * @throws \RuntimeException si no hay entidad configurada
     */
    public function obtenerEntidad(): EntidadResource
    {
        $entidad = $this->entidadRepository->findFirst();

        if ($entidad === null) {
            Log::warning('No se encontró entidad configurada en la base de datos');

            throw new \RuntimeException('No se encontró la entidad configurada.');
        }

        return new EntidadResource($entidad);
    }

    /**
     * Actualiza los datos de la entidad desde el panel de administración.
     *
     * @param  array<string, mixed>  $datos
     *
     * @throws \RuntimeException si no hay entidad configurada
     */
    public function actualizar(array $datos): EntidadResource
    {
        Log::info('Actualizando entidad desde panel', ['campos' => array_keys($datos)]);

        $entidad = $this->entidadRepository->update($datos);

        return new EntidadResource($entidad);
    }
}
