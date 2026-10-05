<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Ingesta;

use App\Support\Ingesta\FichaFuente;
use App\Support\Ingesta\MapeoFicha;
use Tests\TestCase;

/**
 * Tests para coverage de MapeoFicha::atributos() con datos incompletos.
 *
 * @covers \App\Support\Ingesta\MapeoFicha
 */
final class MapeoFichaAtributosConDatosIncompletosTest extends TestCase
{
    private MapeoFicha $mapeo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapeo = new MapeoFicha;
    }

    public function test_atributos_marca_resultado_como_faltante_cuando_es_null(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T9999',
            nombre: 'Trámite sin resultado',
            proposito: null,
            tipoTramite: 'Virtual',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'NO',
            tiempoObtencion: '5 DIA(S)',
            resultadoObtiene: null, // null → se marca como faltante
            observacionTiempo: null,
            perfiles: [],
            puntosAtencion: [],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'tramite-sin-resultado');

        $this->assertArrayHasKey('procedencia_faltantes', $atributos);
        $this->assertContains('resultado', $atributos['procedencia_faltantes']);
    }

    public function test_atributos_marca_tiempo_como_faltante_cuando_formato_desconocido(): void
    {
        // 'FORMATO INVALIDO' no lo entiende TiempoEnDias::desde() → devuelve null
        $ficha = new FichaFuente(
            codigo: 'T9998',
            nombre: 'Trámite sin tiempo',
            proposito: null,
            tipoTramite: 'Virtual',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'NO',
            tiempoObtencion: 'FORMATO INVALIDO', // null via TiempoEnDias
            resultadoObtiene: 'Resultado conocido',
            observacionTiempo: null,
            perfiles: [],
            puntosAtencion: [],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'tramite-sin-tiempo');

        $this->assertArrayHasKey('procedencia_faltantes', $atributos);
        $this->assertContains('tiempo_solucion_dias', $atributos['procedencia_faltantes']);
    }

    public function test_atributos_devuelve_origen_derivado_cuando_tiempo_tiene_nota(): void
    {
        // El término en HORA(S) genera una nota de conversión
        $ficha = new FichaFuente(
            codigo: 'T9997',
            nombre: 'Trámite en horas',
            proposito: null,
            tipoTramite: 'Virtual',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'NO',
            tiempoObtencion: '2 HORA(S)', // TiempoEnDias devuelve nota
            resultadoObtiene: 'Resultado',
            observacionTiempo: null,
            perfiles: [],
            puntosAtencion: [],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'tramite-en-horas');

        // origen_por_campo['tiempo_solucion_dias'] = 'derivado' cuando hay nota
        $this->assertArrayHasKey('procedencia_origen_por_campo', $atributos);
        $this->assertSame('derivado', $atributos['procedencia_origen_por_campo']['tiempo_solucion_dias']);
    }

    public function test_atributos_devuelve_origen_fuente_cuando_tiempo_sin_nota(): void
    {
        // El término en DIA(S) no genera nota
        $ficha = new FichaFuente(
            codigo: 'T9996',
            nombre: 'Trámite en días',
            proposito: null,
            tipoTramite: 'Virtual',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'NO',
            tiempoObtencion: '10 DIA(S)', // Sin nota
            resultadoObtiene: 'Resultado',
            observacionTiempo: null,
            perfiles: [],
            puntosAtencion: [],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'tramite-en-dias');

        $this->assertArrayHasKey('procedencia_origen_por_campo', $atributos);
        $this->assertSame('fuente', $atributos['procedencia_origen_por_campo']['tiempo_solucion_dias']);
    }

    public function test_atributos_con_modalidad_presencial(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T9995',
            nombre: 'Trámite presencial',
            proposito: null,
            tipoTramite: 'Presencial',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'NO',
            tiempoObtencion: '5 DIA(S)',
            resultadoObtiene: 'Resultado',
            observacionTiempo: null,
            perfiles: [],
            puntosAtencion: [],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'tramite-presencial');

        $this->assertSame('presencial', $atributos['modalidad']->value);
    }

    public function test_atributos_con_modalidad_en_linea(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T9994',
            nombre: 'Trámite en línea',
            proposito: null,
            tipoTramite: 'En Línea',
            urlTramiteEnLinea: 'https://example.com',
            paginaWeb: null,
            costoDeclarado: 'NO',
            tiempoObtencion: '5 DIA(S)',
            resultadoObtiene: 'Resultado',
            observacionTiempo: null,
            perfiles: [],
            puntosAtencion: [],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'tramite-en-linea');

        $this->assertSame('en_linea', $atributos['modalidad']->value);
    }

    public function test_atributos_con_costo_no(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T9993',
            nombre: 'Trámite gratuito',
            proposito: null,
            tipoTramite: 'Virtual',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'NO', // match case 'NO' → GRATUITO
            tiempoObtencion: '5 DIA(S)',
            resultadoObtiene: 'Resultado',
            observacionTiempo: null,
            perfiles: [],
            puntosAtencion: [],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'tramite-gratuito');

        $this->assertSame('gratuito', $atributos['tiene_costo']->value);
    }

    public function test_atributos_con_costo_desconocido_devuelve_null(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T9992',
            nombre: 'Trámite costo desconocido',
            proposito: null,
            tipoTramite: 'Virtual',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'DESCONOCIDO', // No match → null
            tiempoObtencion: '5 DIA(S)',
            resultadoObtiene: 'Resultado',
            observacionTiempo: null,
            perfiles: [],
            puntosAtencion: [],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'tramite-costo-desconocido');

        $this->assertNull($atributos['tiene_costo']);
    }

    public function test_atributos_sin_perfiles_marca_como_faltante(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T9991',
            nombre: 'Sin perfiles',
            proposito: null,
            tipoTramite: 'Virtual',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'NO',
            tiempoObtencion: '5 DIA(S)',
            resultadoObtiene: 'Resultado',
            observacionTiempo: null,
            perfiles: [], // vacío → se marca como faltante
            puntosAtencion: [],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'sin-perfiles');

        $this->assertContains('perfiles', $atributos['procedencia_faltantes']);
    }

    public function test_atributos_sin_normativa_marca_como_faltante(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T9990',
            nombre: 'Sin normativa',
            proposito: null,
            tipoTramite: 'Virtual',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'NO',
            tiempoObtencion: '5 DIA(S)',
            resultadoObtiene: 'Resultado',
            observacionTiempo: null,
            perfiles: [],
            puntosAtencion: [],
            normativa: [], // vacío → se marca como faltante
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'sin-normativa');

        $this->assertContains('normativa', $atributos['procedencia_faltantes']);
    }
}
