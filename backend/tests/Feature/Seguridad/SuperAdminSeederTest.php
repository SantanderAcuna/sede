<?php

declare(strict_types=1);

namespace Tests\Feature\Seguridad;

use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

/**
 * La siembra del super-administrador (H-16, AC-14.2 y AC-14.3).
 *
 * Esta siembra corre en cada despliegue. Lo que la prueba defiende es que correrla
 * de nuevo no cambie la contraseña de una cuenta que ya existe, y que la clave de
 * desarrollo —publicada en el repositorio— no se use fuera de `local` y `testing`.
 */
final class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_sembrar_dos_veces_no_cambia_el_hash_de_la_contrasena(): void
    {
        config(['superadmin.password' => 'una-clave-larga-de-prueba-1']);
        $this->seed(SuperAdminSeeder::class);
        $hash = User::query()->where('email', config('superadmin.email'))->firstOrFail()->password;

        $this->seed(SuperAdminSeeder::class);

        $despues = User::query()->where('email', config('superadmin.email'))->firstOrFail()->password;
        $this->assertSame($hash, $despues);
        $this->assertSame(1, User::query()->where('email', config('superadmin.email'))->count());
    }

    public function test_una_contrasena_rotada_no_se_deshace_al_volver_a_sembrar(): void
    {
        config(['superadmin.password' => 'clave-inicial-de-prueba-1']);
        $this->seed(SuperAdminSeeder::class);

        $usuario = User::query()->where('email', config('superadmin.email'))->firstOrFail();
        $usuario->forceFill(['password' => 'clave-rotada-por-la-persona-2'])->save();

        // El despliegue siguiente trae el valor de entorno de siempre.
        $this->seed(SuperAdminSeeder::class);

        $usuario->refresh();
        $this->assertTrue(Hash::check('clave-rotada-por-la-persona-2', $usuario->password));
        $this->assertFalse(Hash::check('clave-inicial-de-prueba-1', $usuario->password));
    }

    public function test_la_clave_de_desarrollo_se_rechaza_fuera_de_local_y_testing(): void
    {
        config(['superadmin.password' => null]);

        foreach (['production', 'staging'] as $entorno) {
            $this->app['env'] = $entorno;

            try {
                $this->seed(SuperAdminSeeder::class);
                $this->fail("Debía negarse a sembrar con la clave de desarrollo en '{$entorno}'.");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('SUPER_ADMIN_PASSWORD', $e->getMessage());
            }
        }

        $this->assertSame(0, User::query()->where('email', config('superadmin.email'))->count());
    }

    public function test_en_testing_la_clave_de_desarrollo_si_se_acepta(): void
    {
        config(['superadmin.password' => null]);

        $this->seed(SuperAdminSeeder::class);

        $this->assertSame(1, User::query()->where('email', config('superadmin.email'))->count());
    }
}
