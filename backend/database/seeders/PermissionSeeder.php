<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Acceso\CatalogoPermisos;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permisos del panel.
 *
 * Seeding explícito de cada permiso para que Spatie pueda verificar `can()`
 * en las Policies. Si un permiso no existe en la tabla, `can('entidad.editar')`
 * returns false aunque el rol tenga `*`.
 *
 * Los permisos salen de `CatalogoPermisos` (módulo × acción) y coinciden con las claves del mapa
 * `PERMISO_POR_RUTA` del panel.
 *
 * Es idempotente: usa `findOrCreate` para no duplicar filas al repetir la siembra.
 */
final class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $nuevos = [];
        foreach (CatalogoPermisos::nombres() as $nombre) {
            if (Permission::findOrCreate($nombre, 'web')->wasRecentlyCreated) {
                $nuevos[] = $nombre;
            }
        }

        // El rol «admin» (administra usuarios y contenidos) se crea una sola vez con todos los
        // permisos del panel; si ya existe no se toca, para no deshacer ajustes hechos desde el panel.
        $admin = Role::findOrCreate('admin', 'web');
        if ($admin->wasRecentlyCreated) {
            $admin->syncPermissions(CatalogoPermisos::nombres());
        } elseif ($nuevos !== []) {
            // Un permiso que aparece por primera vez (un módulo nuevo) llega también al rol «admin»;
            // los que ya existían no se tocan, así un permiso que se le quitó a propósito no vuelve.
            $admin->givePermissionTo($nuevos);
        }
    }
}
