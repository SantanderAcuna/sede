<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Roles y permisos del panel.
 *
 * Cada acción es un permiso propio del módulo `roles` (ver `CatalogoPermisos`).
 */
final class RolPolicy extends PoliticaDeModulo
{
    protected function modulo(): string
    {
        return 'roles';
    }

    public function viewAny(User $user): bool
    {
        return $this->puede($user, 'ver');
    }

    public function view(User $user, Role $role): bool
    {
        return $this->puede($user, 'ver');
    }

    public function create(User $user): bool
    {
        return $this->puede($user, 'crear');
    }

    public function update(User $user, Role $role): bool
    {
        return $this->puede($user, 'editar');
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->puede($user, 'eliminar');
    }
}
