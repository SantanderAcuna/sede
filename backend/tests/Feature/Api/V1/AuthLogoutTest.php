<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Contracts\Services\AuthServiceInterface;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * logout() del AuthController.
 *
 * El test de integración no funciona porque actingAs() con guard 'sanctum'
 * crea un token que currentAccessToken() no puede recuperar — límite del marco
 * de pruebas, no del código. Este test usa mock del servicio para cubrir
 * la línea del controlador.
 */
final class AuthLogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            SuperAdminSeeder::class,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_logout_llama_al_servicio_y_devuelve_204(): void
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(AuthServiceInterface::class);
        $mockService->shouldReceive('logout')->once();

        $this->app->instance(AuthServiceInterface::class, $mockService);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/panel/logout');

        $response->assertNoContent(204);
    }
}
