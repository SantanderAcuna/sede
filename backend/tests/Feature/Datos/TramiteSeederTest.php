<?php

declare(strict_types=1);

namespace Tests\Feature\Datos;

use App\Models\Tramite;
use App\Support\Tramites\ContactoPublicable;
use Database\Seeders\TramiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use JsonException;
use Tests\TestCase;

/**
 * La carga inicial del catálogo (carga ÚNICA, relacional, sin dependencia del SUIT).
 *
 * El seeder es la puerta por la que entra el catálogo la primera vez. Lo que estas pruebas protegen
 * es que cargue una sola vez (no pise lo que edite la Entidad), que escriba filas relacionales, que
 * descarte lo incompleto diciendo por qué, y que no arrastre datos que no son del trámite.
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

    public function test_la_carga_es_unica_no_duplica_al_repetirse(): void
    {
        $this->seed(TramiteSeeder::class);
        $primera = Tramite::query()->count();
        $this->assertGreaterThan(0, $primera, 'La carga no publicó ningún trámite.');

        $this->seed(TramiteSeeder::class);
        $this->assertSame($primera, Tramite::query()->count());
    }

    public function test_la_carga_no_pisa_lo_que_edito_una_persona(): void
    {
        $this->seed(TramiteSeeder::class);
        $antes = Tramite::query()->count();

        $tramite = Tramite::query()->where('codigo', 'T2621')->firstOrFail();
        $tramite->update(['nombre' => 'Editado por la Entidad']);

        // Segunda carga: el catálogo ya existe, no se toca nada.
        $resultado = (new TramiteSeeder)->sembrar();

        $this->assertSame($antes, $resultado['protegidos']);
        $this->assertSame($antes, Tramite::query()->count());
        $this->assertSame('Editado por la Entidad', Tramite::query()->where('codigo', 'T2621')->firstOrFail()->nombre);
    }

    public function test_mapea_un_tramite_conocido_a_filas_relacionales(): void
    {
        $this->seed(TramiteSeeder::class);

        $tramite = Tramite::query()->with(['requisitos', 'documentos', 'categoria'])->where('codigo', 'T2621')->firstOrFail();

        $this->assertSame('Liquidación impuesto(s)  predial unificado', $tramite->nombre);
        $this->assertSame('parcialmente_en_linea', $tramite->modalidad?->value);
        $this->assertSame('con_costo', $tramite->tiene_costo?->value);
        $this->assertSame(1, $tramite->tiempo_solucion_dias);
        $this->assertSame('propio', $tramite->canal_inicio?->value);
        $this->assertSame('https://impuestos.santamarta.gov.co:8443/autoservicios.jsf', $tramite->url_inicio);
        $this->assertSame('https://www.gov.co/ficha-tramites-y-servicios/T2621', $tramite->url_ficha_gov_co);
        $this->assertNotNull($tramite->publicado_en);
        $this->assertTrue($tramite->requisitos->isNotEmpty());
        $this->assertTrue($tramite->documentos->isNotEmpty());
        // La clasificación inicial por tema (revisable en el panel) sí se asigna.
        $this->assertNotNull($tramite->categoria);

        // Un trámite en línea alojado en un portal nacional se declara como tal.
        $nacional = Tramite::query()->where('codigo', 'T73293')->firstOrFail();
        $this->assertSame('portal_nacional', $nacional->canal_inicio?->value);
    }

    public function test_sembrar_descarta_lo_incompleto_por_atributo(): void
    {
        $ruta = $this->fuenteDePrueba();

        $resultado = (new TramiteSeeder($ruta))->sembrar();

        $this->assertSame(1, $resultado['publicados']);
        $this->assertSame(
            ['modalidad' => 1, 'tiempo_solucion_dias' => 1, 'url_ficha_gov_co' => 1],
            $resultado['descartados'],
        );
        $this->assertSame(1, Tramite::query()->count());
    }

    public function test_los_descartes_se_informan_al_ejecutarse(): void
    {
        $ruta = $this->fuenteDePrueba();
        $this->app->bind(TramiteSeeder::class, static fn (): TramiteSeeder => new TramiteSeeder($ruta));

        Artisan::call('db:seed', ['--class' => TramiteSeeder::class, '--no-interaction' => true]);
        $texto = Artisan::output();

        $this->assertStringContainsString('Descartados: 3.', $texto);
        $this->assertStringContainsString('· modalidad: 1', $texto);
        $this->assertStringContainsString('· tiempo_solucion_dias: 1', $texto);
        $this->assertStringContainsString('· url_ficha_gov_co: 1', $texto);
    }

    public function test_no_inventa_un_importe(): void
    {
        $this->seed(TramiteSeeder::class);

        // La fuente no publica importes: ningún trámite lleva un valor que nadie declaró.
        $this->assertSame(0, Tramite::query()->whereNotNull('costo')->count());
    }

    public function test_ningun_punto_publicado_tiene_un_movil_personal(): void
    {
        $this->seed(TramiteSeeder::class);

        $moviles = [];
        foreach (Tramite::query()->with('puntosAtencion')->cursor() as $tramite) {
            foreach ($tramite->puntosAtencion as $punto) {
                if ($punto->telefono === null) {
                    continue;
                }
                foreach (explode(',', $punto->telefono) as $numero) {
                    if (! ContactoPublicable::esInstitucional($numero)) {
                        $moviles[] = "{$tramite->codigo}: {$numero}";
                    }
                }
            }
        }

        $this->assertSame([], $moviles, 'Se publicaron móviles personales: '.implode(' · ', $moviles));
    }

    public function test_todas_las_coordenadas_publicadas_caen_en_colombia(): void
    {
        $this->seed(TramiteSeeder::class);

        $fuera = [];
        foreach (Tramite::query()->with('puntosAtencion')->cursor() as $tramite) {
            foreach ($tramite->puntosAtencion as $punto) {
                if ($punto->latitud === null && $punto->longitud === null) {
                    continue;
                }
                $lat = (float) $punto->latitud;
                $lon = (float) $punto->longitud;
                if ($lat < -5.0 || $lat > 14.0 || $lon < -82.0 || $lon > -66.0) {
                    $fuera[] = "{$tramite->codigo}: {$lat},{$lon}";
                }
            }
        }

        $this->assertSame([], $fuera, 'Se publicaron coordenadas fuera de Colombia: '.implode(' · ', $fuera));
    }

    public function test_el_canal_web_de_consulta_conserva_su_enlace(): void
    {
        $this->seed(TramiteSeeder::class);

        $tramite = Tramite::query()->with('canales')->where('codigo', 'T6139')->firstOrFail();
        $web = $tramite->canales->firstWhere('canal', 'web');

        $this->assertNotNull($web, 'El trámite del SISBÉN perdió su canal web.');
        $this->assertSame('https://www.sisben.gov.co/paginas/consulta-tu-grupo.aspx', $web->url);
        $this->assertTrue($web->habilitado);
    }

    public function test_los_requisitos_no_se_repiten_por_descripcion(): void
    {
        $this->seed(TramiteSeeder::class);

        $conDuplicados = [];
        foreach (Tramite::query()->with('requisitos')->cursor() as $tramite) {
            $claves = $tramite->requisitos
                ->map(static fn ($r): string => $r->tipo.'|'.mb_strtolower(trim($r->descripcion)))
                ->all();
            if (count($claves) !== count(array_unique($claves))) {
                $conDuplicados[] = $tramite->codigo;
            }
        }

        $this->assertSame([], $conDuplicados, 'Trámites con requisitos repetidos: '.implode(' · ', $conDuplicados));
    }

    public function test_al_deduplicar_requisitos_se_reunen_sus_audiencias(): void
    {
        $ruta = $this->fuenteConRequisitosRepetidos();

        $resultado = (new TramiteSeeder($ruta))->sembrar();
        $this->assertSame(1, $resultado['publicados']);

        $tramite = Tramite::query()->with(['requisitos', 'audiencias'])->where('codigo', 'T1')->firstOrFail();

        // Los dos requisitos iguales quedan en uno.
        $this->assertCount(1, $tramite->requisitos);
        $primerRequisito = $tramite->requisitos->first();
        $this->assertNotNull($primerRequisito);
        $this->assertSame('Cédula de ciudadanía', $primerRequisito->descripcion);
        // Una de las copias lo declaraba obligatorio: quitar una obligación es peor que añadirla.
        $this->assertTrue($primerRequisito->obligatorio);
        // Las dos audiencias del trámite se reúnen (no se pierde ninguna).
        $grupos = $tramite->audiencias->pluck('grupo')->sort()->values()->all();
        $this->assertSame(['ciudadano', 'extranjeros'], $grupos);
    }

    public function test_todo_tramite_responde_cuando_se_puede_realizar(): void
    {
        $this->seed(TramiteSeeder::class);

        $sinRespuesta = [];
        $cualquiera = 0;
        foreach (Tramite::query()->cursor() as $tramite) {
            if ($tramite->fecha_cualquiera === true) {
                $cualquiera++;

                continue;
            }
            if (($tramite->cuando_se_puede_realizar ?? '') === '' && ($tramite->url_calendario ?? '') === '') {
                $sinRespuesta[] = $tramite->codigo;
            }
        }

        $this->assertSame([], $sinRespuesta, 'Trámites sin respuesta a «¿Cuándo se puede realizar?»: '.implode(' · ', $sinRespuesta));
        $this->assertGreaterThan(0, $cualquiera);
    }

    /** Una copia con dos requisitos idénticos, cada uno para una audiencia. */
    private function fuenteConRequisitosRepetidos(): string
    {
        $filas = [[
            'id' => 'T1',
            'titulo' => 'Trámite con el mismo documento dos veces',
            'costo' => 'NO',
            'tiempoObtencion' => '15 DIA(S) HÁBIL(ES)',
            'enLinea' => 'NO',
            'link_govco' => 'https://www.gov.co/ficha-tramites-y-servicios/T1',
            'momentos' => [[
                'orden' => 1,
                'descripcion' => 'Reunir documentos',
                'requisitos' => [
                    ['orden' => 1, 'tipo' => 'DOCUMENTO', 'documento' => 'Cédula de ciudadanía', 'obligatorio' => false, 'tipos_audiencia' => ['Ciudadano']],
                    ['orden' => 2, 'tipo' => 'DOCUMENTO', 'documento' => 'Cédula de ciudadanía', 'obligatorio' => true, 'tipos_audiencia' => ['Extranjeros']],
                ],
            ]],
        ]];

        return $this->escribirFixture($filas);
    }

    /** Una copia pequeña con los tres descartes que interesan. */
    private function fuenteDePrueba(): string
    {
        $filas = [
            ['id' => 'T1', 'titulo' => 'Trámite completo', 'costo' => 'NO', 'tiempoObtencion' => '15 DIA(S) HÁBIL(ES)', 'enLinea' => 'NO', 'link_govco' => 'https://www.gov.co/ficha-tramites-y-servicios/T1'],
            ['id' => 'T2', 'titulo' => 'Sin modalidad', 'costo' => 'NO', 'tiempoObtencion' => '15 DIA(S) HÁBIL(ES)', 'enLinea' => 'TAL VEZ', 'link_govco' => 'https://www.gov.co/ficha-tramites-y-servicios/T2'],
            ['id' => 'T3', 'titulo' => 'Sin término', 'costo' => 'NO', 'tiempoObtencion' => '   ', 'enLinea' => 'NO', 'link_govco' => 'https://www.gov.co/ficha-tramites-y-servicios/T3'],
            ['id' => 'T4', 'titulo' => 'Sin ficha en GOV.CO', 'costo' => 'NO', 'tiempoObtencion' => '15 DIA(S) HÁBIL(ES)', 'enLinea' => 'NO', 'link_govco' => null],
        ];

        return $this->escribirFixture($filas);
    }

    /**
     * @param  list<array<string, mixed>>  $filas
     */
    private function escribirFixture(array $filas): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'tramites');
        if ($ruta === false) {
            $this->fail('No se pudo crear el archivo temporal de la prueba.');
        }

        try {
            file_put_contents($ruta, json_encode(['tramites' => $filas], JSON_THROW_ON_ERROR));
        } catch (JsonException $error) {
            $this->fail('La fuente de prueba no se pudo escribir: '.$error->getMessage());
        }

        $this->fixture = $ruta;

        return $ruta;
    }
}
