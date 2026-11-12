<?php

declare(strict_types=1);

namespace App\Support\Acceso;

use App\Models\AuditLog;
use App\Models\User;

/** Deja constancia en la auditoría de un cambio de usuarios o roles. Nunca recibe claves. */
final class Huella
{
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
