<?php

declare(strict_types=1);

namespace Tests\Feature\Seguridad;

use App\Models\Entidad;
use App\Models\FileMedia;
use App\Models\User;
use Database\Seeders\EntidadSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Subida de archivos coherente y segura (H-13, T-12).
 *
 * - AC-12.1: el archivo termina en el disco configurado.
 * - AC-12.2: un SVG con script no se acepta.
 * - AC-12.3: la extensión guardada sale del contenido, no del nombre del cliente.
 * - AC-12.4: pasado el máximo hay un 422 con mensaje, no un error opaco.
 */
final class SubidaDeArchivosTest extends TestCase
{
    use RefreshDatabase;

    /** PNG de 1×1 píxel: bytes reales, para que el tipo se detecte por contenido. */
    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private User $superAdmin;

    private Entidad $entidad;

    protected function setUp(): void
    {
        parent::setUp();

        // El `.env` de CI trae `FILESYSTEM_DISK=s3` (el de `.env.example`): sin fijar el
        // disco, la subida intentaría hablar con S3 de verdad.
        config(['filesystems.default' => 'local']);

        $this->seed([EntidadSeeder::class, PermissionSeeder::class, SuperAdminSeeder::class]);
        $this->superAdmin = User::query()->where('email', config('superadmin.email'))->firstOrFail();
        $this->entidad = Entidad::firstOrFail();
    }

    /** Un archivo real en disco, con el nombre que mande el «cliente». */
    private function archivoReal(string $nombreCliente, string $contenido): UploadedFile
    {
        $ruta = tempnam(sys_get_temp_dir(), 'sede');
        file_put_contents($ruta, $contenido);

        // `test: true` evita la comprobación de «subido por HTTP» y deja a `finfo`
        // mirar los bytes, que es lo que pasa en producción.
        return new UploadedFile($ruta, $nombreCliente, null, null, true);
    }

    private function rutaSubida(): string
    {
        return "/api/v1/panel/entidad/{$this->entidad->uuid}/archivos";
    }

    /**
     * @return array{archivo: UploadedFile, collection: string}
     */
    private function datos(UploadedFile $archivo): array
    {
        return ['archivo' => $archivo, 'collection' => 'documentos'];
    }

    public function test_el_archivo_termina_en_el_disco_por_defecto_de_la_configuracion(): void
    {
        foreach (['local', 's3'] as $disco) {
            config(['filesystems.default' => $disco]);
            Storage::fake($disco);

            $respuesta = $this->actingAs($this->superAdmin, 'sanctum')->postJson($this->rutaSubida(), $this->datos($this->archivoReal('acta.png', (string) base64_decode(self::PNG_1X1))));
            $respuesta->assertOk();

            $fila = FileMedia::query()->where('uuid', $respuesta->json('data.uuid'))->firstOrFail();

            $this->assertSame($disco, $fila->disk);
            Storage::disk($disco)->assertExists($fila->path);
        }
    }

    public function test_un_svg_con_script_se_rechaza(): void
    {
        Storage::fake('local');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.cookie)</script></svg>';

        $this->actingAs($this->superAdmin, 'sanctum')->postJson($this->rutaSubida(), $this->datos($this->archivoReal('escudo.svg', $svg)))
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(0, FileMedia::query()->count());
    }

    public function test_la_extension_guardada_sale_del_contenido_y_no_del_nombre_del_cliente(): void
    {
        Storage::fake('local');

        // JPEG de verdad con nombre `.png`: el tipo real es jpg.
        $jpeg = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9";
        $respuesta = $this->actingAs($this->superAdmin, 'sanctum')->postJson($this->rutaSubida(), $this->datos($this->archivoReal('foto.png', $jpeg)));
        $respuesta->assertOk();

        $fila = FileMedia::query()->where('uuid', $respuesta->json('data.uuid'))->firstOrFail();

        $this->assertStringEndsWith('.jpg', $fila->filename);
        // El nombre original se conserva como dato, no como parte de la ruta.
        $this->assertSame('foto.png', $fila->original_filename);
    }

    public function test_un_doble_nombre_con_php_no_llega_a_la_ruta_guardada(): void
    {
        Storage::fake('local');

        $respuesta = $this->actingAs($this->superAdmin, 'sanctum')->postJson($this->rutaSubida(), $this->datos($this->archivoReal('foto.php.png', (string) base64_decode(self::PNG_1X1))));
        $respuesta->assertOk();

        $fila = FileMedia::query()->where('uuid', $respuesta->json('data.uuid'))->firstOrFail();

        $this->assertStringEndsWith('.png', $fila->filename);
        $this->assertStringNotContainsString('.php', $fila->filename);
        $this->assertStringNotContainsString('.php', $fila->path);
    }

    public function test_un_archivo_con_nombre_php_se_rechaza_aunque_su_contenido_sea_una_imagen(): void
    {
        Storage::fake('local');

        $this->actingAs($this->superAdmin, 'sanctum')->postJson($this->rutaSubida(), $this->datos($this->archivoReal('foto.php', (string) base64_decode(self::PNG_1X1))))
            ->assertStatus(422);

        $this->assertSame(0, FileMedia::query()->count());
    }

    public function test_un_script_php_disfrazado_de_imagen_no_se_acepta(): void
    {
        Storage::fake('local');

        $this->actingAs($this->superAdmin, 'sanctum')->postJson($this->rutaSubida(), $this->datos($this->archivoReal('foto.png', '<?php system($_GET["c"]); ?>')))
            ->assertStatus(422);

        $this->assertSame(0, FileMedia::query()->count());
    }

    public function test_un_archivo_de_mas_de_20_mb_recibe_422_con_mensaje(): void
    {
        Storage::fake('local');

        $respuesta = $this->actingAs($this->superAdmin, 'sanctum')->postJson($this->rutaSubida(), $this->datos(UploadedFile::fake()->create('grande.pdf', 21 * 1024, 'application/pdf')));

        $respuesta->assertStatus(422)->assertJsonPath('success', false);
        $this->assertStringContainsString('20 MB', (string) json_encode($respuesta->json('errors')));
    }

    public function test_la_url_de_un_archivo_en_disco_local_va_firmada(): void
    {
        Storage::fake('local');
        $respuesta = $this->actingAs($this->superAdmin, 'sanctum')->postJson($this->rutaSubida(), $this->datos($this->archivoReal('acta.png', (string) base64_decode(self::PNG_1X1))));
        $respuesta->assertOk();

        $url = (string) $respuesta->json('data.url');

        $this->assertStringContainsString('/storage/', $url);
        $this->assertStringContainsString('signature=', $url);
        $this->assertStringContainsString('expires=', $url);
    }
}
