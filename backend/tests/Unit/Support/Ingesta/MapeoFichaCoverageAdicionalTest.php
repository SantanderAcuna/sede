<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Ingesta;

use App\Support\Ingesta\AccionFuente;
use App\Support\Ingesta\FichaFuente;
use App\Support\Ingesta\MapeoFicha;
use Tests\TestCase;

/**
 * Tests adicionales para coverage de MapeoFicha.
 *
 * @covers \App\Support\Ingesta\MapeoFicha
 */
final class MapeoFichaCoverageAdicionalTest extends TestCase
{
    private MapeoFicha $mapeo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapeo = new MapeoFicha;
    }

    public function test_atributos_con_costo_valor_numerico(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T8001',
            nombre: 'Con costo fijo',
            proposito: null,
            tipoTramite: 'Virtual',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'FIJO',
            tiempoObtencion: '5 DIA(S)',
            resultadoObtiene: 'Resultado',
            observacionTiempo: null,
            perfiles: [],
            puntosAtencion: [],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [new AccionFuente('PAGO', 1, 'Pagar', [
                'TipoAccionCondicion' => 'PAGO',
                'Orden' => 1,
                'data' => [[
                    'Descripcion' => 'Pago en línea',
                    'Moneda' => 'Pesos ($)',
                    'TipoValor' => 'FIJO',
                    'Valor' => '50000',
                ]],
            ])],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'con-costo-fijo');

        $this->assertArrayHasKey('costo', $atributos);
    }

    public function test_atributos_con_costo_smlv(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T8002',
            nombre: 'Con costo SMMLV',
            proposito: null,
            tipoTramite: 'Virtual',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'SMLV',
            tiempoObtencion: '5 DIA(S)',
            resultadoObtiene: 'Resultado',
            observacionTiempo: null,
            perfiles: [],
            puntosAtencion: [],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [new AccionFuente('PAGO', 1, 'Pagar', [
                'TipoAccionCondicion' => 'PAGO',
                'Orden' => 1,
                'data' => [[
                    'Descripcion' => 'Pago',
                    'Moneda' => 'Pesos ($)',
                    'TipoValor' => 'SMLV',
                    'Valor' => '1',
                ]],
            ])],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'con-costo-smlv');

        $this->assertArrayHasKey('costo', $atributos);
    }

    public function test_atributos_con_costo_rango(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T8003',
            nombre: 'Con costo rango',
            proposito: null,
            tipoTramite: 'Virtual',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'RANGO',
            tiempoObtencion: '5 DIA(S)',
            resultadoObtiene: 'Resultado',
            observacionTiempo: null,
            perfiles: [],
            puntosAtencion: [],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [new AccionFuente('PAGO', 1, 'Pagar', [
                'TipoAccionCondicion' => 'PAGO',
                'Orden' => 1,
                'data' => [[
                    'Descripcion' => 'Pago rango',
                    'Moneda' => 'Pesos ($)',
                    'TipoValor' => 'RANGO',
                    'Valor' => '10000-50000',
                ]],
            ])],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'con-costo-rango');

        $this->assertArrayHasKey('costo', $atributos);
    }

    public function test_atributos_con_seguimiento_presencial(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T8004',
            nombre: 'Con seguimiento presencial',
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
            seguimientoPersonal: [[
                'TipoCanal' => 'PRESENCIAL',
                'UrlCanalWeb' => null,
                'Correo' => null,
                'NumeroTelefono' => null,
                'NombreCanalWeb' => null,
                'HorarioAtencionTelef' => null,
            ]],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'seguimiento-presencial');

        $this->assertArrayHasKey('canales_consulta_estado', $atributos);
    }

    public function test_atributos_con_seguimiento_telefonico(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T8005',
            nombre: 'Con seguimiento telefónico',
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
            normativa: [],
            seguimientoPersonal: [[
                'TipoCanal' => 'TELEFONO',
                'UrlCanalWeb' => null,
                'Correo' => null,
                'NumeroTelefono' => '1234567',
                'NombreCanalWeb' => null,
                'HorarioAtencionTelef' => null,
            ]],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'seguimiento-telefonico');

        $this->assertArrayHasKey('canales_consulta_estado', $atributos);
    }

    public function test_atributos_con_seguimiento_correo(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T8006',
            nombre: 'Con seguimiento por correo',
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
            normativa: [],
            seguimientoPersonal: [[
                'TipoCanal' => 'CORREO',
                'UrlCanalWeb' => null,
                'Correo' => 'test@example.com',
                'NumeroTelefono' => null,
                'NombreCanalWeb' => null,
                'HorarioAtencionTelef' => null,
            ]],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'seguimiento-correo');

        $this->assertArrayHasKey('canales_consulta_estado', $atributos);
    }

    public function test_atributos_con_url_absoluta(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T8007',
            nombre: 'Con URL absoluta',
            proposito: null,
            tipoTramite: 'Virtual',
            urlTramiteEnLinea: 'https://tramites.santamarta.gov.co/servicio',
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

        $atributos = $this->mapeo->atributos($ficha, 'con-url-absoluta');

        $this->assertArrayHasKey('url_inicio', $atributos);
        $this->assertSame('https://tramites.santamarta.gov.co/servicio', $atributos['url_inicio']);
    }

    public function test_atributos_sin_url_inicio(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T8008',
            nombre: 'Sin URL de inicio',
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

        $atributos = $this->mapeo->atributos($ficha, 'sin-url-inicio');

        $this->assertNull($atributos['url_inicio']);
    }

    public function test_atributos_con_perfiles(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T8009',
            nombre: 'Con perfiles',
            proposito: null,
            tipoTramite: 'Virtual',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'NO',
            tiempoObtencion: '5 DIA(S)',
            resultadoObtiene: 'Resultado',
            observacionTiempo: null,
            perfiles: [
                ['detalle' => 'Ciudadano'],
                ['detalle' => 'Empresa'],
            ],
            puntosAtencion: [],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'con-perfiles');

        $this->assertArrayHasKey('perfiles', $atributos);
        $this->assertCount(2, $atributos['perfiles']);
    }

    public function test_atributos_con_requisitos_documento(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T8010',
            nombre: 'Con requisitos documento',
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
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [new AccionFuente('DOCUMENTO', 1, 'Presentar documento', [
                'TipoAccionCondicion' => 'DOCUMENTO',
                'Orden' => 1,
                'data' => [[
                    'DocumentoNombre' => 'Cédula de ciudadanía',
                    'Cantidad' => 2,
                    'UnidadCantidad' => 'Original(es)',
                    'DocumentoAnotacionAdicional' => 'Vigencia 30 días',
                ]],
            ])],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'con-requisitos-documento');

        $this->assertArrayHasKey('requisitos', $atributos);
        $this->assertNotEmpty($atributos['requisitos']);
    }

    public function test_atributos_con_requisitos_formulario(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T8011',
            nombre: 'Con requisitos formulario',
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
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [new AccionFuente('FORMULARIO', 1, 'Diligenciar formulario', [
                'TipoAccionCondicion' => 'FORMULARIO',
                'Orden' => 1,
                'data' => [[
                    'FormularioNombre' => 'Formulario único',
                ]],
            ])],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'con-requisitos-formulario');

        $this->assertArrayHasKey('requisitos', $atributos);
    }

    public function test_atributos_con_normativa_con_url(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T8012',
            nombre: 'Con normativa y descarga',
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
            normativa: [[
                'TipoNorma' => 'Acuerdo',
                'NumeroNorma' => '004',
                'AnoNorma' => '2020',
                'Articulos' => 'Artículo 10',
                'UrlNorma' => 'https://example.com/norma',
                'UrlDescarga' => 'https://example.com/descarga',
            ]],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'con-normativa-descarga');

        $this->assertArrayHasKey('normativa', $atributos);
        $this->assertSame('https://example.com/descarga', $atributos['normativa'][0]['url_descarga']);
    }

    public function test_atributos_con_normativa_sin_url_devuelve_null(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T8013',
            nombre: 'Con normativa sin descarga',
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
            normativa: [[
                'TipoNorma' => 'Acuerdo',
                'NumeroNorma' => '004',
                'AnoNorma' => '2020',
                'Articulos' => 'Artículo 10',
                'UrlNorma' => null,
                'UrlDescarga' => null,
            ]],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'con-normativa-sin-descarga');

        $this->assertArrayHasKey('normativa', $atributos);
        $this->assertNull($atributos['normativa'][0]['url_descarga']);
    }

    public function test_atributos_con_tiempo_en_meses(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T8014',
            nombre: 'Trámite en meses',
            proposito: null,
            tipoTramite: 'Virtual',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'NO',
            tiempoObtencion: '3 MES(ES)',
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

        $atributos = $this->mapeo->atributos($ficha, 'tramite-en-meses');

        // 3 meses = 90 días
        $this->assertSame(90, $atributos['tiempo_solucion_dias']);
    }

    public function test_atributos_cuando_todo_esta_presente(): void
    {
        $ficha = new FichaFuente(
            codigo: 'T8015',
            nombre: 'Completo',
            proposito: 'Propósito completo',
            tipoTramite: 'SemiPresencial',
            urlTramiteEnLinea: 'https://example.com',
            paginaWeb: 'https://example.com',
            costoDeclarado: 'SI',
            tiempoObtencion: '10 DIA(S)',
            resultadoObtiene: 'Resultado conocido',
            observacionTiempo: null,
            perfiles: [['detalle' => 'Ciudadano']],
            puntosAtencion: [[
                'PuntoAtencionId' => '1',
                'PuntoAtencionNombre' => 'Oficina principal',
                'HorarioAtencion' => '8am-5pm',
                'PuntoAtencionDireccion' => 'Calle 1 # 1-1',
                'PuntoAtencionTelefono' => '6000000',
                'Latitud' => '11.0',
                'Longitud' => '-74.0',
                'Municipio' => 'Santa Marta',
                'Departamento' => 'Magdalena',
            ]],
            normativa: [[
                'TipoNorma' => 'Acuerdo',
                'NumeroNorma' => '001',
                'AnoNorma' => '2020',
                'Articulos' => 'Artículo 1',
                'UrlNorma' => null,
                'UrlDescarga' => null,
            ]],
            seguimientoPersonal: [[
                'TipoCanal' => 'PRESENCIAL',
                'UrlCanalWeb' => null,
                'Correo' => null,
                'NumeroTelefono' => null,
                'NombreCanalWeb' => null,
                'HorarioAtencionTelef' => null,
            ]],
            seguimientoNoPersonal: [],
            acciones: [new AccionFuente('DOCUMENTO', 1, 'Presentar', [
                'TipoAccionCondicion' => 'DOCUMENTO',
                'Orden' => 1,
                'data' => [['DocumentoNombre' => 'Documento']],
            ])],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'tramite-completo');

        // Todo presente → ninguna marca de faltante
        $this->assertNotContains('perfiles', $atributos['procedencia_faltantes']);
        $this->assertNotContains('normativa', $atributos['procedencia_faltantes']);
        $this->assertNotContains('resultado', $atributos['procedencia_faltantes']);
        $this->assertNotContains('tiempo_solucion_dias', $atributos['procedencia_faltantes']);
    }
}
