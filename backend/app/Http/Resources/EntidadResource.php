<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Entidad;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Entidad
 *
 * @property Entidad $resource
 */
final class EntidadResource extends JsonResource
{
    /**
     * Transforma el recurso en un array compatible con Flat Envelope.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'entidad',
            'nombre' => $this->nombre,
            'sigla' => $this->sigla,
            'nit' => $this->nit,
            'direccion' => $this->direccion,
            'municipio' => $this->municipio,
            'departamento' => $this->departamento,
            'pais' => $this->pais,
            'telefono' => $this->telefono,
            'linea_atencion' => $this->linea_atencion,
            'linea_gratuita' => $this->linea_gratuita,
            'linea_anticorrupcion' => $this->linea_anticorrupcion,
            'correo_atencion' => $this->correo_atencion,
            'correo_notificaciones_judiciales' => $this->correo_notificaciones_judiciales,
            'horario' => $this->horario,
            'codigo_postal' => $this->codigo_postal,
            'dominio' => $this->dominio,
            'logo' => $this->logo,
            'redes' => $this->redes ?? [],
            'politicas' => $this->politicas ?? [],
            'datos_por_confirmar' => $this->datos_por_confirmar ?? [],
        ];
    }
}
