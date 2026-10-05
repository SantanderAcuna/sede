<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\Repositories\AuthRepositoryInterface;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests adicionales para coverage de AuthService.
 *
 * @covers \App\Services\AuthService
 */
final class AuthServiceLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_elimina_token_cuando_usuario_autenticado(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $service = new AuthService($this->app->make(AuthRepositoryInterface::class));
        $service->logout();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_no_hace_nada_cuando_no_hay_usuario(): void
    {
        $service = new AuthService($this->app->make(AuthRepositoryInterface::class));

        $this->expectNotToPerformAssertions();
        $service->logout();
    }
}
