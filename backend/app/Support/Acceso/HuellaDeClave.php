<?php

declare(strict_types=1);

namespace App\Support\Acceso;

use App\Models\User;

/**
 * Huella de la contraseña de una cuenta, para saber si cambió desde que se abrió la sesión.
 *
 * Se guarda en la sesión al iniciarla y `SesionDelPanelVigente` la compara en cada petición: si la
 * contraseña cambió (la cambió un administrador o la propia persona), la sesión abierta deja de valer.
 *
 * No es el hash de la contraseña: es un HMAC-SHA256 de ese hash con la clave de la aplicación. Así la
 * sesión (que vive en Redis) no guarda nada con lo que se pueda atacar la contraseña fuera de línea, y
 * la huella no sirve en otra instalación con otra `APP_KEY`.
 */
final class HuellaDeClave
{
    /** Clave de la sesión donde se guarda la huella. */
    public const CLAVE_SESION = 'panel.huella_clave';

    public static function de(User $usuario): string
    {
        return hash_hmac('sha256', (string) $usuario->getAuthPassword(), (string) config('app.key'));
    }
}
