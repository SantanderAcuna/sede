<?php

declare(strict_types=1);

use App\Support\Acceso\CatalogoPermisos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Políticas propias de la Entidad, además de las cinco obligatorias.
 *
 * - `publicada`: una política nueva nace oculta (borrador). Las ya existentes quedan publicadas.
 * - Permisos nuevos `politicas.crear`, `politicas.eliminar` y `politicas.publicar`: solo el rol `admin`
 *   los recibe; ningún otro rol gana acceso al migrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('politicas', function (Blueprint $tabla): void {
            $tabla->boolean('publicada')->default(true)->after('es_ejemplo');
        });

        $ahora = now();
        $admin = DB::table('roles')->where('name', 'admin')->where('guard_name', 'web')->value('id');
        foreach (CatalogoPermisos::de('politicas') as $nombre) {
            $id = DB::table('permissions')->where('name', $nombre)->where('guard_name', 'web')->value('id')
                ?? DB::table('permissions')->insertGetId([
                    'name' => $nombre, 'guard_name' => 'web', 'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
            if ($admin !== null) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $admin]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('politicas', function (Blueprint $tabla): void {
            $tabla->dropColumn('publicada');
        });
    }
};
