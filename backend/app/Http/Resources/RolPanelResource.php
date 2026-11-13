<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\Acceso\ReglasDeAcceso;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

/** @mixin Role */
final class RolPanelResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Role $rol */
        $rol = $this->resource;

        return [
            'id' => $rol->id,
            'type' => 'roles',
            'name' => $rol->name,
            'permisos' => $rol->permissions->pluck('name')->sort()->values()->all(),
            'usuarios' => (int) $rol->getAttribute('users_count'),
            'protegido' => $rol->name === ReglasDeAcceso::ROL_SUPER,
        ];
    }
}
