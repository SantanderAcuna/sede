<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTOs\Busqueda\ResultadoBusqueda;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una sugerencia del buscador del encabezado (`SugerenciaBusquedaItem` del contrato).
 *
 * Forma liviana y fija: sin extracto ni fecha. La búsqueda completa tiene su propio
 * recurso porque una operación no puede cambiar de forma según un parámetro.
 *
 * @mixin ResultadoBusqueda
 */
final class SugerenciaBusquedaResource extends JsonResource
{
    public const TIPO = 'sugerencias-busqueda';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ResultadoBusqueda $resultado */
        $resultado = $this->resource;

        return [
            'id' => $resultado->id,
            'type' => self::TIPO,
            'titulo' => $resultado->titulo,
            'categoria' => $resultado->categoria,
            'tipo_recurso' => $resultado->tipoRecurso,
            'url' => $resultado->url,
            'score' => $resultado->score,
        ];
    }
}
