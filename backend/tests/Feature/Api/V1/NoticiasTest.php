<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Noticias del home (M01-6, RF-01-013).
 *
 * «El home muestra las 6 más recientes en orden inverso, cada una con imagen y
 * `alt`, título ≤ 150, descripción ≤ 200, fecha y enlace; con 0 noticias, CT-01.»
 *
 * Las pruebas entran por HTTP y arman los datos con `DB::table`, sin nombrar el
 * modelo: comprueban lo que el contrato promete y no cómo lo implementa el
 * código, así que sobreviven a una refactorización del modelo.
 */
final class NoticiasTest extends TestCase
{
    use RefreshDatabase;

    /** Un PNG válido de 1×1 píxel. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        Storage::fake((string) config('filesystems.default'));
    }

    private function editor(): User
    {
        $usuario = User::factory()->create();
        $usuario->givePermissionTo('noticias.gestionar');

        return $usuario;
    }

    /**
     * Una noticia lista para publicarse, con los campos que se pasen cambiados.
     *
     * @param  array<string, mixed>  $campos
     */
    private function noticia(array $campos = []): int
    {
        static $n = 0;
        $n++;

        $datos = $campos + [
            'uuid' => (string) Str::uuid(),
            'slug' => 'noticia-'.$n,
            'titulo' => 'Noticia '.$n,
            'descripcion_corta' => 'Resumen de la noticia '.$n,
            'contenido' => "Primer párrafo {$n}.\n\nSegundo párrafo {$n}.",
            'fecha_publicacion' => now()->subDays(100 - $n),
            'destacada' => false,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return (int) DB::table('noticias')->insertGetId($datos);
    }

    /** Le adjunta la imagen con su `alt`, como lo hace el panel. */
    private function conImagen(int $id, ?string $alt = 'Vista aérea de la bahía de Santa Marta'): void
    {
        $uuid = (string) DB::table('noticias')->where('id', $id)->value('uuid');
        $disco = (string) config('filesystems.default');
        $ruta = "noticias/{$uuid}/imagen/foto.png";
        Storage::disk($disco)->put($ruta, (string) base64_decode(self::PNG, true));

        DB::table('file_media')->insert([
            'uuid' => (string) Str::uuid(),
            'model_type' => 'App\\Models\\Noticia',
            'model_id' => $id,
            'model_uuid' => $uuid,
            'collection' => 'imagen',
            'filename' => 'foto.png',
            'original_filename' => 'foto.png',
            'mime_type' => 'image/png',
            'size' => 70,
            'disk' => $disco,
            'path' => $ruta,
            'metadata' => json_encode(['alt' => $alt], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function png(string $nombre = 'foto.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nombre, (string) base64_decode(self::PNG, true));
    }

    // ------------------------------------------------------------ Lectura pública

    public function test_con_cero_noticias_responde_una_lista_vacia_sin_inventar_nada(): void
    {
        $this->getJson('/api/v1/noticias')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0);
    }

    public function test_devuelve_las_seis_mas_recientes_en_orden_cronologico_inverso(): void
    {
        foreach (range(1, 8) as $dia) {
            $this->noticia(['titulo' => "Del día {$dia}", 'fecha_publicacion' => now()->subDays(20 - $dia)]);
        }

        $titulos = $this->getJson('/api/v1/noticias?per_page=6')
            ->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonPath('meta.total', 8)
            ->json('data.*.titulo');

        $this->assertSame(
            ['Del día 8', 'Del día 7', 'Del día 6', 'Del día 5', 'Del día 4', 'Del día 3'],
            $titulos,
        );
    }

    public function test_con_la_misma_fecha_gana_la_mas_nueva_por_id(): void
    {
        $fecha = now()->subDay();
        $a = $this->noticia(['titulo' => 'A', 'fecha_publicacion' => $fecha]);
        $b = $this->noticia(['titulo' => 'B', 'fecha_publicacion' => $fecha]);

        $this->assertGreaterThan($a, $b);
        $this->assertSame(['B', 'A'], $this->getJson('/api/v1/noticias')->json('data.*.titulo'));
    }

    public function test_una_noticia_recien_publicada_aparece_en_la_primera_posicion(): void
    {
        $this->noticia(['titulo' => 'Vieja', 'fecha_publicacion' => now()->subDays(5)]);
        $this->noticia(['titulo' => 'Recién publicada', 'fecha_publicacion' => now()->subMinute()]);

        $this->getJson('/api/v1/noticias?per_page=6')->assertJsonPath('data.0.titulo', 'Recién publicada');
    }

    public function test_no_publica_borradores_ni_noticias_futuras_ni_eliminadas(): void
    {
        $this->noticia(['titulo' => 'Publicada']);
        $this->noticia(['titulo' => 'Borrador', 'activo' => false]);
        $this->noticia(['titulo' => 'Programada', 'fecha_publicacion' => now()->addDay()]);
        $this->noticia(['titulo' => 'Eliminada', 'deleted_at' => now()]);

        $this->getJson('/api/v1/noticias')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.titulo', 'Publicada');
    }

    public function test_cada_elemento_trae_los_campos_del_requisito(): void
    {
        $id = $this->noticia(['titulo' => 'Con foto', 'slug' => 'con-foto']);
        $this->conImagen($id, 'Niños en la playa de El Rodadero');

        $elemento = $this->getJson('/api/v1/noticias')->assertOk()->json('data.0');

        $this->assertSame('noticias', $elemento['type']);
        $this->assertSame('con-foto', $elemento['slug']);
        $this->assertSame('Con foto', $elemento['titulo']);
        $this->assertNotSame('', $elemento['descripcion_corta']);
        $this->assertNotFalse(strtotime((string) $elemento['fecha_publicacion']));
        $this->assertSame('Niños en la playa de El Rodadero', $elemento['imagen']['alt']);
        $this->assertStringEndsWith('/api/v1/noticias/con-foto/imagen', (string) $elemento['imagen']['url']);
        $this->assertArrayNotHasKey('contenido', $elemento, 'El listado no arrastra el cuerpo de cada noticia.');
    }

    public function test_el_detalle_trae_el_cuerpo_y_una_noticia_inexistente_o_borrador_da_404(): void
    {
        $this->noticia(['slug' => 'visible', 'contenido' => "Uno.\n\nDos."]);
        $this->noticia(['slug' => 'oculta', 'activo' => false]);

        $this->getJson('/api/v1/noticias/visible')
            ->assertOk()
            ->assertJsonPath('data.slug', 'visible')
            ->assertJsonPath('data.contenido', "Uno.\n\nDos.");
        $this->getJson('/api/v1/noticias/oculta')->assertNotFound()->assertJsonPath('success', false);
        $this->getJson('/api/v1/noticias/no-existe')->assertNotFound();
    }

    public function test_el_listado_pagina_con_el_sobre_del_contrato_y_rechaza_mas_de_100_por_pagina(): void
    {
        foreach (range(1, 3) as $_) {
            $this->noticia();
        }

        $this->getJson('/api/v1/noticias?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['success', 'message', 'data', 'meta' => ['current_page', 'last_page', 'per_page', 'total'], 'links', 'errors']);

        $this->getJson('/api/v1/noticias?per_page=101')->assertStatus(422)->assertJsonValidationErrors(['per_page'], 'errors');
        $this->getJson('/api/v1/noticias?per_page=0')->assertStatus(422);
    }

    public function test_la_imagen_se_sirve_por_una_direccion_estable_y_sin_cookies(): void
    {
        $id = $this->noticia(['slug' => 'foto']);
        $this->conImagen($id);
        $sinFoto = $this->noticia(['slug' => 'sin-foto']);
        $this->assertGreaterThan(0, $sinFoto);

        $respuesta = $this->get('/api/v1/noticias/foto/imagen')->assertOk();
        $this->assertStringStartsWith('image/png', (string) $respuesta->headers->get('Content-Type'));
        $this->assertStringContainsString('public', (string) $respuesta->headers->get('Cache-Control'));
        $this->assertNotNull($respuesta->headers->get('ETag'));
        $this->assertSame([], $respuesta->headers->getCookies(), 'Una imagen pública no instala cookies.');

        $this->get('/api/v1/noticias/sin-foto/imagen')->assertNotFound();
        $this->get('/api/v1/noticias/no-existe/imagen')->assertNotFound();
    }

    public function test_la_imagen_de_un_borrador_no_se_sirve(): void
    {
        $id = $this->noticia(['slug' => 'borrador', 'activo' => false]);
        $this->conImagen($id);

        $this->get('/api/v1/noticias/borrador/imagen')->assertNotFound();
    }

    // ------------------------------------------------------------------- Panel

    public function test_el_panel_exige_sesion_y_permiso(): void
    {
        $this->getJson('/api/v1/panel/noticias')->assertUnauthorized();

        $sinPermiso = $this->actingAs(User::factory()->create(), 'web');
        $sinPermiso->getJson('/api/v1/panel/noticias')->assertForbidden();
        $sinPermiso->postJson('/api/v1/panel/noticias', ['titulo' => 'X'])->assertForbidden();
    }

    public function test_el_permiso_de_noticias_se_siembra(): void
    {
        $this->assertTrue(DB::table('permissions')->where('name', 'noticias.gestionar')->exists());
    }

    public function test_crea_un_borrador_con_slug_unico_y_el_autor_de_la_sesion(): void
    {
        $editor = $this->editor();
        $datos = [
            'titulo' => 'Inauguración del parque',
            'descripcion_corta' => 'La Alcaldía entregó el parque.',
            'contenido' => 'Texto de la noticia.',
            'fecha_publicacion' => '2026-11-01T10:00:00-05:00',
        ];

        $primera = $this->actingAs($editor, 'web')->postJson('/api/v1/panel/noticias', $datos)->assertCreated();
        $segunda = $this->actingAs($editor, 'web')->postJson('/api/v1/panel/noticias', $datos)->assertCreated();

        $this->assertSame('inauguracion-del-parque', $primera->json('data.slug'));
        $this->assertNotSame($primera->json('data.slug'), $segunda->json('data.slug'));
        $this->assertFalse($primera->json('data.activo'), 'Nace como borrador.');
        $this->assertSame($editor->id, (int) DB::table('noticias')->where('slug', 'inauguracion-del-parque')->value('autor_id'));
        $this->getJson('/api/v1/noticias')->assertJsonPath('meta.total', 0);
    }

    public function test_titulo_y_descripcion_tienen_sus_limites_exactos(): void
    {
        $base = ['contenido' => 'Texto.', 'fecha_publicacion' => '2026-11-01T10:00:00-05:00'];
        $editor = $this->editor();

        $this->actingAs($editor, 'web')->postJson('/api/v1/panel/noticias', $base + [
            'titulo' => str_repeat('a', 150),
            'descripcion_corta' => str_repeat('b', 200),
        ])->assertCreated();

        $this->actingAs($editor, 'web')->postJson('/api/v1/panel/noticias', $base + [
            'titulo' => str_repeat('a', 151),
            'descripcion_corta' => 'ok',
        ])->assertStatus(422)->assertJsonValidationErrors(['titulo'], 'errors');

        $this->actingAs($editor, 'web')->postJson('/api/v1/panel/noticias', $base + [
            'titulo' => 'ok',
            'descripcion_corta' => str_repeat('b', 201),
        ])->assertStatus(422)->assertJsonValidationErrors(['descripcion_corta'], 'errors');
    }

    public function test_no_se_publica_sin_imagen(): void
    {
        $id = $this->noticia(['activo' => false]);
        $uuid = (string) DB::table('noticias')->where('id', $id)->value('uuid');

        $this->actingAs($this->editor(), 'web')
            ->patchJson("/api/v1/panel/noticias/{$uuid}", ['activo' => true])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['noticia'], 'errors');

        $this->assertFalse((bool) DB::table('noticias')->where('id', $id)->value('activo'));
    }

    public function test_la_imagen_exige_su_alt_y_el_servidor_lo_valida_aunque_el_formulario_no(): void
    {
        $id = $this->noticia(['activo' => false]);
        $uuid = (string) DB::table('noticias')->where('id', $id)->value('uuid');
        $editor = $this->editor();

        foreach ([[], ['alt' => ''], ['alt' => '   ']] as $extra) {
            $this->actingAs($editor, 'web')
                ->post("/api/v1/panel/noticias/{$uuid}/imagen", ['imagen' => $this->png()] + $extra, ['Accept' => 'application/json'])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['alt'], 'errors');
        }

        $this->assertSame(0, DB::table('file_media')->where('model_uuid', $uuid)->count(), 'Sin alt no se guarda nada.');
    }

    public function test_solo_se_aceptan_imagenes(): void
    {
        $id = $this->noticia(['activo' => false]);
        $uuid = (string) DB::table('noticias')->where('id', $id)->value('uuid');

        $this->actingAs($this->editor(), 'web')
            ->post("/api/v1/panel/noticias/{$uuid}/imagen", [
                'imagen' => UploadedFile::fake()->createWithContent('acto.pdf', "%PDF-1.4\n"),
                'alt' => 'Un documento',
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['imagen'], 'errors');
    }

    public function test_sube_la_imagen_con_su_alt_la_reemplaza_y_deja_una_sola(): void
    {
        $id = $this->noticia(['activo' => false]);
        $uuid = (string) DB::table('noticias')->where('id', $id)->value('uuid');
        $editor = $this->editor();

        foreach (['Primera descripción', 'Segunda descripción'] as $alt) {
            $this->actingAs($editor, 'web')
                ->post("/api/v1/panel/noticias/{$uuid}/imagen", ['imagen' => $this->png(), 'alt' => $alt], ['Accept' => 'application/json'])
                ->assertOk()
                ->assertJsonPath('data.imagen.alt', $alt);
        }

        $this->assertSame(1, DB::table('file_media')->where('model_uuid', $uuid)->where('collection', 'imagen')->count());
    }

    public function test_publica_con_imagen_y_alt_y_la_noticia_aparece_en_el_home(): void
    {
        $id = $this->noticia(['activo' => false, 'titulo' => 'Para el home', 'slug' => 'para-el-home']);
        $this->conImagen($id);
        $uuid = (string) DB::table('noticias')->where('id', $id)->value('uuid');

        $this->actingAs($this->editor(), 'web')
            ->patchJson("/api/v1/panel/noticias/{$uuid}", ['activo' => true])
            ->assertOk()
            ->assertJsonPath('data.activo', true);

        $this->getJson('/api/v1/noticias?per_page=6')->assertJsonPath('data.0.titulo', 'Para el home');
    }

    public function test_publicar_con_el_alt_en_blanco_se_rechaza_y_vaciar_el_alt_de_una_publicada_tambien(): void
    {
        $borrador = $this->noticia(['activo' => false]);
        $this->conImagen($borrador, '   ');
        $uuidBorrador = (string) DB::table('noticias')->where('id', $borrador)->value('uuid');

        $publicada = $this->noticia(['activo' => true]);
        $this->conImagen($publicada);
        $uuidPublicada = (string) DB::table('noticias')->where('id', $publicada)->value('uuid');
        $editor = $this->editor();

        $this->actingAs($editor, 'web')
            ->patchJson("/api/v1/panel/noticias/{$uuidBorrador}", ['activo' => true])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['noticia'], 'errors');

        $this->actingAs($editor, 'web')
            ->patchJson("/api/v1/panel/noticias/{$uuidPublicada}", ['imagen_alt' => ''])
            ->assertStatus(422);
    }

    public function test_el_panel_ve_la_imagen_de_un_borrador_por_una_ruta_con_sesion(): void
    {
        $id = $this->noticia(['slug' => 'borrador-con-foto', 'activo' => false]);
        $this->conImagen($id);
        $uuid = (string) DB::table('noticias')->where('id', $id)->value('uuid');

        // La ruta pública no sirve borradores; el panel necesita ver la suya.
        $this->get('/api/v1/noticias/borrador-con-foto/imagen')->assertNotFound();

        $respuesta = $this->actingAs($this->editor(), 'web')->get("/api/v1/panel/noticias/{$uuid}/imagen")->assertOk();
        $this->assertStringStartsWith('image/png', (string) $respuesta->headers->get('Content-Type'));
        $this->assertStringNotContainsString('public', (string) $respuesta->headers->get('Cache-Control'), 'Un borrador no se guarda en cachés compartidas.');
        $this->assertSame('nosniff', $respuesta->headers->get('X-Content-Type-Options'));
    }

    public function test_la_imagen_del_panel_exige_sesion_y_permiso(): void
    {
        $id = $this->noticia(['slug' => 'borrador-privado', 'activo' => false]);
        $this->conImagen($id);
        $uuid = (string) DB::table('noticias')->where('id', $id)->value('uuid');

        $this->getJson("/api/v1/panel/noticias/{$uuid}/imagen")->assertUnauthorized();
        $this->actingAs(User::factory()->create(), 'web')->get("/api/v1/panel/noticias/{$uuid}/imagen")->assertForbidden();
    }

    public function test_la_imagen_del_panel_responde_404_sin_imagen_o_sin_noticia(): void
    {
        $sinFoto = $this->noticia(['slug' => 'sin-foto-panel', 'activo' => false]);
        $uuid = (string) DB::table('noticias')->where('id', $sinFoto)->value('uuid');
        $editor = $this->editor();

        $this->actingAs($editor, 'web')->get("/api/v1/panel/noticias/{$uuid}/imagen")->assertNotFound();
        $this->actingAs($editor, 'web')->get('/api/v1/panel/noticias/'.Str::uuid().'/imagen')->assertNotFound();
    }

    public function test_el_panel_recibe_la_direccion_de_imagen_del_panel_y_el_sitio_la_publica(): void
    {
        $id = $this->noticia(['slug' => 'dos-rutas', 'activo' => false]);
        $uuid = (string) DB::table('noticias')->where('id', $id)->value('uuid');
        $editor = $this->editor();

        $this->actingAs($editor, 'web')
            ->post("/api/v1/panel/noticias/{$uuid}/imagen", ['imagen' => $this->png(), 'alt' => 'Descripción'], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.imagen.url', "/api/v1/panel/noticias/{$uuid}/imagen");

        $this->actingAs($editor, 'web')->getJson("/api/v1/panel/noticias/{$uuid}")
            ->assertJsonPath('data.imagen.url', "/api/v1/panel/noticias/{$uuid}/imagen");

        // También el listado del panel entrega la dirección del panel (la recorre cada elemento).
        $this->actingAs($editor, 'web')->getJson('/api/v1/panel/noticias')
            ->assertJsonPath('data.0.imagen.url', "/api/v1/panel/noticias/{$uuid}/imagen");

        DB::table('noticias')->where('id', $id)->update(['activo' => true]);
        $this->getJson('/api/v1/noticias/dos-rutas')->assertJsonPath('data.imagen.url', '/api/v1/noticias/dos-rutas/imagen');
    }

    public function test_el_listado_del_panel_trae_lo_necesario_para_editar_cada_noticia(): void
    {
        $publicada = $this->noticia(['slug' => 'lista-publicada', 'activo' => true, 'contenido' => 'Cuerpo publicado.']);
        $this->conImagen($publicada);
        $this->noticia(['slug' => 'lista-borrador', 'activo' => false, 'contenido' => 'Cuerpo en borrador.']);

        /** @var list<array<string, mixed>> $datos */
        $datos = $this->actingAs($this->editor(), 'web')->getJson('/api/v1/panel/noticias')->assertOk()->json('data');
        /** @var array<string, array<string, mixed>> $porSlug */
        $porSlug = [];
        foreach ($datos as $elemento) {
            $porSlug[(string) $elemento['slug']] = $elemento;
        }

        foreach (['lista-publicada', 'lista-borrador'] as $slug) {
            $item = $porSlug[$slug];
            $this->assertTrue(Str::isUuid((string) ($item['uuid'] ?? '')), "{$slug}: sin uuid");
            foreach (['activo', 'contenido', 'completa', 'problemas', 'titulo', 'descripcion_corta', 'fecha_publicacion', 'destacada'] as $campo) {
                $this->assertArrayHasKey($campo, $item, "{$slug}: falta {$campo}");
            }
        }
        $this->assertTrue($porSlug['lista-publicada']['activo']);
        $this->assertFalse($porSlug['lista-borrador']['activo']);
        $this->assertSame('Cuerpo publicado.', $porSlug['lista-publicada']['contenido']);
        $this->assertSame('Cuerpo en borrador.', $porSlug['lista-borrador']['contenido']);
        $this->assertTrue($porSlug['lista-publicada']['completa']);
        $this->assertFalse($porSlug['lista-borrador']['completa']);
    }

    public function test_un_identificador_que_no_es_uuid_responde_404_y_no_500(): void
    {
        $editor = $this->editor();

        foreach (['undefined', 'null', '123', 'no-es-uuid'] as $malo) {
            $this->actingAs($editor, 'web')->getJson("/api/v1/panel/noticias/{$malo}")->assertNotFound();
            $this->actingAs($editor, 'web')->patchJson("/api/v1/panel/noticias/{$malo}", ['titulo' => 'X'])->assertNotFound();
            $this->actingAs($editor, 'web')->deleteJson("/api/v1/panel/noticias/{$malo}")->assertNotFound();
            $this->actingAs($editor, 'web')->get("/api/v1/panel/noticias/{$malo}/imagen")->assertNotFound();
            $this->actingAs($editor, 'web')
                ->post("/api/v1/panel/noticias/{$malo}/imagen", ['imagen' => $this->png(), 'alt' => 'x'], ['Accept' => 'application/json'])
                ->assertNotFound();
        }
    }

    public function test_editar_el_titulo_no_cambia_la_direccion_de_la_noticia(): void
    {
        // Una noticia publicada siempre tiene imagen con alt: sin ella ni siquiera se puede editar.
        $id = $this->noticia(['slug' => 'direccion-fija', 'titulo' => 'Antes']);
        $this->conImagen($id);
        $uuid = (string) DB::table('noticias')->where('id', $id)->value('uuid');

        $this->actingAs($this->editor(), 'web')
            ->patchJson("/api/v1/panel/noticias/{$uuid}", ['titulo' => 'Después'])
            ->assertOk()
            ->assertJsonPath('data.slug', 'direccion-fija')
            ->assertJsonPath('data.titulo', 'Después');
    }

    public function test_el_listado_del_panel_incluye_borradores_y_el_eliminar_retira_la_noticia_del_sitio(): void
    {
        $publicada = $this->noticia(['slug' => 'se-va']);
        $this->noticia(['activo' => false]);
        $uuid = (string) DB::table('noticias')->where('id', $publicada)->value('uuid');
        $editor = $this->editor();

        $this->actingAs($editor, 'web')->getJson('/api/v1/panel/noticias')->assertOk()->assertJsonPath('meta.total', 2);

        $this->actingAs($editor, 'web')->deleteJson("/api/v1/panel/noticias/{$uuid}")->assertOk();

        $this->getJson('/api/v1/noticias/se-va')->assertNotFound();
        $this->assertNotNull(DB::table('noticias')->where('id', $publicada)->value('deleted_at'), 'Se archiva, no se borra.');
    }
}
