<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Noticia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una fila de la tabla de noticias del panel: lo necesario para listar, filtrar y abrir
 * cada noticia, sin su cuerpo. El texto completo se pide al abrirla
 * (`GET /panel/noticias/{uuid}`, `NoticiaPanelResource`): una tabla de 100 filas no tiene
 * por qué descargar 100 artículos que no muestra.
 *
 * @mixin Noticia
 */
final class NoticiaPanelFilaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $noticia = $this->resource;
        $problemas = $noticia->problemas();

        return (new NoticiaResource($noticia))->paraPanel()->resolve($request) + [
            'uuid' => $noticia->uuid,
            'activo' => $noticia->activo,
            'estado' => $noticia->estado()->value,
            'completa' => $problemas === [],
            'problemas' => $problemas,
        ];
    }
}
