<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

/**
 * Seed para el menú de navegación.
 *
 * - Items sin roles (roles=null) son visibles para todos (sitio y panel).
 * - Items con roles son visibles solo para esos roles.
 * - rol "sitio" = sitio público
 * - rol "panel" = panel de administración
 * - rol "admin" = solo administradores
 */
final class MenuSeeder extends Seeder
{
    /**
     * Ejecuta el seeder.
     */
    public function run(): void
    {
        // Menú público (visible para todos)
        $sitioItems = [
            ['slug' => 'inicio', 'etiqueta' => 'Inicio', 'ruta' => '/', 'orden' => 1, 'visible' => true, 'tipo' => 'interno', 'roles' => null],
            ['slug' => 'tramites', 'etiqueta' => 'Trámites y servicios', 'ruta' => '/tramites', 'orden' => 2, 'visible' => true, 'tipo' => 'interno', 'roles' => null],
            ['slug' => 'transparencia', 'etiqueta' => 'Transparencia', 'ruta' => '/transparencia', 'orden' => 3, 'visible' => true, 'tipo' => 'interno', 'roles' => null],
            ['slug' => 'contacto', 'etiqueta' => 'Contacto', 'ruta' => '/contacto', 'orden' => 4, 'visible' => true, 'tipo' => 'interno', 'roles' => null],
        ];

        // Menú del panel (solo visible para usuarios del panel)
        $panelItems = [
            ['slug' => 'panel-inicio', 'etiqueta' => 'Dashboard', 'ruta' => '/panel', 'orden' => 1, 'visible' => true, 'tipo' => 'interno', 'roles' => '["panel","admin"]'],
            ['slug' => 'panel-tramites', 'etiqueta' => 'Trámites', 'ruta' => '/panel/tramites', 'orden' => 2, 'visible' => true, 'tipo' => 'interno', 'roles' => '["panel","admin"]'],
            ['slug' => 'panel-entidad', 'etiqueta' => 'Entidad', 'ruta' => '/panel/entidad', 'orden' => 3, 'visible' => true, 'tipo' => 'interno', 'roles' => '["admin"]'],
            ['slug' => 'panel-usuarios', 'etiqueta' => 'Usuarios', 'ruta' => '/panel/usuarios', 'orden' => 4, 'visible' => true, 'tipo' => 'interno', 'roles' => '["admin"]'],
        ];

        // Insertar menú público
        foreach ($sitioItems as $item) {
            Menu::create($item);
        }

        // Insertar menú del panel
        foreach ($panelItems as $item) {
            Menu::create($item);
        }
    }
}
