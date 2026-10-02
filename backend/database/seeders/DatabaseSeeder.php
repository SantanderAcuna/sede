<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /*
     * Aquí **no** se usa `WithoutModelEvents`, y es deliberado.
     *
     * Ese trait apaga los eventos de modelo durante toda la siembra, y
     * `Tramite` calcula su columna `busqueda` —el texto normalizado sin tildes
     * contra el que busca el endpoint— en su evento `saving`. Silenciarlo deja
     * la columna nula y revienta con
     * `NOT NULL constraint failed: tramites.busqueda`.
     *
     * El fallo no se veía en las pruebas porque ellas invocan `TramiteSeeder`
     * directamente y nunca pasan por este sembrador; sólo aparecía al ejecutar
     * `php artisan migrate:fresh --seed`, que es exactamente lo que hace
     * `make preparar`. Un modelo cuya integridad depende de sus eventos no puede
     * tenerlos apagados: si algún día hace falta acelerar la siembra, se
     * calcula el valor en el sembrador, pero no se apaga el modelo.
     */

    /**
     * Seed the application's database.
     *
     * **Delega en `SedeSeeder` para que haya una sola lista de lo que se
     * siembra.** Si este método tuviera su propia lista, el servidor —que
     * ejecuta `SedeSeeder` por su clase— y el desarrollo podrían sembrar cosas
     * distintas, que es justo el defecto que dejó el menú sin sembrar:
     * `MenuSeeder` existía desde el principio y no lo llamaba nadie, así que
     * `migrate:fresh --seed` dejaba `menus: 0`.
     *
     * **Y ya no crea el usuario de prueba.** El esqueleto de Laravel traía
     * `test@example.com` con una contraseña conocida, y este repositorio es
     * público: una credencial de ejemplo no pinta nada en una base que se
     * siembra para una sede del Estado, y menos ahora que la siembra también
     * corre en el servidor. Se comprobó que nada la usaba —ni las pruebas, ni
     * los guiones, ni la documentación— antes de quitarla.
     */
    public function run(): void
    {
        $this->call(SedeSeeder::class);
    }
}
