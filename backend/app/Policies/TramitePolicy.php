<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tramite;
use App\Models\User;

/**
 * Quién gestiona el catálogo de trámites, acción por acción.
 *
 * Cada acción es un permiso propio del módulo `tramites` (ver `CatalogoPermisos`).
 */
final class TramitePolicy extends PoliticaDeModulo
{
    protected function modulo(): string
    {
        return 'tramites';
    }

    public function viewAny(User $user): bool
    {
        return $this->puede($user, 'ver');
    }

    public function view(User $user, Tramite $tramite): bool
    {
        return $this->puede($user, 'ver');
    }

    public function create(User $user): bool
    {
        return $this->puede($user, 'crear');
    }

    public function update(User $user, Tramite $tramite): bool
    {
        return $this->puede($user, 'editar');
    }

    public function delete(User $user, Tramite $tramite): bool
    {
        return $this->puede($user, 'eliminar');
    }

    public function publish(User $user, ?Tramite $tramite = null): bool
    {
        return $this->puede($user, 'publicar');
    }
}
