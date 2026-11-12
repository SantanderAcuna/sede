<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un elemento tal como lo ve quien lo gestiona: la ficha completa (la misma que publica el sitio)
 * más lo que hace falta para editarla: su estado y qué le falta para publicarse.
 *
 * @mixin Tramite
 */
final class TramitePanelResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Tramite $tramite */
        $tramite = $this->resource;

        $problemas = $tramite->problemasParaPublicar();
        $publico = Tramite::query()->publicados()->whereKey($tramite->getKey())->exists();

        return (new TramiteFichaResource($tramite))->toArray($request) + [
            'estado' => $publico ? 'publicado' : 'borrador',
            'dependencia_id' => $tramite->dependencia_id,
            'completo' => $problemas === [],
            'problemas' => $problemas,
        ];
    }
}
