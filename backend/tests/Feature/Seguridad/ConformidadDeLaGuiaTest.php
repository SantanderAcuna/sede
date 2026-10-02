<?php

declare(strict_types=1);

namespace Tests\Feature\Seguridad;

use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Las reglas de la guía maestra que no se ven en una respuesta normal.
 *
 * El super-admin, los límites de peticiones, el CORS y las cabeceras de
 * seguridad no fallan «a la primera»: el sistema sigue respondiendo bien cuando
 * alguno se rompe, sólo que sin protección. Estas pruebas son lo que convierte
 * esa ausencia silenciosa en un fallo ruidoso.
 *
 * Cada una comprueba el comportamiento, no la configuración: que la clave se
 * cifre de verdad, que el `Gate::before` deje pasar y no deniegue, que el origen
 * ajeno no reciba permiso y que la ruta lleve su limitador aplicado.
 */
final class ConformidadDeLaGuiaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: string}
     */
    private function sembrarSuperAdmin(): array
    {
        $clave = 'ClaveDePrueba.2026';
        config(['superadmin.password' => $clave]);

        $this->seed(SuperAdminSeeder::class);

        $usuario = User::query()->where('email', config('superadmin.email'))->firstOrFail();

        return [$usuario, $clave];
    }

    public function test_el_sembrador_crea_al_super_admin_con_la_clave_cifrada(): void
    {
        [$usuario, $clave] = $this->sembrarSuperAdmin();

        $this->assertNotSame(
            $clave,
            $usuario->password,
            'La clave quedó en texto plano: el cast `hashed` de `User` no se aplicó.'
        );

        $this->assertTrue(Hash::check($clave, $usuario->password));
        $this->assertTrue($usuario->hasRole('super-admin'));
    }

    public function test_volver_a_sembrar_no_duplica_la_cuenta_ni_el_rol_ni_la_relacion(): void
    {
        [$usuario] = $this->sembrarSuperAdmin();

        $this->seed(SuperAdminSeeder::class);

        $this->assertSame(1, User::query()->where('email', $usuario->email)->count());
        $this->assertSame(1, DB::table('roles')->where('name', 'super-admin')->count());
        $this->assertSame(1, DB::table('model_has_roles')->count());
    }

    public function test_el_super_admin_pasa_por_encima_de_un_permiso_negado(): void
    {
        [$superAdmin] = $this->sembrarSuperAdmin();

        Gate::define('capacidad-que-nadie-tiene', static fn (User $usuario): bool => false);

        $this->assertTrue($superAdmin->can('capacidad-que-nadie-tiene'));
    }

    public function test_el_gate_before_no_deniega_a_quien_no_es_super_admin(): void
    {
        $usuario = User::factory()->create();

        // La trampa clásica: un `Gate::before` que devuelve `false` en lugar de
        // `null` deniega *todo* a *todos*, porque el `before` corre antes que la
        // capacidad. Esta puerta concede, y debe seguir concediendo.
        Gate::define('capacidad-concedida', static fn (User $usuario): bool => true);

        $this->assertTrue($usuario->can('capacidad-concedida'));
    }

    public function test_las_cabeceras_de_seguridad_viajan_en_la_respuesta_de_la_api(): void
    {
        $respuesta = $this->getJson('/api/v1/tramites');

        $respuesta->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy', "default-src 'self'");
    }

    public function test_las_cabeceras_de_seguridad_tambien_viajan_en_las_sondas(): void
    {
        // Las sondas quedan fuera de la API versionada; si el middleware se
        // registrara sólo en el grupo de la API, estas rutas se quedarían sin
        // cabeceras y nadie lo notaría.
        $this->getJson('/health')->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_el_cors_autoriza_al_origen_declarado_y_a_ningun_otro(): void
    {
        $permitido = (string) config('cors.allowed_origins.0');
        $ajeno = 'https://origen-no-autorizado.example';

        $this->withHeaders(['Origin' => $permitido])
            ->getJson('/api/v1/tramites')
            ->assertHeader('Access-Control-Allow-Origin', $permitido)
            ->assertHeader('Access-Control-Allow-Credentials', 'true');

        // Con un solo origen declarado, `fruitcake/php-cors` responde siempre con
        // ese origen —la comparación final la hace el navegador—, pero nunca con
        // el origen que pidió. Si aquí apareciera el ajeno, cualquier página
        // podría leer la API con la sesión del ciudadano.
        $respuesta = $this->withHeaders(['Origin' => $ajeno])->getJson('/api/v1/tramites');

        $this->assertNotSame($ajeno, $respuesta->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_la_api_corta_al_pasar_el_limite_de_peticiones(): void
    {
        // Un limitador registrado y no aplicado no limita nada, así que la prueba
        // no mira la configuración: agota el cupo de sesenta y espera el corte.
        for ($intento = 0; $intento < 60; $intento++) {
            $this->getJson('/api/v1/tramites')->assertOk();
        }

        $respuesta = $this->getJson('/api/v1/tramites');

        $respuesta->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Demasiadas solicitudes.')
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors', null);

        // `Retry-After` es lo que el contrato expone en CORS para que el cliente
        // sepa cuándo volver. El render propio del 429 lo conserva.
        $this->assertTrue($respuesta->headers->has('Retry-After'));
    }

    public function test_los_limitadores_de_login_y_api_estan_registrados(): void
    {
        $peticion = Request::create('/api/v1/tramites', 'GET', ['email' => 'alguien@example.com']);

        $login = RateLimiter::limiter('login');

        if ($login === null) {
            $this->fail('No hay limitador `login` registrado.');
        }

        $cupos = $login($peticion);

        if (! is_array($cupos)) {
            $this->fail('El limitador `login` debe devolver dos cupos: uno por correo y otro por IP.');
        }

        $this->assertCount(2, $cupos);
        $this->assertContainsOnlyInstancesOf(Limit::class, $cupos);

        $api = RateLimiter::limiter('api');

        if ($api === null) {
            $this->fail('No hay limitador `api` registrado.');
        }

        $this->assertInstanceOf(Limit::class, $api($peticion));
    }
}
