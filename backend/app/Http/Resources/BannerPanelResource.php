<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una diapositiva tal como la ve el panel: estado en palabras y lo que le falta para publicarse.
 * La imagen se sirve por una ruta con sesión (la pública no entrega borradores).
 *
 * @mixin Banner
 */
final class BannerPanelResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Banner $banner */
        $banner = $this->resource;
        $imagen = $banner->imagenPrincipal;

        return [
            'uuid' => $banner->uuid,
            'type' => 'banners',
            'titulo' => $banner->titulo,
            'descripcion' => $banner->descripcion,
            'enlace' => $banner->enlace,
            'orden' => $banner->orden,
            'activo' => $banner->activo,
            'estado' => $banner->estado(),
            'vigente_desde' => $banner->vigente_desde?->toIso8601String(),
            'vigente_hasta' => $banner->vigente_hasta?->toIso8601String(),
            'imagen' => $imagen === null ? null : [
                'url' => '/api/v1/panel/banners/'.$banner->uuid.'/imagen',
                'alt' => $banner->imagenAlt(),
            ],
            'problemas' => $banner->problemas(),
            'actualizado_en' => $banner->updated_at?->toIso8601String(),
        ];
    }
}
