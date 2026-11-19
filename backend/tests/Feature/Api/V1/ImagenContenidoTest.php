<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\FileMedia;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Imágenes que el editor del panel inserta en el contenido.
 *
 * Se guardan con FilesMediaService (colección «contenido») pero NO tal cual: se
 * re-codifican para quitar metadatos y cualquier contenido escondido.
 */
final class ImagenContenidoTest extends TestCase
{
    use RefreshDatabase;

    private const SUBIR = '/api/v1/panel/contenido/imagenes';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        Storage::fake((string) config('filesystems.default'));
    }

    private function usuario(string $permiso = 'noticias.editar'): User
    {
        $u = User::factory()->create();
        $u->givePermissionTo($permiso);

        return $u;
    }

    /**
     * @param  int<1, max>  $ancho
     * @param  int<1, max>  $alto
     */
    private function png(int $ancho = 8, int $alto = 8, string $extra = ''): UploadedFile
    {
        $im = imagecreatetruecolor($ancho, $alto);
        ob_start();
        imagepng($im);
        $bytes = (string) ob_get_clean();

        return UploadedFile::fake()->createWithContent('foto.png', $bytes.$extra);
    }

    private function jpgConExif(): UploadedFile
    {
        $im = imagecreatetruecolor(8, 8);
        ob_start();
        imagejpeg($im);
        $bytes = (string) ob_get_clean();
        // Inserta un segmento APP1 «Exif» con datos reconocibles tras el SOI.
        $exif = "Exif\0\0GPS-LAT-11.24N-LON-74.19W";
        $segmento = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;

        return UploadedFile::fake()->createWithContent('foto.jpg', substr($bytes, 0, 2).$segmento.substr($bytes, 2));
    }

    public function test_exige_sesion_y_permiso(): void
    {
        $this->postJson(self::SUBIR, ['imagen' => $this->png(), 'alt' => 'x'])->assertUnauthorized();

        $sinPermiso = User::factory()->create();
        $this->actingAs($sinPermiso)->postJson(self::SUBIR, ['imagen' => $this->png(), 'alt' => 'x'])->assertForbidden();
    }

    #[DataProvider('permisosQueEditan')]
    public function test_pueden_subir_quienes_gestionan_contenido(string $permiso): void
    {
        $this->actingAs($this->usuario($permiso))
            ->postJson(self::SUBIR, ['imagen' => $this->png(), 'alt' => 'Alcalde en la sede'])
            ->assertCreated();
    }

    /** @return array<string, array{string}> */
    public static function permisosQueEditan(): array
    {
        return ['noticias editar' => ['noticias.editar'], 'noticias crear' => ['noticias.crear'], 'politicas' => ['politicas.editar'], 'entidad' => ['entidad.editar']];
    }

    public function test_sube_y_devuelve_la_url_publica_y_el_alt(): void
    {
        $r = $this->actingAs($this->usuario())
            ->postJson(self::SUBIR, ['imagen' => $this->png(), 'alt' => 'Alcalde en la sede'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.alt', 'Alcalde en la sede');

        $uuid = (string) $r->json('data.uuid');
        $this->assertSame('/api/v1/contenido/imagenes/'.$uuid, $r->json('data.url'));

        $fila = FileMedia::where('uuid', $uuid)->firstOrFail();
        $this->assertSame('contenido', $fila->collection);
        $this->assertSame('Alcalde en la sede', ($fila->metadata ?? [])['alt'] ?? null);
    }

    public function test_la_imagen_se_recodifica_y_pierde_el_exif(): void
    {
        $r = $this->actingAs($this->usuario())
            ->postJson(self::SUBIR, ['imagen' => $this->jpgConExif(), 'alt' => 'x'])
            ->assertCreated();

        $fila = FileMedia::where('uuid', $r->json('data.uuid'))->firstOrFail();
        $guardado = (string) Storage::disk($fila->disk)->get($fila->path);

        $this->assertStringNotContainsString('GPS-LAT', $guardado);
        $this->assertStringNotContainsString('Exif', $guardado);
        $this->assertNotFalse(@imagecreatefromstring($guardado), 'Sigue siendo una imagen válida');
    }

    public function test_un_poliglota_con_php_al_final_no_conserva_el_php(): void
    {
        $r = $this->actingAs($this->usuario())
            ->postJson(self::SUBIR, ['imagen' => $this->png(extra: '<?php system($_GET["c"]); ?>'), 'alt' => 'x'])
            ->assertCreated();

        $fila = FileMedia::where('uuid', $r->json('data.uuid'))->firstOrFail();

        $this->assertStringNotContainsString('<?php', (string) Storage::disk($fila->disk)->get($fila->path));
    }

    public function test_el_alt_es_obligatorio(): void
    {
        $u = $this->usuario();

        $this->actingAs($u)->postJson(self::SUBIR, ['imagen' => $this->png()])->assertUnprocessable()->assertJsonValidationErrors('alt');
        $this->actingAs($u)->postJson(self::SUBIR, ['imagen' => $this->png(), 'alt' => '   '])->assertUnprocessable()->assertJsonValidationErrors('alt');
    }

    public function test_rechaza_lo_que_no_es_una_imagen_permitida(): void
    {
        $u = $this->actingAs($this->usuario());

        $php = UploadedFile::fake()->createWithContent('foto.png', '<?php echo 1;');
        $svg = UploadedFile::fake()->createWithContent('a.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>');
        $gif = UploadedFile::fake()->createWithContent('a.gif', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
        $pdf = UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\n");

        foreach ([$php, $svg, $gif, $pdf] as $archivo) {
            $u->postJson(self::SUBIR, ['imagen' => $archivo, 'alt' => 'x'])->assertUnprocessable()->assertJsonValidationErrors('imagen');
        }
    }

    public function test_rechaza_imagenes_demasiado_grandes(): void
    {
        $u = $this->actingAs($this->usuario());

        $pesada = UploadedFile::fake()->createWithContent('g.png', str_repeat('a', 5 * 1024 * 1024 + 1));
        $u->postJson(self::SUBIR, ['imagen' => $pesada, 'alt' => 'x'])->assertUnprocessable()->assertJsonValidationErrors('imagen');

        $u->postJson(self::SUBIR, ['imagen' => $this->png(4001, 1), 'alt' => 'x'])->assertUnprocessable()->assertJsonValidationErrors('imagen');
    }

    /** Sube una imagen como editor y la incrusta en una noticia con el estado indicado; devuelve el uuid del archivo. */
    private function imagenEnNoticia(bool $activo, string $fecha = '2026-01-01 10:00:00', bool $borrada = false): string
    {
        $uuid = (string) $this->actingAs($this->usuario())
            ->postJson(self::SUBIR, ['imagen' => $this->png(), 'alt' => 'x'])->json('data.uuid');
        $noticia = (int) DB::table('noticias')->insertGetId([
            'uuid' => (string) Str::uuid(), 'slug' => 'n-'.Str::random(8), 'titulo' => 'T',
            'descripcion_corta' => 'c', 'contenido' => '<p>x</p>', 'activo' => $activo, 'fecha_publicacion' => $fecha,
            'deleted_at' => $borrada ? now() : null, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('noticia_imagenes')->insert(['noticia_id' => $noticia, 'file_media_uuid' => $uuid, 'created_at' => now()]);
        $this->app['auth']->forgetGuards();

        return $uuid;
    }

    public function test_la_lectura_publica_sirve_la_imagen_de_una_noticia_publicada_con_cabeceras_seguras(): void
    {
        $uuid = $this->imagenEnNoticia(true);

        $r = $this->get('/api/v1/contenido/imagenes/'.$uuid)->assertOk();

        $this->assertStringStartsWith('image/', (string) $r->headers->get('Content-Type'));
        $this->assertSame('nosniff', $r->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('public', (string) $r->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=86400', (string) $r->headers->get('Cache-Control'));
        $this->assertStringContainsString('immutable', (string) $r->headers->get('Cache-Control'));
    }

    public function test_el_publico_no_ve_la_imagen_de_un_borrador_programada_eliminada_ni_sin_uso(): void
    {
        $borrador = $this->imagenEnNoticia(false);
        $programada = $this->imagenEnNoticia(true, now()->addYear()->toDateTimeString());
        $eliminada = $this->imagenEnNoticia(true, '2026-01-01 10:00:00', borrada: true);
        $sinUso = (string) $this->actingAs($this->usuario())
            ->postJson(self::SUBIR, ['imagen' => $this->png(), 'alt' => 'x'])->json('data.uuid');
        $this->app['auth']->forgetGuards();

        foreach ([$borrador, $programada, $eliminada, $sinUso] as $uuid) {
            $this->get('/api/v1/contenido/imagenes/'.$uuid)->assertNotFound();
        }
    }

    public function test_quien_edita_ve_cualquier_imagen_de_contenido_con_cache_privada(): void
    {
        $sinUso = (string) $this->actingAs($this->usuario())
            ->postJson(self::SUBIR, ['imagen' => $this->png(), 'alt' => 'x'])->json('data.uuid');

        $r = $this->actingAs($this->usuario('politicas.editar'))->get('/api/v1/contenido/imagenes/'.$sinUso)->assertOk();

        $this->assertStringContainsString('private', (string) $r->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $r->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('public', (string) $r->headers->get('Cache-Control'));
    }

    public function test_una_sesion_sin_permiso_de_contenido_se_trata_como_anonima(): void
    {
        $sinUso = (string) $this->actingAs($this->usuario())
            ->postJson(self::SUBIR, ['imagen' => $this->png(), 'alt' => 'x'])->json('data.uuid');

        $this->actingAs(User::factory()->create())->get('/api/v1/contenido/imagenes/'.$sinUso)->assertNotFound();
    }

    public function test_la_lectura_publica_no_sirve_archivos_de_otras_colecciones(): void
    {
        $usuario = $this->usuario();
        Storage::disk((string) config('filesystems.default'))->put('x/secreto.pdf', '%PDF');
        $ajeno = FileMedia::create([
            'model_type' => User::class, 'model_id' => $usuario->id,
            'collection' => 'documento_oficial', 'filename' => 'secreto.pdf', 'original_filename' => 'secreto.pdf',
            'mime_type' => 'application/pdf', 'size' => 4, 'disk' => (string) config('filesystems.default'), 'path' => 'x/secreto.pdf',
        ]);

        $this->get('/api/v1/contenido/imagenes/'.$ajeno->uuid)->assertNotFound();
        $this->get('/api/v1/contenido/imagenes/no-es-un-uuid')->assertNotFound();
    }
}
