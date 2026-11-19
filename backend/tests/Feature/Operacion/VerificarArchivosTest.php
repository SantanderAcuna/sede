<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaFilasDeArchivos;
use Tests\TestCase;

/**
 * 0D, D10c: `archivos:verificar` compara la huella guardada de cada archivo con el contenido que hay en su disco.
 *
 * Con D10b la huella es obligatoria, así que ya no hay nada que completar: lo que importa es descubrir
 * que un archivo cambió o desapareció del disco. El comando solo LEE: nunca corrige la huella (corregirla
 * sería aceptar el cambio sin que nadie lo vea) y termina con error para que se note.
 */
final class VerificarArchivosTest extends TestCase
{
    use CreaFilasDeArchivos;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function archivo(string $contenido, ?string $huella = null): string
    {
        $uuid = $this->fila([
            'path' => 'file_media/prueba/'.md5($contenido.random_int(0, PHP_INT_MAX)).'.png',
            'hash_sha256' => $huella ?? hash('sha256', $contenido),
        ]);
        $ruta = (string) DB::table('file_media')->where('uuid', $uuid)->value('path');
        Storage::disk('local')->put($ruta, $contenido);

        return $uuid;
    }

    public function test_si_todos_coinciden_termina_bien_y_cuenta_los_verificados(): void
    {
        $this->archivo('uno');
        $this->archivo('dos');

        $this->assertSame(0, Artisan::call('archivos:verificar'));
        $this->assertStringContainsString('2 archivo(s) verificado(s)', Artisan::output());
    }

    public function test_sin_archivos_termina_bien(): void
    {
        $this->assertSame(0, Artisan::call('archivos:verificar'));
        $this->assertStringContainsString('0 archivo(s) verificado(s)', Artisan::output());
    }

    public function test_un_contenido_distinto_al_de_la_huella_termina_con_error_y_lista_el_archivo(): void
    {
        $alterado = $this->archivo('original', hash('sha256', 'otro contenido'));
        $bueno = $this->archivo('intacto');

        $this->assertSame(1, Artisan::call('archivos:verificar'));
        $salida = Artisan::output();
        $this->assertStringContainsString($alterado, $salida);
        $this->assertStringNotContainsString($bueno, $salida);
    }

    public function test_un_archivo_ausente_del_disco_termina_con_error_y_lo_lista(): void
    {
        $ausente = $this->fila(['path' => 'file_media/prueba/no-esta.png', 'hash_sha256' => hash('sha256', 'x')]);

        $this->assertSame(1, Artisan::call('archivos:verificar'));
        $this->assertStringContainsString($ausente, Artisan::output());
    }

    public function test_informa_todos_los_problemas_y_no_se_detiene_en_el_primero(): void
    {
        $alterado = $this->archivo('a', hash('sha256', 'b'));
        $ausente = $this->fila(['path' => 'file_media/prueba/falta.png', 'hash_sha256' => hash('sha256', 'y')]);
        $bueno = $this->archivo('c');

        $this->assertSame(1, Artisan::call('archivos:verificar'));
        $salida = Artisan::output();
        $this->assertStringContainsString($alterado, $salida);
        $this->assertStringContainsString($ausente, $salida);
        $this->assertStringContainsString('1 archivo(s) verificado(s)', $salida);
        $this->assertStringNotContainsString($bueno, $salida);
    }

    public function test_solo_lee_y_no_corrige_ninguna_huella(): void
    {
        $esperada = hash('sha256', 'no coincide');
        $uuid = $this->archivo('contenido real', $esperada);
        $antes = DB::table('file_media')->where('uuid', $uuid)->first();
        $this->assertNotNull($antes);

        Artisan::call('archivos:verificar');

        $despues = DB::table('file_media')->where('uuid', $uuid)->first();
        $this->assertNotNull($despues);
        $this->assertEquals($antes, $despues, 'El comando no debe escribir nada en la base.');
        $this->assertSame($esperada, $despues->hash_sha256);
    }

    public function test_recorre_mas_filas_que_un_bloque(): void
    {
        foreach (range(1, 205) as $i) {
            $this->archivo("contenido {$i}");
        }

        $this->assertSame(0, Artisan::call('archivos:verificar'));
        $this->assertStringContainsString('205 archivo(s) verificado(s)', Artisan::output());
    }

    /**
     * La limpieza (03:30) borra la FILA primero y el archivo después (`FileMedia::deleteFully`). Si borra mientras
     * esta verificación corre, el archivo deja de leerse, pero su fila ya no existe: eso no es una pérdida.
     */
    public function test_un_archivo_cuya_fila_se_borro_mientras_se_verificaba_no_se_reporta(): void
    {
        $uuid = $this->fila(['path' => 'file_media/prueba/borrado-en-vuelo.png', 'hash_sha256' => hash('sha256', 'z')]);

        Storage::set('local', new class($uuid)
        {
            public function __construct(private readonly string $uuid) {}

            /** @return resource|false */
            public function readStream(string $ruta)
            {
                DB::table('file_media')->where('uuid', $this->uuid)->delete();

                return false;
            }
        });

        $this->assertSame(0, Artisan::call('archivos:verificar'));
        $this->assertStringNotContainsString($uuid, Artisan::output());
    }
}
