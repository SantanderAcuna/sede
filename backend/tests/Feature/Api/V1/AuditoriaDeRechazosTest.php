<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Acceso\CatalogoPermisos;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PoliticaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un intento rechazado también deja constancia: quién lo intentó, sobre qué, y por qué tipo de rechazo.
 *
 * Se registran las escrituras rechazadas con 403 (sin permiso), 422 (datos inválidos o regla de negocio) y 409
 * (el contenido cambió). Se guardan los NOMBRES de los campos, nunca lo que la persona escribió.
 * Una lectura sin permiso no se registra: la interfaz puede sondear pantallas y llenaría la bitácora de ruido.
 */
final class AuditoriaDeRechazosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, PoliticaSeeder::class]);
    }

    private function con(string ...$modulos): User
    {
        $u = User::factory()->createOne();
        foreach ($modulos as $m) {
            $u->givePermissionTo(CatalogoPermisos::de($m));
        }
        $this->actingAs($u);

        return $u;
    }

    /** @return array<string, mixed> */
    private function unica(string $accion): array
    {
        $filas = AuditLog::query()->where('accion', $accion)->get();
        $this->assertCount(1, $filas, "Debía haber exactamente una huella «{$accion}».");
        /** @var AuditLog $fila */
        $fila = $filas->firstOrFail();

        return ['fila' => $fila, 'cambios' => (array) $fila->cambios];
    }

    public function test_una_escritura_sin_permiso_deja_huella_de_acceso_denegado(): void
    {
        $u = $this->con();

        $this->postJson('/api/v1/panel/noticias', ['titulo' => 'X', 'contenido' => 'y'])->assertForbidden();

        ['fila' => $fila, 'cambios' => $c] = $this->unica('acceso_denegado');
        $this->assertSame($u->id, (int) $fila->causer_id);
        $this->assertSame('noticias', $fila->recurso);
        $this->assertSame('POST', $c['metodo']);
        $this->assertSame('api/v1/panel/noticias', $c['ruta']);
        $this->assertSame(403, $c['estado']);
    }

    public function test_una_lectura_sin_permiso_no_deja_huella(): void
    {
        $this->con();
        $this->getJson('/api/v1/panel/noticias')->assertForbidden();

        $this->assertSame(0, AuditLog::query()->where('accion', 'acceso_denegado')->count());
    }

    public function test_sin_sesion_no_deja_huella(): void
    {
        $antes = AuditLog::query()->count(); // los seeders ya dejan filas propias (permission_attached)

        $this->postJson('/api/v1/panel/noticias', ['titulo' => 'X'])->assertUnauthorized();

        $this->assertSame($antes, AuditLog::query()->count());
    }

    public function test_datos_invalidos_dejan_huella_con_los_nombres_de_campo_y_sin_los_valores(): void
    {
        $this->con('tramites');

        $this->postJson('/api/v1/panel/tramites', ['nombre' => '', 'resumen' => 'dato-sensible-9876'])->assertUnprocessable();

        ['fila' => $fila, 'cambios' => $c] = $this->unica('datos_rechazados');
        $this->assertSame('tramites', $fila->recurso);
        $this->assertContains('nombre', $c['campos']);
        $this->assertSame(422, $c['estado']);
        $this->assertStringNotContainsString('dato-sensible-9876', (string) json_encode($fila->cambios));
    }

    public function test_una_regla_de_negocio_rechazada_dice_sobre_que_registro(): void
    {
        $this->con('politicas');

        $this->deleteJson('/api/v1/panel/politicas/terminos-y-condiciones-de-uso')->assertUnprocessable();

        ['cambios' => $c] = $this->unica('datos_rechazados');
        $this->assertSame('DELETE', $c['metodo']);
        $this->assertSame('terminos-y-condiciones-de-uso', $c['registro']);
    }

    public function test_un_conflicto_409_deja_huella(): void
    {
        $gestor = $this->con('banners');
        $a = (string) $this->postJson('/api/v1/panel/banners', ['titulo' => 'A'])->assertCreated()->json('data.uuid');
        $b = (string) $this->postJson('/api/v1/panel/banners', ['titulo' => 'B'])->assertCreated()->json('data.uuid');
        $this->putJson('/api/v1/panel/banners/orden', ['uuids' => [$b, $a], 'antes' => [$a, $b]])->assertOk();

        $this->putJson('/api/v1/panel/banners/orden', ['uuids' => [$a, $b], 'antes' => [$a, $b]])->assertStatus(409);

        ['fila' => $fila, 'cambios' => $c] = $this->unica('conflicto_rechazado');
        $this->assertSame($gestor->id, (int) $fila->causer_id);
        $this->assertSame('banners', $fila->recurso);
        $this->assertSame(409, $c['estado']);
    }

    public function test_lo_que_sale_bien_no_deja_huella_de_rechazo(): void
    {
        $this->con('banners');
        $this->postJson('/api/v1/panel/banners', ['titulo' => 'A'])->assertCreated();

        foreach (['acceso_denegado', 'datos_rechazados', 'conflicto_rechazado'] as $accion) {
            $this->assertSame(0, AuditLog::query()->where('accion', $accion)->count());
        }
    }

    /** Si la bitácora falla, la persona recibe igual su 403: la constancia no puede cambiar la respuesta. */
    public function test_si_la_huella_falla_la_respuesta_no_cambia(): void
    {
        $this->con();
        AuditLog::creating(static function (): void {
            throw new \RuntimeException('bitácora caída');
        });

        $this->postJson('/api/v1/panel/noticias', ['titulo' => 'X', 'contenido' => 'y'])->assertForbidden();
    }
}
