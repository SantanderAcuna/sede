<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Banner;
use App\Models\User;

/**
 * Quién gestiona el carrusel de la portada, acción por acción. Reordenar es editar.
 *
 * Cada acción es un permiso propio del módulo `banners` (ver `CatalogoPermisos`).
 */
final class BannerPolicy extends PoliticaDeModulo
{
    protected function modulo(): string
    {
        return 'banners';
    }

    public function viewAny(User $user): bool
    {
        return $this->puede($user, 'ver');
    }

    public function view(User $user, Banner $banner): bool
    {
        return $this->puede($user, 'ver');
    }

    public function create(User $user): bool
    {
        return $this->puede($user, 'crear');
    }

    public function update(User $user, Banner $banner): bool
    {
        return $this->puede($user, 'editar');
    }

    public function delete(User $user, Banner $banner): bool
    {
        return $this->puede($user, 'eliminar');
    }

    public function publish(User $user, ?Banner $banner = null): bool
    {
        return $this->puede($user, 'publicar');
    }

    public function reorder(User $user): bool
    {
        return $this->puede($user, 'editar');
    }
}
