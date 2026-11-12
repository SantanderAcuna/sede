<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\RolPanelServiceInterface;
use App\Exceptions\AccionNoPermitida;
use App\Exceptions\RolEnUso;
use App\Models\User;
use App\Support\Acceso\Huella;
use App\Support\Acceso\ReglasDeAcceso;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/** Roles y los permisos que reúnen. El rol `super-admin` no se toca desde el panel. */
final class RolPanelService implements RolPanelServiceInterface
{
    public function listar(): Collection
    {
        return $this->conConteo(Role::query()->with('permissions')->orderBy('name')->get());
    }

    public function porId(int $id): ?Role
    {
        $rol = Role::query()->with('permissions')->find($id);

        return $rol === null ? null : $this->conConteo(new Collection([$rol]))->first();
    }

    public function crear(User $actor, string $nombre, array $permisos): Role
    {
        $this->exigirPoderSobre($actor, $permisos);

        return DB::transaction(function () use ($actor, $nombre, $permisos): Role {
            /** @var Role $rol */
            $rol = Role::create(['name' => $nombre, 'guard_name' => 'web']);
            $rol->syncPermissions($permisos);
            Huella::registrar($actor, 'rol_creado', 'roles', (int) $rol->id, ['rol' => $nombre, 'permisos' => $permisos]);

            return $this->porId((int) $rol->id) ?? $rol;
        });
    }

    public function actualizar(User $actor, Role $rol, ?string $nombre, ?array $permisos): Role
    {
        $this->exigirNoProtegido($rol);

        if ($permisos !== null) {
            // Quitar un permiso ajeno también es tocar poder que no se tiene.
            $actuales = $this->nombresDe($rol);
            $this->exigirPoderSobre($actor, array_values(array_unique(array_merge($permisos, $actuales))));
        }

        return DB::transaction(function () use ($actor, $rol, $nombre, $permisos): Role {
            $antes = $rol->permissions->pluck('name')->sort()->values()->all();
            if ($nombre !== null && $nombre !== $rol->name) {
                $rol->name = $nombre;
                $rol->save();
            }
            if ($permisos !== null) {
                $rol->syncPermissions($permisos);
            }
            Huella::registrar($actor, 'rol_actualizado', 'roles', (int) $rol->id, [
                'rol' => $rol->name,
                'permisos_antes' => $antes,
                'permisos_despues' => $rol->fresh()?->permissions->pluck('name')->sort()->values()->all() ?? [],
            ]);

            return $this->porId((int) $rol->id) ?? $rol;
        });
    }

    public function eliminar(User $actor, Role $rol): void
    {
        $this->exigirNoProtegido($rol);
        $this->exigirPoderSobre($actor, $this->nombresDe($rol));

        if ($this->usuariosDelRol($rol) > 0) {
            throw new RolEnUso('El rol tiene usuarios asignados: quíteselo primero a cada uno.');
        }

        DB::transaction(function () use ($actor, $rol): void {
            $id = (int) $rol->id;
            $nombre = $rol->name;
            $rol->delete();
            Huella::registrar($actor, 'rol_eliminado', 'roles', $id, ['rol' => $nombre]);
        });
    }

    /**
     * Cuántos usuarios tiene cada rol. Se cuenta en la tabla puente: la relación `users()` de
     * Spatie resuelve el modelo desde la configuración de guardas, que aquí no hace falta.
     *
     * @param  Collection<int, Role>  $roles
     * @return Collection<int, Role>
     */
    private function conConteo(Collection $roles): Collection
    {
        $conteos = DB::table('model_has_roles')
            ->whereIn('role_id', $roles->pluck('id'))
            ->selectRaw('role_id, COUNT(*) AS total')
            ->groupBy('role_id')
            ->pluck('total', 'role_id');

        foreach ($roles as $rol) {
            $rol->setAttribute('users_count', (int) ($conteos[$rol->id] ?? 0));
        }

        return $roles;
    }

    private function usuariosDelRol(Role $rol): int
    {
        return DB::table('model_has_roles')->where('role_id', $rol->id)->count();
    }

    /** @return list<string> */
    private function nombresDe(Role $rol): array
    {
        return array_values(array_map('strval', $rol->permissions->pluck('name')->all()));
    }

    private function exigirNoProtegido(Role $rol): void
    {
        if ($rol->name === ReglasDeAcceso::ROL_SUPER) {
            throw new AccionNoPermitida('El rol de súper administrador no se puede modificar ni eliminar desde el panel.');
        }
    }

    /** @param list<string> $permisos */
    private function exigirPoderSobre(User $actor, array $permisos): void
    {
        if (! ReglasDeAcceso::puedeOtorgarPermisos($actor, $permisos)) {
            throw new AccionNoPermitida('No puede otorgar ni quitar permisos que usted no tiene.');
        }
    }
}
