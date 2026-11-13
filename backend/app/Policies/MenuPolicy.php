<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Menu;
use App\Models\User;

/**
 * Menús del sitio.
 *
 * Cada acción es un permiso propio del módulo `menus` (ver `CatalogoPermisos`).
 */
final class MenuPolicy extends PoliticaDeModulo
{
    protected function modulo(): string
    {
        return 'menus';
    }

    public function viewAny(User $user): bool
    {
        return $this->puede($user, 'ver');
    }

    public function view(User $user, Menu $menu): bool
    {
        return $this->puede($user, 'ver');
    }

    public function create(User $user): bool
    {
        return $this->puede($user, 'crear');
    }

    public function update(User $user, Menu $menu): bool
    {
        return $this->puede($user, 'editar');
    }

    public function delete(User $user, Menu $menu): bool
    {
        return $this->puede($user, 'eliminar');
    }
}
