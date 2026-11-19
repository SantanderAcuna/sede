<?php

declare(strict_types=1);

namespace Tests\Feature\Mediateca;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 0D, D9: limpieza de imágenes de contenido sin uso (`archivos:limpiar-sin-uso`).
 *
 * Solo la colección «contenido», sin ningún uso en las cuatro relaciones y con 7 días sin tocar (la fecha cuenta
 * desde que dejó de usarse: `coalesce(updated_at, created_at)`). Las huérfanas de `imagen` solo se listan y los
 * documentos oficiales jamás se borran. Primero la fila, después el disco.
 */
final class LimpiezaSinUsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-20 12:00:00');
        Storage::fake((string) config('filesystems.default'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function archivo(string $uso, ?string $actualizado, ?string $creado = null): string
    {
        $uuid = (string) Str::uuid();
        $disco = (string) config('filesystems.default');
        $ruta = "file_media/prueba/{$uuid}.png";
        Storage::disk($disco)->put($ruta, 'x');

        DB::table('file_media')->insert([
            'uuid' => $uuid, 'uso' => $uso, 'hash_sha256' => hash('sha256', $uuid),
            'filename' => "{$uuid}.png", 'original_filename' => 'f.png', 'mime_type' => 'image/png', 'size' => 1,
            'disk' => $disco, 'path' => $ruta, 'created_at' => $creado ?? $actualizado, 'updated_at' => $actualizado,
        ]);

        return $uuid;
    }

    private function noticia(): int
    {
        return (int) DB::table('noticias')->insertGetId([
            'uuid' => (string) Str::uuid(), 'slug' => 'n-'.Str::random(8), 'titulo' => 'T', 'descripcion_corta' => 'c',
            'contenido' => '<p>x</p>', 'fecha_publicacion' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $opciones
     */
    private function limpiar(array $opciones = []): string
    {
        $this->assertSame(0, Artisan::call('archivos:limpiar-sin-uso', $opciones));

        return Artisan::output();
    }

    private function existe(string $uuid): bool
    {
        return DB::table('file_media')->where('uuid', $uuid)->exists();
    }

    private function enDisco(string $uuid): bool
    {
        return Storage::disk((string) config('filesystems.default'))->exists("file_media/prueba/{$uuid}.png");
    }

    public function test_borra_la_fila_y_el_archivo_de_lo_que_lleva_mas_de_7_dias_sin_uso(): void
    {
        $viejo = $this->archivo('contenido', '2026-11-12 11:59:59');

        $this->limpiar();

        $this->assertFalse($this->existe($viejo));
        $this->assertFalse($this->enDisco($viejo));
    }

    public function test_respeta_los_7_dias_justos_y_lo_reciente(): void
    {
        $limite = $this->archivo('contenido', '2026-11-13 12:00:00');
        $reciente = $this->archivo('contenido', '2026-11-19 12:00:00');

        $this->limpiar();

        $this->assertTrue($this->existe($limite));
        $this->assertTrue($this->existe($reciente));
    }

    public function test_sin_updated_at_cuenta_created_at_y_sin_ninguna_fecha_nunca_se_borra(): void
    {
        $solo_creado = $this->archivo('contenido', null, '2026-11-01 00:00:00');
        $sin_fechas = $this->archivo('contenido', null, null);

        $this->limpiar();

        $this->assertFalse($this->existe($solo_creado));
        $this->assertTrue($this->existe($sin_fechas));
    }

    public function test_no_borra_lo_que_alguna_relacion_usa_aunque_sea_viejo(): void
    {
        $enNoticia = $this->archivo('contenido', '2026-10-01 00:00:00');
        DB::table('noticia_imagenes')->insert(['noticia_id' => $this->noticia(), 'file_media_uuid' => $enNoticia, 'created_at' => now()]);
        $principal = $this->archivo('contenido', '2026-10-01 00:00:00');
        DB::table('noticias')->where('id', $this->noticia())->update(['imagen_principal_uuid' => $principal]);
        $enBanner = $this->archivo('contenido', '2026-10-01 00:00:00');
        DB::table('banners')->insert(['uuid' => (string) Str::uuid(), 'titulo' => 'B', 'imagen_uuid' => $enBanner, 'created_at' => now(), 'updated_at' => now()]);

        $this->limpiar();

        foreach ([$enNoticia, $principal, $enBanner] as $uuid) {
            $this->assertTrue($this->existe($uuid));
            $this->assertTrue($this->enDisco($uuid));
        }
    }

    public function test_las_imagenes_huerfanas_y_los_documentos_oficiales_no_se_borran(): void
    {
        $imagen = $this->archivo('imagen', '2026-10-01 00:00:00');
        $documento = $this->archivo('documento', '2026-10-01 00:00:00');

        $this->assertStringContainsString($imagen, $this->limpiar());

        $this->assertTrue($this->existe($imagen));
        $this->assertTrue($this->existe($documento));
    }

    public function test_en_simulacion_lista_y_no_borra(): void
    {
        $viejo = $this->archivo('contenido', '2026-10-01 00:00:00');

        $this->assertStringContainsString($viejo, $this->limpiar(['--simular' => true]));

        $this->assertTrue($this->existe($viejo));
        $this->assertTrue($this->enDisco($viejo));
    }

    public function test_si_justo_lo_empiezan_a_usar_no_se_borra_y_el_comando_sigue(): void
    {
        $carrera = $this->archivo('contenido', '2026-10-01 00:00:00');
        $otro = $this->archivo('contenido', '2026-10-01 00:00:00');
        $noticia = $this->noticia();

        $insertado = false;
        DB::beforeExecuting(function (string $sql) use (&$insertado, $carrera, $noticia): void {
            if (! $insertado && str_starts_with(strtolower($sql), 'delete from "file_media"')) {
                $insertado = true;
                DB::table('noticia_imagenes')->insert(['noticia_id' => $noticia, 'file_media_uuid' => $carrera, 'created_at' => now()]);
            }
        });

        $this->limpiar();

        $this->assertTrue($this->existe($carrera), 'La fila que alguien empezó a usar se conserva.');
        $this->assertTrue($this->enDisco($carrera));
        $this->assertFalse($this->existe($otro), 'El resto sigue su curso.');
    }

    public function test_esta_programada_a_las_0330_de_bogota_sin_solaparse(): void
    {
        $evento = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'archivos:limpiar-sin-uso'));

        $this->assertNotNull($evento);
        $this->assertSame('30 3 * * *', $evento->expression);
        $zona = $evento->timezone;
        $this->assertSame('America/Bogota', $zona instanceof \DateTimeZone ? $zona->getName() : $zona);
        $this->assertTrue($evento->withoutOverlapping);
    }
}
