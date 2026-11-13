<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

interface RolPanelServiceInterface
{
    /** @return Collection<int, Role> */
    public function listar(): Collection;

    public function porId(int $id): ?Role;

    /** @param list<string> $permisos */
    public function crear(User $actor, string $nombre, array $permisos): Role;

    /** @param list<string> $permisos */
    public function actualizar(User $actor, Role $rol, ?string $nombre, ?array $permisos): Role;

    public function eliminar(User $actor, Role $rol): void;
}
