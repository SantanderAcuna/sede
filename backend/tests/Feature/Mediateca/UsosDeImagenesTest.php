<?php

declare(strict_types=1);

namespace Tests\Feature\Mediateca;

use App\Models\User;
use App\Support\Acceso\CatalogoPermisos;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 0D, D9: `noticia_imagenes` refleja EXACTAMENTE las imágenes que el cuerpo de la noticia incrusta.
 *
 * Se recalcula al guardar el contenido (ya saneado): lo que el cuerpo ya no usa se desvincula (y toca
 * `file_media.updated_at` para que la limpieza cuente los 7 días desde ahí), lo que usa se vincula. Una
 * referencia a un archivo que no existe o que no es de la colección «contenido» da 422 y no guarda nada.
 */
final class UsosDeImagenesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        Storage::fake((string) config('filesystems.default'));
    }

    private function editor(): User
    {
        $u = User::factory()->create();
        $u->givePermissionTo([...CatalogoPermisos::de('noticias'), 'archivos.ver']);

        return $u;
    }

    private function subirImagen(User $u, string $alt = 'Foto'): string
    {
        $im = imagecreatetruecolor(8, 8);
        imagefill($im, 0, 0, random_int(1, 0xFFFFFF)); // distinta cada vez: la misma imagen se reutilizaría
        ob_start();
        imagepng($im);
        $bytes = (string) ob_get_clean();

        return (string) $this->actingAs($u)->postJson('/api/v1/panel/contenido/imagenes', [
            'imagen' => UploadedFile::fake()->createWithContent('f.png', $bytes), 'alt' => $alt,
        ])->assertCreated()->json('data.uuid');
    }

    private function cuerpo(string ...$uuids): string
    {
        $imgs = array_map(static fn (string $u): string => '<img src="/api/v1/contenido/imagenes/'.$u.'" alt="Foto">', $uuids);

        return '<p>Texto</p>'.implode('', $imgs);
    }

    /** @return array<string, mixed> */
    private function datos(string $contenido, string $titulo = 'Titulo'): array
    {
        return [
            'titulo' => $titulo, 'descripcion_corta' => 'Corta', 'contenido' => $contenido,
            'fecha_publicacion' => '2026-11-01T10:00:00-05:00',
        ];
    }

    /** @return list<string> */
    private function usos(string $noticiaUuid): array
    {
        $id = (int) DB::table('noticias')->where('uuid', $noticiaUuid)->value('id');

        return DB::table('noticia_imagenes')->where('noticia_id', $id)->orderBy('file_media_uuid')->pluck('file_media_uuid')->all();
    }

    public function test_al_crear_se_vinculan_las_imagenes_del_cuerpo_sin_repetidas(): void
    {
        $u = $this->editor();
        [$a, $b] = [$this->subirImagen($u), $this->subirImagen($u)];
        $mayus = strtoupper($a);

        $r = $this->actingAs($u)->postJson('/api/v1/panel/noticias', $this->datos($this->cuerpo($a, $b, $a).'<img src="/api/v1/contenido/imagenes/'.$mayus.'" alt="x">'))
            ->assertCreated();

        $esperadas = [$a, $b];
        sort($esperadas);
        $this->assertSame($esperadas, $this->usos((string) $r->json('data.uuid')));
    }

    public function test_al_editar_se_desvincula_lo_que_ya_no_se_usa_y_se_toca_su_fecha(): void
    {
        $u = $this->editor();
        [$a, $b] = [$this->subirImagen($u), $this->subirImagen($u)];
        $uuid = (string) $this->actingAs($u)->postJson('/api/v1/panel/noticias', $this->datos($this->cuerpo($a, $b)))->json('data.uuid');
        DB::table('file_media')->whereIn('uuid', [$a, $b])->update(['updated_at' => now()->subDays(30)]);

        $this->actingAs($u)->patchJson("/api/v1/panel/noticias/{$uuid}", $this->datos($this->cuerpo($b)))->assertOk();

        $this->assertSame([$b], $this->usos($uuid));
        $tocado = (string) DB::table('file_media')->where('uuid', $a)->value('updated_at');
        $this->assertTrue(now()->subMinute()->lt(Carbon::parse($tocado, 'UTC')), 'Al desvincular se renueva updated_at.');
        $intacto = (string) DB::table('file_media')->where('uuid', $b)->value('updated_at');
        $this->assertTrue(now()->subDays(29)->gt(Carbon::parse($intacto, 'UTC')), 'Lo que sigue en uso no se toca.');
    }

    public function test_editar_sin_enviar_contenido_no_cambia_los_usos(): void
    {
        $u = $this->editor();
        $a = $this->subirImagen($u);
        $uuid = (string) $this->actingAs($u)->postJson('/api/v1/panel/noticias', $this->datos($this->cuerpo($a)))->json('data.uuid');

        $this->actingAs($u)->patchJson("/api/v1/panel/noticias/{$uuid}", ['titulo' => 'Otro titulo'])->assertOk();

        $this->assertSame([$a], $this->usos($uuid));
    }

    public function test_una_imagen_inexistente_da_422_y_no_se_guarda_nada(): void
    {
        $u = $this->editor();
        $fantasma = '11111111-1111-4111-8111-111111111111';

        $this->actingAs($u)->postJson('/api/v1/panel/noticias', $this->datos($this->cuerpo($fantasma)))
            ->assertStatus(422)->assertJsonValidationErrors('contenido');

        $this->assertSame(0, DB::table('noticias')->count());
    }

    public function test_una_imagen_de_otra_coleccion_no_se_puede_incrustar(): void
    {
        $u = $this->editor();
        $ajena = '22222222-2222-4222-8222-222222222222';
        DB::table('file_media')->insert([
            'uuid' => $ajena, 'model_type' => 'App\\Models\\Banner', 'model_id' => 1, 'collection' => 'imagen',
            'filename' => 'x.png', 'original_filename' => 'x.png', 'mime_type' => 'image/png', 'size' => 1,
            'disk' => 'local', 'path' => 'x/x.png', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($u)->postJson('/api/v1/panel/noticias', $this->datos($this->cuerpo($ajena)))
            ->assertStatus(422)->assertJsonValidationErrors('contenido');
    }

    public function test_una_imagen_que_el_saneador_descarta_no_cuenta_como_uso(): void
    {
        $u = $this->editor();
        $a = $this->subirImagen($u);
        // Sin alt: el saneador la quita, así que no puede quedar vinculada.
        $cuerpo = '<p>x</p><img src="/api/v1/contenido/imagenes/'.$a.'">';

        $uuid = (string) $this->actingAs($u)->postJson('/api/v1/panel/noticias', $this->datos($cuerpo))->assertCreated()->json('data.uuid');

        $this->assertSame([], $this->usos($uuid));
    }

    public function test_subir_una_imagen_de_contenido_registra_quien_la_subio(): void
    {
        $u = $this->editor();
        $a = $this->subirImagen($u);

        $this->assertSame($u->id, (int) DB::table('file_media')->where('uuid', $a)->value('subido_por'));
    }
}
