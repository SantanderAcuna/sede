<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de auditoría de acciones de usuarios.
 *
 * Captura eventos de:
 * - Spatie Permission: attach/detach de roles y permisos
 * - Auth de Laravel: login y logout de sesión
 *
 * @property int $id
 * @property string $accion
 * @property string $recurso
 * @property int|null $recurso_id
 * @property array<string, mixed>|null $cambios
 * @property string|null $ip_origen
 * @property string|null $user_agent
 * @property int|null $causer_id
 * @property string|null $causer_type
 * @property Carbon $created_at
 */
final class AuditLog extends Model
{
    /**
     * Tabla explícita: la migración usa singular para coincidir con el contrato
     * (audit_log, no audit_logs).
     */
    protected $table = 'audit_log';

    /**
     * Indica que no use columnas updated_at (solo created_at).
     */
    public const UPDATED_AT = null;

    /**
     * Los-fillable son todos los campos de la tabla excepto created_at.
     */
    protected $fillable = [
        'accion',
        'recurso',
        'recurso_id',
        'cambios',
        'ip_origen',
        'user_agent',
        'causer_id',
        'causer_type',
    ];

    /**
     * Los cast para cada campo.
     */
    protected function casts(): array
    {
        return [
            'cambios' => 'array',
            'recurso_id' => 'integer',
            'causer_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Relación con el usuario que provocó el evento.
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
}
