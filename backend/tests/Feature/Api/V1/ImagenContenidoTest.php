<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\FileMedia;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    private function usuario(string $permiso = 'noticias.gestionar'): User
    {
        $u = User::factory()->create();
        $u->givePermissionTo($permiso);

        return $u;
    }

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
        return ['noticias' => ['noticias.gestionar'], 'politicas' => ['politicas.gestionar'], 'entidad' => ['entidad.gestionar']];
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
        $this->assertSame('Alcalde en la sede', $fila->metadata['alt']);
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

    public function test_la_lectura_publica_sirve_la_imagen_con_cabeceras_seguras(): void
    {
        $uuid = (string) $this->actingAs($this->usuario())
            ->postJson(self::SUBIR, ['imagen' => $this->png(), 'alt' => 'x'])->json('data.uuid');

        $this->app['auth']->forgetGuards();
        $r = $this->get('/api/v1/contenido/imagenes/'.$uuid)->assertOk();

        $this->assertStringStartsWith('image/', (string) $r->headers->get('Content-Type'));
        $this->assertSame('nosniff', $r->headers->get('X-Content-Type-Options'));
    }

    public function test_la_lectura_publica_no_sirve_archivos_de_otras_colecciones(): void
    {
        $usuario = $this->usuario();
        Storage::disk((string) config('filesystems.default'))->put('x/secreto.pdf', '%PDF');
        $ajeno = FileMedia::create([
            'model_type' => User::class, 'model_id' => $usuario->id, 'model_uuid' => $usuario->uuid,
            'collection' => 'documento_oficial', 'filename' => 'secreto.pdf', 'original_filename' => 'secreto.pdf',
            'mime_type' => 'application/pdf', 'size' => 4, 'disk' => (string) config('filesystems.default'), 'path' => 'x/secreto.pdf',
        ]);

        $this->get('/api/v1/contenido/imagenes/'.$ajeno->uuid)->assertNotFound();
        $this->get('/api/v1/contenido/imagenes/no-es-un-uuid')->assertNotFound();
    }
}
