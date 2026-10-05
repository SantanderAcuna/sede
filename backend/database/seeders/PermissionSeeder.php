<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

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
        'configuracion.gestionar',
    ];

    public function run(): void
    {
        foreach (self::PERMISOS_PANEL as $nombre) {
            Permission::findOrCreate($nombre, 'web');
        }
    }
}
