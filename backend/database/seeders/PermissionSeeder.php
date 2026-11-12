<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permisos del panel.
 *
 * Seeding explícito de cada permiso para que Spatie pueda verificar `can()`
 * en las Policies. Si un permiso no existe en la tabla, `can('entidad.gestionar')`
 * returns false aunque el rol tenga `*`.
 *
 * Los permisos del panel coinciden con las claves del mapa
 * `PERMISO_POR_RUTA` en el frontend.
 *
 * Es idempotente: usa `findOrCreate` para no duplicar filas al repetir la siembra.
 */
final class PermissionSeeder extends Seeder
{
    private const PERMISOS_PANEL = [
        // Principal
        'panel-administrative',

        // Atención al ciudadano
        'pqrsd.ver',
        'tramites.ver',
        'citas.ver',
        'notificaciones.ver',

        // Servicios al ciudadano
        'sede.publicar',
        'carpeta.ver',
        'autenticacion.gestionar',

        // Contenidos
        'cms.gestionar',
        'noticias.gestionar',
        'tramites.gestionar',
        'banners.gestionar',
        'portal.publicar',
        'transparencia.ver',

        // Gestión documental e integraciones
        'documental.gestionar',
        'sigmi.ver',
        'integraciones.gestionar',

        // Administración
        'usuarios.gestionar',
        'auditoria.ver',
        'reportes.ver',
        'asignacion.gestionar',
        'entidad.gestionar',
        'politicas.gestionar',

        // Archivos (tabla común `file_media`). Antes no se sembraban: las rutas de
        // archivos los exigen con `can:` y sólo el super-admin podía usarlas.
        'archivos.ver',
        'archivos.crear',
        'archivos.eliminar',

        'configuracion.gestionar',
    ];

    public function run(): void
    {
        $nuevos = [];
        foreach (self::PERMISOS_PANEL as $nombre) {
            if (Permission::findOrCreate($nombre, 'web')->wasRecentlyCreated) {
                $nuevos[] = $nombre;
            }
        }

        // El rol «admin» (administra usuarios y contenidos) se crea una sola vez con todos los
        // permisos del panel; si ya existe no se toca, para no deshacer ajustes hechos desde el panel.
        $admin = Role::findOrCreate('admin', 'web');
        if ($admin->wasRecentlyCreated) {
            $admin->syncPermissions(self::PERMISOS_PANEL);
        } elseif ($nuevos !== []) {
            // Un permiso que aparece por primera vez (un módulo nuevo) llega también al rol «admin»;
            // los que ya existían no se tocan, así un permiso que se le quitó a propósito no vuelve.
            $admin->givePermissionTo($nuevos);
        }
    }
}
