<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Dependencia;
use App\Models\User;

/**
 * Dependencias de la Alcaldía.
 *
 * Cada acción es un permiso propio del módulo `dependencias` (ver `CatalogoPermisos`).
 */
final class DependenciaPolicy extends PoliticaDeModulo
{
    protected function modulo(): string
    {
        return 'dependencias';
    }

    public function viewAny(User $user): bool
    {
        return $this->puede($user, 'ver');
    }

    public function view(User $user, Dependencia $dependencia): bool
    {
        return $this->puede($user, 'ver');
    }

    public function create(User $user): bool
    {
        return $this->puede($user, 'crear');
    }

    public function update(User $user, Dependencia $dependencia): bool
    {
        return $this->puede($user, 'editar');
    }

    public function delete(User $user, Dependencia $dependencia): bool
    {
        return $this->puede($user, 'eliminar');
    }
}
