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
            // Nulos imposibles en la práctica: este recurso sólo se construye con
            // lo que devuelve el repositorio, y su consulta exige los seis
            // atributos. Se leen sin afirmarlo para que un cambio futuro en esa
            // consulta produzca una respuesta vacía y no un error del servidor.
            'modalidad' => $tramite->modalidad?->value,
            'tiene_costo' => $tramite->tiene_costo?->value,
            // El costo viaja como objeto y no como el importe suelto que era.
            // `costo` a secas no puede decir «depende del avalúo de su predio»,
            // que es lo que la ficha oficial declara para el impuesto predial:
            // con `Valor: null` y `TipoValor: "AVALUO_LIQUIDACION"` un decimal
            // sólo puede mentir con un cero.
            'costo' => [
                // El mismo valor que `tiene_costo`, y a propósito: el bloque tiene
                // que poder leerse solo, sin mirar fuera de él para saber si el
                // trámite es gratuito. Los dos salen de la misma columna en la
                // misma respuesta, así que no pueden discrepar.
                'tiene_costo' => $tramite->tiene_costo?->value,
                'tipo_valor' => $tramite->costo_tipo_valor,
                'valor' => $tramite->costo,
                'moneda' => $tramite->costo_moneda,
                'url_pago' => $tramite->costo_url_pago,
                'descripcion' => $tramite->costo_descripcion,
                'cuentas' => $tramite->costo_cuentas,
            ],
            'tiempo_solucion_dias' => $tramite->tiempo_solucion_dias,
            'canal_inicio' => $tramite->canal_inicio?->value,
            'consulta_estado' => $tramite->consulta_estado,
            'requisitos' => $tramite->requisitos,
            'documentos' => $tramite->documentos,

            // Lo que acompaña a los seis atributos. Cada bloque viaja aunque vaya
            // vacío: la ficha los presenta rotulados y un bloque ausente y uno
            // vacío se dibujan igual, así que declararlos siempre deja al cliente
            // sin una rama de más.
            'resultado' => $tramite->resultado,
            'perfiles' => $tramite->perfiles,
            'puntos_atencion' => $tramite->puntos_atencion,
            'normativa' => $tramite->normativa,
            'canales_consulta_estado' => $tramite->canales_consulta_estado,

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
                // El servicio del que salió la ficha, para poder repetir la
                // procedencia. Sin la dirección exacta, «lo saqué de GOV.CO» no es
                // una afirmación comprobable.
                'api' => $tramite->procedencia_api,
                // Y campo por campo, porque cuando la fuente no declara el término
                // en días, publicarlo convertido sin decir que se convirtió deja un
                // dato que parece declarado y no lo está.
                'origen_por_campo' => $tramite->procedencia_origen_por_campo,
                'derivados' => $tramite->procedencia_derivados,
                'faltantes' => $tramite->procedencia_faltantes,
            ];
        }

        return $datos;
    }
}
