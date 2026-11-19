<?php

declare(strict_types=1);

namespace Tests\Feature\Esquema;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Concerns\RevierteUsoDeArchivos;
use Tests\TestCase;

/**
 * 0D, D10b (mediateca, lanzamiento 3 de 3, parte B): contraer.
 *
 * Borra el dueño polimórfico, `collection` y `metadata`, y deja la huella obligatoria. Se prueba con el
 * migrador real: se retrocede a D10a, se siembra el estado «de antes» y se vuelve a subir. Lo que protege
 * cada caso: que no se borre nada si falta una huella, y que `down()` reconstruya lo que las relaciones
 * permiten sin inventar dueños.
 */
final class MediatecaContraerMigracionTest extends TestCase
{
    use RefreshDatabase;
    use RevierteUsoDeArchivos;

    private const MIGRACION = '2026_11_20_300000_mediateca_contraer.php';

    private function migracion(): mixed
    {
        return require database_path('migrations/'.self::MIGRACION);
    }

    /** @param array<string, mixed> $campos */
    private function archivo(string $uso, array $campos = []): string
    {
        $uuid = (string) Str::uuid();
        DB::table('file_media')->insert(array_merge([
            'uuid' => $uuid, 'uso' => $uso, 'filename' => $uuid.'.png', 'original_filename' => 'f.png',
            'mime_type' => 'image/png', 'size' => 1, 'disk' => 'public', 'path' => "file_media/{$uso}/{$uuid}.png",
            'hash_sha256' => hash('sha256', $uuid), 'created_at' => now(), 'updated_at' => now(),
        ], $campos));

        return $uuid;
    }

    private function noticia(?string $imagen = null, ?string $alt = null): int
    {
        return (int) DB::table('noticias')->insertGetId([
            'uuid' => (string) Str::uuid(), 'slug' => 'n-'.Str::random(8), 'titulo' => 'T', 'descripcion_corta' => 'c',
            'contenido' => '<p>t</p>', 'fecha_publicacion' => now(), 'imagen_principal_uuid' => $imagen,
            'imagen_principal_alt' => $alt, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function nulable(string $columna): string
    {
        return (string) DB::table('information_schema.columns')
            ->where('table_name', 'file_media')->where('column_name', $columna)->value('is_nullable');
    }

    public function test_up_borra_lo_viejo_el_disparador_y_deja_la_huella_obligatoria(): void
    {
        foreach (['model_type', 'model_id', 'collection', 'metadata'] as $columna) {
            $this->assertFalse(Schema::hasColumn('file_media', $columna), $columna);
        }
        $this->assertSame('NO', $this->nulable('hash_sha256'));
        $this->assertSame(0, DB::table('pg_trigger')->where('tgname', 'file_media_completar_uso')->count());
        $this->assertSame(0, DB::table('pg_proc')->where('proname', 'file_media_completar_uso')->count());
    }

    public function test_la_huella_debe_ser_un_sha256_en_hexadecimal_minuscula(): void
    {
        $this->expectException(QueryException::class);
        $this->archivo('imagen', ['hash_sha256' => str_repeat('G', 64)]);
    }

    public function test_sin_huella_la_base_rechaza_la_fila(): void
    {
        $this->expectException(QueryException::class);
        $this->archivo('imagen', ['hash_sha256' => null]);
    }

    public function test_up_se_detiene_sin_cambiar_nada_si_falta_una_huella(): void
    {
        $migracion = $this->migracion();
        $migracion->down();
        $con = $this->archivo('imagen');
        $sin = $this->archivo('imagen', ['hash_sha256' => null]);

        try {
            $migracion->up();
            $this->fail('Debió detenerse.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString($sin, $e->getMessage());
            $this->assertStringNotContainsString($con, $e->getMessage());
            $this->assertStringContainsString('archivos:calcular-hash', $e->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('file_media', 'collection'), 'No debe quedar nada a medias.');
        $this->assertSame('YES', $this->nulable('hash_sha256'));
    }

    public function test_up_se_detiene_con_una_huella_que_no_es_sha256(): void
    {
        $migracion = $this->migracion();
        $migracion->down();
        $mala = $this->archivo('imagen', ['hash_sha256' => str_repeat('A', 64)]);

        try {
            $migracion->up();
            $this->fail('Debió detenerse: las mayúsculas no son la forma que calcula el sistema.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString($mala, $e->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('file_media', 'model_type'));
    }

    public function test_up_conserva_todas_las_filas(): void
    {
        $migracion = $this->migracion();
        $migracion->down();
        $uuids = [$this->archivo('imagen'), $this->archivo('contenido'), $this->archivo('documento')];

        $migracion->up();

        $this->assertEqualsCanonicalizing($uuids, DB::table('file_media')->pluck('uuid')->all());
    }

    public function test_down_reconstruye_dueno_collection_y_alt_desde_las_relaciones(): void
    {
        $imagen = $this->archivo('imagen');
        $noticia = $this->noticia($imagen, 'Vista de la bahía');
        $usuario = (int) DB::table('users')->insertGetId([
            'name' => 'U', 'email' => 'u-'.Str::random(6).'@example.com', 'password' => 'x', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $contenido = $this->archivo('contenido', ['subido_por' => $usuario]);
        $suelto = $this->archivo('documento');

        $this->migracion()->down();

        $fila = DB::table('file_media')->where('uuid', $imagen)->first();
        $this->assertNotNull($fila);
        $this->assertSame('App\\Models\\Noticia', $fila->model_type);
        $this->assertSame($noticia, (int) $fila->model_id);
        $this->assertSame('imagen', $fila->collection);
        $this->assertSame('Vista de la bahía', json_decode((string) $fila->metadata, true)['alt']);

        $deContenido = DB::table('file_media')->where('uuid', $contenido)->first();
        $this->assertNotNull($deContenido);
        $this->assertSame('App\\Models\\User', $deContenido->model_type);
        $this->assertSame($usuario, (int) $deContenido->model_id);

        $sinDueno = DB::table('file_media')->where('uuid', $suelto)->first();
        $this->assertNotNull($sinDueno);
        $this->assertNull($sinDueno->model_type, 'Un archivo sin uso no tiene un dueño que se pueda conocer: no se inventa.');
        $this->assertSame('documento-oficial', $sinDueno->collection);
        $this->assertSame('YES', $this->nulable('hash_sha256'));
    }

    public function test_down_deja_el_disparador_para_que_el_codigo_anterior_siga_subiendo(): void
    {
        $this->migracion()->down();

        $uuid = (string) Str::uuid();
        DB::table('file_media')->insert([
            'uuid' => $uuid, 'model_type' => 'App\\Models\\Politica', 'model_id' => 1, 'collection' => 'documento-oficial',
            'filename' => 'a.pdf', 'original_filename' => 'a.pdf', 'mime_type' => 'application/pdf', 'size' => 1,
            'disk' => 'public', 'path' => 'x/a.pdf', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame('documento', DB::table('file_media')->where('uuid', $uuid)->value('uso'));
    }
}
