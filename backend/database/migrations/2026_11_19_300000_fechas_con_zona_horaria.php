<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Las 38 fechas `timestamp(0) without time zone` pasan a `timestamptz(0)` (0D, D2; R3 del plan).
 *
 * Los valores ya están en UTC desde #625 (la sesión de la aplicación fija `UTC`), así que se
 * interpretan como UTC: `columna AT TIME ZONE 'UTC'`. Un cliente con otra zona (un reporte, `psql`,
 * una réplica) deja de leer horas equivocadas. Un solo `ALTER` por tabla (una sola reescritura);
 * los dos índices parciales que usan `deleted_at` se reconstruyen solos. No hay vistas.
 *
 * `lock_timeout` de 3 s: si la tabla está ocupada la migración falla y no deja a la aplicación
 * esperando. Todo corre en una transacción (PostgreSQL admite DDL transaccional).
 *
 * `down()` devuelve `timestamp(0)` con la hora en UTC, tal como estaba.
 */
return new class extends Migration
{
    /** @var array<string, list<string>> tabla => columnas */
    private const COLUMNAS = [
        'audit_log' => ['created_at'],
        'banners' => ['created_at', 'deleted_at', 'updated_at'],
        'consentimientos' => ['registrado_en'],
        'dependencias' => ['created_at', 'updated_at'],
        'entidads' => ['created_at', 'updated_at'],
        'failed_jobs' => ['failed_at'],
        'file_media' => ['created_at', 'updated_at'],
        'menus' => ['created_at', 'updated_at'],
        'noticias' => ['created_at', 'deleted_at', 'updated_at'],
        'password_reset_tokens' => ['created_at'],
        'permissions' => ['created_at', 'updated_at'],
        'personal_access_tokens' => ['created_at', 'expires_at', 'last_used_at', 'updated_at'],
        'politicas' => ['created_at', 'updated_at'],
        'roles' => ['created_at', 'updated_at'],
        'tramite_categorias' => ['created_at', 'updated_at'],
        'tramite_valoraciones' => ['created_at'],
        'tramites' => ['created_at', 'deleted_at', 'publicado_en', 'updated_at'],
        'users' => ['created_at', 'email_verified_at', 'updated_at'],
    ];

    public function up(): void
    {
        $this->convertir('timestamptz(0)', fn (string $c): string => "{$c} at time zone 'UTC'");
    }

    public function down(): void
    {
        $this->convertir('timestamp(0) without time zone', fn (string $c): string => "{$c} at time zone 'UTC'");
    }

    /** @param callable(string): string $usando */
    private function convertir(string $tipo, callable $usando): void
    {
        DB::transaction(function () use ($tipo, $usando): void {
            DB::statement("set local lock_timeout = '3s'");

            foreach (self::COLUMNAS as $tabla => $columnas) {
                $cambios = array_map(
                    fn (string $c): string => "alter column {$c} type {$tipo} using ".$usando($c),
                    $columnas,
                );

                DB::statement("alter table {$tabla} ".implode(', ', $cambios));
            }
        });
    }
};
