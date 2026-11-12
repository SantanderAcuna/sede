<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * El cuerpo de la noticia es HTML saneado: se sanea al escribir (control principal) y de
 * nuevo al leer (por si alguien tocó la base). Se prueba POR LA API, no solo por el editor:
 * quien llama a la API directamente se salta cualquier defensa del navegador.
 */
final class NoticiasContenidoHtmlTest extends TestCase
{
    use RefreshDatabase;

    private const HOSTIL = '<h2>Título</h2><p onclick="alert(1)">Texto <strong>fuerte</strong></p><script>alert(1)</script><iframe src="https://evil.test"></iframe><a href="javascript:alert(1)">x</a><img src="https://evil.test/p.png" alt="x">';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        Storage::fake((string) config('filesystems.default'));
    }

    private function editor(): User
    {
        $u = User::factory()->create();
        $u->givePermissionTo('noticias.gestionar');

        return $u;
    }

    /** @return array<string, mixed> */
    private function datos(string $contenido): array
    {
        return ['titulo' => 'Titular', 'descripcion_corta' => 'Resumen', 'contenido' => $contenido, 'fecha_publicacion' => '2026-11-01T10:00:00-05:00'];
    }

    private function noticiaEnBase(string $contenido, bool $activo = true): string
    {
        $uuid = (string) Str::uuid();
        DB::table('noticias')->insert([
            'uuid' => $uuid, 'slug' => 'n-'.substr($uuid, 0, 8), 'titulo' => 'T', 'descripcion_corta' => 'D', 'contenido' => $contenido,
            'fecha_publicacion' => now()->subDay(), 'destacada' => false, 'activo' => $activo, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $uuid;
    }

    private function sinPeligro(string $html): void
    {
        foreach (['<script', 'onclick', 'javascript:', '<iframe', 'evil.test'] as $p) {
            $this->assertStringNotContainsString($p, $html);
        }
    }

    public function test_al_crear_se_sanea_lo_guardado_y_lo_devuelto(): void
    {
        $r = $this->actingAs($this->editor(), 'web')->postJson('/api/v1/panel/noticias', $this->datos(self::HOSTIL))->assertCreated();

        $guardado = (string) DB::table('noticias')->where('uuid', $r->json('data.uuid'))->value('contenido');
        $this->sinPeligro($guardado);
        $this->sinPeligro((string) $r->json('data.contenido'));
        $this->assertStringContainsString('<h2>Título</h2>', $guardado);
        $this->assertStringContainsString('<strong>fuerte</strong>', $guardado);
    }

    public function test_al_editar_se_sanea(): void
    {
        $uuid = $this->noticiaEnBase('<p>ok</p>', activo: false);

        $this->actingAs($this->editor(), 'web')->patchJson("/api/v1/panel/noticias/{$uuid}", ['contenido' => self::HOSTIL])->assertOk();

        $this->sinPeligro((string) DB::table('noticias')->where('uuid', $uuid)->value('contenido'));
    }

    public function test_el_texto_plano_que_llegue_por_la_api_se_guarda_como_parrafos(): void
    {
        $r = $this->actingAs($this->editor(), 'web')->postJson('/api/v1/panel/noticias', $this->datos("Uno.\n\nDos <b>."))->assertCreated();

        $this->assertSame('<p>Uno.</p><p>Dos &lt;b&gt;.</p>', DB::table('noticias')->where('uuid', $r->json('data.uuid'))->value('contenido'));
    }

    public function test_lo_que_ya_estaba_manipulado_en_la_base_se_sanea_al_leer(): void
    {
        $uuid = $this->noticiaEnBase(self::HOSTIL);
        $slug = (string) DB::table('noticias')->where('uuid', $uuid)->value('slug');

        $publica = $this->getJson("/api/v1/noticias/{$slug}")->assertOk();
        $this->sinPeligro((string) $publica->json('data.contenido'));

        $panel = $this->actingAs($this->editor(), 'web')->getJson("/api/v1/panel/noticias/{$uuid}")->assertOk();
        $this->sinPeligro((string) $panel->json('data.contenido'));
    }

    public function test_las_noticias_antiguas_en_texto_plano_se_leen_como_parrafos(): void
    {
        $uuid = $this->noticiaEnBase("Uno.\n\nDos.");
        $slug = (string) DB::table('noticias')->where('uuid', $uuid)->value('slug');

        $this->getJson("/api/v1/noticias/{$slug}")->assertJsonPath('data.contenido', '<p>Uno.</p><p>Dos.</p>');
    }

    public function test_un_contenido_sin_texto_no_cuenta_como_contenido_para_publicar(): void
    {
        $uuid = $this->noticiaEnBase('<p></p>', activo: false);

        $this->actingAs($this->editor(), 'web')->patchJson("/api/v1/panel/noticias/{$uuid}", ['activo' => true])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['noticia'], 'errors');
    }

    public function test_el_contenido_tiene_un_tope_de_tamano(): void
    {
        $this->actingAs($this->editor(), 'web')
            ->postJson('/api/v1/panel/noticias', $this->datos('<p>'.str_repeat('a', 100_001).'</p>'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contenido'], 'errors');
    }

    public function test_la_migracion_convierte_el_texto_plano_y_se_puede_deshacer(): void
    {
        $plano = $this->noticiaEnBase("Uno & dos.\n\nTres.");
        $html = $this->noticiaEnBase('<h2>Ya es HTML</h2>');

        $migracion = require database_path('migrations/2026_11_12_000000_noticias_contenido_a_html.php');
        $migracion->up();

        $this->assertSame('<p>Uno &amp; dos.</p><p>Tres.</p>', DB::table('noticias')->where('uuid', $plano)->value('contenido'));
        $this->assertSame('<h2>Ya es HTML</h2>', DB::table('noticias')->where('uuid', $html)->value('contenido'));

        $migracion->down();

        $this->assertSame("Uno & dos.\n\nTres.", DB::table('noticias')->where('uuid', $plano)->value('contenido'));
    }
}
