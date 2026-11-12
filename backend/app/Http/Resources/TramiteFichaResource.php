<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Tramite;
use App\Models\TramiteCanal;
use App\Models\TramiteCuenta;
use App\Models\TramiteDocumento;
use App\Models\TramiteNorma;
use App\Models\TramitePaso;
use App\Models\TramitePuntoAtencion;
use App\Models\TramiteRequisito;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * La ficha completa de un elemento del catálogo (el detalle `/tramites/{slug}`).
 *
 * Trae lo mismo que la tarjeta más todo lo que la Guía §5.1.3 pide de una ficha, ya por partes
 * relacionales: pasos, requisitos (con el paso al que pertenecen), documentos, puntos de atención
 * con georreferenciación, normas, canales de consulta de estado, tipos de solicitante y cuentas de
 * recaudo. El registro nacional (`url_ficha_gov_co`) es un enlace de referencia opcional.
 *
 * @mixin Tramite
 */
final class TramiteFichaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tramite = $this->resource;

        // Para referir cada requisito a su paso con un número estable (el orden) en vez del id de la
        // fila, que no significa nada fuera de la base.
        $ordenDePaso = $tramite->pasos->pluck('orden', 'id');

        return [
            'id' => $tramite->id,
            'uuid' => $tramite->uuid,
            'type' => $tramite->type,
            'slug' => $tramite->slug,
            'codigo' => $tramite->codigo,
            'nombre' => $tramite->nombre,
            'resumen' => $tramite->resumen,

            'modalidad' => $tramite->modalidad?->value,
            'tiene_costo' => $tramite->tiene_costo?->value,
            'tiempo_solucion_dias' => $tramite->tiempo_solucion_dias,
            'canal_inicio' => $tramite->canal_inicio?->value,
            'consulta_estado' => $tramite->consulta_estado,

            'dependencia' => $tramite->dependencia === null
                ? null
                : ['id' => $tramite->dependencia->id, 'nombre' => $tramite->dependencia->nombre],
            'categoria' => $tramite->categoria === null
                ? null
                : ['slug' => $tramite->categoria->slug, 'nombre' => $tramite->categoria->nombre],

            // El costo viaja como objeto: un decimal suelto no puede decir «depende del avalúo de su
            // predio», que es lo que la ficha del predial declara.
            'costo' => [
                'tiene_costo' => $tramite->tiene_costo?->value,
                'tipo_valor' => $tramite->costo_tipo_valor,
                'valor' => $tramite->costo,
                'moneda' => $tramite->costo_moneda,
                'url_pago' => $tramite->costo_url_pago,
                'descripcion' => $tramite->costo_descripcion,
                'cuentas' => $tramite->cuentas->map(static fn (TramiteCuenta $c): array => [
                    'banco' => $c->banco,
                    'tipo' => $c->tipo,
                    'numero' => $c->numero,
                    'titular' => $c->titular,
                ])->all(),
            ],

            'pasos' => $tramite->pasos->map(static fn (TramitePaso $p): array => [
                'orden' => $p->orden,
                'titulo' => $p->titulo,
                'descripcion' => $p->descripcion,
            ])->all(),

            'requisitos' => $tramite->requisitos->map(static fn (TramiteRequisito $r): array => [
                'orden' => $r->orden,
                'tipo' => $r->tipo,
                'descripcion' => $r->descripcion,
                'obligatorio' => $r->obligatorio,
                'nota' => $r->nota,
                'url' => $r->url,
                // El paso al que pertenece el requisito, por su número de orden; nulo si es general.
                'paso' => $r->paso_id === null ? null : ($ordenDePaso[$r->paso_id] ?? null),
            ])->all(),

            'documentos' => $tramite->documentos->map(static fn (TramiteDocumento $d): array => [
                'nombre' => $d->nombre,
                'url' => $d->url,
                'formato' => $d->formato,
            ])->all(),

            'puntos_atencion' => $tramite->puntosAtencion->map(static fn (TramitePuntoAtencion $p): array => [
                'nombre' => $p->nombre,
                'direccion' => $p->direccion,
                'telefono' => $p->telefono,
                'horario' => $p->horario,
                'municipio' => $p->municipio,
                'departamento' => $p->departamento,
                'latitud' => $p->latitud === null ? null : (float) $p->latitud,
                'longitud' => $p->longitud === null ? null : (float) $p->longitud,
            ])->all(),

            'normativa' => $tramite->normas->map(static fn (TramiteNorma $n): array => [
                'tipo' => $n->tipo,
                'numero' => $n->numero,
                'anio' => $n->anio,
                'articulos' => $n->articulos,
                'url' => $n->url,
            ])->all(),

            'canales_consulta_estado' => $tramite->canales->map(static fn (TramiteCanal $c): array => [
                'canal' => $c->canal,
                'habilitado' => $c->habilitado,
                'nombre' => $c->nombre,
                'url' => $c->url,
                'correo' => $c->correo,
                'telefono' => $c->telefono,
                'horario' => $c->horario,
            ])->all(),

            // El tipo de solicitante (RF-B1-022): los grupos de audiencia de la ficha.
            'solicitantes' => $tramite->audiencias->pluck('grupo')->values()->all(),

            'resultado' => $tramite->resultado,
            'observaciones_resultado' => $tramite->observaciones_resultado,
            'medios_resultado' => $tramite->medios_resultado,
            'fecha_cualquiera' => $tramite->fecha_cualquiera,
            'cuando_se_puede_realizar' => $tramite->cuando_se_puede_realizar,
            'url_calendario' => $tramite->url_calendario,
            'palabras_relacionadas' => $tramite->palabras_relacionadas,

            'url_inicio' => $tramite->url_inicio,
            'url_manual_tramite_en_linea' => $tramite->url_manual_tramite_en_linea,
            'url_ficha_gov_co' => $tramite->url_ficha_gov_co,

            'nivel_transformacion' => $tramite->nivel_transformacion,
            'estado_estandarizado' => $tramite->estado_estandarizado,
            'nivel_autenticacion_requerido' => $tramite->nivel_autenticacion_requerido,
            'aplica_sap' => $tramite->aplica_sap,
            'tipo_silencio_administrativo' => $tramite->tipo_silencio_administrativo,

            'publicado_en' => $tramite->publicado_en?->toIso8601String(),
            'actualizado_en' => $tramite->updated_at?->toIso8601String(),
        ];
    }
}
