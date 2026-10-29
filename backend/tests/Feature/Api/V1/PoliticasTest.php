<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Politica;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PoliticaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * Las cinco políticas: texto en base de datos, editable desde el panel y
 * descargable en formato abierto (T-20, H-21; AC-20.1 a 20.8).
 *
 * Los textos sembrados son de ejemplo y nacen marcados como tales: lo que se
 * prueba aquí es que la estructura está completa, que el panel no deja publicar
 * una política incompleta y que las descargas son ODT y PDF/A de verdad.
 */
final class PoliticasTest extends TestCase
{
    use RefreshDatabase;

    private const SLUGS = [
        'terminos-y-condiciones-de-uso',
        'seguridad-y-privacidad',
        'proteccion-y-tratamiento-de-datos-personales',
        'uso-de-cookies',
        'derechos-de-autor-y-uso-sobre-contenidos',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, PoliticaSeeder::class]);
    }

    private function editor(): User
    {
        $usuario = User::factory()->create();
        $usuario->givePermissionTo('politicas.gestionar');

        return $usuario;
    }

    // ---------------------------------------------------------------- Estructura

    public function test_las_cinco_politicas_existen_y_coinciden_con_las_del_sitio(): void
    {
        $this->assertEqualsCanonicalizing(self::SLUGS, Politica::query()->pluck('slug')->all());
        $this->assertSame(self::SLUGS, array_keys(Politica::COMPONENTES));

        $config = (string) file_get_contents(base_path('../sitio/app/config/sitemap.ts'));
        preg_match('/export const POLITICAS[^=]*=\s*\[(.*?)\n\]/s', $config, $bloque);
        $this->assertNotSame('', $bloque[1] ?? '', 'No se encontró POLITICAS en sitemap.ts.');
        preg_match_all("/slug:\s*'([^']+)'/", $bloque[1] ?? '', $delSitio);
        $this->assertSame(self::SLUGS, $delSitio[1]);
    }

    public function test_cada_politica_sembrada_esta_completa_y_marcada_como_ejemplo(): void
    {
        foreach (Politica::query()->get() as $politica) {
            $this->assertSame([], $politica->problemas(), $politica->slug);
            $this->assertTrue($politica->es_ejemplo, $politica->slug.' debe nacer como ejemplo.');
            $this->assertNull($politica->acto_numero, 'No se inventan actos administrativos.');
        }
    }

    public function test_ningun_texto_sembrado_dice_en_preparacion(): void
    {
        foreach (Politica::query()->get() as $politica) {
            $this->assertDoesNotMatchRegularExpression('/en preparaci[oó]n/iu', json_encode($politica->secciones, JSON_THROW_ON_ERROR));
        }
    }

    public function test_sembrar_de_nuevo_no_pisa_lo_que_la_entidad_ya_edito(): void
    {
        Politica::query()->where('slug', 'uso-de-cookies')->update(['nombre' => 'Editada por la Entidad']);

        $this->seed(PoliticaSeeder::class);

        $this->assertSame('Editada por la Entidad', Politica::query()->where('slug', 'uso-de-cookies')->firstOrFail()->nombre);
        $this->assertSame(5, Politica::query()->count());
    }

    // ------------------------------------------------------------- API pública

    public function test_la_lista_publica_trae_las_cinco_en_orden_y_sin_el_texto(): void
    {
        $respuesta = $this->getJson('/api/v1/politicas')->assertOk();

        $this->assertSame(self::SLUGS, array_column($respuesta->json('data'), 'slug'));
        $this->assertArrayNotHasKey('secciones', $respuesta->json('data.0'));
        $respuesta->assertJsonPath('data.0.es_ejemplo', true);
    }

    public function test_una_politica_trae_version_vigencia_acto_y_apartados(): void
    {
        $this->getJson('/api/v1/politicas/uso-de-cookies')
            ->assertOk()
            ->assertJsonPath('data.slug', 'uso-de-cookies')
            ->assertJsonPath('data.version', 1)
            ->assertJsonStructure(['data' => ['acto' => ['tipo', 'numero', 'fecha'], 'vigente_desde', 'secciones' => [['id', 'titulo', 'parrafos']]]]);
    }

    public function test_una_politica_inexistente_da_404(): void
    {
        $this->getJson('/api/v1/politicas/no-existe')->assertNotFound();
    }

    // ------------------------------------------------------------------- Panel

    public function test_el_panel_exige_sesion_y_permiso(): void
    {
        $this->getJson('/api/v1/panel/politicas')->assertStatus(401);
        $this->actingAs(User::factory()->create(), 'web')->getJson('/api/v1/panel/politicas')->assertForbidden();
        $this->actingAs(User::factory()->create(), 'web')
            ->patchJson('/api/v1/panel/politicas/uso-de-cookies', ['nombre' => 'X'])
            ->assertForbidden();
    }

    public function test_el_panel_lista_con_el_estado_de_completitud(): void
    {
        $respuesta = $this->actingAs($this->editor(), 'web')->getJson('/api/v1/panel/politicas')->assertOk();

        $this->assertCount(5, $respuesta->json('data'));
        $respuesta->assertJsonPath('data.0.completa', true)->assertJsonPath('data.0.problemas', []);
        $this->assertNotEmpty($respuesta->json('data.0.componentes'));
    }

    public function test_cambiar_el_texto_sin_tocar_la_version_la_sube_sola(): void
    {
        $secciones = Politica::query()->where('slug', 'uso-de-cookies')->firstOrFail()->secciones;
        $secciones[0]['parrafos'] = ['Texto nuevo del apartado.'];

        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/uso-de-cookies', ['secciones' => $secciones])
            ->assertOk()
            ->assertJsonPath('data.version', 2);
    }

    public function test_cambiar_solo_el_acto_no_sube_la_version(): void
    {
        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/uso-de-cookies', ['acto_tipo' => 'Resolución'])
            ->assertOk()
            ->assertJsonPath('data.version', 1);
    }

    public function test_no_deja_guardar_una_politica_con_un_apartado_vacio_o_ausente(): void
    {
        $secciones = Politica::query()->where('slug', 'uso-de-cookies')->firstOrFail()->secciones;
        $secciones[1]['parrafos'] = ['   '];
        array_pop($secciones);

        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/uso-de-cookies', ['secciones' => $secciones])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['politica'], 'errors');

        $this->assertSame(1, Politica::query()->where('slug', 'uso-de-cookies')->firstOrFail()->version);
    }

    public function test_un_apartado_adicional_vacio_tampoco_se_guarda(): void
    {
        $secciones = Politica::query()->where('slug', 'uso-de-cookies')->firstOrFail()->secciones;
        $secciones[] = ['id' => 'extra', 'titulo' => 'Un extra', 'parrafos' => ['  ']];

        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/uso-de-cookies', ['secciones' => $secciones])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['politica'], 'errors');
    }

    public function test_no_deja_guardar_el_texto_en_preparacion(): void
    {
        $secciones = Politica::query()->where('slug', 'uso-de-cookies')->firstOrFail()->secciones;
        $secciones[0]['parrafos'] = ['Este apartado está En Preparación.'];

        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/uso-de-cookies', ['secciones' => $secciones])
            ->assertStatus(422);
    }

    public function test_quitar_la_marca_de_ejemplo_exige_el_acto_y_que_no_queden_marcadores(): void
    {
        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/uso-de-cookies', ['es_ejemplo' => false])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['politica'], 'errors');

        // Con acto completo pero con un marcador «[por definir: ...]» en el texto, tampoco.
        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/derechos-de-autor-y-uso-sobre-contenidos', [
                'es_ejemplo' => false,
                'vigente_desde' => '2026-11-01',
                'acto_tipo' => 'Resolución',
                'acto_numero' => '123',
                'acto_fecha' => '2026-10-20',
            ])
            ->assertStatus(422);

        $this->assertTrue(Politica::query()->where('slug', 'uso-de-cookies')->firstOrFail()->es_ejemplo);
    }

    public function test_un_acto_con_marcador_en_el_numero_no_vale(): void
    {
        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/uso-de-cookies', [
                'es_ejemplo' => false,
                'vigente_desde' => '2026-11-01',
                'acto_tipo' => 'Resolución',
                'acto_numero' => '[por asignar]',
                'acto_fecha' => '2026-10-20',
            ])
            ->assertStatus(422);
    }

    public function test_con_acto_completo_y_sin_marcadores_se_puede_quitar_la_marca_de_ejemplo(): void
    {
        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/uso-de-cookies', [
                'es_ejemplo' => false,
                'vigente_desde' => '2026-11-01',
                'acto_tipo' => 'Resolución',
                'acto_numero' => '123',
                'acto_fecha' => '2026-10-20',
            ])
            ->assertOk()
            ->assertJsonPath('data.es_ejemplo', false)
            ->assertJsonPath('data.acto.numero', '123')
            ->assertJsonPath('data.vigente_desde', '2026-11-01');
    }

    public function test_una_politica_inexistente_en_el_panel_da_404(): void
    {
        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/no-existe', ['nombre' => 'X'])
            ->assertNotFound();
    }

    // ------------------------------------------------------------------ Descargas

    public function test_la_descarga_odt_es_un_odt_valido_y_no_pone_cookies(): void
    {
        $respuesta = $this->get('/politicas/uso-de-cookies.odt')->assertOk();

        $this->assertStringStartsWith('application/vnd.oasis.opendocument.text', (string) $respuesta->headers->get('Content-Type'));
        $this->assertNull($respuesta->headers->get('Set-Cookie'), 'Una descarga pública no debe poner cookies.');
        $this->assertSame([], $respuesta->headers->getCookies());

        $ruta = tempnam(sys_get_temp_dir(), 'odt');
        $this->assertIsString($ruta);
        file_put_contents($ruta, $respuesta->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($ruta) === true);
        $primero = $zip->statIndex(0);
        $this->assertIsArray($primero);
        $this->assertSame('mimetype', $primero['name']);
        $this->assertSame(ZipArchive::CM_STORE, $primero['comp_method'], 'mimetype va sin comprimir.');
        $this->assertSame('application/vnd.oasis.opendocument.text', $zip->getFromName('mimetype'));
        $contenido = $zip->getFromName('content.xml');
        $this->assertIsString($contenido);
        $this->assertStringContainsString('Uso de cookies', $contenido);
        $this->assertNotFalse(simplexml_load_string($contenido), 'content.xml debe ser XML bien formado.');
        $zip->close();
        unlink($ruta);
    }

    public function test_la_descarga_pdf_es_pdf_a(): void
    {
        $respuesta = $this->get('/politicas/uso-de-cookies.pdf')->assertOk();

        $this->assertSame('application/pdf', $respuesta->headers->get('Content-Type'));
        $contenido = (string) $respuesta->getContent();
        $this->assertStringStartsWith('%PDF-', $contenido);
        $this->assertStringContainsString('pdfaid:part', $contenido, 'Debe declarar su conformidad PDF/A en los metadatos XMP.');
        $this->assertStringContainsString('<pdfaid:part>2</pdfaid:part>', $contenido, 'PDF/A-2: el 1b no valida con veraPDF.');
        $this->assertStringContainsString('<pdfaid:conformance>B</pdfaid:conformance>', $contenido);
    }

    public function test_las_cinco_se_descargan_en_los_dos_formatos(): void
    {
        foreach (self::SLUGS as $slug) {
            $this->get("/politicas/{$slug}.odt")->assertOk();
            $this->get("/politicas/{$slug}.pdf")->assertOk();
        }
    }

    public function test_descargar_una_politica_inexistente_o_un_formato_raro_da_404(): void
    {
        $this->get('/politicas/no-existe.pdf')->assertNotFound();
        $this->get('/politicas/uso-de-cookies.exe')->assertNotFound();
    }

    public function test_la_descarga_refleja_el_texto_editado(): void
    {
        $secciones = Politica::query()->where('slug', 'uso-de-cookies')->firstOrFail()->secciones;
        $secciones[0]['parrafos'] = ['Frase única de verificación.'];
        $this->actingAs($this->editor(), 'web')
            ->patchJson('/api/v1/panel/politicas/uso-de-cookies', ['secciones' => $secciones])
            ->assertOk();

        $ruta = tempnam(sys_get_temp_dir(), 'odt');
        $this->assertIsString($ruta);
        file_put_contents($ruta, $this->get('/politicas/uso-de-cookies.odt')->getContent());
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($ruta) === true);
        $this->assertStringContainsString('Frase única de verificación.', (string) $zip->getFromName('content.xml'));
        $this->assertStringContainsString('Versión: 2', (string) $zip->getFromName('content.xml'));
        $zip->close();
        unlink($ruta);
    }

    // ------------------------------------------------- Documento oficial adjunto

    /**
     * Cambia de usuario dentro de la misma prueba. El guardia de Sanctum recuerda
     * al primero que resolvió; sin olvidarlo, el segundo `actingAs` seguiría siendo
     * el primer usuario y la prueba mediría otra cosa.
     */
    private function como(User $usuario): static
    {
        Auth::forgetGuards();

        return $this->actingAs($usuario, 'web');
    }

    private function editorConArchivos(): User
    {
        $usuario = $this->editor();
        $usuario->givePermissionTo(['archivos.ver', 'archivos.crear', 'archivos.eliminar']);

        return $usuario;
    }

    /**
     * Sube un PDF como documento oficial de la política y devuelve el UUID del archivo.
     */
    private function adjuntar(User $usuario, string $slug = 'uso-de-cookies', string $contenido = "%PDF-1.4\nActo de ejemplo\n"): string
    {
        $politica = Politica::query()->where('slug', $slug)->firstOrFail();

        $respuesta = $this->como($usuario)
            ->postJson("/api/v1/panel/entidad/{$politica->uuid}/archivos", [
                'archivo' => UploadedFile::fake()->createWithContent('acto.pdf', $contenido),
                'collection' => Politica::COLECCION_DOCUMENTO,
            ])
            ->assertOk();

        return (string) $respuesta->json('data.uuid');
    }

    public function test_el_panel_entrega_el_uuid_para_adjuntar_y_no_hay_documento_al_inicio(): void
    {
        $respuesta = $this->como($this->editor())->getJson('/api/v1/panel/politicas')->assertOk();

        $this->assertNotEmpty($respuesta->json('data.0.uuid'));
        $this->assertNull($respuesta->json('data.0.documento_oficial'));
        $this->assertNull($this->getJson('/api/v1/politicas/uso-de-cookies')->json('data.documento_oficial'));
        $this->get('/politicas/uso-de-cookies/documento')->assertNotFound();
    }

    public function test_el_documento_adjunto_queda_en_la_tabla_comun_y_se_descarga_sin_cookies(): void
    {
        Storage::fake((string) config('filesystems.default'));
        $contenido = "%PDF-1.4\nActo administrativo de ejemplo\n";

        $this->adjuntar($this->editorConArchivos(), 'uso-de-cookies', $contenido);

        $this->assertDatabaseHas('file_media', [
            'model_type' => Politica::class,
            'collection' => Politica::COLECCION_DOCUMENTO,
            'original_filename' => 'acto.pdf',
        ]);

        $publica = $this->getJson('/api/v1/politicas/uso-de-cookies')->assertOk();
        $publica->assertJsonPath('data.documento_oficial.nombre', 'acto.pdf')
            ->assertJsonPath('data.documento_oficial.url', '/politicas/uso-de-cookies/documento');

        $descarga = $this->get('/politicas/uso-de-cookies/documento')->assertOk();
        $this->assertSame($contenido, $descarga->streamedContent());
        $this->assertSame('nosniff', $descarga->headers->get('X-Content-Type-Options'));
        $this->assertCount(0, $descarga->headers->getCookies(), 'La descarga pública no debe poner cookies.');

        // Una política no ve el documento de otra.
        $this->get('/politicas/seguridad-y-privacidad/documento')->assertNotFound();
    }

    public function test_el_listado_de_archivos_de_la_politica_muestra_el_adjunto(): void
    {
        Storage::fake((string) config('filesystems.default'));
        $editor = $this->editorConArchivos();
        $this->adjuntar($editor);
        $politica = Politica::query()->where('slug', 'uso-de-cookies')->firstOrFail();

        $this->como($editor)
            ->getJson('/api/v1/panel/entidad/'.$politica->uuid.'/archivos?collection='.Politica::COLECCION_DOCUMENTO)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_si_se_adjuntan_varios_vale_el_mas_reciente(): void
    {
        Storage::fake((string) config('filesystems.default'));
        $editor = $this->editorConArchivos();
        $this->adjuntar($editor, 'uso-de-cookies', "%PDF-1.4\nprimero\n");
        $this->travel(5)->minutes();
        $this->adjuntar($editor, 'uso-de-cookies', "%PDF-1.4\nsegundo\n");

        $this->assertStringContainsString('segundo', $this->get('/politicas/uso-de-cookies/documento')->streamedContent());
    }

    public function test_a_una_politica_solo_se_le_adjunta_el_documento_oficial(): void
    {
        Storage::fake((string) config('filesystems.default'));
        $politica = Politica::query()->where('slug', 'uso-de-cookies')->firstOrFail();

        $this->como($this->editorConArchivos())
            ->postJson("/api/v1/panel/entidad/{$politica->uuid}/archivos", [
                'archivo' => UploadedFile::fake()->createWithContent('logo.pdf', "%PDF-1.4\n"),
                'collection' => 'logos',
            ])
            ->assertStatus(422);
    }

    public function test_quien_tiene_archivos_pero_no_politicas_no_puede_tocar_el_adjunto(): void
    {
        Storage::fake((string) config('filesystems.default'));
        $politica = Politica::query()->where('slug', 'uso-de-cookies')->firstOrFail();
        $soloArchivos = User::factory()->create();
        $soloArchivos->givePermissionTo(['archivos.ver', 'archivos.crear', 'archivos.eliminar']);

        $this->como($soloArchivos)
            ->postJson("/api/v1/panel/entidad/{$politica->uuid}/archivos", [
                'archivo' => UploadedFile::fake()->createWithContent('acto.pdf', "%PDF-1.4\n"),
                'collection' => Politica::COLECCION_DOCUMENTO,
            ])
            ->assertForbidden();

        $archivo = $this->adjuntar($this->editorConArchivos());

        $this->como($soloArchivos)
            ->getJson("/api/v1/panel/entidad/{$politica->uuid}/archivos?collection=".Politica::COLECCION_DOCUMENTO)
            ->assertForbidden();
        $this->como($soloArchivos)->deleteJson("/api/v1/panel/archivos/{$archivo}")->assertForbidden();
        $this->assertDatabaseHas('file_media', ['uuid' => $archivo]);
    }

    public function test_sin_permiso_de_archivos_no_se_adjunta_aunque_pueda_gestionar_politicas(): void
    {
        $politica = Politica::query()->where('slug', 'uso-de-cookies')->firstOrFail();

        $this->como($this->editor())
            ->postJson("/api/v1/panel/entidad/{$politica->uuid}/archivos", [
                'archivo' => UploadedFile::fake()->createWithContent('acto.pdf', "%PDF-1.4\n"),
                'collection' => Politica::COLECCION_DOCUMENTO,
            ])
            ->assertForbidden();
    }

    public function test_quitar_el_adjunto_borra_el_archivo_y_la_descarga_da_404(): void
    {
        Storage::fake((string) config('filesystems.default'));
        $editor = $this->editorConArchivos();
        $archivo = $this->adjuntar($editor);

        $this->como($editor)->deleteJson("/api/v1/panel/archivos/{$archivo}")->assertOk();

        $this->assertDatabaseMissing('file_media', ['uuid' => $archivo]);
        $this->get('/politicas/uso-de-cookies/documento')->assertNotFound();
        $this->assertNull($this->getJson('/api/v1/politicas/uso-de-cookies')->json('data.documento_oficial'));
    }

    public function test_se_acepta_odt_como_documento_oficial(): void
    {
        Storage::fake((string) config('filesystems.default'));
        $politica = Politica::query()->where('slug', 'uso-de-cookies')->firstOrFail();

        $this->como($this->editorConArchivos())
            ->postJson("/api/v1/panel/entidad/{$politica->uuid}/archivos", [
                'archivo' => UploadedFile::fake()->create('texto.odt', 20),
                'collection' => Politica::COLECCION_DOCUMENTO,
            ])
            ->assertOk();
    }

    // ------------------------------------------------------------------- Caché

    public function test_las_descargas_no_se_guardan_en_cache_compartida_y_llevan_etag(): void
    {
        foreach (['odt', 'pdf'] as $formato) {
            $respuesta = $this->get("/politicas/uso-de-cookies.{$formato}")->assertOk();

            $cabecera = (string) $respuesta->headers->get('Cache-Control');
            $this->assertStringContainsString('private', $cabecera, $formato);
            $this->assertStringContainsString('no-cache', $cabecera, $formato);
            $this->assertStringNotContainsString('public', $cabecera, $formato);
            $this->assertNotEmpty($respuesta->headers->get('ETag'), $formato);
        }
    }

    public function test_una_descarga_sin_cambios_responde_304_y_tras_editar_vuelve_a_generarse(): void
    {
        $primera = $this->get('/politicas/uso-de-cookies.pdf')->assertOk();
        $etag = (string) $primera->headers->get('ETag');

        $this->get('/politicas/uso-de-cookies.pdf', ['If-None-Match' => $etag])->assertStatus(304);

        $secciones = Politica::query()->where('slug', 'uso-de-cookies')->firstOrFail()->secciones;
        $secciones[0]['parrafos'] = ['Texto cambiado.'];
        $this->travel(2)->minutes();
        $this->como($this->editor())
            ->patchJson('/api/v1/panel/politicas/uso-de-cookies', ['secciones' => $secciones])
            ->assertOk();

        $despues = $this->get('/politicas/uso-de-cookies.pdf', ['If-None-Match' => $etag])->assertOk();
        $this->assertNotSame($etag, $despues->headers->get('ETag'));
    }

    public function test_el_documento_oficial_tampoco_se_guarda_en_cache_compartida_y_cambia_su_etag_al_reemplazarlo(): void
    {
        Storage::fake((string) config('filesystems.default'));
        $editor = $this->editorConArchivos();
        $this->adjuntar($editor, 'uso-de-cookies', "%PDF-1.4\nprimero\n");

        $primera = $this->get('/politicas/uso-de-cookies/documento')->assertOk();
        $this->assertStringContainsString('private', (string) $primera->headers->get('Cache-Control'));
        $etag = (string) $primera->headers->get('ETag');
        $this->assertNotEmpty($etag);

        $this->get('/politicas/uso-de-cookies/documento', ['If-None-Match' => $etag])->assertStatus(304);

        $this->travel(5)->minutes();
        $this->adjuntar($editor, 'uso-de-cookies', "%PDF-1.4\nsegundo\n");

        $this->get('/politicas/uso-de-cookies/documento', ['If-None-Match' => $etag])->assertOk();
    }
}
