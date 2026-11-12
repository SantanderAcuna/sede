<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Noticia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una noticia tal como la ve el panel: el documento público con el cuerpo, la
 * dirección de imagen del panel y lo que hace falta para editarla y publicarla
 * (`uuid`, `activo`, `completa`, `problemas`).
 *
 * Lo usan por igual el listado, el detalle y las escrituras del panel. Que el listado
 * lo use es lo que hace que abrir una noticia ya creada traiga su contenido y su
 * estado: con el recurso público, la lista no llevaba ni `uuid` ni `activo`.
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
        $noticia = $this->resource;
        $problemas = $noticia->problemas();

        return (new NoticiaResource($noticia))->conCuerpo()->paraPanel()->resolve($request) + [
            'uuid' => $noticia->uuid,
            'activo' => $noticia->activo,
            'completa' => $problemas === [],
            'problemas' => $problemas,
        ];
    }
}
