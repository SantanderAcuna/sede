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
final class FooterResource extends JsonResource
{
    /**
     * Transforma el recurso al formato FooterItem.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'footer',
            'nombre_autoridad' => $this->nombre,
            'nit' => $this->nit,
            'direccion' => $this->direccion,
            'codigo_postal' => $this->codigo_postal,
            'municipio' => $this->municipio,
            'departamento' => $this->departamento,
            'horario' => $this->horario,
            'commutador' => $this->telefono,
            'linea_anticorrupcion' => $this->linea_anticorrupcion,
            'correo_institucional' => $this->correo_atencion,
            'correo_notificaciones' => $this->correo_notificaciones_judiciales,
            'redes' => $this->redes ?? [],
            'politicas' => $this->politicas ?? [],
        ];
    }
}
