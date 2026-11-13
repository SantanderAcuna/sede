<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

/**
 * Bitácora de auditoría: solo consulta; no se modifica ni se borra.
 *
 * Cada acción es un permiso propio del módulo `auditoria` (ver `CatalogoPermisos`).
 */
final class AuditoriaPolicy extends PoliticaDeModulo
{
    protected function modulo(): string
    {
        return 'auditoria';
    }

    public function viewAny(User $user): bool
    {
        return $this->puede($user, 'ver');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $this->puede($user, 'ver');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
