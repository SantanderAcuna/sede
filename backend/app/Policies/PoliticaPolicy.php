<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Politica;
use App\Models\User;

/**
 * Políticas del sitio: se ven y se editan; no se crean ni se eliminan desde el panel.
 *
 * Cada acción es un permiso propio del módulo `politicas` (ver `CatalogoPermisos`).
 */
final class PoliticaPolicy extends PoliticaDeModulo
{
    protected function modulo(): string
    {
        return 'politicas';
    }

    public function viewAny(User $user): bool
    {
        return $this->puede($user, 'ver');
    }

    public function view(User $user, Politica $politica): bool
    {
        return $this->puede($user, 'ver');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Politica $politica): bool
    {
        return $this->puede($user, 'editar');
    }

    public function delete(User $user, Politica $politica): bool
    {
        return false;
    }
}
