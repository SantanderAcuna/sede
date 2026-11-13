<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Consentimiento;
use App\Models\User;

/**
 * Registro de consentimientos: solo consulta; es evidencia y no se modifica.
 *
 * Cada acción es un permiso propio del módulo `consentimientos` (ver `CatalogoPermisos`).
 */
final class ConsentimientoPolicy extends PoliticaDeModulo
{
    protected function modulo(): string
    {
        return 'consentimientos';
    }

    public function viewAny(User $user): bool
    {
        return $this->puede($user, 'ver');
    }

    public function view(User $user, Consentimiento $consentimiento): bool
    {
        return $this->puede($user, 'ver');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Consentimiento $consentimiento): bool
    {
        return false;
    }

    public function delete(User $user, Consentimiento $consentimiento): bool
    {
        return false;
    }
}
