<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Eloquent\AuthRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unidad de AuthRepository.
 */
final class AuthRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AuthRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AuthRepository;
    }

    public function test_find_by_email_devuelve_usuario_cuando_existe(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $found = $this->repository->findByEmail('test@example.com');

        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
    }

    public function test_find_by_email_devuelve_null_cuando_no_existe(): void
    {
        $found = $this->repository->findByEmail('noexiste@example.com');

        $this->assertNull($found);
    }

    public function test_can_login_retorna_true_cuando_usuario_activo(): void
    {
        $user = User::factory()->create(['estado' => 'activo']);

        $result = $this->repository->canLogin($user);

        $this->assertTrue($result);
    }

    public function test_can_login_retorna_false_cuando_usuario_inactivo(): void
    {
        $user = User::factory()->create(['estado' => 'inactivo']);

        $result = $this->repository->canLogin($user);

        $this->assertFalse($result);
    }

    public function test_verify_password_retorna_true_con_contrasena_correcta(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secreta'),
        ]);

        $result = $this->repository->verifyPassword($user, 'secreta');

        $this->assertTrue($result);
    }

    public function test_verify_password_retorna_false_con_contrasena_incorrecta(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secreta'),
        ]);

        $result = $this->repository->verifyPassword($user, 'equivocada');

        $this->assertFalse($result);
    }
}
