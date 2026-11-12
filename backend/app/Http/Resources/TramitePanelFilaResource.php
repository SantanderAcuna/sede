<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * La fila de la tabla de trámites del panel: lo justo para listar, sin la ficha entera.
 *
 * @mixin Tramite
 */
final class TramitePanelFilaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Tramite $tramite */
        $tramite = $this->resource;
        $problemas = $tramite->problemasParaPublicar();

        return [
            'id' => $tramite->id,
            'uuid' => $tramite->uuid,
            'type' => 'tramites',
            'codigo' => $tramite->codigo,
            'slug' => $tramite->slug,
            'nombre' => $tramite->nombre,
            'modalidad' => $tramite->modalidad?->value,
            'estado' => $tramite->publicado_en !== null && $problemas === [] ? 'publicado' : 'borrador',
            'origen' => $tramite->esDelPanel() || $tramite->procedencia_fuente === Tramite::FUENTE_PANEL ? 'panel' : 'fuente',
            'completo' => $problemas === [],
            'problemas' => $problemas,
            'publicado_en' => $tramite->publicado_en?->toIso8601String(),
            'actualizado_en' => $tramite->updated_at?->toIso8601String(),
        ];
    }
}
