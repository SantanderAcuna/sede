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
 *
 * **Es idempotente, y no lo era.** El sembrador creaba cada ítem con
 * `Menu::create()`, así que ejecutarlo dos veces dejaba el menú duplicado: ocho
 * filas donde van cuatro. Se vio al plantear la siembra del servidor, donde el
 * mismo sembrador tiene que poder correr en cada despliegue. La llave es
 * `slug`, que es único en la tabla, así que `updateOrCreate` deja el menú igual
 * la primera vez y las siguientes —y de paso corrige la etiqueta si cambió—.
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

        foreach ([...$sitioItems, ...$panelItems] as $item) {
            Menu::updateOrCreate(['slug' => $item['slug']], $item);
        }
    }
}
