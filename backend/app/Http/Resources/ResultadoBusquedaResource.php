<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTOs\Busqueda\ResultadoBusqueda;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un resultado de la búsqueda completa (`ResultadoBusquedaItem` del contrato).
 *
 * `autor` y `miniatura` viajan siempre y son `null`: el contrato los declara porque la
 * página de resultados los muestra cuando existan, y ningún contenido de hoy los tiene.
 * No se inventan: un autor falso en una sede electrónica es una afirmación falsa.
 *
 * @mixin ResultadoBusqueda
 */
final class ResultadoBusquedaResource extends JsonResource
{
    public const TIPO = 'resultados-busqueda';

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
            'extracto' => $resultado->extracto,
            'fecha' => $resultado->fecha,
            'autor' => null,
            'miniatura' => null,
            'url' => $resultado->url,
            'score' => $resultado->score,
        ];
    }
}
