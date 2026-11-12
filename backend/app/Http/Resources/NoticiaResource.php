<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Noticia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una noticia del contrato.
 *
 * El listado no lleva el cuerpo: el home pide seis y el cuerpo de cada una no se
 * muestra ahí. El detalle sí (`conCuerpo()`).
 *
 * @mixin Noticia
 */
final class NoticiaResource extends JsonResource
{
    private bool $conCuerpo = false;

    public function conCuerpo(): self
    {
        $this->conCuerpo = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $noticia = $this->resource;
        $imagen = $noticia->imagenPrincipal;

        $datos = [
            'id' => $noticia->id,
            'type' => 'noticias',
            'slug' => $noticia->slug,
            'titulo' => $noticia->titulo,
            'descripcion_corta' => $noticia->descripcion_corta,
            'fecha_publicacion' => $noticia->fecha_publicacion->toIso8601String(),
            'destacada' => $noticia->destacada,
            // La dirección es estable: la URL firmada de `FileMedia::url()` caduca a
            // los 15 minutos y no sirve para una imagen de una página pública.
            'imagen' => $imagen === null ? null : [
                'url' => '/api/v1/noticias/'.$noticia->slug.'/imagen',
                'alt' => $noticia->imagenAlt(),
            ],
        ];

        if ($this->conCuerpo) {
            $datos['contenido'] = $noticia->contenido;
        }

        return $datos;
    }
}
