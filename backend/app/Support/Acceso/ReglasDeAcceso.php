<?php

declare(strict_types=1);

namespace App\Support\Acceso;

use App\Exceptions\AccionNoPermitida;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Las reglas que impiden la escalada de privilegios.
 *
 * Principio: nadie otorga, quita ni toca un poder que no tiene. El súper administrador
 * puede todo; los demás solo operan dentro de lo que ya poseen y jamás sobre el rol
 * `super-admin` ni sobre quienes lo tienen.
 */
final class ReglasDeAcceso
{
    public const ROL_SUPER = 'super-admin';

    public static function esSuper(User $usuario): bool
    {
        return $usuario->hasRole(self::ROL_SUPER);
    }

    /** @return list<string> */
    public static function permisosDe(User $usuario): array
    {
        return array_values(array_map('strval', $usuario->getAllPermissions()->pluck('name')->all()));
    }

    /** @param list<string> $permisos */
    public static function puedeOtorgarPermisos(User $actor, array $permisos): bool
    {
        if (self::esSuper($actor)) {
            return true;
        }

        return array_diff($permisos, self::permisosDe($actor)) === [];
    }

    public static function puedeOtorgarRol(User $actor, Role $rol): bool
    {
        if (self::esSuper($actor)) {
            return true;
        }
        if ($rol->name === self::ROL_SUPER) {
            return false;
        }

        return self::puedeOtorgarPermisos($actor, array_values(array_map('strval', $rol->permissions->pluck('name')->all())));
    }

    public static function exigirOtorgarRol(User $actor, Role $rol): void
    {
        if (! self::puedeOtorgarRol($actor, $rol)) {
            throw new AccionNoPermitida("No puede asignar ni quitar el rol «{$rol->name}»: incluye permisos que usted no tiene.");
        }
    }

    public static function exigirTocarUsuario(User $actor, User $objetivo): void
    {
        if (self::esSuper($objetivo) && ! self::esSuper($actor)) {
            throw new AccionNoPermitida('Solo un súper administrador puede modificar a otro súper administrador.');
        }
    }
}
