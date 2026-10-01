<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un trámite del contrato.
 *
 * La ficha y el elemento del listado son el mismo objeto: el contrato declara un
 * solo esquema —`TramiteItem`— para los dos, así que el catálogo siempre viaja
 * completo y no hay una versión «corta» del trámite que pueda quedarse sin uno
 * de sus seis atributos obligatorios.
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

        /** @var array<string, mixed> $datos */
        $datos = [
            'id' => $tramite->id,
            'type' => 'tramites',
            'slug' => $tramite->slug,
            'nombre' => $tramite->nombre,
            'resumen' => $tramite->resumen,
            'url_ficha_gov_co' => $tramite->url_ficha_gov_co,

            // Los seis atributos obligatorios, en el orden en que la ficha los
            // presenta al ciudadano.
            'modalidad' => $tramite->modalidad->value,
            'tiene_costo' => $tramite->tiene_costo->value,
            'costo' => $tramite->costo,
            'tiempo_solucion_dias' => $tramite->tiempo_solucion_dias,
            'canal_inicio' => $tramite->canal_inicio->value,
            'consulta_estado' => $tramite->consulta_estado,
            'requisitos' => $tramite->requisitos,
            'documentos' => $tramite->documentos,

            'url_inicio' => $tramite->url_inicio,
            'categoria' => $tramite->categoria_slug === null
                ? null
                : ['slug' => $tramite->categoria_slug, 'nombre' => $tramite->categoria_nombre],

            'publicado_en' => $tramite->publicado_en?->toIso8601String(),
            'actualizado_en' => $tramite->updated_at?->toIso8601String(),
        ];

        // La procedencia se añade al final y sólo cuando existe. Un trámite
        // cargado a mano en el panel no tiene fuente externa que declarar, y
        // devolver un objeto con la fuente en nulo haría creer que sí la tiene.
        // El orden de las claves no significa nada en un objeto JSON.
        if ($tramite->procedencia_fuente !== null && $tramite->procedencia_obtenido_en !== null) {
            $datos['procedencia'] = [
                'fuente' => $tramite->procedencia_fuente,
                'url' => $tramite->procedencia_url,
                'obtenido_en' => $tramite->procedencia_obtenido_en->format('Y-m-d'),
                'nota' => $tramite->procedencia_nota,
            ];
        }

        return $datos;
    }
}
