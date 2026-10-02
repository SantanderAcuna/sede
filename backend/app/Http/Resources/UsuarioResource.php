<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * @mixin User
 *
 * @property User $resource
 */
final class UsuarioResource extends JsonResource
{
    /**
     * Transforma el recurso al formato UsuarioItem.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var list<array{id:int,type:string,nombre:string,permisos:list<string>}> $roles */
        $roles = [];

        /** @var Role $rol */
        foreach ($this->roles as $rol) {
            /** @var Collection $permissions */
            $permissions = $rol->permissions;

            $roles[] = [
                'id' => $rol->id,
                'type' => 'rol',
                'nombre' => $rol->name,
                'permisos' => $permissions->pluck('name')->toArray(),
            ];
        }

        return [
            'id' => $this->id,
            'type' => 'usuario',
            'email' => $this->email,
            'mfa_habilitado' => $this->mfa_habilitado ?? false,
            'estado' => $this->estado ?? 'activo',
            'roles' => $roles,
        ];
    }
}
