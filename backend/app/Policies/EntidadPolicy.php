<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Entidad;
use App\Models\User;

/**
 * Datos institucionales: se pueden ver y editar; la entidad es un registro único y no se crea ni se elimina desde la API.
 *
 * Cada acción es un permiso propio del módulo `entidad` (ver `CatalogoPermisos`).
 */
final class EntidadPolicy extends PoliticaDeModulo
{
    protected function modulo(): string
    {
        return 'entidad';
    }

    public function viewAny(User $user): bool
    {
        return $this->puede($user, 'ver');
    }

    public function view(User $user, Entidad $entidad): bool
    {
        return $this->puede($user, 'ver');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Entidad $entidad): bool
    {
        return $this->puede($user, 'editar');
    }

    public function delete(User $user, Entidad $entidad): bool
    {
        return false;
    }
}
