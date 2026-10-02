<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
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
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Los datos institucionales de la entidad titular.
        // Alimentan la cabecera y el pie de página de la sede.
        $this->call(EntidadSeeder::class);

        // El catálogo de trámites de la Entidad. Va aquí y no en un comando
        // aparte porque `php artisan migrate:fresh --seed` —lo que ejecuta
        // `make preparar`— tiene que dejar la sede con el mismo catálogo que
        // sirve en producción: una base de desarrollo sin trámites esconde
        // justo el estado vacío que no se quiere volver a tener.
        $this->call(TramiteSeeder::class);

        // Y el super-admin. Sin él, una base recién sembrada no tiene quién
        // conceda permisos: es la cuenta que el `Gate::before` deja pasar
        // siempre, así que su ausencia deja el panel inaccesible.
        $this->call(SuperAdminSeeder::class);
    }
}
