<?php

declare(strict_types=1);

namespace Tests\Feature\Esquema;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use stdClass;
use Tests\TestCase;

/**
 * 0D, D10a (mediateca, lanzamiento 3 de 3, parte A): `uso` reemplaza a `collection`.
 *
 * Se prueba con el migrador real. Lo que protege cada caso:
 *  - el CHECK impide valores fuera de `imagen`, `contenido`, `documento`;
 *  - el disparador deja que el código anterior (que solo escribe `collection`) siga subiendo archivos
 *    durante la ventana del despliegue, y que el nuevo (que solo escribe `uso`) no deje filas que D9
 *    no entienda si hay que volver atrás;
 *  - una colección que el sistema nunca escribió detiene la migración sin cambiar nada.
 */
final class MediatecaUsoMigracionTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRACION = '2026_11_20_200000_mediateca_uso.php';

    private function migracion(): mixed
    {
        return require database_path('migrations/'.self::MIGRACION);
    }

    /** @param array<string, mixed> $campos */
    private function insertar(array $campos): string
    {
        $uuid = (string) Str::uuid();
        DB::table('file_media')->insert(array_merge([
            'uuid' => $uuid, 'filename' => $uuid.'.png', 'original_filename' => 'f.png', 'mime_type' => 'image/png',
            'size' => 1, 'disk' => 'public', 'path' => "file_media/x/{$uuid}.png", 'created_at' => now(), 'updated_at' => now(),
        ], $campos));

        return $uuid;
    }

    private function nulable(string $columna): string
    {
        /** @var stdClass $fila */
        $fila = DB::selectOne(
            'select is_nullable from information_schema.columns where table_schema = ? and table_name = ? and column_name = ?',
            ['public', 'file_media', $columna],
        );

        return $fila->is_nullable;
    }

    public function test_up_deja_uso_obligatorio_con_check_y_las_columnas_viejas_opcionales(): void
    {
        $this->assertSame('NO', $this->nulable('uso'));
        foreach (['model_type', 'model_id', 'collection'] as $columna) {
            $this->assertSame('YES', $this->nulable($columna), $columna);
        }

        $this->expectException(QueryException::class);
        $this->insertar(['uso' => 'logos']);
    }

    public function test_el_indice_unico_compuesto_ya_no_existe(): void
    {
        $this->assertSame(0, DB::table('pg_indexes')->where('indexname', 'file_media_model_collection_filename_unique')->count());
    }

    public function test_el_insert_del_codigo_anterior_solo_con_collection_rellena_uso(): void
    {
        $doc = $this->insertar(['model_type' => 'App\\Models\\Politica', 'model_id' => 1, 'collection' => 'documento-oficial']);
        $img = $this->insertar(['model_type' => 'App\\Models\\Banner', 'model_id' => 1, 'collection' => 'imagen']);
        $con = $this->insertar(['model_type' => 'App\\Models\\User', 'model_id' => 1, 'collection' => 'contenido']);

        $this->assertSame('documento', DB::table('file_media')->where('uuid', $doc)->value('uso'));
        $this->assertSame('imagen', DB::table('file_media')->where('uuid', $img)->value('uso'));
        $this->assertSame('contenido', DB::table('file_media')->where('uuid', $con)->value('uso'));
    }

    public function test_el_insert_del_codigo_nuevo_solo_con_uso_rellena_collection_para_poder_volver_atras(): void
    {
        $doc = $this->insertar(['uso' => 'documento']);
        $img = $this->insertar(['uso' => 'imagen']);

        $this->assertSame('documento-oficial', DB::table('file_media')->where('uuid', $doc)->value('collection'));
        $this->assertSame('imagen', DB::table('file_media')->where('uuid', $img)->value('collection'));
    }

    public function test_sin_uso_ni_collection_la_base_rechaza_la_fila(): void
    {
        $this->expectException(QueryException::class);
        $this->insertar([]);
    }

    public function test_up_rellena_uso_de_las_filas_que_ya_existian(): void
    {
        $migracion = $this->migracion();
        $migracion->down();

        $doc = $this->insertar(['model_type' => 'App\\Models\\Politica', 'model_id' => 1, 'collection' => 'documento-oficial']);
        $con = $this->insertar(['model_type' => 'App\\Models\\User', 'model_id' => 1, 'collection' => 'contenido']);

        $migracion->up();

        $this->assertSame('documento', DB::table('file_media')->where('uuid', $doc)->value('uso'));
        $this->assertSame('contenido', DB::table('file_media')->where('uuid', $con)->value('uso'));
    }

    public function test_up_se_detiene_sin_cambiar_nada_con_una_coleccion_que_el_sistema_no_usa(): void
    {
        $migracion = $this->migracion();
        $migracion->down();

        $buena = $this->insertar(['model_type' => 'App\\Models\\Banner', 'model_id' => 1, 'collection' => 'imagen']);
        $rara = $this->insertar(['model_type' => 'App\\Models\\Banner', 'model_id' => 2, 'collection' => 'logos']);

        try {
            $migracion->up();
            $this->fail('Debió detenerse.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString($rara, $e->getMessage());
            $this->assertStringNotContainsString($buena, $e->getMessage());
        }

        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('file_media', 'uso'), 'No debe quedar nada a medias.');
    }

    public function test_down_vuelve_a_la_forma_anterior_y_conserva_las_filas_con_dueno(): void
    {
        $migracion = $this->migracion();
        $conDueno = $this->insertar(['model_type' => 'App\\Models\\Banner', 'model_id' => 1, 'collection' => 'imagen']);

        $migracion->down();

        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('file_media', 'uso'));
        $this->assertSame('NO', $this->nulable('model_type'));
        $this->assertSame('NO', $this->nulable('collection'));
        $this->assertSame('imagen', DB::table('file_media')->where('uuid', $conDueno)->value('collection'));
        $this->assertSame(1, DB::table('pg_indexes')->where('indexname', 'file_media_model_collection_filename_unique')->count());
    }

    public function test_down_no_inventa_dueno_a_las_subidas_del_codigo_nuevo(): void
    {
        $migracion = $this->migracion();
        $nueva = $this->insertar(['uso' => 'documento']);

        $migracion->down();

        $fila = DB::table('file_media')->where('uuid', $nueva)->first();
        $this->assertNotNull($fila);
        $this->assertNull($fila->model_type, 'Sin dueño conocido no se inventa uno.');
        $this->assertSame('documento-oficial', $fila->collection, 'D9 valida los documentos por esta columna.');
        $this->assertSame('YES', $this->nulable('model_type'));
    }
}
