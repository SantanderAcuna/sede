<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaFilasDeArchivos;
use Tests\TestCase;

/**
 * `archivos:calcular-hash` rellena `hash_sha256` de los archivos que ya estaban (Fase 0B, T8).
 *
 * Lee del disco de cada fila. Si el archivo no está, no inventa nada: deja el hash vacío,
 * lo lista y termina con error para que se note. Es idempotente.
 */
final class CalcularHashArchivosTest extends TestCase
{
    use CreaFilasDeArchivos;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_calcula_el_hash_de_los_archivos_presentes(): void
    {
        $banner = $this->banner();
        $uuid = $this->fila(['model_id' => $banner['id'], 'path' => 'file_media/a.png']);
        Storage::disk('local')->put('file_media/a.png', 'hola');

        $this->artisan('archivos:calcular-hash')->assertExitCode(0);

        $this->assertSame(hash('sha256', 'hola'), DB::table('file_media')->where('uuid', $uuid)->value('hash_sha256'));
    }

    public function test_lista_los_archivos_ausentes_sin_inventar_su_hash_y_termina_con_error(): void
    {
        $banner = $this->banner();
        $ausente = $this->fila(['model_id' => $banner['id'], 'path' => 'file_media/no-esta.png']);

        $this->artisan('archivos:calcular-hash')
            ->expectsOutputToContain($ausente)
            ->assertExitCode(1);

        $this->assertNull(DB::table('file_media')->where('uuid', $ausente)->value('hash_sha256'));
    }

    public function test_no_recalcula_los_que_ya_tienen_hash(): void
    {
        $banner = $this->banner();
        $uuid = $this->fila(['model_id' => $banner['id'], 'path' => 'file_media/b.png', 'hash_sha256' => str_repeat('a', 64)]);
        Storage::disk('local')->put('file_media/b.png', 'otro contenido');

        $this->artisan('archivos:calcular-hash')->assertExitCode(0);

        $this->assertSame(str_repeat('a', 64), DB::table('file_media')->where('uuid', $uuid)->value('hash_sha256'));
    }
}
