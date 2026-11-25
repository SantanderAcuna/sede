<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Acceso\Huella;
use App\Support\Acceso\HuellaDeClave;
use App\Support\Api\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * La sesión del panel vale mientras la cuenta esté activa y su contraseña sea la de cuando se abrió.
 *
 * Va después de `auth:sanctum` en las rutas del panel que exigen sesión. En cada petición comprueba:
 *
 *   - el estado de la cuenta: `inactivo`, `bloqueado` o `pendiente` cortan la sesión;
 *   - la huella de la contraseña guardada al iniciar sesión (`HuellaDeClave`): si no coincide, la
 *     contraseña cambió y la sesión deja de valer.
 *
 * Al cortarla cierra el guard, invalida la sesión (la cookie queda inservible aunque la cuenta vuelva a
 * estar activa), deja constancia en la bitácora y responde 401: el panel lleva entonces al inicio de
 * sesión, que es la regla definida para un 401.
 *
 * Una sesión abierta antes de este cambio no tiene huella: si la cuenta está activa sigue valiendo, y
 * desde esa petición queda guardada la huella de la contraseña actual.
 *
 * Es un middleware de la aplicación, no el `AuthenticateSession` de Sanctum: ese está desactivado a
 * propósito (`config/sanctum.php` explica por qué) y el propio comentario de esa configuración pide esto.
 */
final class SesionDelPanelVigente
{
    public function handle(Request $peticion, Closure $siguiente): Response
    {
        $usuario = Auth::guard('web')->user();
        if (! $usuario instanceof User) {
            return $siguiente($peticion);
        }

        $sesion = $peticion->hasSession() ? $peticion->session() : null;
        $huellaActual = HuellaDeClave::de($usuario);
        $guardada = $sesion?->get(HuellaDeClave::CLAVE_SESION);

        $motivo = null;
        if ($usuario->estado !== 'activo') {
            $motivo = 'cuenta_no_activa';
        } elseif (is_string($guardada) && ! hash_equals($guardada, $huellaActual)) {
            $motivo = 'clave_cambiada';
        }

        if ($motivo === null) {
            if ($sesion !== null && ! is_string($guardada)) {
                $sesion->put(HuellaDeClave::CLAVE_SESION, $huellaActual);
            }

            return $siguiente($peticion);
        }

        Huella::registrar($usuario, 'sesion_terminada', 'usuarios', $usuario->id, ['motivo' => $motivo]);

        Auth::guard('web')->logout();
        if ($sesion !== null) {
            $sesion->invalidate();
            $sesion->regenerateToken();
        }

        return ApiResponse::error(
            $motivo === 'cuenta_no_activa'
                ? 'La sesión terminó porque la cuenta ya no está activa.'
                : 'La sesión terminó porque la contraseña cambió. Vuelva a iniciar sesión.',
            401
        );
    }
}
