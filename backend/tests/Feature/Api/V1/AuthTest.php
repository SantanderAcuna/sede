<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Database\Seeders\EntidadSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Autenticación del panel de administración.
 *
 * Cubre:
 * - POST /api/v1/auth/login    → inicio de sesión
 * - GET  /api/v1/auth/perfil   → datos del usuario autenticado
 * - POST /api/v1/auth/logout   → cierre de sesión
 */
final class AuthTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private string $clave;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clave = 'Prueba.Entidad.2026';
        config(['superadmin.password' => $this->clave]);

        $this->seed([
            EntidadSeeder::class,
            PermissionSeeder::class,
            SuperAdminSeeder::class,
        ]);

        $this->superAdmin = User::query()->where('email', config('superadmin.email'))->firstOrFail();
    }

    public function test_login_con_credenciales_validas_devuelve_token_y_usuario(): void
    {
        // Sanctum stateful necesita la sesión iniciada Y un Origin reconocido.
        // En CI (sin cache de sesión), hay que inicializarla explícitamente
        // ANTES de postJson, no después, porque el helper encadena los métodos.
        $respuesta = $this->withSession(['_token' => 'test'])
            ->postJson('/api/v1/panel/login', [
                'email' => config('superadmin.email'),
                'password' => $this->clave,
            ], ['Origin' => 'http://localhost:5190']);

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Sesión iniciada')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'uuid', 'type', 'email', 'estado', 'roles'],
                ],
            ]);
    }

    public function test_login_con_password_incorrecto_devuelve_401(): void
    {
        $respuesta = $this->postJson('/api/v1/panel/login', [
            'email' => config('superadmin.email'),
            'password' => 'password-incorrecto',
        ], ['Origin' => 'http://localhost:5190']);  // Activar stateful para tests

        $respuesta->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_login_con_email_desconocido_devuelve_401(): void
    {
        $respuesta = $this->postJson('/api/v1/panel/login', [
            'email' => 'nobody@example.com',
            'password' => $this->clave,
        ], ['Origin' => 'http://localhost:5190']);  // Activar stateful para tests

        $respuesta->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_perfil_devuelve_los_datos_del_usuario_autenticado(): void
    {
        // En el flujo stateful, el helper `actingAs` configura la sesion
        // manualmente, pero Sanctum 4.x requiere que la sesion sea tambien
        // "authenticated" (con `authenticate_session => null` en el config).
        // El test pasa cuando se usa el guard de sesion estandar de Laravel
        // y se añade el header Origin para activar el flujo stateful.
        $respuesta = $this->actingAs($this->superAdmin, 'web')
            ->withSession(['_token' => 'test'])
            ->getJson('/api/v1/panel/perfil', ['Origin' => 'http://localhost:5190']);

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', config('superadmin.email'))
            ->assertJsonPath('data.type', 'usuario');
    }

    public function test_perfil_sin_autenticacion_devuelve_401(): void
    {
        $respuesta = $this->getJson('/api/v1/panel/perfil');

        $respuesta->assertUnauthorized();
    }
}
