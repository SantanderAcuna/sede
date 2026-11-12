<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Noticia;
use App\Support\Noticias\ContenidoNoticia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una noticia completa tal como la ve el panel al abrirla: la fila del listado
 * (`NoticiaPanelFilaResource`) más el cuerpo. La usan el detalle y las escrituras.
 *
 * @mixin Noticia
 */
final class NoticiaPanelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return (new NoticiaPanelFilaResource($this->resource))->resolve($request) + [
            'contenido' => ContenidoNoticia::limpio($this->resource->contenido),
        ];
    }
}
