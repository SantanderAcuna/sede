<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Support\Acceso\CatalogoPermisos;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El panel manda las fechas con la zona de quien edita («…T10:00:00-05:00»). El servidor
 * guarda en UTC: «10:00 en Bogotá» tiene que quedar como 15:00 UTC.
 *
 * Verificado en staging: el servidor guardaba 10:00 UTC (descartaba el desfase), así que
 * una noticia programada para las 10:00 de Santa Marta salía a las 05:00 y se mostraba
 * a la persona con 5 horas de diferencia.
 */
final class FechasConZonaHorariaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    private function gestor(string ...$modulos): User
    {
        $u = User::factory()->createOne();
        foreach ($modulos as $m) {
            $u->givePermissionTo(CatalogoPermisos::de($m));
        }

        return $u;
    }

    public function test_la_noticia_guarda_la_fecha_en_utc_respetando_el_desfase(): void
    {
        $uuid = '5b0d2c1e-6c53-4f4e-9a41-0d3a6f8f1a11';
        DB::table('noticias')->insert([
            'uuid' => $uuid, 'slug' => 'zona', 'titulo' => 'Zona', 'descripcion_corta' => 'x', 'contenido' => 'x',
            'fecha_publicacion' => '2026-01-01 00:00:00', 'destacada' => false, 'activo' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $r = $this->actingAs($this->gestor('noticias'))
            ->patchJson("/api/v1/panel/noticias/{$uuid}", ['fecha_publicacion' => '2026-11-19T10:00:00-05:00'])
            ->assertOk();

        $this->assertSame('2026-11-19T15:00:00+00:00', $r->json('data.fecha_publicacion'));
        $this->assertSame('2026-11-19T15:00:00+00:00', Carbon::parse((string) DB::table('noticias')->where('uuid', $uuid)->value('fecha_publicacion'), 'UTC')->utc()->toIso8601String());
    }

    public function test_una_fecha_ya_en_utc_no_cambia(): void
    {
        $uuid = '5b0d2c1e-6c53-4f4e-9a41-0d3a6f8f1a12';
        DB::table('noticias')->insert([
            'uuid' => $uuid, 'slug' => 'utc', 'titulo' => 'Utc', 'descripcion_corta' => 'x', 'contenido' => 'x',
            'fecha_publicacion' => '2026-01-01 00:00:00', 'destacada' => false, 'activo' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $r = $this->actingAs($this->gestor('noticias'))
            ->patchJson("/api/v1/panel/noticias/{$uuid}", ['fecha_publicacion' => '2026-11-19T10:00:00+00:00'])
            ->assertOk();

        $this->assertSame('2026-11-19T10:00:00+00:00', $r->json('data.fecha_publicacion'));
    }

    public function test_el_carrusel_guarda_la_vigencia_en_utc_respetando_el_desfase(): void
    {
        $gestor = $this->gestor('banners');
        $uuid = $this->actingAs($gestor)->postJson('/api/v1/panel/banners', [
            'titulo' => 'Vigencia',
            'vigente_desde' => '2026-11-19T10:00:00-05:00',
            'vigente_hasta' => '2026-11-20T10:00:00-05:00',
        ])->assertCreated()->json('data.uuid');

        $fila = DB::table('banners')->where('uuid', $uuid)->first();
        $this->assertNotNull($fila);
        $this->assertSame('2026-11-19T15:00:00+00:00', Carbon::parse($fila->vigente_desde, 'UTC')->utc()->toIso8601String());
        $this->assertSame('2026-11-20T15:00:00+00:00', Carbon::parse($fila->vigente_hasta, 'UTC')->utc()->toIso8601String());
    }
}
