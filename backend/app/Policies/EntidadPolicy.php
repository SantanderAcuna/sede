<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Entidad;
use App\Models\User;

/**
 * Política para la entidad institucional.
 *
 * Solo los usuarios con el permiso `entidad.gestionar` pueden ver, crear o editar
 * los datos institucionales. No se permite eliminar la entidad (es un registro
 * sistema único).
 */
final class EntidadPolicy
{
    /**
     * Anyone authenticated with the permission can access the entity list.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('entidad.gestionar');
    }

    /**
     * Anyone authenticated with the permission can view the entity details.
     */
    public function view(User $user, Entidad $entidad): bool
    {
        return $user->can('entidad.gestionar');
    }

    /**
     * Only users with the permission can create / update the entity.
     * There is typically only one entity record in the system.
     */
    public function create(User $user): bool
    {
        return $user->can('entidad.gestionar');
    }

    /**
     * Only users with the permission can update the entity.
     */
    public function update(User $user, Entidad $entidad): bool
    {
        return $user->can('entidad.gestionar');
    }

    /**
     * Entity deletion is not permitted through the API.
     */
    public function delete(User $user, Entidad $entidad): bool
    {
        return false;
    }

    /**
     * Entity restoration is not applicable.
     */
    public function restore(User $user, Entidad $entidad): bool
    {
        return false;
    }

    /**
     * Entity force deletion is not permitted.
     */
    public function forceDelete(User $user, Entidad $entidad): bool
    {
        return false;
    }
}
