<?php

declare(strict_types=1);

namespace Tests\Feature\Datos;

use App\Models\Entidad;
use App\Models\Menu;
use App\Models\Tramite;
use App\Models\User;
use Database\Seeders\SedeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La siembra institucional: lo que el servidor ejecuta en cada despliegue.
 *
 * Estas pruebas existen por un defecto concreto y medido: el despliegue migraba
 * y **no sembraba**, así que la base de pruebas quedó con `tramites: 0`, la API
 * devolvía `total: 0` y cada ficha respondía 404. Y de paso destaparon otro:
 * `MenuSeeder` no lo llamaba nadie, así que ni en desarrollo existía el menú.
 *
 * Lo que se protege aquí no es «que siembre», sino **que pueda volver a
 * ejecutarse**: corre en cada despliegue, así que si algún sembrador deja de ser
 * idempotente, el menú o el catálogo se duplican en silencio.
 */
final class SedeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_siembra_los_datos_institucionales(): void
    {
        $this->seed(SedeSeeder::class);

        $this->assertSame(1, Entidad::query()->count(), 'Falta la entidad titular.');
        $this->assertGreaterThan(0, Tramite::query()->count(), 'El catálogo quedó vacío.');
        $this->assertGreaterThan(0, User::query()->count(), 'Sin super-admin no hay quién administre.');
    }

    /**
     * El menú, que era el sembrador huérfano.
     *
     * `MenuSeeder` existía desde el principio y no lo llamaba nadie: la única
     * aparición de su nombre en todo el backend era su propia declaración de
     * clase. Se midió tras `migrate:fresh --seed`: `menus: 0`. Y el menú se
     * publica por `GET /api/v1/identidad/menu`, así que ese endpoint devolvía
     * nada —incluido el ítem «Trámites y servicios»—.
     */
    public function test_siembra_el_menu_de_navegacion(): void
    {
        $this->seed(SedeSeeder::class);

        // Cuatro del sitio público y cuatro del panel.
        $this->assertSame(8, Menu::query()->count(), 'El menú no se sembró completo.');

        $tramites = Menu::query()->where('slug', 'tramites')->first();

        $this->assertNotNull($tramites, 'Falta el ítem «Trámites y servicios».');
        $this->assertSame('Trámites y servicios', $tramites->etiqueta);
        $this->assertSame('/tramites', $tramites->ruta);
    }

    /**
     * La siembra corre en **cada** despliegue: si no fuera idempotente, el
     * catálogo y el menú crecerían con cada uno.
     *
     * `TramiteSeeder` ya respetaba lo corregido a mano; `MenuSeeder` no, y
     * creaba filas a ciegas. Esta prueba es la que habría atrapado las dos
     * cosas: compara las cuentas de la primera siembra con las de la segunda.
     */
    public function test_sembrar_dos_veces_deja_las_mismas_filas(): void
    {
        $this->seed(SedeSeeder::class);

        $cuentas = [
            'entidad' => Entidad::query()->count(),
            'tramites' => Tramite::query()->count(),
            'menus' => Menu::query()->count(),
            'usuarios' => User::query()->count(),
        ];

        $this->seed(SedeSeeder::class);

        $this->assertSame($cuentas['entidad'], Entidad::query()->count(), 'La entidad se duplicó.');
        $this->assertSame($cuentas['tramites'], Tramite::query()->count(), 'El catálogo se duplicó.');
        $this->assertSame($cuentas['menus'], Menu::query()->count(), 'El menú se duplicó.');
        $this->assertSame($cuentas['usuarios'], User::query()->count(), 'Los usuarios se duplicaron.');
    }

    /**
     * Y no siembra la credencial de ejemplo del esqueleto de Laravel.
     *
     * `test@example.com` viajaba en `DatabaseSeeder` y este repositorio es
     * público. Ahora que la siembra también corre en el servidor, esa cuenta
     * habría acabado en una base del Estado con su contraseña conocida.
     */
    public function test_no_siembra_el_usuario_de_prueba(): void
    {
        $this->seed(SedeSeeder::class);

        $this->assertSame(
            0,
            User::query()->where('email', 'test@example.com')->count(),
            'Se sembró el usuario de prueba del esqueleto de Laravel.',
        );
    }
}
