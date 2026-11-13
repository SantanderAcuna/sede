<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TramiteValoracion;
use App\Models\User;

/**
 * Valoraciones que deja la ciudadanía: se consultan y, si hace falta, se retiran; no se editan.
 *
 * Cada acción es un permiso propio del módulo `valoraciones` (ver `CatalogoPermisos`).
 */
final class ValoracionPolicy extends PoliticaDeModulo
{
    protected function modulo(): string
    {
        return 'valoraciones';
    }

    public function viewAny(User $user): bool
    {
        return $this->puede($user, 'ver');
    }

    public function view(User $user, TramiteValoracion $tramiteValoracion): bool
    {
        return $this->puede($user, 'ver');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, TramiteValoracion $tramiteValoracion): bool
    {
        return false;
    }

    public function delete(User $user, TramiteValoracion $tramiteValoracion): bool
    {
        return $this->puede($user, 'eliminar');
    }
}
