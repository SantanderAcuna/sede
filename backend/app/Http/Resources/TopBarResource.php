<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Entidad;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Entidad
 *
 * @property Entidad $resource
 */
final class TopBarResource extends JsonResource
{
    /**
     * Transforma el recurso al formato TopBarItem.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'top-bar',
            'logo_path' => $this->logo,
            'url_govco' => 'https://www.gov.co/home/',
            'altura_px' => 56,
            'idioma_default' => 'es-CO',
        ];
    }
}
