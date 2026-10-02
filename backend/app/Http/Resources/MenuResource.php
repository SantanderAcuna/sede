<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Menu
 *
 * @property Menu $resource
 */
final class MenuResource extends JsonResource
{
    /**
     * Transforma el recurso al formato MenuItem.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'menu-item',
            'slug' => $this->slug,
            'etiqueta' => $this->etiqueta,
            'ruta' => $this->ruta,
            'descripcion' => $this->descripcion,
            'orden' => $this->orden,
            'visible' => $this->visible,
            'tipo' => $this->tipo,
            'hijos' => MenuResource::collection($this->whenLoaded('hijos')),
        ];
    }
}
