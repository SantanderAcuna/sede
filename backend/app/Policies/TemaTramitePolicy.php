<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TramiteCategoria;
use App\Models\User;

/**
 * Temas con que se clasifican los trámites.
 *
 * Cada acción es un permiso propio del módulo `temas` (ver `CatalogoPermisos`).
 */
final class TemaTramitePolicy extends PoliticaDeModulo
{
    protected function modulo(): string
    {
        return 'temas';
    }

    public function viewAny(User $user): bool
    {
        return $this->puede($user, 'ver');
    }

    public function view(User $user, TramiteCategoria $tramiteCategoria): bool
    {
        return $this->puede($user, 'ver');
    }

    public function create(User $user): bool
    {
        return $this->puede($user, 'crear');
    }

    public function update(User $user, TramiteCategoria $tramiteCategoria): bool
    {
        return $this->puede($user, 'editar');
    }

    public function delete(User $user, TramiteCategoria $tramiteCategoria): bool
    {
        return $this->puede($user, 'eliminar');
    }
}
