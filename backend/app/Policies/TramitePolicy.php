<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tramite;
use App\Models\User;

/** Quién gestiona los trámites desde el panel: quien tenga `tramites.gestionar`. */
final class TramitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tramites.gestionar');
    }

    public function view(User $user, Tramite $tramite): bool
    {
        return $user->can('tramites.gestionar');
    }

    public function create(User $user): bool
    {
        return $user->can('tramites.gestionar');
    }

    public function update(User $user, Tramite $tramite): bool
    {
        return $user->can('tramites.gestionar');
    }

    public function delete(User $user, Tramite $tramite): bool
    {
        return $user->can('tramites.gestionar');
    }
}
