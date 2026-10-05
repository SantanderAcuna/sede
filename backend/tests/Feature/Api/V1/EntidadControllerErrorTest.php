<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Contracts\Services\EntidadServiceInterface;
use App\Models\Entidad;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Casos de error del controlador de Entidad.
 *
 * Usa mock del servicio para forzar los paths de error en el controlador.
 */
final class EntidadControllerErrorTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $clave = 'Prueba.Entidad.2026';
        config(['superadmin.password' => $clave]);

        $this->seed([
            PermissionSeeder::class,
            SuperAdminSeeder::class,
        ]);

        $this->superAdmin = User::query()->where('email', config('superadmin.email'))->firstOrFail();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_mostrar_devuelve_404_cuando_servicio_lanza_runtime_exception(): void
    {
        $mock = Mockery::mock(EntidadServiceInterface::class);
        $mock->shouldReceive('obtenerEntidad')
            ->andThrow(new \RuntimeException('No se encontró la entidad.'));

        $this->app->instance(EntidadServiceInterface::class, $mock);

        $response = $this->getJson('/api/v1/entidad');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_mostrar_devuelve_500_cuando_servicio_lanza_excepcion_interna(): void
    {
        $mock = Mockery::mock(EntidadServiceInterface::class);
        $mock->shouldReceive('obtenerEntidad')
            ->andThrow(new \Exception('DB error'));

        $this->app->instance(EntidadServiceInterface::class, $mock);

        $response = $this->getJson('/api/v1/entidad');

        $response->assertStatus(500)
            ->assertJsonPath('success', false);
    }

    public function test_actualizar_devuelve_404_cuando_servicio_lanza_runtime_exception(): void
    {
        $mock = Mockery::mock(EntidadServiceInterface::class);
        $mock->shouldReceive('actualizar')
            ->andThrow(new \RuntimeException('No se encontró la entidad.'));

        $this->app->instance(EntidadServiceInterface::class, $mock);

        $response = $this->actingAs($this->superAdmin, 'web')
            ->patchJson('/api/v1/panel/entidad', ['telefono' => '+57 601 999 0000']);

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_actualizar_devuelve_500_cuando_servicio_lanza_excepcion_interna(): void
    {
        $mock = Mockery::mock(EntidadServiceInterface::class);
        $mock->shouldReceive('actualizar')
            ->andThrow(new \Exception('Error interno'));

        $this->app->instance(EntidadServiceInterface::class, $mock);

        $response = $this->actingAs($this->superAdmin, 'web')
            ->patchJson('/api/v1/panel/entidad', ['telefono' => '+57 601 999 0000']);

        $response->assertStatus(500)
            ->assertJsonPath('success', false);
    }
}
