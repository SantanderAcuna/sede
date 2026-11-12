<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Banner;
use App\Models\User;

/** Quién gestiona el carrusel de la portada: quien tenga `banners.gestionar`. */
final class BannerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('banners.gestionar');
    }

    public function view(User $user, Banner $banner): bool
    {
        return $user->can('banners.gestionar');
    }

    public function create(User $user): bool
    {
        return $user->can('banners.gestionar');
    }

    public function update(User $user, Banner $banner): bool
    {
        return $user->can('banners.gestionar');
    }

    public function delete(User $user, Banner $banner): bool
    {
        return $user->can('banners.gestionar');
    }

    public function reorder(User $user): bool
    {
        return $user->can('banners.gestionar');
    }
}
