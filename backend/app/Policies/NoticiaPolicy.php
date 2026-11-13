<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Noticia;
use App\Models\User;

/**
 * Quién gestiona las noticias del sitio, acción por acción.
 *
 * Cada acción es un permiso propio del módulo `noticias` (ver `CatalogoPermisos`).
 */
final class NoticiaPolicy extends PoliticaDeModulo
{
    protected function modulo(): string
    {
        return 'noticias';
    }

    public function viewAny(User $user): bool
    {
        return $this->puede($user, 'ver');
    }

    public function view(User $user, Noticia $noticia): bool
    {
        return $this->puede($user, 'ver');
    }

    public function create(User $user): bool
    {
        return $this->puede($user, 'crear');
    }

    public function update(User $user, Noticia $noticia): bool
    {
        return $this->puede($user, 'editar');
    }

    public function delete(User $user, Noticia $noticia): bool
    {
        return $this->puede($user, 'eliminar');
    }

    public function publish(User $user, ?Noticia $noticia = null): bool
    {
        return $this->puede($user, 'publicar');
    }
}
