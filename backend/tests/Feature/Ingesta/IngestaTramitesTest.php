<?php

declare(strict_types=1);

namespace Tests\Feature\Ingesta;

use App\Contracts\Integraciones\FuenteFichaGovCoInterface;
use App\Contracts\Services\IngestaTramitesInterface;
use App\Models\Tramite;
use App\Support\Tramites\FuenteSuit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FichaFalsa;
use Tests\Support\FuenteFalsa;
use Tests\TestCase;

/**
 * La ingesta de la ficha oficial.
 *
 * Lo que estas pruebas protegen no es que el comando escriba filas, sino las
 * cuatro promesas que lo hacen utilizable sobre una fuente que limita la tasa:
 * que se pueda **reanudar**, que **no duplique**, que **no pise** lo que corrigió
 * una persona y que **no publique datos personales**. Ninguna de las cuatro se ve
 * mirando el catálogo al final: un catálogo impecable puede haberse construido
 * pidiendo los 124 trámites tres veces y publicando el móvil de un funcionario.
 *
 * La fuente se sustituye por `FuenteFalsa`, que lleva la cuenta de qué códigos se
 * pidieron: sin esa lista, «no repite lo que ya trajo» no se puede afirmar.
 */
final class IngestaTramitesTest extends TestCase
{
    use RefreshDatabase;

    private FuenteFalsa $fuente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fuente = new FuenteFalsa;

        $this->app->instance(FuenteFichaGovCoInterface::class, $this->fuente);
    }

    private function ingesta(): IngestaTramitesInterface
    {
        /** @var IngestaTramitesInterface $ingesta */
        $ingesta = $this->app->make(IngestaTramitesInterface::class);

        return $ingesta;
    }

    public function test_la_ingesta_trae_la_ficha_y_completa_el_catalogo(): void
    {
        $this->fuente->agregar('T2621');

        $resultado = $this->ingesta()->ejecutar(['T2621']);

        $this->assertSame(1, $resultado['traidos']);
        $this->assertSame(1, $resultado['creados']);
        $this->assertSame(1, $resultado['completos']);
        $this->assertSame([], $resultado['incompletos']);
        $this->assertSame([], $resultado['fallidos']);

        $tramite = Tramite::query()->where('codigo', 'T2621')->firstOrFail();

        $this->assertNotNull($tramite->publicado_en);
        $this->assertSame('parcialmente_en_linea', $tramite->modalidad?->value);
        $this->assertSame('con_costo', $tramite->tiene_costo?->value);
        // «2 HORA(S)» es un día: la jornada son ocho horas.
        $this->assertSame(1, $tramite->tiempo_solucion_dias);
        $this->assertSame('propio', $tramite->canal_inicio?->value);
        $this->assertSame('/seguimiento?radicado=…', $tramite->consulta_estado);
        $this->assertNotEmpty($tramite->requisitos);

        // Y la ficha llega por el contrato, que es lo que lee el sitio.
        $this->getJson('/api/v1/tramites/'.$tramite->slug)
            ->assertOk()
            ->assertJsonPath('data.resultado', 'Formulario o factura del impuesto predial con sello de pago que se obtiene en 2 HORA(S)')
            ->assertJsonPath('data.perfiles.0', 'Ciudadano')
            ->assertJsonPath('data.costo.tipo_valor', 'avaluo_liquidacion')
            ->assertJsonPath('data.normativa.0.tipo', 'Acuerdo')
            ->assertJsonPath('data.normativa.0.numero', '004')
            ->assertJsonPath('data.normativa.0.anio', 2016)
            ->assertJsonPath('data.normativa.0.articulos', 'articulo 27, 40')
            ->assertJsonPath('data.puntos_atencion.0.nombre', 'Dirección de Impuestos')
            ->assertJsonPath('data.puntos_atencion.0.horario', 'Lunes a viernes de 8:00 a 11:30 am y 2:00 a 5:30 pm')
            ->assertJsonPath('data.puntos_atencion.0.direccion', 'Calle 14 N 2-49 Primer Piso');
    }

    /**
     * La prueba que el proyecto pide de forma expresa: **la Ley 1581 no admite
     * excepciones por venir de una API pública**.
     *
     * Se comprueba sobre la respuesta entera y no campo por campo: buscar el móvil
     * en el JSON completo es lo único que garantiza que no se haya colado por una
     * clave que nadie recordaba —un identificador, una nota, una descripción—. Un
     * `assert` sobre `puntos_atencion.0.telefono` pasaría aunque el mismo número
     * viajara en otro sitio.
     */
    public function test_los_puntos_de_atencion_se_publican_solo_con_su_direccion_institucional(): void
    {
        $this->fuente->agregar('T2621');

        $this->ingesta()->ejecutar(['T2621']);

        $respuesta = $this->getJson('/api/v1/tramites/impuesto-predial-unificado')->assertOk();

        // Se busca sobre la carga entera y **con las tildes sin escapar**: el
        // cuerpo en crudo viaja con `Direcci\u00f3n`, así que buscarlo tal cual
        // daría un «no está» falso y la prueba no comprobaría nada.
        $cuerpo = json_encode($respuesta->json('data'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->assertIsString($cuerpo);

        // La fuente trae este móvil en el punto de atención «ALCALDIA LOCAL 3».
        // No puede estar en ninguna parte de lo que la sede publica.
        $this->assertStringNotContainsString('3007659666', $cuerpo);
        $this->assertStringNotContainsString('María Fernanda Ospina Rangel', $cuerpo);

        // Y lo institucional sí está: el filtro no puede llevarse por delante el
        // dato que el ciudadano necesita para ir.
        $this->assertStringContainsString('Dirección de Impuestos', $cuerpo);
        $this->assertStringContainsString('Calle 14 N 2-49 Primer Piso', $cuerpo);

        $puntos = Tramite::query()->where('codigo', 'T2621')->firstOrFail()->puntos_atencion;

        $this->assertCount(2, $puntos);
        $this->assertSame('(5) 4209600 - 1231', $puntos[0]['telefono']);
        $this->assertNull($puntos[1]['telefono'], 'El móvil de un punto de atención no se publica.');
    }

    /**
     * Las coordenadas imposibles tampoco se publican.
     *
     * La fuente da `Latitud: "20"` y `Longitud: "30"` al punto de atención
     * `ALCALDIA LOCAL 3`, que caen en el golfo de Guinea: un mapa que pone el
     * marcador en el Atlántico es peor que un mapa sin marcador.
     */
    public function test_las_coordenadas_que_no_son_de_ningun_sitio_no_se_publican(): void
    {
        $this->fuente->agregar('T2621');

        $this->ingesta()->ejecutar(['T2621']);

        $puntos = Tramite::query()->where('codigo', 'T2621')->firstOrFail()->puntos_atencion;

        $this->assertNotNull($puntos[0]['latitud']);
        $this->assertEqualsWithDelta(11.24516286, $puntos[0]['latitud'], 0.0000001);

        $this->assertNull($puntos[1]['latitud']);
        $this->assertNull($puntos[1]['longitud']);
    }

    /**
     * Un costo calculado no se publica como cero.
     *
     * La fuente declara el impuesto predial con `Valor: null` y
     * `TipoValor: "AVALUO_LIQUIDACION"`. Publicar «$0» sería decirle al ciudadano
     * que el trámite es gratis, y sobre esa lectura decide si paga o no.
     */
    public function test_un_costo_calculado_no_se_publica_como_cero(): void
    {
        $this->fuente->agregar('T2621');

        $this->ingesta()->ejecutar(['T2621']);

        $this->getJson('/api/v1/tramites/impuesto-predial-unificado')
            ->assertOk()
            ->assertJsonPath('data.tiene_costo', 'con_costo')
            ->assertJsonPath('data.costo.tiene_costo', 'con_costo')
            ->assertJsonPath('data.costo.tipo_valor', 'avaluo_liquidacion')
            ->assertJsonPath('data.costo.valor', null)
            ->assertJsonPath('data.costo.moneda', 'Pesos ($)')
            ->assertJsonPath('data.costo.cuentas.0.entidad', 'Banco de Bogotá')
            ->assertJsonPath('data.costo.cuentas.0.numero_cuenta', '070091897');

        // La cuenta a nombre de una persona no viaja; la de la Entidad sí.
        $this->assertCount(1, Tramite::query()->where('codigo', 'T2621')->firstOrFail()->costo_cuentas);
    }

    /** El canal de la sede se declara y se declara sin habilitar: no se finge el servicio. */
    public function test_el_canal_de_la_sede_se_declara_sin_habilitar(): void
    {
        $this->fuente->agregar('T2621');

        $this->ingesta()->ejecutar(['T2621']);

        $canales = Tramite::query()->where('codigo', 'T2621')->firstOrFail()->canales_consulta_estado;

        /** @var array<string, mixed>|null $sede */
        $sede = null;

        foreach ($canales as $canal) {
            if (($canal['canal'] ?? null) === 'sede') {
                $sede = $canal;
            }
        }

        $this->assertNotNull($sede, 'El canal de la sede tiene que declararse aunque no exista todavía.');
        $this->assertFalse($sede['habilitado']);
        $this->assertSame('/seguimiento', $sede['url']);

        // Y un canal de la fuente que sí atiende se declara habilitado.
        $this->getJson('/api/v1/tramites/impuesto-predial-unificado')
            ->assertOk()
            ->assertJsonPath('data.canales_consulta_estado.0.canal', 'presencial')
            ->assertJsonPath('data.canales_consulta_estado.0.habilitado', true);
    }

    /** Los requisitos llegan con su naturaleza, y la solicitud con su canal y su URL. */
    public function test_los_requisitos_llegan_con_su_naturaleza(): void
    {
        $this->fuente->agregar('T2621');

        $this->ingesta()->ejecutar(['T2621']);

        $this->getJson('/api/v1/tramites/impuesto-predial-unificado')
            ->assertOk()
            ->assertJsonPath('data.requisitos.0.tipo', 'solicitud')
            ->assertJsonPath('data.requisitos.0.canal', 'web')
            ->assertJsonPath('data.requisitos.0.url', 'https://impuestos.santamarta.gov.co:8443/autoservicios.jsf')
            ->assertJsonPath('data.requisitos.1.tipo', 'solicitud')
            ->assertJsonPath('data.requisitos.1.canal', 'presencial')
            ->assertJsonPath('data.requisitos.2.tipo', 'verificacion_institucional')
            ->assertJsonPath('data.requisitos.2.descripcion', 'Ser Propietario o Poseedor de un Bien inmueble en el Municipio de Santa Marta')
            ->assertJsonPath('data.requisitos.3.tipo', 'documento')
            ->assertJsonPath('data.requisitos.3.descripcion', 'Cédula de ciudadanía')
            ->assertJsonPath('data.requisitos.3.cantidad', 1)
            ->assertJsonPath('data.requisitos.3.unidad_cantidad', 'Original(es)')
            ->assertJsonPath('data.requisitos.4.tipo', 'pago');
    }

    /**
     * La prueba de la reanudación: se corta a la mitad y se retoma.
     *
     * No basta con comprobar que al final el catálogo tenga los cuatro trámites:
     * un catálogo correcto se puede haber construido pidiendo los cuatro dos
     * veces, que es exactamente lo que hay que evitar contra una fuente que limita
     * la tasa. Por eso se comprueba **qué códigos se pidieron** en cada pasada.
     */
    public function test_la_ingesta_que_se_corta_a_la_mitad_retoma_sin_duplicar_ni_repetir(): void
    {
        foreach (['T1', 'T2', 'T3', 'T4'] as $codigo) {
            $this->fuente->agregar($codigo);
        }

        // La fuente se corta al llegar al tercero: es lo que pasa cuando el
        // límite de tasa entra a mitad de una recolección.
        $this->fuente->romper('T3');
        $this->fuente->romper('T4');

        $primera = $this->ingesta()->ejecutar(['T1', 'T2', 'T3', 'T4']);

        $this->assertSame(2, $primera['traidos']);
        $this->assertSame(2, $primera['creados']);
        $this->assertCount(2, $primera['fallidos']);
        $this->assertSame(2, Tramite::query()->count());

        // Y lo que sigue pide **otra vez** T3 y T4 —los que no terminaron—: la
        // reanudación no puede dejar huecos ni repetir lo concluido.
        $this->fuente->arreglar('T3');
        $this->fuente->arreglar('T4');
        $this->fuente->pedidas = [];

        $segunda = $this->ingesta()->ejecutar(['T1', 'T2', 'T3', 'T4']);

        $this->assertSame(['T3', 'T4'], $this->fuente->pedidas, 'Lo que ya se trajo no se vuelve a pedir.');
        $this->assertSame(2, $segunda['reanudados']);
        $this->assertSame(4, Tramite::query()->count(), 'Reanudar no duplica.');

        // Idempotente: una tercera pasada sobre lo mismo no pide nada ni escribe nada.
        $this->fuente->pedidas = [];
        $tercera = $this->ingesta()->ejecutar(['T1', 'T2', 'T3', 'T4']);

        $this->assertSame([], $this->fuente->pedidas);
        $this->assertSame(4, $tercera['reanudados']);
        $this->assertSame(4, Tramite::query()->count());
    }

    /**
     * La ingesta no pisa lo que corrigió una persona.
     *
     * Un trámite deja de ser de la fuente en cuanto la Entidad pone su propia
     * procedencia. A partir de ahí la ingesta lo respeta, y por eso se puede volver
     * a ejecutar sin miedo después de una corrección manual.
     */
    public function test_no_pisa_lo_que_corrigio_una_persona(): void
    {
        $this->fuente->agregar('T2621');

        $this->ingesta()->ejecutar(['T2621']);

        $corregido = Tramite::query()->where('codigo', 'T2621')->firstOrFail();

        $corregido->forceFill([
            'nombre' => 'Impuesto predial unificado — corregido por la Entidad',
            'procedencia_fuente' => 'Alcaldía Distrital de Santa Marta',
        ])->save();

        // Se olvida el avance para forzar a la ingesta a volver a pedir la ficha:
        // sin eso la saltaría por estar hecha y la prueba no comprobaría nada.
        $this->ingesta()->olvidarAvance();

        $resultado = $this->ingesta()->ejecutar(['T2621']);

        $this->assertSame(1, $resultado['protegidos']);
        $this->assertSame(0, $resultado['actualizados']);

        $corregido->refresh();

        $this->assertSame('Impuesto predial unificado — corregido por la Entidad', $corregido->nombre);
        $this->assertSame('Alcaldía Distrital de Santa Marta', $corregido->procedencia_fuente);
    }

    /**
     * Un trámite al que le falta un atributo obligatorio no se publica, y el
     * informe dice cuál falta.
     *
     * Es la mitad del valor del comando: un catálogo a medias que no dice dónde
     * está a medias es peor que uno incompleto declarado.
     */
    public function test_lo_que_queda_incompleto_se_declara_con_el_campo_que_falta(): void
    {
        $this->fuente->agregar('T2621');
        $this->fuente->agregar('T90001');
        $this->fuente->agregar('T90002');

        // Una ficha sin modalidad: la fuente no declara `Tipotramite`.
        $this->fuente->agregar('T90001', FichaFalsa::sin('T90001', 'modalidad'));
        $this->fuente->agregar('T90002', FichaFalsa::sin('T90002', 'requisitos'));

        $resultado = $this->ingesta()->ejecutar(['T2621', 'T90001', 'T90002']);

        $this->assertSame(1, $resultado['completos']);
        $this->assertCount(2, $resultado['incompletos']);
        $this->assertSame(['modalidad' => 1, 'requisitos' => 1], $resultado['faltantes_por_campo']);

        // Guardado pero no publicado: la Entidad puede verlo y el ciudadano no,
        // que es justo lo que permite arreglarlo sin borrarlo.
        $this->assertNull(Tramite::query()->where('codigo', 'T90001')->firstOrFail()->publicado_en);
        $this->getJson('/api/v1/tramites')->assertOk()->assertJsonPath('meta.total', 1);
    }

    /**
     * La procedencia se publica campo por campo.
     *
     * Es lo que permite distinguir lo que la fuente declara de lo que el programa
     * derivó, y sin ello un término convertido de horas a días parecería declarado
     * por la fuente (Ley 1712 de 2014, artículo 11.b).
     */
    public function test_la_procedencia_dice_que_se_declaro_y_que_se_derivo(): void
    {
        $this->fuente->agregar('T2621');

        $this->ingesta()->ejecutar(['T2621']);

        $this->getJson('/api/v1/tramites/impuesto-predial-unificado')
            ->assertOk()
            ->assertJsonPath('data.procedencia.fuente', FuenteSuit::NOMBRE)
            ->assertJsonPath('data.procedencia.api', FuenteSuit::API_FICHA)
            ->assertJsonPath('data.procedencia.origen_por_campo.modalidad', 'fuente')
            ->assertJsonPath('data.procedencia.origen_por_campo.tiempo_solucion_dias', 'derivado')
            ->assertJsonPath('data.procedencia.origen_por_campo.consulta_estado', 'entidad')
            ->assertJsonPath('data.procedencia.derivados.1.campo', 'url_ficha_gov_co');
    }
}
