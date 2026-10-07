<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FileMedia;
use App\Models\User;

/**
 * Política para archivos subidos al sistema (FileMedia).
 *
 * Permisos declarados en el panel (src/config/permisos.ts):
 *   - `archivos.ver`: listar y descargar archivos
 *   - `archivos.crear`: subir archivos
 *   - `archivos.eliminar`: eliminar archivos
 *
 * Los archivos son inmutables una vez subidos: no hay método `update`.
 */
final class ArchivoPolicy
{
    /**
     * Anyone with the permission can list files.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('archivos.ver');
    }

    /**
     * Anyone with the permission can view a single file.
     */
    public function view(User $user, FileMedia $archivo): bool
    {
        return $user->can('archivos.ver');
    }

    /**
     * Only users with the permission can upload files.
     */
    public function create(User $user): bool
    {
        return $user->can('archivos.crear');
    }

    /**
     * Files are immutable — updates are not allowed.
     */
    public function update(User $user, FileMedia $archivo): bool
    {
        return false;
    }

    /**
     * Only users with the permission can delete files.
     * The super-admin bypasses this via Gate::before in AppServiceProvider.
     */
    public function delete(User $user, FileMedia $archivo): bool
    {
        return $user->can('archivos.eliminar');
    }

    /**
     * Files cannot be restored once soft-deleted.
     */
    public function restore(User $user, FileMedia $archivo): bool
    {
        return false;
    }

    /**
     * Files cannot be force-deleted via the API.
     */
    public function forceDelete(User $user, FileMedia $archivo): bool
    {
        return false;
    }
}
