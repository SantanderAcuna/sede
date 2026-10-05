<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\Repositories\AuthRepositoryInterface;
use App\DTOs\Auth\LoginCredentials;
use App\Models\User;
use App\Services\AuthService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Unidad de AuthService.
 */
final class AuthServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_login_devuelve_cuenta_no_activa_cuando_can_login_retorna_false(): void
    {
        /** @var MockInterface&User $user */
        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasRole')->andReturn(false);

        /** @var MockInterface&AuthRepositoryInterface $mockAuth */
        $mockAuth = Mockery::mock(AuthRepositoryInterface::class);
        $mockAuth->shouldReceive('findByEmail')
            ->andReturn($user);
        $mockAuth->shouldReceive('verifyPassword')
            ->with($user, 'password')
            ->andReturn(true);
        $mockAuth->shouldReceive('canLogin')
            ->with($user)
            ->andReturn(false);

        $service = new AuthService($mockAuth);

        $credentials = new LoginCredentials(
            email: 'test@example.com',
            password: 'password'
        );

        $result = $service->login($credentials);

        $this->assertFalse($result['success']);
        $this->assertSame('La cuenta no está activa.', $result['message']);
    }
}
