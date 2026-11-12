<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Noticia;
use App\Models\User;

/**
 * Quién gestiona las noticias: quien tenga el permiso `noticias.gestionar`.
 *
 * Es el mismo permiso para ver el listado del panel, crear, editar, publicar,
 * subir la imagen y eliminar: publicar y editar no se separan hoy porque no hay
 * dos roles que lo pidan, y no se inventa una distinción que nadie ha decidido.
 */
final class NoticiaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('noticias.gestionar');
    }

    public function view(User $user, Noticia $noticia): bool
    {
        return $user->can('noticias.gestionar');
    }

    public function create(User $user): bool
    {
        return $user->can('noticias.gestionar');
    }

    public function update(User $user, Noticia $noticia): bool
    {
        return $user->can('noticias.gestionar');
    }

    public function delete(User $user, Noticia $noticia): bool
    {
        return $user->can('noticias.gestionar');
    }
}
