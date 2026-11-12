<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\UsuarioPanelServiceInterface;
use App\Exceptions\AccionNoPermitida;
use App\Exceptions\UltimoSuperAdmin;
use App\Models\User;
use App\Support\Acceso\Huella;
use App\Support\Acceso\ReglasDeAcceso;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Altas, cambios y bloqueos de usuarios del panel.
 *
 * Aquí viven las reglas de seguridad; la interfaz solo las anticipa. Ver {@see ReglasDeAcceso}.
 */
final class UsuarioPanelService implements UsuarioPanelServiceInterface
{
    public function listar(int $porPagina, ?string $texto, ?string $estado, ?string $rol): LengthAwarePaginator
    {
        $consulta = User::query()->with('roles')->orderBy('name');

        if ($texto !== null && $texto !== '') {
            $patron = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower($texto)).'%';
            $consulta->where(fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', [$patron])->orWhereRaw('LOWER(email) LIKE ?', [$patron]));
        }
        if ($estado !== null && $estado !== '') {
            $consulta->where('estado', $estado);
        }
        if ($rol !== null && $rol !== '') {
            $consulta->whereHas('roles', fn ($q) => $q->where('name', $rol));
        }

        return $consulta->paginate($porPagina);
    }

    public function porUuid(string $uuid): ?User
    {
        return User::query()->with('roles')->where('uuid', $uuid)->first();
    }

    public function crear(User $actor, array $datos): User
    {
        $roles = $this->rolesPorNombre($datos['roles'] ?? []);
        foreach ($roles as $rol) {
            ReglasDeAcceso::exigirOtorgarRol($actor, $rol);
        }

        return DB::transaction(function () use ($actor, $datos, $roles): User {
            $usuario = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'password' => $datos['password'],
                'estado' => $datos['estado'] ?? 'activo',
            ]);
            $usuario->syncRoles($roles);

            Huella::registrar($actor, 'usuario_creado', 'usuarios', $usuario->id, [
                'correo' => $usuario->email,
                'estado' => $usuario->estado,
                'roles' => $roles->pluck('name')->all(),
            ]);

            return $usuario->load('roles');
        });
    }

    public function actualizar(User $actor, User $objetivo, array $cambios): User
    {
        $esUnoMismo = $actor->is($objetivo);
        ReglasDeAcceso::exigirTocarUsuario($actor, $objetivo);

        $estadoNuevo = array_key_exists('estado', $cambios) ? (string) $cambios['estado'] : $objetivo->estado;
        $cambiaEstado = $estadoNuevo !== $objetivo->estado;

        $rolesActuales = $objetivo->roles->pluck('name')->sort()->values()->all();
        $rolesNuevos = null;
        if (array_key_exists('roles', $cambios)) {
            $rolesNuevos = $this->rolesPorNombre($cambios['roles'] ?? []);
        }
        $nombresNuevos = $rolesNuevos?->pluck('name')->sort()->values()->all();
        $cambiaRoles = $nombresNuevos !== null && $nombresNuevos !== $rolesActuales;

        if ($esUnoMismo && ($cambiaEstado || $cambiaRoles)) {
            throw new AccionNoPermitida('No puede cambiar sus propios roles ni su estado: pídaselo a otro administrador.');
        }

        // Solo se exige poder sobre los roles que realmente se agregan o se quitan.
        if ($cambiaRoles && $rolesNuevos !== null) {
            $diferencia = array_merge(array_diff($nombresNuevos ?? [], $rolesActuales), array_diff($rolesActuales, $nombresNuevos ?? []));
            foreach (Role::query()->whereIn('name', $diferencia)->get() as $rol) {
                ReglasDeAcceso::exigirOtorgarRol($actor, $rol);
            }
        }

        $dejaDeSerSuperActivo = ReglasDeAcceso::esSuper($objetivo) && $objetivo->estado === 'activo'
            && ($estadoNuevo !== 'activo' || ($cambiaRoles && ! in_array(ReglasDeAcceso::ROL_SUPER, $nombresNuevos ?? [], true)));
        if ($dejaDeSerSuperActivo && $this->superAdminsActivosAparte($objetivo) === 0) {
            throw new UltimoSuperAdmin('Es el único súper administrador activo: el sistema no puede quedarse sin uno.');
        }

        return DB::transaction(function () use ($actor, $objetivo, $cambios, $rolesNuevos, $cambiaEstado, $cambiaRoles, $estadoNuevo): User {
            $campos = [];
            foreach (['name', 'email'] as $campo) {
                if (array_key_exists($campo, $cambios) && $cambios[$campo] !== $objetivo->{$campo}) {
                    $objetivo->{$campo} = $cambios[$campo];
                    $campos[] = $campo;
                }
            }
            if ($cambiaEstado) {
                $objetivo->estado = $estadoNuevo;
            }
            $cambiaClave = isset($cambios['password']) && $cambios['password'] !== '';
            if ($cambiaClave) {
                $objetivo->password = (string) $cambios['password'];
                $campos[] = 'clave';
            }
            $objetivo->save();

            if ($cambiaRoles && $rolesNuevos !== null) {
                $objetivo->syncRoles($rolesNuevos);
                $campos[] = 'roles';
            }

            if ($cambiaEstado || $cambiaClave) {
                // Una sesión abierta no debe sobrevivir a un bloqueo ni a un cambio de clave.
                $objetivo->tokens()->delete();
            }

            if ($cambiaEstado) {
                Huella::registrar($actor, 'usuario_estado_cambiado', 'usuarios', $objetivo->id, ['estado' => $estadoNuevo]);
            }
            if ($campos !== []) {
                Huella::registrar($actor, 'usuario_actualizado', 'usuarios', $objetivo->id, [
                    'campos' => $campos,
                    'roles' => $objetivo->roles()->pluck('name')->all(),
                ]);
            }

            return $objetivo->load('roles');
        });
    }

    private function superAdminsActivosAparte(User $excluido): int
    {
        return User::query()
            ->where('estado', 'activo')
            ->where('id', '!=', $excluido->id)
            ->whereHas('roles', fn ($q) => $q->where('name', ReglasDeAcceso::ROL_SUPER))
            ->count();
    }

    /**
     * @param  list<string>  $nombres
     * @return \Illuminate\Support\Collection<int, Role>
     */
    private function rolesPorNombre(array $nombres): \Illuminate\Support\Collection
    {
        return Role::query()->whereIn('name', $nombres)->where('guard_name', 'web')->get();
    }
}
