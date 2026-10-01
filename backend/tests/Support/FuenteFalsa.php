<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\Integraciones\FuenteFichaGovCoInterface;
use App\Exceptions\FuenteNoDisponible;
use App\Support\Ingesta\AccionFuente;
use App\Support\Ingesta\FichaFuente;

/**
 * Una fuente de fichas de mentira, para poder probar la ingesta sin red.
 *
 * Sustituye a `FuenteFichaGovCo` en las pruebas por una razón que no es sólo
 * velocidad: la API de contenido de GOV.CO **limita la tasa y cambia sin avisar**.
 * Una prueba que dependiera de ella fallaría los días que la fuente decide no
 * contestar, y lo que hay que comprobar aquí —que la ingesta reanude, que no
 * duplique, que descarte los datos personales— no depende de la red.
 *
 * Lleva la cuenta de **qué códigos se pidieron**, que es lo que permite afirmar
 * que una ejecución reanudada no vuelve a pedir lo que ya trajo: sin esa lista, la
 * prueba sólo podría comprobar que el catálogo quedó bien, y un catálogo puede
 * quedar bien habiendo pedido los 124 trámites dos veces.
 */
final class FuenteFalsa implements FuenteFichaGovCoInterface
{
    /** @var array<string, FichaFuente> */
    private array $fichas = [];

    /** @var list<string> */
    public array $pedidas = [];

    /** @var array<string, string> */
    private array $fallos = [];

    /** Añade una ficha que la fuente sabrá devolver. */
    public function agregar(string $codigo, ?FichaFuente $ficha = null): self
    {
        $this->fichas[$codigo] = $ficha ?? FichaFalsa::para($codigo);

        return $this;
    }

    /** Hace que un código falle, como si la fuente se hubiera cortado a mitad. */
    public function romper(string $codigo, string $motivo = 'La fuente dejó de responder.'): self
    {
        $this->fallos[$codigo] = $motivo;

        return $this;
    }

    /** Deja de fallar: la fuente se recuperó. */
    public function arreglar(string $codigo): self
    {
        unset($this->fallos[$codigo]);

        return $this;
    }

    public function ficha(string $codigo): FichaFuente
    {
        $this->pedidas[] = $codigo;

        if (isset($this->fallos[$codigo])) {
            throw FuenteNoDisponible::porRespuesta($codigo, 'ficha', 500, 5);
        }

        return $this->fichas[$codigo] ?? throw FuenteNoDisponible::porRespuesta($codigo, 'ficha', 500, 5);
    }
}

/**
 * Una ficha de mentira con los datos verificados del impuesto predial unificado.
 *
 * Los valores no son inventados: salen de la respuesta real de la API para el
 * trámite `T2621`, comprobada con `curl`. Se usan los reales y no unos de relleno
 * porque lo que hay que probar es justo el comportamiento frente a los casos que
 * la fuente produce: un teléfono móvil en un punto de atención, unas coordenadas
 * imposibles, un costo calculado y un término declarado en horas.
 */
final class FichaFalsa
{
    /**
     * Una ficha a la que la fuente no le declara un bloque.
     *
     * Sirve para comprobar la mitad del valor del informe: que un trámite
     * incompleto se **declare** con el campo que le falta en vez de publicarse
     * como si estuviera entero. Se construye quitando, y no poniendo, para que la
     * prueba diga en su propio código qué falta.
     */
    public static function sin(string $codigo, string $campo): FichaFuente
    {
        $ficha = self::para($codigo);

        return match ($campo) {
            'modalidad' => new FichaFuente(
                ...array_merge(self::propiedades($ficha), ['tipoTramite' => null]),
            ),
            'costo' => new FichaFuente(
                ...array_merge(self::propiedades($ficha), ['costoDeclarado' => null]),
            ),
            'tiempo_solucion_dias' => new FichaFuente(
                ...array_merge(self::propiedades($ficha), ['tiempoObtencion' => null]),
            ),
            'requisitos' => new FichaFuente(
                ...array_merge(self::propiedades($ficha), ['acciones' => []]),
            ),
            default => $ficha,
        };
    }

    /**
     * Las propiedades de una ficha como arreglo con nombre, para poder sustituir
     * una sola sin repetir las demás.
     *
     * @return array<string, mixed>
     */
    private static function propiedades(FichaFuente $ficha): array
    {
        return [
            'codigo' => $ficha->codigo,
            'nombre' => $ficha->nombre,
            'proposito' => $ficha->proposito,
            'tipoTramite' => $ficha->tipoTramite,
            'urlTramiteEnLinea' => $ficha->urlTramiteEnLinea,
            'paginaWeb' => $ficha->paginaWeb,
            'costoDeclarado' => $ficha->costoDeclarado,
            'tiempoObtencion' => $ficha->tiempoObtencion,
            'resultadoObtiene' => $ficha->resultadoObtiene,
            'observacionTiempo' => $ficha->observacionTiempo,
            'perfiles' => $ficha->perfiles,
            'puntosAtencion' => $ficha->puntosAtencion,
            'normativa' => $ficha->normativa,
            'seguimientoPersonal' => $ficha->seguimientoPersonal,
            'seguimientoNoPersonal' => $ficha->seguimientoNoPersonal,
            'acciones' => $ficha->acciones,
            'faltantes' => $ficha->faltantes,
        ];
    }

    public static function para(string $codigo): FichaFuente
    {
        $numero = ltrim($codigo, 'Tt');

        return new FichaFuente(
            codigo: $codigo,
            nombre: 'Impuesto predial unificado',
            proposito: 'Pago que todo propietario, poseedor o quien disfrute del bien ajeno, debe realizar sobre los bienes inmuebles o predios ubicados en la respectiva jurisdicción del Municipio o Distrito',
            tipoTramite: 'SemiPresencial',
            urlTramiteEnLinea: 'https://impuestos.santamarta.gov.co:8443/autoservicios.jsf',
            paginaWeb: 'http://www.santamarta.gov.co',
            costoDeclarado: 'SI',
            tiempoObtencion: '2 HORA(S) ',
            resultadoObtiene: 'Formulario o factura del impuesto predial con sello de pago que se obtiene en 2 HORA(S)',
            observacionTiempo: null,
            perfiles: [
                ['detalle' => 'Ciudadano'],
                ['detalle' => 'Extranjero'],
                ['detalle' => 'Empresa privada'],
                ['detalle' => 'Entidad pública'],
            ],
            puntosAtencion: [[
                'PuntoAtencionId' => '4167',
                'PuntoAtencionNombre' => 'Dirección de Impuestos',
                'HorarioAtencion' => 'Lunes a viernes de 8:00 a 11:30 am y 2:00 a 5:30 pm',
                'PuntoAtencionDireccion' => 'Calle 14 N 2-49 Primer Piso',
                'PuntoAtencionTelefono' => '(5) 4209600 - 1231',
                'Latitud' => '11.24516286',
                'Longitud' => '-74.21307865',
                'Municipio' => 'Santa marta',
                'Departamento' => 'Magdalena',
            ], [
                // El caso que hay que descartar, con los valores reales de la
                // fuente para el certificado de residencia: un móvil de diez
                // dígitos y unas coordenadas que caen en el golfo de Guinea.
                'PuntoAtencionId' => '20387',
                'PuntoAtencionNombre' => 'ALCALDIA LOCAL 3',
                'HorarioAtencion' => '8am - 4:30pm',
                'PuntoAtencionDireccion' => 'CR CR 4 # 21 - 180',
                'PuntoAtencionTelefono' => '3007659666',
                'Latitud' => '20',
                'Longitud' => '30',
                'Municipio' => 'Santa marta',
                'Departamento' => 'Magdalena',
            ]],
            normativa: [[
                'TipoNorma' => 'Acuerdo',
                'NumeroNorma' => '004',
                'AnoNorma' => '2016',
                'Articulos' => 'articulo 27, 40',
                'UrlNorma' => null,
                'UrlDescarga' => 'https://tramites1.suit.gov.co/registro-web/suit_descargar_archivo?A=79463',
            ], [
                'TipoNorma' => 'Decreto',
                'NumeroNorma' => '1333',
                'AnoNorma' => '1986',
                'Articulos' => 'Artículos 171-194, 261',
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
            seguimientoNoPersonal: [[
                'TipoCanal' => 'Aprenda con tutoriales',
                'UrlCanalWeb' => '',
                'Correo' => 'Ver tutoriales',
                'NumeroTelefono' => '',
                'NombreCanalWeb' => '',
                'HorarioAtencionTelef' => '',
            ]],
            acciones: [
                new AccionFuente('SOLICITUD', 1, 'Cumplir condiciones', [
                    'TipoAccionCondicion' => 'SOLICITUD',
                    'Orden' => 1,
                    'data' => [[
                        'TipoCanal' => 'WEB',
                        'UrlCanalWeb' => 'https://impuestos.santamarta.gov.co:8443/autoservicios.jsf',
                        'NombreCanalWeb' => 'Liquidación Impuesto Predial vigencia actual y anteriores',
                        'Correo' => null,
                    ], [
                        'TipoCanal' => 'PRESENCIAL',
                        'UrlCanalWeb' => null,
                        'NombreCanalWeb' => null,
                        'Correo' => null,
                    ]],
                ]),
                new AccionFuente('VERIFICACION_INST', 2, 'Cumplir condiciones', [
                    'TipoAccionCondicion' => 'VERIFICACION_INST',
                    'Orden' => 2,
                    'data' => [[
                        'VerificacionInstDescripcion' => 'Ser Propietario o Poseedor de un Bien inmueble en el Municipio de Santa Marta',
                    ]],
                ]),
                new AccionFuente('DOCUMENTO', 3, 'Reunir documentos', [
                    'TipoAccionCondicion' => 'DOCUMENTO',
                    'Orden' => 3,
                    'data' => [[
                        'DocumentoNombre' => 'Cédula de ciudadanía ',
                        'Cantidad' => 1.0,
                        'UnidadCantidad' => 'Original(es)',
                        'DocumentoAnotacionAdicional' => null,
                    ]],
                ]),
                new AccionFuente('PAGO', 1, 'Realizar el pago', [
                    'TipoAccionCondicion' => 'PAGO',
                    'Orden' => 1,
                    'PagoDisponibleEnLinea' => '0',
                    'PagoDispInstDescripcion' => 'Puede realizar el pago en el punto de recaudación ubicado en la Dirección de impuestos.',
                    'PagoEnlineaUrl' => 'https://impuestos.santamarta.gov.co:8443/autoservicios.jsf',
                    'data' => [[
                        'Descripcion' => 'De acuerdo con el avalúo catastral del predio y el Estatuto Tributario Distrital del Acuerdo 004 del 2016, el pago se puede realizar con PSE y con tarjeta débito.',
                        'Moneda' => 'Pesos ($)',
                        'TipoValor' => 'AVALUO_LIQUIDACION',
                        'Valor' => null,
                    ]],
                    'entidadesPago' => [[
                        'TipoCuenta' => 'AHORROS',
                        'NombreCuenta' => 'D.T.C.H Santa Marta',
                        'NombreEntidad' => 'Banco de Bogotá',
                        'NumeroCuenta' => '070091897',
                        'CodigoRecaudo' => null,
                    ], [
                        // Una cuenta a nombre de una persona: no se publica.
                        'TipoCuenta' => 'AHORROS',
                        'NombreCuenta' => 'María Fernanda Ospina Rangel',
                        'NombreEntidad' => 'Banco Popular',
                        'NumeroCuenta' => '220400314639',
                        'CodigoRecaudo' => null,
                    ]],
                ]),
            ],
            faltantes: [],
        );
    }
}
