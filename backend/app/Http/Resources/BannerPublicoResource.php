<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Lo que el sitio necesita de una diapositiva vigente: nada interno (ni id, ni estado, ni fechas). */
final class BannerPublicoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Banner $banner */
        $banner = $this->resource;

        return [
            'uuid' => $banner->uuid,
            'titulo' => $banner->titulo,
            'descripcion' => $banner->descripcion,
            'enlace' => $banner->enlace,
            'imagen' => [
                'url' => '/api/v1/banners/'.$banner->uuid.'/imagen',
                'alt' => (string) $banner->imagenAlt(),
            ],
        ];
    }
}
