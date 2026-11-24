<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\Concerns\CreaFilasDeArchivos;
use Tests\TestCase;

/**
 * Retiro de la columna `file_media.model_uuid` (Fase 0B, entrega B; ADR 0005).
 *
 * El dueño de un archivo se reconoce por `model_type` + `model_id` desde T8 y la entrega A dejó de
 * escribir y de exponer `model_uuid`. Aquí se comprueba que la migración la borra con su índice, que
 * **no pierde filas**, que **se detiene sin cambiar nada** si algún `model_uuid` contradice a su
 * `model_id` (es lo único que se perdería con información), y que `down()` la devuelve rellenada
 * desde el dueño, de modo que el retroceso no exige restaurar una copia.
 */
final class FileMediaRetiroModelUuidTest extends TestCase
{
    use CreaFilasDeArchivos;
    use RefreshDatabase;

    /** Estas pruebas siembran filas con dueño polimórfico: el esquema de antes de D10a. */
    protected function setUp(): void
    {
        parent::setUp();

        $this->volverAAntesDeUso();
    }

    /** Ejecuta `up` o `down` de la migración: la clase anónima las tiene, el tipo `Migration` no. */
    private function ejecutar(string $metodo): void
    {
        $this->migracion()->{$metodo}();
    }

    private function migracion(): Migration
    {
        $rutas = glob(database_path('migrations/*_retirar_model_uuid_de_file_media.php')) ?: [];
        $this->assertCount(1, $rutas, 'Falta la migración *_retirar_model_uuid_de_file_media.php');

        /** @var Migration $migracion */
        $migracion = require $rutas[0];

        return $migracion;
    }

    private function existeIndice(): bool
    {
        return DB::table('pg_indexes')->where('tablename', 'file_media')->where('indexname', 'file_media_model_uuid_index')->exists();
    }

    public function test_tras_migrar_la_columna_y_su_indice_ya_no_existen(): void
    {
        $this->assertFalse(Schema::hasColumn('file_media', 'model_uuid'));
        $this->assertFalse($this->existeIndice());
    }

    public function test_down_la_devuelve_con_su_indice_y_rellena_el_uuid_desde_el_dueno(): void
    {
        $banner = $this->banner();
        $uuid = $this->fila(['model_id' => $banner['id']]);

        $this->ejecutar('down');

        $this->assertTrue(Schema::hasColumn('file_media', 'model_uuid'));
        $this->assertTrue($this->existeIndice());
        $this->assertSame($banner['uuid'], DB::table('file_media')->where('uuid', $uuid)->value('model_uuid'));
    }

    public function test_up_conserva_todas_las_filas_y_sus_duenos(): void
    {
        $a = $this->banner();
        $b = $this->banner();
        $this->fila(['model_id' => $a['id'], 'filename' => 'uno.png']);
        $this->fila(['model_id' => $b['id'], 'filename' => 'dos.png']);
        $antes = DB::table('file_media')->orderBy('uuid')->pluck('model_id', 'uuid')->all();

        $this->ejecutar('down');
        $this->ejecutar('up');

        $this->assertFalse(Schema::hasColumn('file_media', 'model_uuid'));
        $this->assertSame($antes, DB::table('file_media')->orderBy('uuid')->pluck('model_id', 'uuid')->all());
    }

    public function test_up_se_detiene_sin_cambiar_nada_si_model_uuid_contradice_a_model_id(): void
    {
        $uno = $this->banner();
        $otro = $this->banner();
        $this->ejecutar('down');
        $fila = $this->fila(['model_id' => $uno['id']]);
        DB::table('file_media')->where('uuid', $fila)->update(['model_uuid' => $otro['uuid']]);

        try {
            $this->ejecutar('up');
            $this->fail('La migración debía detenerse y no lo hizo.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString($fila, $e->getMessage());
            $this->assertStringContainsString('SIN cambiar nada', $e->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('file_media', 'model_uuid'), 'No debía borrar la columna.');
        $this->assertTrue($this->existeIndice());
        $this->assertSame($otro['uuid'], DB::table('file_media')->where('uuid', $fila)->value('model_uuid'));
    }

    public function test_up_no_se_detiene_por_un_model_uuid_sin_dueno_porque_no_contradice_nada(): void
    {
        $banner = $this->banner();
        $this->ejecutar('down');
        $fila = $this->fila(['model_id' => $banner['id']]);
        DB::table('file_media')->where('uuid', $fila)->update(['model_uuid' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa']);

        $this->ejecutar('up');

        $this->assertFalse(Schema::hasColumn('file_media', 'model_uuid'));
        $this->assertSame($banner['id'], (int) DB::table('file_media')->where('uuid', $fila)->value('model_id'));
    }
}
