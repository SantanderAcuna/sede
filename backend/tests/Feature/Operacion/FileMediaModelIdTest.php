<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Concerns\CreaFilasDeArchivos;
use Tests\TestCase;

/**
 * Migración de `file_media` (Fase 0B, T8): `model_id` obligatorio y `hash_sha256`.
 *
 * La migración se ejercita con filas «viejas»: se la deshace, se escriben filas como las
 * que había antes (sin `model_id`, o sin dueño) y se la vuelve a aplicar. Regla de fondo:
 * lo que no se puede inferir con certeza **no se arregla ni se borra**: la migración se
 * detiene, lista cada fila y no cambia nada.
 */
final class FileMediaModelIdTest extends TestCase
{
    use CreaFilasDeArchivos;
    use RefreshDatabase;

    /**
     * Estas pruebas escriben filas «viejas», que llevaban `model_uuid`: la entrega B borró esa columna,
     * así que se la devuelve (con su índice) deshaciendo esa migración, como pide el ADR 0005.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $rutas = glob(database_path('migrations/*_retirar_model_uuid_de_file_media.php')) ?: [];
        $this->assertCount(1, $rutas, 'Falta la migración *_retirar_model_uuid_de_file_media.php');

        /** @var Migration $retiro */
        $retiro = require $rutas[0];
        $retiro->down();
    }

    /** Ejecuta `up` o `down` de la migración: la clase anónima las tiene, el tipo `Migration` no. */
    private function ejecutar(string $metodo): void
    {
        $this->migracion()->{$metodo}();
    }

    private function migracion(): Migration
    {
        $rutas = glob(database_path('migrations/*_endurecer_file_media.php')) ?: [];
        $this->assertCount(1, $rutas, 'Falta la migración *_endurecer_file_media.php');

        /** @var Migration $migracion */
        $migracion = require $rutas[0];

        return $migracion;
    }

    private function modelIdAdmiteNulos(): bool
    {
        return DB::table('information_schema.columns')
            ->where('table_name', 'file_media')->where('column_name', 'model_id')
            ->value('is_nullable') === 'YES';
    }

    private function existeHash(): bool
    {
        return DB::table('information_schema.columns')
            ->where('table_name', 'file_media')->where('column_name', 'hash_sha256')->exists();
    }

    private function fallaCon(callable $accion): string
    {
        try {
            $accion();
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }
        $this->fail('La migración debía detenerse y no lo hizo.');
    }

    public function test_rellena_model_id_desde_model_uuid(): void
    {
        $this->ejecutar('down');
        $banner = $this->banner();
        $uuid = $this->fila(['model_uuid' => $banner['uuid']]);

        $this->ejecutar('up');

        $this->assertSame($banner['id'], (int) DB::table('file_media')->where('uuid', $uuid)->value('model_id'));
    }

    public function test_deja_intacta_una_fila_que_ya_era_coherente(): void
    {
        $this->ejecutar('down');
        $banner = $this->banner();
        $uuid = $this->fila(['model_id' => $banner['id'], 'model_uuid' => $banner['uuid']]);

        $this->ejecutar('up');

        $this->assertSame($banner['id'], (int) DB::table('file_media')->where('uuid', $uuid)->value('model_id'));
    }

    public function test_se_detiene_y_lista_las_filas_sin_dueno_sin_cambiar_nada(): void
    {
        $this->ejecutar('down');
        $banner = $this->banner();
        $arreglable = $this->fila(['model_uuid' => $banner['uuid']]);
        $huerfana = $this->fila(['model_uuid' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa']);
        $sinNada = $this->fila();

        $mensaje = $this->fallaCon(fn () => $this->ejecutar('up'));

        $this->assertStringContainsString($huerfana, $mensaje);
        $this->assertStringContainsString($sinNada, $mensaje);
        $this->assertStringNotContainsString($arreglable, $mensaje, 'La fila arreglable no es un problema.');
        $this->assertNull(DB::table('file_media')->where('uuid', $arreglable)->value('model_id'), 'No debe quedar nada a medias.');
        $this->assertFalse($this->existeHash(), 'Tampoco la columna nueva.');
        $this->assertSame(3, DB::table('file_media')->count(), 'No se borra ninguna fila.');
    }

    public function test_se_detiene_si_el_model_id_no_coincide_con_el_dueno_del_uuid(): void
    {
        $this->ejecutar('down');
        $banner = $this->banner();
        $otro = $this->banner();
        $incoherente = $this->fila(['model_id' => $otro['id'], 'model_uuid' => $banner['uuid']]);

        $mensaje = $this->fallaCon(fn () => $this->ejecutar('up'));

        $this->assertStringContainsString($incoherente, $mensaje);
        $this->assertSame($otro['id'], (int) DB::table('file_media')->where('uuid', $incoherente)->value('model_id'));
    }

    public function test_se_detiene_con_un_model_type_que_no_existe(): void
    {
        $this->ejecutar('down');
        $banner = $this->banner();
        $ajena = $this->fila(['model_type' => 'App\\Models\\NoExiste', 'model_uuid' => $banner['uuid']]);

        $mensaje = $this->fallaCon(fn () => $this->ejecutar('up'));

        $this->assertStringContainsString($ajena, $mensaje);
    }

    public function test_no_resuelve_un_dueno_que_no_es_un_modelo_propio(): void
    {
        $this->ejecutar('down');
        // Un dueño que SÍ existe (el usuario), pero cuyo tipo es una clase de las dependencias:
        // sin la restricción a `App\Models` se habría resuelto y rellenado.
        $usuario = User::factory()->create();
        $ajena = $this->fila(['model_type' => 'Illuminate\\Foundation\\Auth\\User', 'model_uuid' => $usuario->uuid]);

        $mensaje = $this->fallaCon(fn () => $this->ejecutar('up'));

        $this->assertStringContainsString($ajena, $mensaje);
    }

    public function test_se_detiene_con_un_mensaje_claro_si_dos_filas_chocarian_en_el_indice_unico(): void
    {
        $this->ejecutar('down');
        $banner = $this->banner();
        // Antes eludían la unicidad por tener model_id nulo; al asignarles dueño serían la misma fila.
        $a = $this->fila(['model_uuid' => $banner['uuid'], 'filename' => 'igual.png']);
        $b = $this->fila(['model_uuid' => $banner['uuid'], 'filename' => 'igual.png']);
        $apartada = $this->fila(['model_uuid' => $banner['uuid'], 'filename' => 'otra.png']);

        $mensaje = $this->fallaCon(fn () => $this->ejecutar('up'));

        $this->assertStringContainsString($a, $mensaje);
        $this->assertStringContainsString($b, $mensaje);
        $this->assertStringContainsString('igual.png', $mensaje);
        $this->assertStringNotContainsString($apartada, $mensaje, 'Solo se listan las filas que chocan.');
        $this->assertNull(DB::table('file_media')->where('uuid', $a)->value('model_id'), 'No debe quedar nada a medias.');
        $this->assertFalse($this->existeHash());
    }

    public function test_se_detiene_si_una_fila_con_model_id_choca_con_otra_sin_model_id(): void
    {
        $this->ejecutar('down');
        $banner = $this->banner();
        $con = $this->fila(['model_id' => $banner['id'], 'model_uuid' => $banner['uuid'], 'filename' => 'igual.png']);
        $sin = $this->fila(['model_uuid' => $banner['uuid'], 'filename' => 'igual.png']);

        $mensaje = $this->fallaCon(fn () => $this->ejecutar('up'));

        $this->assertStringContainsString($con, $mensaje);
        $this->assertStringContainsString($sin, $mensaje);
    }

    public function test_se_detiene_si_una_fila_con_model_id_apunta_a_un_dueno_que_no_existe(): void
    {
        $this->ejecutar('down');
        $banner = $this->banner();
        $sana = $this->fila(['model_id' => $banner['id']]);
        $huerfana = $this->fila(['model_id' => 987654]);

        $mensaje = $this->fallaCon(fn () => $this->ejecutar('up'));

        $this->assertStringContainsString($huerfana, $mensaje);
        $this->assertStringNotContainsString($sana, $mensaje, 'Un model_id con dueño real no es un problema.');
    }

    public function test_se_detiene_si_una_fila_con_model_id_tiene_un_tipo_que_no_es_un_modelo(): void
    {
        $this->ejecutar('down');
        $ajena = $this->fila(['model_type' => 'App\\Models\\NoExiste', 'model_id' => 1]);

        $mensaje = $this->fallaCon(fn () => $this->ejecutar('up'));

        $this->assertStringContainsString($ajena, $mensaje);
    }

    public function test_una_fila_con_varios_problemas_se_lista_una_sola_vez(): void
    {
        $this->ejecutar('down');
        $banner = $this->banner();
        $doble = $this->fila(['model_id' => 987654, 'model_uuid' => $banner['uuid']]);

        $mensaje = $this->fallaCon(fn () => $this->ejecutar('up'));

        $this->assertSame(1, substr_count($mensaje, $doble), 'La fila debe salir una vez, con todos sus motivos.');
        $this->assertStringContainsString('1 problema(s)', $mensaje);
        $this->assertStringContainsString('no corresponde a ningún dueño existente', $mensaje);
        $this->assertStringContainsString('contradice a model_uuid', $mensaje);
    }

    public function test_despues_de_migrar_model_id_es_obligatorio_y_existe_el_hash(): void
    {
        $this->assertFalse($this->modelIdAdmiteNulos());
        $this->assertTrue($this->existeHash());

        $this->expectException(QueryException::class);
        $this->fila(['model_id' => null]);
    }

    public function test_down_devuelve_el_esquema_anterior(): void
    {
        $this->ejecutar('down');

        $this->assertTrue($this->modelIdAdmiteNulos());
        $this->assertFalse($this->existeHash());
    }
}
