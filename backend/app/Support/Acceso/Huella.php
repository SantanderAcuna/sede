<?php

declare(strict_types=1);

namespace App\Support\Acceso;

use App\Models\AuditLog;
use App\Models\User;

/** Deja constancia en la auditoría de un cambio de usuarios o roles. Nunca recibe claves. */
final class Huella
{
    /**
     * Los nombres de lo que de verdad cambió entre dos instantáneas.
     *
     * El formulario del panel manda campos que la persona no tocó (el nombre, los datos del acto…), así que
     * registrar las claves de la petición diría que se cambió algo que quedó igual. Esto compara lo que había
     * antes con lo que quedó.
     *
     * @param  array<string, mixed>  $antes
     * @param  array<string, mixed>  $despues
     * @return list<string>
     */
    public static function diferencias(array $antes, array $despues): array
    {
        $cambiados = [];
        foreach (array_unique([...array_keys($antes), ...array_keys($despues)]) as $clave) {
            if (json_encode($antes[$clave] ?? null) !== json_encode($despues[$clave] ?? null)) {
                $cambiados[] = (string) $clave;
            }
        }

        return $cambiados;
    }

    /**
     * Igual que `registrar`, con quien tiene la sesión abierta como actor: para los controladores que aún no
     * reciben al usuario como parámetro. Las rutas que la usan exigen sesión, así que siempre hay usuario.
     *
     * @param  array<string, mixed>  $cambios
     */
    public static function deSesion(string $accion, string $recurso, ?int $recursoId, array $cambios): void
    {
        /** @var User $actor */
        $actor = auth()->user();
        self::registrar($actor, $accion, $recurso, $recursoId, $cambios);
    }

    /** @param array<string, mixed> $cambios */
    public static function registrar(User $actor, string $accion, string $recurso, ?int $recursoId, array $cambios): void
    {
        AuditLog::create([
            'accion' => $accion,
            'recurso' => $recurso,
            'recurso_id' => $recursoId,
            'cambios' => $cambios,
            'ip_origen' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'causer_id' => $actor->id,
            'causer_type' => null,
        ]);
    }
}
