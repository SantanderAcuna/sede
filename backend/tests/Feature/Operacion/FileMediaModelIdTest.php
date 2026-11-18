<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

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
        $migracion = $this->migracion();
        $migracion->down();
        $banner = $this->banner();
        $uuid = $this->fila(['model_uuid' => $banner['uuid']]);

        $migracion->up();

        $this->assertSame($banner['id'], (int) DB::table('file_media')->where('uuid', $uuid)->value('model_id'));
    }

    public function test_deja_intacta_una_fila_que_ya_era_coherente(): void
    {
        $migracion = $this->migracion();
        $migracion->down();
        $banner = $this->banner();
        $uuid = $this->fila(['model_id' => $banner['id'], 'model_uuid' => $banner['uuid']]);

        $migracion->up();

        $this->assertSame($banner['id'], (int) DB::table('file_media')->where('uuid', $uuid)->value('model_id'));
    }

    public function test_se_detiene_y_lista_las_filas_sin_dueno_sin_cambiar_nada(): void
    {
        $migracion = $this->migracion();
        $migracion->down();
        $banner = $this->banner();
        $arreglable = $this->fila(['model_uuid' => $banner['uuid']]);
        $huerfana = $this->fila(['model_uuid' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa']);
        $sinNada = $this->fila();

        $mensaje = $this->fallaCon(fn () => $migracion->up());

        $this->assertStringContainsString($huerfana, $mensaje);
        $this->assertStringContainsString($sinNada, $mensaje);
        $this->assertStringNotContainsString($arreglable, $mensaje, 'La fila arreglable no es un problema.');
        $this->assertNull(DB::table('file_media')->where('uuid', $arreglable)->value('model_id'), 'No debe quedar nada a medias.');
        $this->assertFalse($this->existeHash(), 'Tampoco la columna nueva.');
        $this->assertSame(3, DB::table('file_media')->count(), 'No se borra ninguna fila.');
    }

    public function test_se_detiene_si_el_model_id_no_coincide_con_el_dueno_del_uuid(): void
    {
        $migracion = $this->migracion();
        $migracion->down();
        $banner = $this->banner();
        $otro = $this->banner();
        $incoherente = $this->fila(['model_id' => $otro['id'], 'model_uuid' => $banner['uuid']]);

        $mensaje = $this->fallaCon(fn () => $migracion->up());

        $this->assertStringContainsString($incoherente, $mensaje);
        $this->assertSame($otro['id'], (int) DB::table('file_media')->where('uuid', $incoherente)->value('model_id'));
    }

    public function test_se_detiene_con_un_model_type_que_no_existe(): void
    {
        $migracion = $this->migracion();
        $migracion->down();
        $banner = $this->banner();
        $ajena = $this->fila(['model_type' => 'App\\Models\\NoExiste', 'model_uuid' => $banner['uuid']]);

        $mensaje = $this->fallaCon(fn () => $migracion->up());

        $this->assertStringContainsString($ajena, $mensaje);
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
        $migracion = $this->migracion();

        $migracion->down();

        $this->assertTrue($this->modelIdAdmiteNulos());
        $this->assertFalse($this->existeHash());
    }
}
