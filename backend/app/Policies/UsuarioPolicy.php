<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Usuarios del panel: no se eliminan desde el panel (se desactivan editándolos).
 *
 * Cada acción es un permiso propio del módulo `usuarios` (ver `CatalogoPermisos`).
 */
final class UsuarioPolicy extends PoliticaDeModulo
{
    protected function modulo(): string
    {
        return 'usuarios';
    }

    public function viewAny(User $user): bool
    {
        return $this->puede($user, 'ver');
    }

    public function view(User $user, User $objetivo): bool
    {
        return $this->puede($user, 'ver');
    }

    public function create(User $user): bool
    {
        return $this->puede($user, 'crear');
    }

    public function update(User $user, User $objetivo): bool
    {
        return $this->puede($user, 'editar');
    }

    public function delete(User $user, User $objetivo): bool
    {
        return false;
    }
}
