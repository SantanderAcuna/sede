<?php

declare(strict_types=1);

use App\Support\Acceso\CatalogoPermisos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Parte los permisos «X.gestionar» (que daban ver, crear, editar, eliminar y publicar de golpe) en
 * acciones separadas por módulo. Ver `CatalogoPermisos`.
 *
 * - Quien tenía «X.gestionar» (por rol o de forma directa) recibe **todas** las acciones de X: nadie
 *   gana ni pierde acceso al migrar; después se afina en el panel.
 * - `usuarios.gestionar` abría usuarios y también roles y permisos: recibe las dos familias.
 * - `tramites.ver` significaba «ver solicitudes de la ciudadanía»; pasa a `solicitudes.ver`, y
 *   `tramites.ver` queda para el catálogo (solo lo recibe quien gestionaba trámites).
 * - `portal.publicar` y `sede.publicar` reciben además su «ver».
 * - El rol `admin` recibe todos los permisos del catálogo (los de los módulos nuevos incluidos).
 * - Se borran los permisos viejos `*.gestionar`.
 *
 * El cambio de significado de `tramites.ver` solo se hace una vez: si ya existe `tramites.crear`
 * (el modelo nuevo ya está sembrado) no se vuelve a tocar.
 */
return new class extends Migration
{
    public function up(): void
    {
        $ahora = now();
        $yaGranular = DB::table('permissions')->where('name', 'tramites.crear')->exists();

        $ids = DB::table('permissions')->where('guard_name', 'web')->pluck('id', 'name')->all();
        foreach (CatalogoPermisos::nombres() as $nombre) {
            if (! isset($ids[$nombre])) {
                $ids[$nombre] = DB::table('permissions')->insertGetId([
                    'name' => $nombre, 'guard_name' => 'web', 'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
            }
        }

        $mapa = CatalogoPermisos::legado();

        // 1) `tramites.ver` de antes → `solicitudes.ver`, y se vacía para el significado nuevo.
        if (! $yaGranular && isset($ids['tramites.ver'])) {
            $this->copiarTitulares($ids['tramites.ver'], [$ids['solicitudes.ver']]);
            DB::table('role_has_permissions')->where('permission_id', $ids['tramites.ver'])->delete();
            DB::table('model_has_permissions')->where('permission_id', $ids['tramites.ver'])->delete();
        }
        unset($mapa['tramites.ver']);

        // 2) Cada permiso viejo reparte sus titulares entre las acciones nuevas.
        foreach ($mapa as $viejo => $nuevos) {
            if (! isset($ids[$viejo]) || in_array($viejo, CatalogoPermisos::nombres(), true)) {
                // `portal.publicar` y `sede.publicar` siguen existiendo: solo ganan su «ver».
                if (isset($ids[$viejo])) {
                    $this->copiarTitulares($ids[$viejo], array_map(static fn (string $n): int => $ids[$n], array_diff($nuevos, [$viejo])));
                }

                continue;
            }
            $this->copiarTitulares($ids[$viejo], array_map(static fn (string $n): int => $ids[$n], $nuevos));
            DB::table('role_has_permissions')->where('permission_id', $ids[$viejo])->delete();
            DB::table('model_has_permissions')->where('permission_id', $ids[$viejo])->delete();
            DB::table('permissions')->where('id', $ids[$viejo])->delete();
        }

        // 3) El rol `admin` tiene todo.
        $admin = DB::table('roles')->where('name', 'admin')->where('guard_name', 'web')->value('id');
        if ($admin !== null) {
            foreach ($ids as $nombre => $id) {
                if (in_array($nombre, CatalogoPermisos::nombres(), true)) {
                    DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $admin]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Es una partición de datos: volver a juntar acciones no tiene un estado anterior cierto.
    }

    /** @param  list<int>  $destinos */
    private function copiarTitulares(int $origen, array $destinos): void
    {
        $roles = DB::table('role_has_permissions')->where('permission_id', $origen)->pluck('role_id');
        $modelos = DB::table('model_has_permissions')->where('permission_id', $origen)->get(['model_type', 'model_id']);

        foreach ($destinos as $destino) {
            foreach ($roles as $rol) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $destino, 'role_id' => $rol]);
            }
            foreach ($modelos as $m) {
                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $destino, 'model_type' => $m->model_type, 'model_id' => $m->model_id,
                ]);
            }
        }
    }
};
