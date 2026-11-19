<?php

declare(strict_types=1);

namespace Tests\Feature\Mediateca;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * 0D, D9: subir dos veces la misma imagen de contenido reutiliza el archivo (misma huella tras re-codificar).
 *
 * El reuso renueva `updated_at` de forma atómica (con la hora de PHP) para que la limpieza no se lo lleve en medio,
 * comprueba que el archivo exista en el disco y devuelve el alt RECIBIDO (el alt vive en cada noticia, no en el
 * archivo compartido).
 */
final class ReutilizoDeImagenesTest extends TestCase
{
    use RefreshDatabase;

    private const SUBIR = '/api/v1/panel/contenido/imagenes';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        Storage::fake((string) config('filesystems.default'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function editor(): User
    {
        $u = User::factory()->create();
        $u->givePermissionTo('noticias.editar');

        return $u;
    }

    private function png(int $color = 0): UploadedFile
    {
        $im = imagecreatetruecolor(8, 8);
        imagefill($im, 0, 0, $color);
        ob_start();
        imagepng($im);

        return UploadedFile::fake()->createWithContent('foto.png', (string) ob_get_clean());
    }

    private function subir(User $u, UploadedFile $f, string $alt = 'Foto'): TestResponse
    {
        return $this->actingAs($u)->postJson(self::SUBIR, ['imagen' => $f, 'alt' => $alt])->assertCreated();
    }

    public function test_la_misma_imagen_subida_dos_veces_es_un_solo_archivo(): void
    {
        $u = $this->editor();

        $a = $this->subir($u, $this->png(0x336699))->json('data.uuid');
        $b = $this->subir($u, $this->png(0x336699))->json('data.uuid');

        $this->assertSame($a, $b);
        $this->assertSame(1, DB::table('file_media')->where('collection', 'contenido')->count());
    }

    public function test_una_imagen_distinta_es_otro_archivo(): void
    {
        $u = $this->editor();

        $a = $this->subir($u, $this->png(0x336699))->json('data.uuid');
        $b = $this->subir($u, $this->png(0x993366))->json('data.uuid');

        $this->assertNotSame($a, $b);
    }

    public function test_el_reuso_devuelve_el_alt_recibido_y_no_el_guardado(): void
    {
        $u = $this->editor();
        $this->subir($u, $this->png(0x336699), 'Primer texto');

        $r = $this->subir($u, $this->png(0x336699), 'Segundo texto');

        $r->assertJsonPath('data.alt', 'Segundo texto');
    }

    public function test_el_reuso_renueva_updated_at_con_la_hora_de_php(): void
    {
        $u = $this->editor();
        $uuid = $this->subir($u, $this->png(0x336699))->json('data.uuid');
        DB::table('file_media')->where('uuid', $uuid)->update(['updated_at' => '2026-01-01 00:00:00']);
        Carbon::setTestNow('2026-11-20 12:00:00');

        $this->subir($u, $this->png(0x336699));

        $this->assertSame('2026-11-20 12:00:00', Carbon::parse((string) DB::table('file_media')->where('uuid', $uuid)->value('updated_at'), 'UTC')->utc()->format('Y-m-d H:i:s'));
    }

    public function test_si_el_archivo_ya_no_esta_en_el_disco_se_sube_uno_nuevo(): void
    {
        $u = $this->editor();
        $a = $this->subir($u, $this->png(0x336699))->json('data.uuid');
        Storage::disk((string) config('filesystems.default'))->delete((string) DB::table('file_media')->where('uuid', $a)->value('path'));

        $b = $this->subir($u, $this->png(0x336699))->json('data.uuid');

        $this->assertNotSame($a, $b);
        Storage::disk((string) config('filesystems.default'))->assertExists((string) DB::table('file_media')->where('uuid', $b)->value('path'));
    }

    public function test_el_reuso_solo_aplica_a_la_coleccion_contenido(): void
    {
        $u = $this->editor();
        $a = $this->subir($u, $this->png(0x336699))->json('data.uuid');
        DB::table('file_media')->where('uuid', $a)->update(['collection' => 'imagen']);

        $b = $this->subir($u, $this->png(0x336699))->json('data.uuid');

        $this->assertNotSame($a, $b);
    }
}
