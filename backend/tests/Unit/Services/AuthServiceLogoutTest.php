<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de logout para el flujo cookie-based de AuthService.
 *
 * @covers \App\Services\AuthService
 */
final class AuthServiceLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_devuelve_401_cuando_no_hay_sesion(): void
    {
        $respuesta = $this->postJson('/api/v1/panel/logout');

        $respuesta->assertStatus(401);
    }
}
