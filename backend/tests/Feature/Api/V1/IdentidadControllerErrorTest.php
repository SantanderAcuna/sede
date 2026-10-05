<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Contracts\Services\IdentidadServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Paths de error en IdentidadController (catch \Throwable).
 *
 * El menú puede lanzar \Throwable cuando el repositorio falla; cubrimos ese path
 * con un mock del servicio.
 */
final class IdentidadControllerErrorTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_menu_devuelve_500_cuando_servicio_lanza_excepcion(): void
    {
        $mock = Mockery::mock(IdentidadServiceInterface::class);
        $mock->shouldReceive('obtenerMenu')
            ->andThrow(new \Exception('Error de base de datos'));

        $this->app->instance(IdentidadServiceInterface::class, $mock);

        $response = $this->getJson('/api/v1/identidad/menu');

        $response->assertStatus(500)
            ->assertJsonPath('success', false);
    }

    public function test_top_bar_devuelve_500_cuando_servicio_lanza_excepcion_interna(): void
    {
        $mock = Mockery::mock(IdentidadServiceInterface::class);
        $mock->shouldReceive('obtenerTopBar')
            ->andThrow(new \Exception('Error interno'));

        $this->app->instance(IdentidadServiceInterface::class, $mock);

        $response = $this->getJson('/api/v1/identidad/top-bar');

        $response->assertStatus(500)
            ->assertJsonPath('success', false);
    }

    public function test_footer_devuelve_500_cuando_servicio_lanza_excepcion_interna(): void
    {
        $mock = Mockery::mock(IdentidadServiceInterface::class);
        $mock->shouldReceive('obtenerFooter')
            ->andThrow(new \Exception('Error interno'));

        $this->app->instance(IdentidadServiceInterface::class, $mock);

        $response = $this->getJson('/api/v1/identidad/footer');

        $response->assertStatus(500)
            ->assertJsonPath('success', false);
    }
}
