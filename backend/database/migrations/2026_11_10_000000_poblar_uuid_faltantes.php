<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rellena el `uuid` de las filas que lo tienen nulo.
 *
 * `add_uuid_columns` añadió la columna como opcional y solo pobló `users`, con el
 * supuesto de que «las demás tablas se seedean desde cero». En staging la fila de la
 * entidad ya existía, el sembrador (`updateOrCreate(['id' => 1])`) no toca el `uuid`
 * de una fila existente, y quedó en NULL: `GET /api/v1/entidad` devolvía
 * `uuid: null` y subir un archivo a la entidad respondía 500.
 *
 * Es idempotente: solo toca filas con `uuid IS NULL`, de modo que un uuid ya
 * publicado no cambia nunca.
 */
return new class extends Migration
{
    private const TABLAS = ['users', 'entidads', 'menus', 'tramites', 'ingesta_tramites'];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            // `ingesta_tramites` desapareció al pasar el catálogo a base propia; se comprueba que la
            // tabla exista para que esta migración histórica siga corriendo en limpio y al reejecutarse.
            if (! Schema::hasTable($tabla)) {
                continue;
            }
            DB::statement("UPDATE {$tabla} SET uuid = gen_random_uuid() WHERE uuid IS NULL");
        }
    }

    public function down(): void
    {
        // Sin marcha atrás: volver a dejar uuid nulos no tiene uso.
    }
};
