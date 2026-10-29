<?php

declare(strict_types=1);

namespace Tests\Feature\Seguridad;

use App\Models\AuditLog;
use App\Services\AuthService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Endurecimiento del inicio de sesión (H-14, T-13).
 *
 * - AC-13.1: un acceso fallido queda en `audit_log`, con IP y sin la contraseña.
 * - AC-13.2: el correo inexistente cuesta lo mismo que el existente.
 * - AC-13.3: pasado el límite hay un 429 con `Retry-After`.
 */
final class LoginEndurecidoTest extends TestCase
{
    use RefreshDatabase;

    private const CLAVE_ERRADA = 'esta-clave-no-debe-quedar-en-ningun-lado';

    public function test_un_acceso_fallido_se_audita_con_la_ip_y_sin_la_contrasena(): void
    {
        $usuario = User::factory()->create(['email' => 'funcionaria@example.com']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->postJson('/api/v1/panel/login', [
                'email' => 'funcionaria@example.com',
                'password' => self::CLAVE_ERRADA,
            ])
            ->assertStatus(401);

        $registro = AuditLog::query()->where('accion', 'login_failed')->firstOrFail();

        $this->assertSame('203.0.113.9', $registro->ip_origen);
        $this->assertSame($usuario->id, $registro->causer_id);
        $cambios = $registro->cambios;
        $this->assertIsArray($cambios);
        $this->assertSame('funcionaria@example.com', $cambios['email']);
        $this->assertSame('contrasena_incorrecta', $cambios['motivo']);

        // Lo que más importa: ni la contraseña ni nada derivado en ninguna columna.
        $this->assertStringNotContainsString(
            self::CLAVE_ERRADA,
            json_encode($registro->getAttributes(), JSON_THROW_ON_ERROR)
        );
    }

    public function test_un_correo_inexistente_tambien_se_audita_sin_usuario(): void
    {
        $this->postJson('/api/v1/panel/login', [
            'email' => 'nadie@example.com',
            'password' => self::CLAVE_ERRADA,
        ])->assertStatus(401);

        $registro = AuditLog::query()->where('accion', 'login_failed')->firstOrFail();

        $this->assertNull($registro->causer_id);
        $cambios = $registro->cambios;
        $this->assertIsArray($cambios);
        $this->assertSame('usuario_inexistente', $cambios['motivo']);
        $this->assertStringNotContainsString(
            self::CLAVE_ERRADA,
            json_encode($registro->getAttributes(), JSON_THROW_ON_ERROR)
        );
    }

    public function test_una_cuenta_inactiva_con_la_clave_correcta_se_audita_con_su_motivo(): void
    {
        $usuario = User::factory()->create([
            'email' => 'baja@example.com',
            'password' => 'Clave.Correcta.2026',
            'estado' => 'inactivo',
        ]);

        $this->postJson('/api/v1/panel/login', [
            'email' => 'baja@example.com',
            'password' => 'Clave.Correcta.2026',
        ])->assertStatus(401);

        $registro = AuditLog::query()->where('accion', 'login_failed')->firstOrFail();
        $cambios = $registro->cambios;

        $this->assertIsArray($cambios);
        $this->assertSame('cuenta_inactiva', $cambios['motivo']);
        $this->assertSame($usuario->id, $registro->causer_id);
        // Ninguna sesión se abrió.
        $this->assertGuest('web');
    }

    public function test_el_correo_inexistente_comprueba_un_hash_igual_que_el_existente(): void
    {
        // Medir milisegundos en CI es una prueba inestable. Lo que se vigila es la
        // causa de la diferencia: que los dos caminos hagan UNA comprobación de hash.
        User::factory()->create(['email' => 'existe@example.com']);

        $comprobaciones = 0;
        Hash::partialMock()
            ->shouldReceive('check')
            ->andReturnUsing(function () use (&$comprobaciones): bool {
                $comprobaciones++;

                return false;
            });

        $this->postJson('/api/v1/panel/login', ['email' => 'existe@example.com', 'password' => 'x-incorrecta-1'])
            ->assertStatus(401);
        $conUsuario = $comprobaciones;

        $comprobaciones = 0;
        $this->postJson('/api/v1/panel/login', ['email' => 'inexistente@example.com', 'password' => 'x-incorrecta-1'])
            ->assertStatus(401);
        $sinUsuario = $comprobaciones;

        $this->assertSame(1, $conUsuario);
        $this->assertSame($conUsuario, $sinUsuario);
    }

    public function test_el_hash_de_relleno_es_bcrypt_con_el_coste_de_produccion(): void
    {
        // Si el coste del relleno baja del de las contraseñas reales, el correo
        // inexistente vuelve a responder más rápido y delata qué cuentas existen.
        $info = password_get_info(AuthService::HASH_DE_RELLENO);

        $this->assertSame('bcrypt', $info['algoName']);
        $this->assertSame(12, $info['options']['cost'] ?? null, 'Debe coincidir con BCRYPT_ROUNDS de producción (12).');
    }

    public function test_pasado_el_limite_hay_429_con_retry_after_y_el_sobre_de_la_api(): void
    {
        for ($intento = 0; $intento < 3; $intento++) {
            $this->postJson('/api/v1/panel/login', [
                'email' => 'limite@example.com',
                'password' => self::CLAVE_ERRADA,
            ])->assertStatus(401);
        }

        $respuesta = $this->postJson('/api/v1/panel/login', [
            'email' => 'limite@example.com',
            'password' => self::CLAVE_ERRADA,
        ]);

        $respuesta->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Demasiadas solicitudes.');

        $this->assertTrue($respuesta->headers->has('Retry-After'));
        $this->assertGreaterThan(0, (int) $respuesta->headers->get('Retry-After'));
    }
}
