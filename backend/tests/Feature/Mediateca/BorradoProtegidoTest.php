<?php

declare(strict_types=1);

namespace Tests\Feature\Mediateca;

use App\Exceptions\ArchivoEnUso;
use App\Models\FileMedia;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 0D, D9: un archivo en uso no se borra.
 *
 * La base lo respalda con claves foráneas `ON DELETE RESTRICT` (PostgreSQL 18 lo reporta como `23001` y la 16
 * como `23503`); el código lo detecta antes y, si alguien empieza a usarlo justo en medio, traduce el error de la
 * base a una excepción de dominio. El controlador responde 409 (no 500: el `catch (\Throwable)` no lo traga).
 * Un archivo de una noticia o banner borrados lógicamente sigue contando como uso.
 */
final class BorradoProtegidoTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        Storage::fake((string) config('filesystems.default'));
    }

    /** @return array{0: string, 1: string} uuid y ruta del archivo en disco */
    private function archivo(string $uso = 'contenido'): array
    {
        $uuid = (string) Str::uuid();
        $disco = (string) config('filesystems.default');
        $ruta = "file_media/prueba/{$uuid}.png";
        Storage::disk($disco)->put($ruta, (string) base64_decode(self::PNG, true));

        DB::table('file_media')->insert([
            'uuid' => $uuid, 'uso' => $uso,
            'filename' => "{$uuid}.png", 'original_filename' => 'foto.png', 'mime_type' => 'image/png', 'size' => 70,
            'disk' => $disco, 'path' => $ruta,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$uuid, $ruta];
    }

    private function noticia(?string $imagen = null, bool $borrada = false): int
    {
        return (int) DB::table('noticias')->insertGetId([
            'uuid' => (string) Str::uuid(), 'slug' => 'n-'.Str::random(8), 'titulo' => 'T', 'descripcion_corta' => 'c',
            'contenido' => '<p>x</p>', 'fecha_publicacion' => now(), 'imagen_principal_uuid' => $imagen,
            'deleted_at' => $borrada ? now() : null, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function modelo(string $uuid): FileMedia
    {
        return FileMedia::query()->where('uuid', $uuid)->firstOrFail();
    }

    private function gestor(): User
    {
        $u = User::factory()->create();
        $u->givePermissionTo('archivos.eliminar');

        return $u;
    }

    public function test_un_archivo_sin_uso_se_borra_primero_de_la_base_y_luego_del_disco(): void
    {
        [$uuid, $ruta] = $this->archivo();

        $this->modelo($uuid)->deleteFully();

        $this->assertFalse(DB::table('file_media')->where('uuid', $uuid)->exists());
        Storage::disk((string) config('filesystems.default'))->assertMissing($ruta);
    }

    public function test_la_imagen_principal_de_una_noticia_no_se_borra_aunque_la_noticia_este_eliminada(): void
    {
        [$uuid, $ruta] = $this->archivo('imagen');
        $this->noticia($uuid, borrada: true);

        $this->expectException(ArchivoEnUso::class);
        try {
            $this->modelo($uuid)->deleteFully();
        } finally {
            $this->assertTrue(DB::table('file_media')->where('uuid', $uuid)->exists());
            Storage::disk((string) config('filesystems.default'))->assertExists($ruta);
        }
    }

    public function test_la_imagen_de_un_banner_no_se_borra(): void
    {
        [$uuid] = $this->archivo('imagen');
        DB::table('banners')->insert(['uuid' => (string) Str::uuid(), 'titulo' => 'B', 'imagen_uuid' => $uuid, 'created_at' => now(), 'updated_at' => now()]);

        $this->expectException(ArchivoEnUso::class);
        $this->modelo($uuid)->deleteFully();
    }

    public function test_una_imagen_incrustada_en_una_noticia_no_se_borra(): void
    {
        [$uuid] = $this->archivo();
        $noticia = $this->noticia();
        DB::table('noticia_imagenes')->insert(['noticia_id' => $noticia, 'file_media_uuid' => $uuid]);

        $this->expectException(ArchivoEnUso::class);
        $this->modelo($uuid)->deleteFully();
    }

    public function test_un_documento_vinculado_a_una_politica_no_se_borra(): void
    {
        [$uuid] = $this->archivo('documento');
        $politica = (int) DB::table('politicas')->insertGetId([
            'uuid' => (string) Str::uuid(), 'slug' => 'p-'.Str::random(6), 'nombre' => 'P', 'secciones' => '[]',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('politica_documentos')->insert(['politica_id' => $politica, 'rol' => 'oficial', 'file_media_uuid' => $uuid]);

        $this->expectException(ArchivoEnUso::class);
        $this->modelo($uuid)->deleteFully();
    }

    public function test_si_empiezan_a_usarlo_justo_en_medio_el_error_de_la_base_se_traduce_y_la_transaccion_sigue_viva(): void
    {
        [$uuid, $ruta] = $this->archivo();
        $noticia = $this->noticia();

        // La comprobación previa ve el archivo libre; justo antes del DELETE alguien lo vincula (la carrera real).
        $insertado = false;
        DB::beforeExecuting(function (string $sql) use (&$insertado, $uuid, $noticia): void {
            if (! $insertado && str_starts_with(strtolower($sql), 'delete from "file_media"')) {
                $insertado = true;
                DB::table('noticia_imagenes')->insert(['noticia_id' => $noticia, 'file_media_uuid' => $uuid]);
            }
        });

        try {
            $this->modelo($uuid)->deleteFully();
            $this->fail('La base debió impedir el borrado.');
        } catch (ArchivoEnUso $e) {
            $this->assertSame($uuid, $e->uuid);
            $previo = $e->getPrevious();
            $this->assertInstanceOf(QueryException::class, $previo);
            $this->assertContains($previo->errorInfo[0] ?? null, ['23001', '23503']);
        }

        $this->assertTrue(DB::table('file_media')->where('uuid', $uuid)->exists(), 'La fila sigue y la transacción de la prueba no quedó abortada.');
        Storage::disk((string) config('filesystems.default'))->assertExists($ruta);
    }

    public function test_la_api_responde_409_y_no_500_cuando_el_archivo_esta_en_uso(): void
    {
        [$uuid, $ruta] = $this->archivo('imagen');
        $this->noticia($uuid);

        $this->actingAs($this->gestor())->deleteJson("/api/v1/panel/archivos/{$uuid}")
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'El archivo está en uso y no se puede eliminar.');

        $this->assertTrue(DB::table('file_media')->where('uuid', $uuid)->exists());
        Storage::disk((string) config('filesystems.default'))->assertExists($ruta);
    }

    public function test_la_api_borra_un_archivo_que_nadie_usa(): void
    {
        [$uuid, $ruta] = $this->archivo();

        $this->actingAs($this->gestor())->deleteJson("/api/v1/panel/archivos/{$uuid}")->assertOk();

        $this->assertFalse(DB::table('file_media')->where('uuid', $uuid)->exists());
        Storage::disk((string) config('filesystems.default'))->assertMissing($ruta);
    }
}
