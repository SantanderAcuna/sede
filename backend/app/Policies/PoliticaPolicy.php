<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Politica;
use App\Models\User;

/**
 * Políticas del sitio: se ven, se crean, se editan, se publican y se eliminan. Las cinco de base
 * (`Politica::COMPONENTES`) no se eliminan.
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
        return $this->puede($user, 'ver') && $this->puede($user, 'crear');
    }

    /** Publicar o retirar de la versión pública. */
    public function publish(User $user, Politica $politica): bool
    {
        return $this->puede($user, 'publicar');
    }

    public function update(User $user, Politica $politica): bool
    {
        return $this->puede($user, 'editar');
    }

    public function delete(User $user, Politica $politica): bool
    {
        return ! $politica->esBase() && $this->puede($user, 'eliminar');
    }
}
