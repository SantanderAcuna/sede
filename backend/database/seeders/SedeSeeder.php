<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Los datos institucionales de la Sede, en el orden en que se necesitan.
 *
 * # Por qué existe este sembrador y no basta `DatabaseSeeder`
 *
 * `DatabaseSeeder` es el punto de entrada de Laravel y lo ejecuta
 * `migrate:fresh --seed` —lo que corre `make preparar`— en desarrollo. Pero el
 * servidor no debe ejecutar «el sembrador por defecto»: si mañana alguien añade
 * a `DatabaseSeeder` un dato de desarrollo, ese dato viaja a producción sin que
 * nadie lo note. El despliegue nombra a **este** sembrador por su clase
 * (`db:seed --class=SedeSeeder`), así que lo que se siembra en el servidor está
 * escrito aquí y en ningún otro sitio.
 *
 * # Por qué faltaba el menú
 *
 * `MenuSeeder` existía desde el principio y **no lo llamaba nadie**: se
 * comprobó que la única aparición de su nombre en todo el backend era su propia
 * declaración de clase. El efecto medido, tras `migrate:fresh --seed`:
 * `menus: 0`, con la tabla vacía y el endpoint `GET /api/v1/identidad/menu`
 * devolviendo nada —incluido el ítem «Trámites y servicios»—. Un sembrador
 * huérfano es peor que no tenerlo: parece que el menú se siembra.
 *
 * # Por qué el orden es este
 *
 *   - `Entidad` primero: alimenta la cabecera y el pie, y no depende de nada.
 *   - `Menu` después: sus roles viajan como texto en la propia fila, así que no
 *     dependen de que existan los roles, pero se siembran antes que el usuario
 *     para que el panel tenga menú en su primer arranque.
 *   - `Tramite`: el catálogo, la operación más pesada.
 *   - `SuperAdmin` al final: es la cuenta que concede permisos, así que va
 *     cuando ya hay algo que administrar.
 *
 * **Los cuatro son idempotentes**, y eso no es un adorno: este sembrador corre
 * en cada despliegue. `TramiteSeeder` respeta lo que haya corregido una persona
 * y no lo pisa; `Entidad` y `SuperAdmin` usan `updateOrCreate`; y `Menu` también
 * desde que se corrigió —antes creaba filas a ciegas y sembrar dos veces dejaba
 * el menú duplicado—.
 */
final class SedeSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(EntidadSeeder::class);
        $this->call(MenuSeeder::class);
        $this->call(TramiteSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(SuperAdminSeeder::class);
    }
}
