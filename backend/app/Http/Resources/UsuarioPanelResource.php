<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use App\Support\Acceso\ReglasDeAcceso;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un usuario para el panel. Jamás incluye la clave ni el token de sesión.
 *
 * @mixin User
 */
final class UsuarioPanelResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var User $usuario */
        $usuario = $this->resource;
        $roles = $usuario->roles->pluck('name')->sort()->values()->all();

        return [
            'uuid' => $usuario->uuid,
            'type' => 'usuarios',
            'name' => $usuario->name,
            'email' => $usuario->email,
            'estado' => $usuario->estado,
            'mfa_habilitado' => $usuario->mfa_habilitado,
            'roles' => $roles,
            'es_super_admin' => in_array(ReglasDeAcceso::ROL_SUPER, $roles, true),
            'creado_en' => $usuario->created_at?->toIso8601String(),
        ];
    }
}
