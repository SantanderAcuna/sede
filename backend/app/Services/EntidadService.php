<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\EntidadRepositoryInterface;
use App\Contracts\Services\EntidadServiceInterface;
use App\Http\Resources\EntidadResource;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para la entidad institucional.
 */
final class EntidadService implements EntidadServiceInterface
{
    public function __construct(
        private readonly EntidadRepositoryInterface $entidadRepository,
    ) {}

    /**
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
     * @param  array<string, mixed>  $datos  Datos ya validados por ActualizarEntidadRequest
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
