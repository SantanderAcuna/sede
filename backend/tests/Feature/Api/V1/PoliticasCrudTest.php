<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\FileMedia;
use App\Models\Politica;
use App\Models\User;
use App\Support\Acceso\CatalogoPermisos;
use Database\Seeders\EntidadSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PoliticaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Crear, editar, publicar y eliminar políticas propias de la Entidad (además de las cinco obligatorias).
 *
 * Las cinco de base no se eliminan ni se ocultan. Una política nueva nace como borrador oculto y solo se
 * publica cuando está adoptada: texto definitivo, acto administrativo completo y sin marcadores.
 */
final class PoliticasCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, PoliticaSeeder::class]);
    }

    private function con(string ...$permisos): User
    {
        $u = User::factory()->create();
        $u->givePermissionTo($permisos);
        $this->actingAs($u);

        return $u;
    }

    /** @return array<string, mixed> */
    private function nueva(array $extra = []): array
    {
        return $extra + [
            'nombre' => 'Política de accesibilidad',
            'secciones' => [['id' => 'objeto', 'titulo' => 'Objeto', 'parrafos' => ['Texto del objeto.']]],
        ];
    }

    /** @return array<string, mixed> */
    private function adoptada(): array
    {
        return [
            'es_ejemplo' => false, 'vigente_desde' => '2026-11-01',
            'acto_tipo' => 'Resolución', 'acto_numero' => '123', 'acto_fecha' => '2026-10-30',
        ];
    }

    public function test_el_catalogo_tiene_las_cinco_acciones_de_politicas(): void
    {
        foreach (['ver', 'crear', 'editar', 'eliminar', 'publicar'] as $a) {
            $this->assertContains("politicas.{$a}", CatalogoPermisos::nombres());
            $this->assertDatabaseHas('permissions', ['name' => "politicas.{$a}"]);
        }
    }

    public function test_crear_exige_politicas_crear_y_ver(): void
    {
        $this->con('politicas.ver');
        $this->postJson('/api/v1/panel/politicas', $this->nueva())->assertForbidden();

        $this->con('politicas.ver', 'politicas.crear');
        $this->postJson('/api/v1/panel/politicas', $this->nueva())->assertCreated();
    }

    public function test_crear_genera_el_slug_nace_borrador_oculto_y_como_ejemplo(): void
    {
        $this->con('politicas.ver', 'politicas.crear');
        $r = $this->postJson('/api/v1/panel/politicas', $this->nueva())->assertCreated();

        $r->assertJsonPath('data.slug', 'politica-de-accesibilidad')
            ->assertJsonPath('data.publicada', false)
            ->assertJsonPath('data.es_ejemplo', true)
            ->assertJsonPath('data.es_base', false)
            ->assertJsonPath('data.version', 1);
        $this->assertDatabaseHas('politicas', ['slug' => 'politica-de-accesibilidad', 'publicada' => false]);
    }

    public function test_el_slug_no_choca_con_uno_existente(): void
    {
        $this->con('politicas.ver', 'politicas.crear');
        $this->postJson('/api/v1/panel/politicas', $this->nueva(['nombre' => 'Uso de cookies']))->assertCreated()
            ->assertJsonPath('data.slug', 'uso-de-cookies-2');
        $this->postJson('/api/v1/panel/politicas', $this->nueva(['nombre' => 'Uso de cookies']))->assertCreated()
            ->assertJsonPath('data.slug', 'uso-de-cookies-3');
    }

    public function test_ninguna_politica_se_llama_nueva_porque_es_la_ruta_del_formulario(): void
    {
        $this->con('politicas.ver', 'politicas.crear');
        $this->postJson('/api/v1/panel/politicas', $this->nueva(['nombre' => 'Nueva']))->assertCreated()
            ->assertJsonPath('data.slug', 'nueva-2');
    }

    public function test_exige_nombre_y_al_menos_un_apartado_con_texto(): void
    {
        $this->con('politicas.ver', 'politicas.crear');
        $this->postJson('/api/v1/panel/politicas', ['secciones' => []])->assertUnprocessable()->assertJsonValidationErrors(['nombre', 'secciones']);
        $this->postJson('/api/v1/panel/politicas', $this->nueva(['secciones' => [['id' => 'a', 'titulo' => 'A', 'parrafos' => []]]]))
            ->assertUnprocessable();
    }

    public function test_crear_sin_publicar_no_puede_nacer_publicada(): void
    {
        $this->con('politicas.ver', 'politicas.crear');
        $this->postJson('/api/v1/panel/politicas', $this->nueva(['publicada' => true]))->assertForbidden();
    }

    public function test_publicar_exige_el_permiso_y_que_este_adoptada(): void
    {
        $this->con('politicas.ver', 'politicas.crear', 'politicas.editar');
        $slug = $this->postJson('/api/v1/panel/politicas', $this->nueva())->json('data.slug');

        // Sin «publicar», aunque pueda editar.
        $this->patchJson("/api/v1/panel/politicas/{$slug}", ['publicada' => true])->assertForbidden();

        $this->con('politicas.ver', 'politicas.editar', 'politicas.publicar');
        // Con permiso pero todavía de ejemplo: no.
        $this->patchJson("/api/v1/panel/politicas/{$slug}", ['publicada' => true])->assertUnprocessable();
        // Adoptada: sí.
        $this->patchJson("/api/v1/panel/politicas/{$slug}", $this->adoptada() + ['publicada' => true])
            ->assertOk()->assertJsonPath('data.publicada', true);
    }

    public function test_una_publicada_que_vuelve_a_ser_ejemplo_no_se_guarda(): void
    {
        $this->con(...CatalogoPermisos::de('politicas'));
        $slug = $this->postJson('/api/v1/panel/politicas', $this->nueva())->json('data.slug');
        $this->patchJson("/api/v1/panel/politicas/{$slug}", $this->adoptada() + ['publicada' => true])->assertOk();
        $this->patchJson("/api/v1/panel/politicas/{$slug}", ['es_ejemplo' => true])->assertUnprocessable();
    }

    public function test_las_de_base_no_se_ocultan(): void
    {
        $this->con(...CatalogoPermisos::de('politicas'));
        $this->patchJson('/api/v1/panel/politicas/uso-de-cookies', ['publicada' => false])->assertUnprocessable();
        $this->getJson('/api/v1/panel/politicas')->assertJsonPath('data.0.es_base', true);
    }

    public function test_el_publico_solo_ve_las_publicadas(): void
    {
        $this->con(...CatalogoPermisos::de('politicas'));
        $oculta = $this->postJson('/api/v1/panel/politicas', $this->nueva())->json('data.slug');
        $visible = $this->postJson('/api/v1/panel/politicas', $this->nueva(['nombre' => 'Política visible']))->json('data.slug');
        $this->patchJson("/api/v1/panel/politicas/{$visible}", $this->adoptada() + ['publicada' => true])->assertOk();

        $this->app['auth']->forgetGuards();
        $slugs = collect($this->getJson('/api/v1/politicas')->assertOk()->json('data'))->pluck('slug');
        $this->assertTrue($slugs->contains($visible));
        $this->assertFalse($slugs->contains($oculta));
        $this->assertCount(6, $slugs);
        $this->getJson("/api/v1/politicas/{$oculta}")->assertNotFound();
        $this->getJson("/api/v1/politicas/{$visible}")->assertOk();
        $this->get("/politicas/{$oculta}.pdf")->assertNotFound();
        $this->get("/politicas/{$visible}.pdf")->assertOk();
    }

    public function test_el_panel_lista_las_de_base_primero_y_luego_las_nuevas(): void
    {
        $this->con(...CatalogoPermisos::de('politicas'));
        $this->postJson('/api/v1/panel/politicas', $this->nueva())->assertCreated();
        $slugs = collect($this->getJson('/api/v1/panel/politicas')->json('data'))->pluck('slug')->all();
        $this->assertSame(array_keys(Politica::COMPONENTES), array_slice($slugs, 0, 5));
        $this->assertSame('politica-de-accesibilidad', $slugs[5]);
    }

    public function test_eliminar_exige_el_permiso_y_borra_tambien_el_archivo_adjunto(): void
    {
        Storage::fake('local');
        $this->con(...CatalogoPermisos::de('politicas'));
        $slug = $this->postJson('/api/v1/panel/politicas', $this->nueva())->json('data.slug');
        $uuid = Politica::query()->where('slug', $slug)->value('uuid');
        Storage::disk('local')->put('politicas/acto.pdf', 'contenido');
        FileMedia::query()->create([
            'model_uuid' => $uuid, 'model_type' => Politica::class, 'collection' => Politica::COLECCION_DOCUMENTO,
            'filename' => 'acto.pdf', 'original_filename' => 'acto.pdf', 'mime_type' => 'application/pdf',
            'size' => 9, 'disk' => 'local', 'path' => 'politicas/acto.pdf',
        ]);
        $this->assertDatabaseHas('file_media', ['model_uuid' => $uuid]);

        $this->con('politicas.ver', 'politicas.editar');
        $this->deleteJson("/api/v1/panel/politicas/{$slug}")->assertForbidden();

        $this->con('politicas.ver', 'politicas.eliminar');
        $this->deleteJson("/api/v1/panel/politicas/{$slug}")->assertOk();
        $this->assertDatabaseMissing('politicas', ['slug' => $slug]);
        $this->assertDatabaseMissing('file_media', ['model_uuid' => $uuid]);
        Storage::disk('local')->assertMissing('politicas/acto.pdf');
        $this->deleteJson("/api/v1/panel/politicas/{$slug}")->assertNotFound();
    }

    public function test_las_cinco_obligatorias_no_se_eliminan(): void
    {
        $this->con(...CatalogoPermisos::de('politicas'));
        $this->deleteJson('/api/v1/panel/politicas/uso-de-cookies')->assertUnprocessable();
        $this->assertDatabaseHas('politicas', ['slug' => 'uso-de-cookies']);
    }

    public function test_la_busqueda_no_encuentra_las_ocultas(): void
    {
        $this->con(...CatalogoPermisos::de('politicas'));
        $this->postJson('/api/v1/panel/politicas', $this->nueva(['nombre' => 'Accesibilidad zeta']))->assertCreated();
        $visible = $this->postJson('/api/v1/panel/politicas', $this->nueva(['nombre' => 'Accesibilidad omega']))->json('data.slug');
        $this->patchJson("/api/v1/panel/politicas/{$visible}", $this->adoptada() + ['publicada' => true])->assertOk();
        $this->app['auth']->forgetGuards();

        $r = $this->getJson('/api/v1/buscar?q=accesibilidad')->assertOk();
        $r->assertSee('accesibilidad-omega', false)->assertDontSee('accesibilidad-zeta', false);
    }

    public function test_el_pie_incluye_las_publicadas_y_no_las_ocultas(): void
    {
        $this->seed(EntidadSeeder::class);
        $this->con(...CatalogoPermisos::de('politicas'));
        $this->postJson('/api/v1/panel/politicas', $this->nueva(['nombre' => 'Oculta uno']))->assertCreated();
        $v = $this->postJson('/api/v1/panel/politicas', $this->nueva(['nombre' => 'Visible dos']))->json('data.slug');
        $this->patchJson("/api/v1/panel/politicas/{$v}", $this->adoptada() + ['publicada' => true])->assertOk();
        $this->app['auth']->forgetGuards();

        $slugs = collect($this->getJson('/api/v1/identidad/footer')->assertOk()->json('data.politicas'))->pluck('slug');
        $this->assertTrue($slugs->contains('visible-dos'));
        $this->assertFalse($slugs->contains('oculta-uno'));
    }
}
