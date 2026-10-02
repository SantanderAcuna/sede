<?php

declare(strict_types=1);

namespace Tests\Feature\Datos;

use App\Models\Tramite;
use Database\Seeders\TramiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use JsonException;
use Tests\TestCase;

/**
 * La siembra del catálogo desde la fuente oficial.
 *
 * El sembrador es la puerta por la que entra todo el catálogo, así que lo que
 * estas pruebas protegen es que no duplique al repetirse, que descarte lo
 * incompleto diciendo por qué y que no arrastre datos que no son del trámite.
 */
final class TramiteSeederTest extends TestCase
{
    use RefreshDatabase;

    private ?string $fixture = null;

    protected function tearDown(): void
    {
        if ($this->fixture !== null && is_file($this->fixture)) {
            unlink($this->fixture);
        }

        parent::tearDown();
    }

    public function test_sembrar_dos_veces_deja_el_mismo_numero_de_filas(): void
    {
        $this->seed(TramiteSeeder::class);

        $primera = Tramite::query()->count();

        $this->assertGreaterThan(0, $primera, 'La siembra no publicó ningún trámite.');

        $this->seed(TramiteSeeder::class);

        $this->assertSame($primera, Tramite::query()->count());
    }

    public function test_lo_que_se_siembra_es_lo_que_la_fuente_declara(): void
    {
        $this->seed(TramiteSeeder::class);

        // «Impuesto predial unificado» de la fuente: parcialmente en línea, con
        // costo, dos horas de término y su trámite en línea en el sistema de
        // impuestos del Distrito. Con él se comprueba el mapeo entero, incluida
        // la conversión de horas a días y la nota que la declara.
        $tramite = Tramite::query()->where('codigo', 'T2621')->firstOrFail();

        $this->assertSame('Liquidación impuesto(s)  predial unificado', $tramite->nombre);
        $this->assertSame('parcialmente_en_linea', $tramite->modalidad?->value);
        $this->assertSame('con_costo', $tramite->tiene_costo?->value);
        $this->assertSame(1, $tramite->tiempo_solucion_dias);
        $this->assertSame('propio', $tramite->canal_inicio?->value);
        $this->assertSame('https://impuestos.santamarta.gov.co:8443/autoservicios.jsf', $tramite->url_inicio);
        $this->assertSame('https://www.gov.co/ficha-tramites-y-servicios/T2621', $tramite->url_ficha_gov_co);
        $this->assertNotNull($tramite->publicado_en);
        $this->assertSame(TramiteSeeder::FUENTE, $tramite->procedencia_fuente);
        $this->assertSame(TramiteSeeder::OBTENIDO_EN, $tramite->procedencia_obtenido_en?->format('Y-m-d'));
        $this->assertNotNull($tramite->procedencia_nota);
        $this->assertNotEmpty($tramite->requisitos);
        $this->assertNotEmpty($tramite->documentos);

        // El trámite en línea de un portal del Estado que no es la Entidad se
        // declara como tal: es la diferencia que el ciudadano necesita saber
        // antes de pulsar.
        $nacional = Tramite::query()->where('codigo', 'T73293')->firstOrFail();

        $this->assertSame('portal_nacional', $nacional->canal_inicio?->value);
    }

    public function test_los_descartes_se_cuentan_por_atributo_y_se_informan(): void
    {
        $ruta = $this->fuenteDePrueba();

        $this->app->bind(TramiteSeeder::class, static fn (): TramiteSeeder => new TramiteSeeder($ruta));

        $resultado = (new TramiteSeeder($ruta))->sembrar();

        // Una fila completa y tres a las que les falta un atributo distinto.
        $this->assertSame(1, $resultado['publicados']);
        $this->assertSame(
            ['modalidad' => 1, 'tiempo_solucion_dias' => 1, 'url_ficha_gov_co' => 1],
            $resultado['descartados'],
        );
        $this->assertSame(3, array_sum($resultado['descartados']));
        $this->assertCount(3, $resultado['detalle_descartes']);
        $this->assertSame(1, Tramite::query()->count());

        // Y lo dice al ejecutarse: «descartados 3» sin decir por qué no sirve
        // para arreglar la fuente.
        Artisan::call('db:seed', ['--class' => TramiteSeeder::class, '--no-interaction' => true]);

        $texto = Artisan::output();

        $this->assertStringContainsString('Descartados: 3.', $texto);
        $this->assertStringContainsString('· modalidad: 1', $texto);
        $this->assertStringContainsString('· tiempo_solucion_dias: 1', $texto);
        $this->assertStringContainsString('· url_ficha_gov_co: 1', $texto);
    }

    public function test_la_siembra_no_pisa_lo_que_corrigio_una_persona(): void
    {
        $this->seed(TramiteSeeder::class);

        $corregido = Tramite::query()->where('codigo', 'T2621')->firstOrFail();

        $corregido->forceFill([
            'nombre' => 'Impuesto predial unificado — corregido por la Entidad',
            // La marca de la siembra se sustituye por la de la Entidad: a partir
            // de aquí el trámite es suyo y no de la fuente, y la siembra no lo
            // toca.
            'procedencia_fuente' => 'Alcaldía Distrital de Santa Marta',
        ])->save();

        $resultado = (new TramiteSeeder)->sembrar();

        $this->assertSame(1, $resultado['protegidos']);
        $this->assertSame(
            'Impuesto predial unificado — corregido por la Entidad',
            Tramite::query()->where('codigo', 'T2621')->firstOrFail()->nombre,
        );
    }

    public function test_la_copia_de_la_fuente_no_trae_datos_de_contacto_personal(): void
    {
        $contenido = (string) file_get_contents(database_path(TramiteSeeder::ARCHIVO));

        // El listado crudo (array `tramites` de primer nivel) es la fuente
        // oficial: lo que el SUIT publica por su API. Esta parte **no** debe
        // contener direcciones de correo de personas, teléfonos móviles ni
        // datos de contacto que sean de un funcionario.
        //
        // A partir de 2026-10, la copia se enriquece con los datos del **visor**
        // de SUIT (cumplimiento del Anexo 2.1), donde aparecen correos
        // institucionales de la Entidad y de sus dependencias. Esos correos
        // **son** datos del trámite, no del funcionario, y se publican en la
        // ficha como medio de radicación y seguimiento.
        //
        // Lo que este test protege, entonces, es el listado crudo: que el
        // primer nivel del JSON no haya añadido emails que la Sede sembraría
        // como si fuesen parte del trámite.
        $listado = json_decode($contenido, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($listado);
        $this->assertArrayHasKey('tramites', $listado);

        foreach ($listado['tramites'] as $fila) {
            // El listado por trámite: los campos de la fuente SUIT, que no
            // incluyen correo del funcionario.
            foreach (['titulo', 'proposito', 'costo', 'tiempoObtencion', 'enLinea', 'link_govco', 'urlTramiteEnLinea'] as $campo) {
                if (!isset($fila[$campo])) {
                    continue;
                }
                $this->assertIsString(
                    $fila[$campo],
                    "El campo {$campo} del listado de SUIT debe ser texto, no un buzón.",
                );
                $this->assertStringNotContainsString(
                    '@',
                    $fila[$campo],
                    "El campo {$campo} de la fuente SUIT no debe contener correos de funcionarios.",
                );
            }
        }
    }

    public function test_lo_que_la_fuente_no_declara_no_se_inventa(): void
    {
        $this->seed(TramiteSeeder::class);

        // La fuente no publica importes ni categorías: ningún trámite sembrado
        // lleva un valor que nadie haya declarado.
        $this->assertSame(0, Tramite::query()->whereNotNull('costo')->count());
        $this->assertSame(0, Tramite::query()->whereNotNull('categoria_slug')->count());
    }

    /**
     * Una copia pequeña de la fuente con los tres descartes que interesan.
     *
     * Se escribe fuera del proyecto para no dejar basura dentro, y se borra al
     * terminar la prueba.
     */
    private function fuenteDePrueba(): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'tramites');

        if ($ruta === false) {
            $this->fail('No se pudo crear el archivo temporal de la prueba.');
        }

        $filas = [
            ['id' => 'T1', 'titulo' => 'Trámite completo', 'costo' => 'NO', 'tiempoObtencion' => '15 DIA(S) HÁBIL(ES)', 'enLinea' => 'NO', 'link_govco' => 'https://www.gov.co/ficha-tramites-y-servicios/T1'],
            ['id' => 'T2', 'titulo' => 'Sin modalidad', 'costo' => 'NO', 'tiempoObtencion' => '15 DIA(S) HÁBIL(ES)', 'enLinea' => 'TAL VEZ', 'link_govco' => 'https://www.gov.co/ficha-tramites-y-servicios/T2'],
            ['id' => 'T3', 'titulo' => 'Sin término', 'costo' => 'NO', 'tiempoObtencion' => '   ', 'enLinea' => 'NO', 'link_govco' => 'https://www.gov.co/ficha-tramites-y-servicios/T3'],
            ['id' => 'T4', 'titulo' => 'Sin ficha en GOV.CO', 'costo' => 'NO', 'tiempoObtencion' => '15 DIA(S) HÁBIL(ES)', 'enLinea' => 'NO', 'link_govco' => null],
        ];

        try {
            file_put_contents($ruta, json_encode(['tramites' => $filas], JSON_THROW_ON_ERROR));
        } catch (JsonException $error) {
            $this->fail('La fuente de prueba no se pudo escribir: '.$error->getMessage());
        }

        $this->fixture = $ruta;

        return $ruta;
    }
}
