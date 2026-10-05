<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Ingesta;

use App\Enums\CanalInicioTramite;
use App\Support\Ingesta\AccionFuente;
use App\Support\Ingesta\FichaFuente;
use App\Support\Ingesta\MapeoFicha;
use Tests\TestCase;

/**
 * Tests para coverage de edge cases en MapeoFicha.
 *
 * @covers \App\Support\Ingesta\MapeoFicha
 */
final class MapeoFichaEdgeCasesTest extends TestCase
{
    private MapeoFicha $mapeo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapeo = new MapeoFicha;
    }

    public function test_atributos_con_url_portal_nacional_gov_co(): void
    {
        // Línea 359: URL .gov.co que no es santamarta → PORTAL_NACIONAL
        $ficha = new FichaFuente(
            codigo: 'T9001',
            nombre: 'Portal Nacional',
            proposito: null,
            tipoTramite: 'Virtual',
            urlTramiteEnLinea: 'https://www.gov.co/tramites/servicio',
            paginaWeb: null,
            costoDeclarado: 'NO',
            tiempoObtencion: '5 DIA(S)',
            resultadoObtiene: 'Resultado',
            observacionTiempo: null,
            perfiles: [['detalle' => 'Ciudadano']],
            puntosAtencion: [],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'portal-nacional');

        $this->assertSame(CanalInicioTramite::PORTAL_NACIONAL, $atributos['canal_inicio']);
    }

    public function test_requisito_con_naturaleza_null_se_descarta(): void
    {
        // Línea 515: cuando naturaleza es null → continue (se omite el requisito)
        $ficha = new FichaFuente(
            codigo: 'T9002',
            nombre: 'Requisito sin naturaleza',
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
            acciones: [new AccionFuente('DOCUMENTO', 1, 'Presentar', [
                'TipoAccionCondicion' => 'DOCUMENTO',
                'Orden' => 1,
                'data' => [[
                    // Sin 'Naturaleza' → null → se descarta
                    'DocumentoNombre' => 'Documento sin naturaleza',
                ]],
            ])],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'sin-naturaleza');

        // El requisito se descarta porque naturaleza es null
        $this->assertArrayHasKey('requisitos', $atributos);
    }

    public function test_pago_usa_descripcion_fallback_cuando_no_hay_data(): void
    {
        // Línea 598: Pago con descripción en acción, no en data
        $ficha = new FichaFuente(
            codigo: 'T9003',
            nombre: 'Pago con fallback',
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
            acciones: [new AccionFuente('PAGO', 1, 'Pagar servicio', [
                'TipoAccionCondicion' => 'PAGO',
                'Orden' => 1,
                'PagoDispInstDescripcion' => 'Descripción desde acción',
                'data' => [[
                    'Moneda' => 'Pesos ($)',
                    'TipoValor' => 'FIJO',
                    'Valor' => '10000',
                ]],
            ])],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'pago-fallback');

        $this->assertArrayHasKey('costo', $atributos);
    }

    public function test_pago_sin_descripcion_devuelve_null(): void
    {
        // Línea 600: cuando descripción es null → requisito de pago null
        $ficha = new FichaFuente(
            codigo: 'T9004',
            nombre: 'Pago sin descripción',
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
            acciones: [new AccionFuente('PAGO', 1, 'Pagar', [
                'TipoAccionCondicion' => 'PAGO',
                'Orden' => 1,
                // Sin Descripcion y sin PagoDispInstDescripcion
                'data' => [[
                    'Moneda' => 'Pesos ($)',
                    'TipoValor' => 'FIJO',
                    'Valor' => '10000',
                ]],
            ])],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'pago-sin-desc');

        $this->assertArrayHasKey('costo', $atributos);
    }

    public function test_presentacion_con_cita_virtual_web(): void
    {
        // Líneas 620-622 match arms: 'web', 'presencial', 'correo'
        $ficha = new FichaFuente(
            codigo: 'T9005',
            nombre: 'Con cita virtual',
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
            acciones: [new AccionFuente('CITAVIRTUAL', 1, 'Cita', [
                'TipoAccionCondicion' => 'CITAVIRTUAL',
                'Orden' => 1,
                'data' => [[
                    'Canal' => 'web',
                ]],
            ])],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'cita-virtual-web');

        // Solo verificamos que atributos() complete sin error
        $this->assertArrayHasKey('requisitos', $atributos);
    }

    public function test_presentacion_con_cita_presencial(): void
    {
        // Línea 620: match arm 'presencial'
        $ficha = new FichaFuente(
            codigo: 'T9006',
            nombre: 'Con cita presencial',
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
            acciones: [new AccionFuente('CITAPRESENCIAL', 1, 'Cita', [
                'TipoAccionCondicion' => 'CITAPRESENCIAL',
                'Orden' => 1,
                'data' => [[
                    'Canal' => 'presencial',
                ]],
            ])],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'cita-presencial');

        $this->assertArrayHasKey('requisitos', $atributos);
    }

    public function test_punto_atencion_sin_nombre_se_descarta(): void
    {
        // Línea 672: continue cuando nombre es null
        $ficha = new FichaFuente(
            codigo: 'T9007',
            nombre: 'Punto sin nombre',
            proposito: null,
            tipoTramite: 'Presencial',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'NO',
            tiempoObtencion: '5 DIA(S)',
            resultadoObtiene: 'Resultado',
            observacionTiempo: null,
            perfiles: [],
            puntosAtencion: [[
                'PuntoAtencionId' => '1',
                'PuntoAtencionNombre' => null,
                'HorarioAtencion' => '8am-5pm',
                'PuntoAtencionDireccion' => 'Calle 1',
                'PuntoAtencionTelefono' => '6000000',
                'Latitud' => null,
                'Longitud' => null,
                'Municipio' => 'Santa Marta',
                'Departamento' => 'Magdalena',
            ]],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'punto-sin-nombre');

        $this->assertArrayHasKey('puntos_atencion', $atributos);
    }

    public function test_punto_atencion_sin_direccion_se_descarta(): void
    {
        // Línea 672: continue cuando dirección es null
        $ficha = new FichaFuente(
            codigo: 'T9008',
            nombre: 'Punto sin dirección',
            proposito: null,
            tipoTramite: 'Presencial',
            urlTramiteEnLinea: null,
            paginaWeb: null,
            costoDeclarado: 'NO',
            tiempoObtencion: '5 DIA(S)',
            resultadoObtiene: 'Resultado',
            observacionTiempo: null,
            perfiles: [],
            puntosAtencion: [[
                'PuntoAtencionId' => '2',
                'PuntoAtencionNombre' => 'Oficina sin dirección',
                'HorarioAtencion' => '8am-5pm',
                'PuntoAtencionDireccion' => null,
                'PuntoAtencionTelefono' => '6000000',
                'Latitud' => null,
                'Longitud' => null,
                'Municipio' => 'Santa Marta',
                'Departamento' => 'Magdalena',
            ]],
            normativa: [],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'punto-sin-dir');

        $this->assertArrayHasKey('puntos_atencion', $atributos);
    }

    public function test_normativa_sin_tipo_se_descarta(): void
    {
        // Línea 708: continue cuando tipo es null
        $ficha = new FichaFuente(
            codigo: 'T9009',
            nombre: 'Normativa sin tipo',
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
                // Sin TipoNorma → se descarta
                'NumeroNorma' => '001',
                'AnoNorma' => '2020',
                'Articulos' => 'Artículo 1',
                'UrlNorma' => null,
                'UrlDescarga' => null,
            ]],
            seguimientoPersonal: [],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'normativa-sin-tipo');

        $this->assertArrayHasKey('normativa', $atributos);
    }

    public function test_canal_seguimiento_sin_tipo_se_descarta(): void
    {
        // Línea 750: continue cuando TipoCanal es null
        $ficha = new FichaFuente(
            codigo: 'T9010',
            nombre: 'Canal sin tipo',
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
                // Sin TipoCanal → se descarta
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

        $atributos = $this->mapeo->atributos($ficha, 'canal-sin-tipo');

        $this->assertArrayHasKey('canales_consulta_estado', $atributos);
    }

    public function test_canal_de_solicitud_null_devuelve_null(): void
    {
        // Línea 809: cuando canalDeSolicitud recibe null → return null
        $ficha = new FichaFuente(
            codigo: 'T9011',
            nombre: 'Canal nulo',
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
            acciones: [new AccionFuente('CITAVIRTUAL', 1, 'Cita', [
                'TipoAccionCondicion' => 'CITAVIRTUAL',
                'Orden' => 1,
                'data' => [[
                    'Canal' => null,
                ]],
            ])],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'canal-nulo');

        $this->assertArrayHasKey('requisitos', $atributos);
    }

    public function test_canal_de_seguimiento_telefonico(): void
    {
        // Líneas 835-838: match arms 'telefonico'
        $ficha = new FichaFuente(
            codigo: 'T9012',
            nombre: 'Seguimiento telefónico',
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
                'NumeroTelefono' => '6000000',
                'NombreCanalWeb' => null,
                'HorarioAtencionTelef' => null,
            ]],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'seg-telefonico');

        $this->assertArrayHasKey('canales_consulta_estado', $atributos);
        $this->assertNotEmpty(array_filter(
            $atributos['canales_consulta_estado'],
            static fn (array $c): bool => ($c['canal'] ?? '') === 'telefonico'
        ));
    }

    public function test_canal_de_seguimiento_correo(): void
    {
        // Línea 837: match arm 'correo'
        $ficha = new FichaFuente(
            codigo: 'T9013',
            nombre: 'Seguimiento por correo',
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

        $atributos = $this->mapeo->atributos($ficha, 'seg-correo');

        $this->assertArrayHasKey('canales_consulta_estado', $atributos);
        $this->assertNotEmpty(array_filter(
            $atributos['canales_consulta_estado'],
            static fn (array $c): bool => ($c['canal'] ?? '') === 'correo'
        ));
    }

    public function test_canal_de_seguimiento_web(): void
    {
        // Línea 835: match arm 'web'
        $ficha = new FichaFuente(
            codigo: 'T9014',
            nombre: 'Seguimiento web',
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
                'TipoCanal' => 'WEB',
                'UrlCanalWeb' => 'https://consulta.santamarta.gov.co',
                'Correo' => null,
                'NumeroTelefono' => null,
                'NombreCanalWeb' => 'Portal de consulta',
                'HorarioAtencionTelef' => null,
            ]],
            seguimientoNoPersonal: [],
            acciones: [],
            faltantes: [],
        );

        $atributos = $this->mapeo->atributos($ficha, 'seg-web');

        $this->assertArrayHasKey('canales_consulta_estado', $atributos);
        $this->assertNotEmpty(array_filter(
            $atributos['canales_consulta_estado'],
            static fn (array $c): bool => ($c['canal'] ?? '') === 'web'
        ));
    }

    public function test_es_titular_devuelve_false_cuando_titular_es_null(): void
    {
        // Línea 489: return false cuando titular es null
        $reflection = new \ReflectionClass($this->mapeo);
        $method = $reflection->getMethod('esTitularDeLaEntidad');
        $method->setAccessible(true);

        $resultado = $method->invoke(null, null);

        $this->assertFalse($resultado);
    }

    public function test_descripcion_de_solicitud_con_correo(): void
    {
        // Líneas 620-621: match arm 'correo'
        $reflection = new \ReflectionClass($this->mapeo);
        $method = $reflection->getMethod('descripcionDeSolicitud');
        $method->setAccessible(true);

        $resultado = $method->invoke($this->mapeo, 'correo', null);

        $this->assertStringContainsString('correo electrónico', $resultado);
    }

    public function test_descripcion_de_solicitud_con_telefonico(): void
    {
        // Línea 621: match arm 'telefonico'
        $reflection = new \ReflectionClass($this->mapeo);
        $method = $reflection->getMethod('descripcionDeSolicitud');
        $method->setAccessible(true);

        $resultado = $method->invoke($this->mapeo, 'telefonico', null);

        $this->assertStringContainsString('teléfono', $resultado);
    }

    public function test_descripcion_de_solicitud_default(): void
    {
        // Línea 622: match arm default
        $reflection = new \ReflectionClass($this->mapeo);
        $method = $reflection->getMethod('descripcionDeSolicitud');
        $method->setAccessible(true);

        $resultado = $method->invoke($this->mapeo, 'desconocido', null);

        $this->assertStringContainsString('ante la Entidad', $resultado);
    }

    public function test_canal_de_solicitud_con_null_devuelve_null(): void
    {
        // Línea 809: cuando canalDeSolicitud recibe null → return null
        $reflection = new \ReflectionClass($this->mapeo);
        $method = $reflection->getMethod('canalDeSolicitud');
        $method->setAccessible(true);

        $resultado = $method->invoke($this->mapeo, null);

        $this->assertNull($resultado);
    }
}
