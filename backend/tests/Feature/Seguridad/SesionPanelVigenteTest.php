<?php

declare(strict_types=1);

namespace Tests\Feature\Seguridad;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Acceso\CatalogoPermisos;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Una sesión del panel deja de valer cuando la cuenta deja de estar activa o cambia su contraseña.
 *
 * Medido el 2026-11-24 contra la imagen de staging (a171a26), con sesiones reales: tras bloquear a un
 * administrador desde el panel, su sesión abierta siguió leyendo `/panel/noticias` y `/panel/usuarios`
 * (200); tras cambiarle la contraseña, igual. `UsuarioPanelService` solo borraba tokens de Sanctum, que el
 * panel no usa desde el 2026-10-07: la sesión es una cookie y nada la cortaba.
 *
 * Y al revés: los tokens de acceso personal que emitía el panel antiguo seguían autenticando por
 * `Authorization: Bearer` en las rutas del panel (`expiration` nula: no caducan). En staging, un token de
 * octubre guardado en el `localStorage` de un navegador leyó `/panel/usuarios` con 200 sin cookie.
 *
 * Se prueba con sesiones REALES (archivo de sesión y cookie, como entre dos peticiones del navegador), no
 * con `actingAs`: con `actingAs` la prueba anterior de «cerrar sesiones» pasaba sin cerrar ninguna.
 */
final class SesionPanelVigenteTest extends TestCase
{
    use RefreshDatabase;

    private const CLAVE = 'Clave-Vigente-2026x';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('s', 32)),
            'session.driver' => 'file',
            'session.files' => sys_get_temp_dir().'/sesiones-vigentes-'.Str::random(8),
        ]);
        @mkdir((string) config('session.files'), 0777, true);
        $this->seed(PermissionSeeder::class);
    }

    private function editor(): User
    {
        $u = User::factory()->createOne(['estado' => 'activo', 'password' => bcrypt(self::CLAVE)]);
        $u->givePermissionTo(CatalogoPermisos::de('noticias', 'usuarios'));

        return $u;
    }

    /** @return array<string, string> */
    private function cabeceras(): array
    {
        return ['Referer' => 'http://'.((array) config('sanctum.stateful'))[0].'/', 'Accept' => 'application/json'];
    }

    /** Como entre dos peticiones reales: de la sesión solo queda lo que está en el archivo. */
    private function olvidarSesionEnMemoria(): void
    {
        $this->app['auth']->forgetGuards();
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
    }

    /** @return array{0: string, 1: string} nombre y valor de la cookie de sesión */
    private function iniciarSesion(User $u): array
    {
        $r = $this->withHeaders($this->cabeceras())
            ->post('/api/v1/panel/login', ['email' => $u->email, 'password' => self::CLAVE])
            ->assertOk();
        foreach ($r->headers->getCookies() as $c) {
            if ($c->getName() === config('session.cookie')) {
                $this->olvidarSesionEnMemoria();

                return [$c->getName(), (string) $c->getValue()];
            }
        }
        $this->fail('El inicio de sesión no entregó la cookie de sesión.');
    }

    /**
     * Código de estado de una petición con la cookie de sesión, como la haría el navegador.
     *
     * @param  array{0: string, 1: string}  $cookie
     */
    private function pedir(array $cookie, string $ruta): int
    {
        $estado = $this->withHeaders($this->cabeceras())->withUnencryptedCookie($cookie[0], $cookie[1])->get($ruta)->getStatusCode();
        $this->olvidarSesionEnMemoria();

        return $estado;
    }

    /** @return array<string, array{0: string}> */
    public static function estadosNoActivos(): array
    {
        return ['bloqueado' => ['bloqueado'], 'inactivo' => ['inactivo'], 'pendiente' => ['pendiente']];
    }

    #[DataProvider('estadosNoActivos')]
    public function test_una_sesion_abierta_termina_cuando_la_cuenta_deja_de_estar_activa(string $estado): void
    {
        $u = $this->editor();
        $cookie = $this->iniciarSesion($u);
        $this->assertSame(200, $this->pedir($cookie, '/api/v1/panel/noticias'));

        User::query()->whereKey($u->getKey())->update(['estado' => $estado]);

        $this->assertSame(401, $this->pedir($cookie, '/api/v1/panel/noticias'));
        $this->assertSame(401, $this->pedir($cookie, '/api/v1/panel/usuarios'));

        $huella = AuditLog::query()->where('accion', 'sesion_terminada')->first();
        $this->assertNotNull($huella, 'El cierre forzado de la sesión debe quedar en la bitácora.');
        $this->assertSame($u->id, $huella->causer_id);
        $this->assertSame('cuenta_no_activa', $huella->cambios['motivo'] ?? null);
    }

    public function test_la_sesion_cortada_no_revive_al_reactivar_la_cuenta(): void
    {
        $u = $this->editor();
        $cookie = $this->iniciarSesion($u);

        User::query()->whereKey($u->getKey())->update(['estado' => 'bloqueado']);
        $this->assertSame(401, $this->pedir($cookie, '/api/v1/panel/noticias'));

        User::query()->whereKey($u->getKey())->update(['estado' => 'activo']);
        $this->assertSame(401, $this->pedir($cookie, '/api/v1/panel/noticias'));
    }

    public function test_una_sesion_abierta_termina_cuando_cambia_la_contrasena(): void
    {
        $u = $this->editor();
        $cookie = $this->iniciarSesion($u);
        $this->assertSame(200, $this->pedir($cookie, '/api/v1/panel/noticias'));

        User::query()->whereKey($u->getKey())->update(['password' => bcrypt('Otra-Clave-Nueva-2026x')]);

        $this->assertSame(401, $this->pedir($cookie, '/api/v1/panel/noticias'));
        $huella = AuditLog::query()->where('accion', 'sesion_terminada')->first();
        $this->assertNotNull($huella);
        $this->assertSame('clave_cambiada', $huella->cambios['motivo'] ?? null);
    }

    public function test_bloquear_desde_el_panel_corta_la_sesion_abierta_de_esa_cuenta(): void
    {
        $objetivo = $this->editor();
        $cookie = $this->iniciarSesion($objetivo);
        $this->assertSame(200, $this->pedir($cookie, '/api/v1/panel/noticias'));

        $admin = User::factory()->createOne(['estado' => 'activo']);
        $admin->givePermissionTo(CatalogoPermisos::de('usuarios', 'roles'));
        $this->actingAs($admin)->patchJson("/api/v1/panel/usuarios/{$objetivo->uuid}", ['estado' => 'bloqueado'])->assertOk();
        $this->olvidarSesionEnMemoria();

        $this->assertSame(401, $this->pedir($cookie, '/api/v1/panel/noticias'));
    }

    public function test_cambios_que_no_tocan_estado_ni_contrasena_no_cortan_la_sesion(): void
    {
        $u = $this->editor();
        $cookie = $this->iniciarSesion($u);

        User::query()->whereKey($u->getKey())->update(['name' => 'Otro nombre']);

        $this->assertSame(200, $this->pedir($cookie, '/api/v1/panel/noticias'));
        $this->assertSame(200, $this->pedir($cookie, '/api/v1/panel/perfil'));
        $this->assertSame(0, AuditLog::query()->where('accion', 'sesion_terminada')->count());
    }

    public function test_una_sesion_anterior_al_cambio_sigue_valiendo_mientras_nada_cambie(): void
    {
        // Las sesiones abiertas antes de desplegar esto no llevan la huella de la contraseña: siguen
        // valiendo (la cuenta está activa) y desde esa petición quedan vigiladas.
        $u = $this->editor();
        $this->actingAs($u)->getJson('/api/v1/panel/noticias')->assertOk();
    }

    public function test_un_token_de_acceso_personal_ya_no_autentica_en_el_panel(): void
    {
        $u = $this->editor();
        $plano = Str::random(40);
        $id = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => User::class,
            'tokenable_id' => $u->getKey(),
            'name' => 'panel',
            'token' => hash('sha256', $plano),
            'abilities' => '["*"]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeaders(['Accept' => 'application/json', 'Authorization' => "Bearer {$id}|{$plano}"])
            ->get('/api/v1/panel/usuarios')
            ->assertUnauthorized();
        $this->withHeaders(['Accept' => 'application/json', 'Authorization' => "Bearer {$id}|{$plano}"])
            ->get('/api/v1/panel/noticias')
            ->assertUnauthorized();
    }
}
