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
                if (! isset($fila[$campo])) {
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
     * **La regresión que motivó estas pruebas.** La ingesta del visor nació sin
     * el filtro `ContactoPublicable` que la ingesta anterior sí usaba, y volvió a
     * publicar 74 móviles personales y 39 coordenadas imposibles. Estas tres
     * pruebas cierran esa puerta con una medición sobre el catálogo publicado,
     * no sobre la intención del código.
     */
    public function test_ningun_tramite_publicado_tiene_un_movil_personal(): void
    {
        $this->seed(TramiteSeeder::class);

        $moviles = [];

        foreach (Tramite::query()->cursor() as $tramite) {
            $numeros = [];

            foreach (($tramite->puntos_atencion ?? []) as $punto) {
                if (($punto['telefono'] ?? null) !== null) {
                    $numeros[] = (string) $punto['telefono'];
                }
            }

            foreach (($tramite->seguimiento['telefono'] ?? []) as $telefono) {
                if (($telefono['numero'] ?? null) !== null) {
                    $numeros[] = (string) $telefono['numero'];
                }
            }

            foreach ($numeros as $declarado) {
                // Un campo puede traer varios números separados por comas: se
                // comprueba número a número, que es lo que hace el filtro.
                foreach (explode(',', $declarado) as $numero) {
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

        foreach (Tramite::query()->cursor() as $tramite) {
            foreach (($tramite->puntos_atencion ?? []) as $punto) {
                $latitud = $punto['latitud'] ?? null;
                $longitud = $punto['longitud'] ?? null;

                if ($latitud === null && $longitud === null) {
                    continue;
                }

                $this->assertNotNull($latitud, "{$tramite->codigo}: media coordenada publicada.");
                $this->assertNotNull($longitud, "{$tramite->codigo}: media coordenada publicada.");

                if ($latitud < -5.0 || $latitud > 14.0 || $longitud < -82.0 || $longitud > -66.0) {
                    $fuera[] = "{$tramite->codigo}: {$latitud},{$longitud}";
                }
            }
        }

        $this->assertSame([], $fuera, 'Se publicaron coordenadas fuera de Colombia: '.implode(' · ', $fuera));
    }

    public function test_el_canal_web_de_seguimiento_conserva_su_enlace(): void
    {
        $this->seed(TramiteSeeder::class);

        // El visor nombra el enlace `urlCanal` y su rótulo `nombreCanal`. Leer
        // `url` y `nombre` —como hacía el sembrador— dejaba el canal web sin
        // dirección: el ciudadano perdía la consulta en línea de su trámite.
        $tramite = Tramite::query()->where('codigo', 'T6139')->firstOrFail();

        $web = collect($tramite->canales_consulta_estado ?? [])
            ->firstWhere('canal', 'web');

        $this->assertNotNull($web, 'El trámite del SISBÉN perdió su canal web.');
        $this->assertSame('Consulta tu Grupo de SISBEN', $web['nombre']);
        $this->assertSame('https://www.sisben.gov.co/paginas/consulta-tu-grupo.aspx', $web['url']);
        $this->assertTrue($web['habilitado']);
    }

    /**
     * La fuente repite requisitos —`T41040` trae dos veces «Diploma o acta de
     * grado» en el mismo paso— y la Sede no puede transcribir el defecto.
     */
    public function test_los_requisitos_no_se_repiten_por_nombre_visible(): void
    {
        $this->seed(TramiteSeeder::class);

        $conDuplicados = [];

        foreach (Tramite::query()->cursor() as $tramite) {
            $claves = [];

            foreach (($tramite->requisitos ?? []) as $requisito) {
                $visible = null;

                foreach (['documento', 'formulario', 'descripcion'] as $campo) {
                    $valor = $requisito[$campo] ?? null;

                    if (is_string($valor) && trim($valor) !== '') {
                        $visible = mb_strtolower(trim($valor));
                        break;
                    }
                }

                if ($visible === null) {
                    continue;
                }

                $claves[] = ($requisito['tipo'] ?? '').'|'.$visible;
            }

            if (count($claves) !== count(array_unique($claves))) {
                $conDuplicados[] = $tramite->codigo;
            }
        }

        $this->assertSame([], $conDuplicados, 'Trámites con requisitos repetidos: '.implode(' · ', $conDuplicados));
    }

    public function test_el_canal_presencial_no_repite_el_telefono_del_punto(): void
    {
        $this->seed(TramiteSeeder::class);

        // El teléfono y el horario del punto ya se publican en «¿Cuál es el
        // horario y los puntos de atención?». Repetirlos en el bloque de
        // seguimiento duplica el mismo dato en la misma pantalla.
        foreach (Tramite::query()->cursor() as $tramite) {
            $presencial = collect($tramite->canales_consulta_estado ?? [])
                ->firstWhere('canal', 'presencial');

            if ($presencial === null) {
                continue;
            }

            $this->assertNull($presencial['telefono'], "{$tramite->codigo} repite el teléfono del punto.");
            $this->assertNull($presencial['horario'], "{$tramite->codigo} repite el horario del punto.");
        }
    }

    /**
     * Al fusionar dos requisitos iguales, sus audiencias se **reúnen**.
     *
     * Es la mitad de la decisión que no se lee en el código: unir es la opción
     * que muestra el requisito en vez de esconderlo. Si al fusionar se quedara
     * sólo la audiencia del primero, el ciudadano que entra por la otra pestaña
     * no vería el documento que la fuente sí le exige —y la pestaña no daría
     * ninguna señal de que falta algo—.
     */
    public function test_al_fusionar_requisitos_repetidos_se_reunen_sus_audiencias(): void
    {
        $ruta = $this->fuenteConRequisitosRepetidos();

        $resultado = (new TramiteSeeder($ruta))->sembrar();

        $this->assertSame(1, $resultado['publicados']);

        $tramite = Tramite::query()->where('codigo', 'T1')->firstOrFail();

        // El modelo declara la forma de los seis atributos obligatorios, pero no
        // la de los campos del visor: se leen como arreglo abierto para que el
        // análisis estático no confunda la forma declarada con la real.
        /** @var list<array<string, mixed>> $requisitos */
        $requisitos = $tramite->requisitos ?? [];

        $this->assertCount(
            1,
            $requisitos,
            'Los dos requisitos iguales tenían que quedar en uno.',
        );

        /** @var array<string, mixed> $requisito */
        $requisito = $requisitos[0];

        $this->assertSame('Cédula de ciudadanía', $requisito['documento'] ?? null);
        $this->assertSame(
            ['Ciudadano', 'Extranjeros'],
            $requisito['tipos_audiencia'] ?? null,
            'Al fusionar se perdió una de las audiencias.',
        );
        // Una de las dos copias lo declaraba obligatorio: quitar una obligación
        // es más grave que añadirla.
        $this->assertTrue($requisito['obligatorio']);
    }

    /**
     * La descarga de la norma se publica cuando la fuente da la llave del
     * archivo, y **no se inventa** cuando no la da.
     *
     * `url_descarga` es un valor derivado: se arma con `archivo.id` y la
     * plantilla de descarga de SUIT (paso 03 de la ingesta). Esta prueba fija
     * las dos mitades: que el enlace salga bien formado donde hay archivo, y que
     * no aparezca un enlace roto donde la fuente no publica ninguno.
     */
    public function test_las_normas_publican_su_descarga_solo_cuando_hay_archivo(): void
    {
        $this->seed(TramiteSeeder::class);

        $conDescarga = 0;
        $sinLlave = 0;

        foreach (Tramite::query()->cursor() as $tramite) {
            foreach (($tramite->normativa ?? []) as $norma) {
                $url = $norma['url_descarga'] ?? null;

                if ($url === null) {
                    $sinLlave++;

                    continue;
                }

                $conDescarga++;
                $this->assertMatchesRegularExpression(
                    '#^https://tramites1\.suit\.gov\.co/registro-web/suit_descargar_archivo\?A=\d+$#',
                    $url,
                    "{$tramite->codigo}: la URL de descarga no sigue la plantilla de SUIT.",
                );
            }
        }

        // La copia publica normas con y sin archivo: si una de las dos cuentas
        // llegara a cero, la prueba dejaría de comprobar lo que dice comprobar.
        $this->assertGreaterThan(0, $conDescarga, 'Ninguna norma publicó su descarga.');
        $this->assertGreaterThan(0, $sinLlave, 'Todas las normas tienen archivo: la prueba no cubre la ausencia.');
    }

    /**
     * Todo trámite publicado responde «¿Cuándo se puede realizar?».
     *
     * La pregunta se responde con la unión de tres hechos —el booleano de
     * «cualquier fecha», la condición en prosa y el calendario externo—, así que
     * la regresión que hay que impedir es que un trámite quede con los tres
     * vacíos: la ficha dibujaría la pregunta sin respuesta, o ninguna, y el
     * ciudadano no sabría cuándo puede ir. La fuente cubre los 123, y por eso se
     * exige que los cubra todos.
     */
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

            if (
                ($tramite->cuando_se_puede_realizar ?? '') === ''
                && ($tramite->url_calendario ?? '') === ''
            ) {
                $sinRespuesta[] = $tramite->codigo;
            }
        }

        $this->assertSame(
            [],
            $sinRespuesta,
            'Trámites sin respuesta a «¿Cuándo se puede realizar?»: '.implode(' · ', $sinRespuesta),
        );

        // La mayoría lo responde con el booleano: si esa cuenta cayera a cero,
        // la prueba estaría pasando por la rama de excepción y no probaría nada.
        $this->assertGreaterThan(0, $cualquiera, 'Ningún trámite declara «cualquier fecha».');
    }

    /**
     * Las «Observaciones» del resultado no se guardan en blanco.
     *
     * La fuente usa el blanco para decir «no hay dato». Guardarlo como cadena
     * vacía haría que la ficha dibujara el rótulo «Observaciones:» seguido de
     * nada, que se lee como un dato que falta y no como uno que la fuente no
     * declara.
     */
    public function test_las_observaciones_del_resultado_no_quedan_en_blanco(): void
    {
        $this->seed(TramiteSeeder::class);

        $enBlanco = [];

        foreach (Tramite::query()->cursor() as $tramite) {
            $observaciones = $tramite->observaciones_resultado;

            if ($observaciones !== null && trim($observaciones) === '') {
                $enBlanco[] = $tramite->codigo;
            }
        }

        $this->assertSame([], $enBlanco, 'Observaciones en blanco: '.implode(' · ', $enBlanco));
    }

    /**
     * Una copia con dos requisitos idénticos, cada uno para una audiencia.
     *
     * Se escribe fuera del proyecto para no dejar basura dentro, como la fuente
     * de los descartes.
     */
    private function fuenteConRequisitosRepetidos(): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'tramites');

        if ($ruta === false) {
            $this->fail('No se pudo crear el archivo temporal de la prueba.');
        }

        $filas = [
            [
                'id' => 'T1',
                'titulo' => 'Trámite con el mismo documento dos veces',
                'costo' => 'NO',
                'tiempoObtencion' => '15 DIA(S) HÁBIL(ES)',
                'enLinea' => 'NO',
                'link_govco' => 'https://www.gov.co/ficha-tramites-y-servicios/T1',
                'momentos' => [
                    [
                        'orden' => 1,
                        'descripcion' => 'Reunir documentos',
                        'requisitos' => [
                            [
                                'orden' => 1,
                                'tipo' => 'DOCUMENTO',
                                'documento' => 'Cédula de ciudadanía',
                                'obligatorio' => false,
                                'tipos_audiencia' => ['Ciudadano'],
                            ],
                            [
                                'orden' => 2,
                                'tipo' => 'DOCUMENTO',
                                'documento' => 'Cédula de ciudadanía',
                                'obligatorio' => true,
                                'tipos_audiencia' => ['Extranjeros'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            file_put_contents($ruta, json_encode(['tramites' => $filas], JSON_THROW_ON_ERROR));
        } catch (JsonException $error) {
            $this->fail('La fuente de prueba no se pudo escribir: '.$error->getMessage());
        }

        $this->fixture = $ruta;

        return $ruta;
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
