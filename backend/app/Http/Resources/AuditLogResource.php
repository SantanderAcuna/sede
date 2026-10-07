<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AuditLog
 *
 * @property AuditLog $resource
 */
final class AuditLogResource extends JsonResource
{
    /**
     * Transforma el recurso al formato AuditoriaItem.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'auditoria',
            'accion' => $this->accion,
            'recurso' => $this->recurso,
            'recurso_id' => $this->recurso_id,
            'cambios' => $this->cambios,
            'ip_origen' => $this->ip_origen,
            'user_agent' => $this->user_agent,
            'usuario' => $this->when(
                $this->causer_id !== null,
                fn () => [
                    'id' => $this->causer_id,
                    /** @var string|null */
                    'email' => $this->causer->email ?? null,
                ],
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
