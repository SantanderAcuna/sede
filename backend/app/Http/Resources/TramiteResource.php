<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un elemento del catálogo, en la forma de **tarjeta** del listado.
 *
 * El listado es un directorio: cada tarjeta trae lo justo para comparar trámites de un vistazo
 * —nombre, resumen, modalidad, costo, tiempo y dependencia— y el enlace a la ficha propia. La ficha
 * completa, con requisitos, pasos y puntos de atención, la sirve `TramiteFichaResource` en el
 * detalle, para no arrastrar toda la ficha en cada fila de la galería.
 *
 * @mixin Tramite
 */
final class TramiteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tramite = $this->resource;

        return [
            'id' => $tramite->id,
            'uuid' => $tramite->uuid,
            'type' => $tramite->type,
            'slug' => $tramite->slug,
            'codigo' => $tramite->codigo,
            'nombre' => $tramite->nombre,
            'resumen' => $tramite->resumen,
            // Los metadatos que la tarjeta muestra con icono y etiqueta (RF-B1-021).
            'modalidad' => $tramite->modalidad?->value,
            'tiene_costo' => $tramite->tiene_costo?->value,
            'tiempo_solucion_dias' => $tramite->tiempo_solucion_dias,
            'dependencia' => $tramite->dependencia === null
                ? null
                : ['id' => $tramite->dependencia->id, 'nombre' => $tramite->dependencia->nombre],
            // La categoría sólo viaja si la Entidad la declaró; nula no se inventa.
            'categoria' => $tramite->categoria === null
                ? null
                : ['slug' => $tramite->categoria->slug, 'nombre' => $tramite->categoria->nombre],
            // El registro nacional es una referencia **opcional**: la tarjeta lo ofrece como enlace
            // secundario sólo cuando existe. El destino principal del nombre es la ficha propia.
            'url_ficha_gov_co' => $tramite->url_ficha_gov_co,
            'url_inicio' => $tramite->url_inicio,
        ];
    }
}
