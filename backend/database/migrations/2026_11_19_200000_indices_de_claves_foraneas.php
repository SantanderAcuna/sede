<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Índices en las cinco claves foráneas que no tenían uno (0D, D3; R4 del plan de robustez).
 *
 * Sin índice, borrar o actualizar la fila padre recorre la tabla hija entera y la bloquea más de la
 * cuenta. `role_has_permissions.role_id` es el segundo componente de su clave primaria, así que no
 * la encabeza y no sirve para buscar por rol.
 *
 * Las tablas son pequeñas; aun así el `lock_timeout` hace que la migración falle en vez de dejar a
 * la aplicación esperando un bloqueo. `IF NOT EXISTS`/`IF EXISTS` la vuelven repetible.
 */
return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string}> índice => [tabla, columna] */
    private const INDICES = [
        'noticias_autor_id_index' => ['noticias', 'autor_id'],
        'role_has_permissions_role_id_index' => ['role_has_permissions', 'role_id'],
        'tramite_requisitos_paso_id_index' => ['tramite_requisitos', 'paso_id'],
        'tramites_categoria_id_index' => ['tramites', 'categoria_id'],
        'tramites_dependencia_id_index' => ['tramites', 'dependencia_id'],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            DB::statement("set local lock_timeout = '3s'");

            foreach (self::INDICES as $indice => [$tabla, $columna]) {
                DB::statement("create index if not exists {$indice} on {$tabla} ({$columna})");
            }
        });
    }

    public function down(): void
    {
        foreach (array_keys(self::INDICES) as $indice) {
            DB::statement("drop index if exists {$indice}");
        }
    }
};
