<?php

declare(strict_types=1);

namespace App\Support\Ingesta;

use App\Enums\CanalInicioTramite;
use App\Enums\CostoTramite;
use App\Enums\ModalidadTramite;
use App\Models\Tramite;
use App\Services\GovCo\ClienteFichaGovCo;
use App\Support\Tramites\ContactoPublicable;
use App\Support\Tramites\FuenteSuit;
use App\Support\Tramites\TiempoEnDias;
use Illuminate\Support\Carbon;

/**
 * Convierte lo que declara la fuente en los atributos del catálogo.
 *
 * Es la clase donde se decide **qué se publica y qué no**, y por eso no toca ni la
 * red ni la base: recibe una `FichaFuente` y devuelve el arreglo de atributos del
 * modelo. Así lo que más importa —el descarte de datos personales, la no invención
 * de valores y la declaración de lo que falta— se puede probar sin red y sin base.
 *
 * # Las cinco naturalezas del requisito
 *
 * La fuente devuelve cada exigencia con un `TipoAccionCondicion`, y ese tipo se
 * traduce a las cinco naturalezas que el contrato declara. No es una lista de
 * frases porque el ciudadano necesita distinguir un documento que hay que llevar
 * de una condición que hay que cumplir: lo primero se prepara antes de salir de
 * casa y lo segundo no se prepara, se cumple o no se cumple.
 *
 * # Lo que no se inventa
 *
 * Ningún valor ausente se rellena con un valor por defecto. Un campo que la fuente
 * no declara viaja nulo y se anota en `procedencia.faltantes`. La única excepción
 * es `consulta_estado`, que **es un dato de la Sede y no del trámite**: quien
 * consulta el estado lo hace por radicado en `/seguimiento`, el mecanismo es el
 * mismo para los 124 y no hay 124 maneras distintas de consultar. Se declara como
 * `entidad` en la procedencia para que se vea que no viene de la fuente.
 */
final class MapeoFicha
{
    /** El nombre del documento que contiene los requisitos, por si hay que leerlos. */
    private const DOCUMENTO = 'Ficha oficial del trámite en GOV.CO (SUIT)';

    /**
     * El mecanismo con el que la Sede permite consultar el estado de una
     * solicitud, por radicado. Es de la Sede, no del trámite.
     */
    private const CONSULTA_ESTADO = '/seguimiento?radicado=…';

    /**
     * Los atributos que el trámite necesita para publicarse, con el nombre que
     * tienen en el contrato, en el orden en que el informe los nombra.
     */
    public const ATRIBUTOS = [
        'modalidad',
        'tiene_costo',
        'tiempo_solucion_dias',
        'canal_inicio',
        'consulta_estado',
        'requisitos',
        'url_ficha_gov_co',
    ];

    /**
     * El valor de `costo.tipo_valor` que significa «el importe se calcula».
     *
     * La fuente lo declara así para el impuesto predial: valor nulo y tipo
     * `AVALUO_LIQUIDACION`, es decir un costo que depende del avalúo del predio.
     * Publicarlo como cero sería decirle al ciudadano que no paga.
     */
    private const TIPOS_DE_VALOR = [
        'AVALUO_LIQUIDACION' => 'avaluo_liquidacion',
        'SMLV' => 'smlv',
        'SALARIO MINIMO' => 'smlv',
        'FIJO' => 'fijo',
        'RANGO' => 'rango',
    ];

    /**
     * Las naturalezas de requisito que el contrato declara, por el nombre que les
     * da la fuente.
     *
     * Se declaran aquí y no en un `match` disperso para que un tipo nuevo de la
     * fuente no se cuele como si fuera conocido: lo que no está en esta tabla se
     * cuenta y se reporta, en vez de publicarse con la naturaleza de otro.
     */
    private const NATURALEZAS = [
        'DOCUMENTO' => 'documento',
        'VERIFICACION_INST' => 'verificacion_institucional',
        'SOLICITUD' => 'solicitud',
        'FORMULARIO' => 'formulario',
        'PAGO' => 'pago',
    ];

    /**
     * Los atributos del trámite a partir de la ficha de la fuente.
     *
     * @return array<string, mixed>
     */
    public function atributos(FichaFuente $ficha, string $slug): array
    {
        $numero = ClienteFichaGovCo::numero($ficha->codigo);
        $urlFicha = sprintf('https://www.gov.co/ficha-tramites-y-servicios/T%s', $numero);

        $tiempo = TiempoEnDias::desde($ficha->tiempoObtencion);
        $costo = $this->costo($ficha);
        $requisitos = $this->requisitos($ficha);
        $puntos = $this->puntosAtencion($ficha);
        $normativa = $this->normativa($ficha);

        /** @var list<string> $faltantes */
        $faltantes = $ficha->faltantes;

        if ($puntos === []) {
            $faltantes[] = 'puntos_atencion';
        }

        if ($normativa === []) {
            $faltantes[] = 'normativa';
        }

        if ($ficha->perfiles === []) {
            $faltantes[] = 'perfiles';
        }

        if ($ficha->resultadoObtiene === null) {
            $faltantes[] = 'resultado';
        }

        if ($tiempo === null) {
            $faltantes[] = 'tiempo_solucion_dias';
        }

        /** @var list<array{campo: string, regla: string}> $derivados */
        $derivados = [];

        if ($tiempo !== null && $tiempo['nota'] !== null) {
            $derivados[] = ['campo' => 'tiempo_solucion_dias', 'regla' => $tiempo['nota']];
        }

        $derivados[] = [
            'campo' => 'url_ficha_gov_co',
            'regla' => 'La dirección de la ficha en GOV.CO se compone con el código del trámite en SUIT: https://www.gov.co/ficha-tramites-y-servicios/T{código}. La fuente no devuelve este enlace en ninguna de sus respuestas.',
        ];

        $derivados[] = [
            'campo' => 'canal_inicio',
            'regla' => 'El canal es propio cuando el trámite en línea vive en un dominio de la Entidad y es portal nacional cuando vive en un dominio «.gov.co» que no es suyo; sin enlace se declara propio, porque el trámite lo presta la Alcaldía aunque sea en ventanilla.',
        ];

        return [
            'slug' => $slug,
            'nombre' => $ficha->nombre,
            'resumen' => Tramite::recortarResumen($ficha->proposito),

            // Los seis atributos obligatorios.
            'modalidad' => $this->modalidad($ficha),
            'tiene_costo' => $this->tieneCosto($ficha),
            'tiempo_solucion_dias' => $tiempo['dias'] ?? null,
            'canal_inicio' => $this->canal($ficha),
            'consulta_estado' => self::CONSULTA_ESTADO,
            'requisitos' => $requisitos,

            // El costo, con lo que la fuente declara sobre él.
            'costo' => $costo['valor'],
            'costo_tipo_valor' => $costo['tipo_valor'],
            'costo_moneda' => $costo['moneda'],
            'costo_url_pago' => $costo['url_pago'],
            'costo_descripcion' => $costo['descripcion'],
            'costo_cuentas' => $costo['cuentas'],

            'url_inicio' => $this->urlAbsoluta($ficha->urlTramiteEnLinea),
            'documentos' => [
                ['nombre' => self::DOCUMENTO, 'url' => $urlFicha, 'formato' => 'HTML'],
            ],
            'url_ficha_gov_co' => $urlFicha,

            // Lo que acompaña a los seis atributos.
            'resultado' => $ficha->resultadoObtiene,
            'perfiles' => $this->perfiles($ficha),
            'puntos_atencion' => $puntos,
            'normativa' => $normativa,
            'canales_consulta_estado' => $this->canalesDeConsulta($ficha),

            // La fuente oficial no clasifica los trámites por categoría. Inventar
            // la clasificación sería tan falso como inventar el trámite.
            'categoria_slug' => null,
            'categoria_nombre' => null,

            // La procedencia, ahora campo por campo.
            'procedencia_fuente' => FuenteSuit::NOMBRE,
            'procedencia_url' => FuenteSuit::URL,
            'procedencia_api' => FuenteSuit::API_FICHA,
            'procedencia_obtenido_en' => Carbon::now()->format('Y-m-d'),
            'procedencia_nota' => $tiempo['nota'] ?? null,
            'procedencia_origen_por_campo' => $this->origenPorCampo($ficha, $tiempo),
            'procedencia_derivados' => $derivados,
            'procedencia_faltantes' => array_values(array_unique($faltantes)),
        ];
    }

    /**
     * El primer atributo obligatorio que falta, o nulo si no falta ninguno.
     *
     * Se comprueba sobre el resultado del mapeo y no sobre la respuesta: lo que
     * decide si un trámite se publica es que sus valores existan, no que la fuente
     * tuviera la clave.
     *
     * @param  array<string, mixed>  $atributos
     */
    public function atributoQueFalta(array $atributos): ?string
    {
        return $this->atributosQueFaltan($atributos)[0] ?? null;
    }

    /**
     * Todos los atributos obligatorios que faltan.
     *
     * Devuelve la lista entera y no sólo el primero porque el informe tiene que
     * decir **dónde** está a medias cada trámite: decir «le falta modalidad» y
     * callar que además no tiene requisitos obliga a arreglarlo en dos pasadas. Un
     * catálogo a medias que no dice dónde está a medias es peor que uno incompleto
     * declarado.
     *
     * @param  array<string, mixed>  $atributos
     * @return list<string>
     */
    public function atributosQueFaltan(array $atributos): array
    {
        $faltan = [];

        foreach (self::ATRIBUTOS as $atributo) {
            $valor = $atributos[$atributo] ?? null;

            if ($valor === null) {
                $faltan[] = $atributo;

                continue;
            }

            if (is_string($valor) && trim($valor) === '') {
                $faltan[] = $atributo;

                continue;
            }

            if (is_array($valor) && $valor === []) {
                $faltan[] = $atributo;
            }
        }

        return $faltan;
    }

    /**
     * De dónde salió cada campo que el contrato publica.
     *
     * @param  array{dias: int, nota: string|null}|null  $tiempo
     * @return array<string, string>
     */
    private function origenPorCampo(FichaFuente $ficha, ?array $tiempo): array
    {
        return [
            'nombre' => 'fuente',
            'resumen' => 'fuente',
            'modalidad' => $ficha->tipoTramite === null ? 'ausente' : 'fuente',
            'tiene_costo' => $ficha->costoDeclarado === null ? 'ausente' : 'fuente',
            'costo' => $ficha->acciones === [] ? 'ausente' : 'fuente',
            'tiempo_solucion_dias' => $tiempo === null
                ? 'ausente'
                : ($tiempo['nota'] === null ? 'fuente' : 'derivado'),
            // El canal no lo declara la fuente como tal: se deduce de dónde vive
            // el trámite en línea. Por eso es «derivado» aunque el enlace venga de
            // la fuente.
            'canal_inicio' => 'derivado',
            'consulta_estado' => 'entidad',
            'requisitos' => $ficha->acciones === [] ? 'ausente' : 'fuente',
            'documentos' => 'entidad',
            'url_ficha_gov_co' => 'derivado',
            'resultado' => $ficha->resultadoObtiene === null ? 'ausente' : 'fuente',
            'perfiles' => $ficha->perfiles === [] ? 'ausente' : 'fuente',
            'puntos_atencion' => $ficha->puntosAtencion === [] ? 'ausente' : 'fuente',
            'normativa' => $ficha->normativa === [] ? 'ausente' : 'fuente',
            'canales_consulta_estado' => $ficha->seguimientoPersonal === [] && $ficha->seguimientoNoPersonal === []
                ? 'entidad'
                : 'fuente',
        ];
    }

    /**
     * La modalidad declarada por la fuente.
     *
     * La fuente usa tres valores y se traducen uno a uno. Un valor nuevo se deja
     * nulo en vez de interpretarlo: la distinción entre «en línea» y
     * «parcialmente en línea» decide si el ciudadano puede terminar el trámite sin
     * desplazarse, y adivinar cuál de las dos es sería adivinar eso.
     */
    private function modalidad(FichaFuente $ficha): ?ModalidadTramite
    {
        $declarado = $ficha->tipoTramite;

        if ($declarado === null) {
            return null;
        }

        $clave = mb_strtolower(trim($declarado));

        if (str_contains($clave, 'semi') || str_contains($clave, 'parcial')) {
            return ModalidadTramite::PARCIALMENTE_EN_LINEA;
        }

        if (str_contains($clave, 'presencial')) {
            return ModalidadTramite::PRESENCIAL;
        }

        if (str_contains($clave, 'línea') || str_contains($clave, 'linea') || str_contains($clave, 'virtual')) {
            return ModalidadTramite::EN_LINEA;
        }

        return null;
    }

    /** Si el trámite tiene costo, según la fuente. */
    private function tieneCosto(FichaFuente $ficha): ?CostoTramite
    {
        return match ($ficha->costoDeclarado) {
            'NO' => CostoTramite::GRATUITO,
            'SI' => CostoTramite::CON_COSTO,
            default => null,
        };
    }

    /**
     * El canal por el que se inicia el trámite.
     *
     * `portal_nacional` está reservado a lo que se inicia en un portal del Estado
     * que no es la Entidad. Todo lo demás es canal propio: el trámite lo presta la
     * Alcaldía, y que su sistema esté alojado en un subdominio suyo o en el de la
     * plataforma que contrató no lo convierte en ajeno. Por eso la ausencia de
     * enlace no deja el canal vacío —el trámite se inicia igualmente en la
     * Entidad, aunque sea en ventanilla— y este atributo no puede descartar a
     * nadie por sí solo.
     */
    private function canal(FichaFuente $ficha): CanalInicioTramite
    {
        $enlace = $this->urlAbsoluta($ficha->urlTramiteEnLinea);

        if ($enlace === null) {
            return CanalInicioTramite::PROPIO;
        }

        $host = parse_url($enlace, PHP_URL_HOST);
        $host = is_string($host) ? mb_strtolower($host) : '';

        if ($host !== '' && str_ends_with($host, '.gov.co') && ! str_contains($host, 'santamarta.gov.co')) {
            return CanalInicioTramite::PORTAL_NACIONAL;
        }

        return CanalInicioTramite::PROPIO;
    }

    /**
     * El costo, tal como lo declara la fuente.
     *
     * Se toma de la acción `PAGO` de los pasos del trámite, que es donde la fuente
     * lo publica. El endpoint dedicado a los pagos
     * —`GetPagosByMomentoIdAudiencia`— responde HTTP 500 a todo lo que se le pide,
     * así que no se usa: no se llama a un servicio que se sabe roto para descartar
     * su error.
     *
     * @return array{
     *     valor: string|null,
     *     tipo_valor: string|null,
     *     moneda: string|null,
     *     url_pago: string|null,
     *     descripcion: string|null,
     *     cuentas: list<array<string, string|null>>,
     * }
     */
    private function costo(FichaFuente $ficha): array
    {
        $vacio = [
            'valor' => null,
            'tipo_valor' => null,
            'moneda' => null,
            'url_pago' => null,
            'descripcion' => null,
            'cuentas' => [],
        ];

        foreach ($ficha->acciones as $accion) {
            if ($accion->tipo !== 'PAGO') {
                continue;
            }

            $datos = ClienteFichaGovCo::lista($accion->cruda['data'] ?? []);
            $primero = $datos[0] ?? [];

            $tipoValor = ClienteFichaGovCo::texto($primero, 'TipoValor');

            return [
                // El importe sólo se publica cuando la fuente declara un número.
                // Con `Valor: null` y un tipo de valor calculado, publicar cero
                // sería decirle al ciudadano que el trámite es gratis.
                'valor' => $this->importe(ClienteFichaGovCo::texto($primero, 'Valor')),
                'tipo_valor' => $tipoValor === null
                    ? null
                    : (self::TIPOS_DE_VALOR[mb_strtoupper($tipoValor)] ?? null),
                'moneda' => ClienteFichaGovCo::texto($primero, 'Moneda'),
                'url_pago' => $this->urlAbsoluta(ClienteFichaGovCo::texto($accion->cruda, 'PagoEnlineaUrl')),
                'descripcion' => ClienteFichaGovCo::texto($primero, 'Descripcion'),
                'cuentas' => $this->cuentas($accion->cruda),
            ];
        }

        return $vacio;
    }

    /**
     * El importe, como texto decimal, o nulo.
     *
     * Se normaliza a dos decimales porque la columna es `decimal(12,2)` y un
     * céntimo perdido en una tasa es un defecto legal. Un valor que no es un número
     * se descarta: la fuente usa el campo para textos en algunas fichas.
     */
    private function importe(?string $declarado): ?string
    {
        if ($declarado === null) {
            return null;
        }

        $limpio = str_replace([' ', '.'], '', $declarado);
        $limpio = str_replace(',', '.', $limpio);

        return is_numeric($limpio) ? number_format((float) $limpio, 2, '.', '') : null;
    }

    /**
     * Las cuentas de recaudo que se pueden publicar.
     *
     * **Sólo las de titular institucional.** La fuente declara el titular en
     * `NombreCuenta` —«D.T.C.H Santa Marta»—, y una cuenta a nombre de una persona
     * no entra: publicar el número de una cuenta personal en una sede electrónica
     * es publicar un dato personal, y que venga de una API pública no cambia nada
     * (Ley 1581 de 2012). Tampoco entra una cuenta cuyo titular no venga: no se
     * puede demostrar que sea de la Entidad, y ante la duda no se publica.
     *
     * @param  array<string, mixed>  $accion
     * @return list<array<string, string|null>>
     */
    private function cuentas(array $accion): array
    {
        $cuentas = [];

        foreach (ClienteFichaGovCo::lista($accion['entidadesPago'] ?? []) as $cruda) {
            $titular = ClienteFichaGovCo::texto($cruda, 'NombreCuenta');
            $banco = ClienteFichaGovCo::texto($cruda, 'NombreEntidad');
            $numero = ClienteFichaGovCo::texto($cruda, 'NumeroCuenta');

            if ($banco === null || $numero === null || ! self::esTitularDeLaEntidad($titular)) {
                continue;
            }

            $cuentas[] = [
                'entidad' => $banco,
                'tipo_cuenta' => ClienteFichaGovCo::texto($cruda, 'TipoCuenta'),
                'titular' => $titular,
                'numero_cuenta' => $numero,
                'codigo_recaudo' => ClienteFichaGovCo::texto($cruda, 'CodigoRecaudo'),
            ];
        }

        return $cuentas;
    }

    /**
     * Si el titular de una cuenta es la Entidad y no una persona.
     *
     * Se comprueba por la forma del nombre: el titular institucional dice «Santa
     * Marta», «D.T.C.H.» o «Distrito». Un nombre de persona no dice ninguna de las
     * tres cosas, y ante la duda la cuenta se descarta.
     */
    public static function esTitularDeLaEntidad(?string $titular): bool
    {
        if ($titular === null) {
            return false;
        }

        $clave = Tramite::normalizar($titular);

        return str_contains($clave, 'santa marta')
            || str_contains($clave, 'dtch')
            || str_contains($clave, 'distrito');
    }

    /**
     * Los requisitos, con la naturaleza que la fuente les declara.
     *
     * @return list<array<string, mixed>>
     */
    private function requisitos(FichaFuente $ficha): array
    {
        $requisitos = [];

        foreach ($ficha->acciones as $accion) {
            $naturaleza = self::NATURALEZAS[$accion->tipo] ?? null;

            // Un tipo de acción que el contrato no declara no se publica con la
            // naturaleza de otro: se descarta. Inventarle una naturaleza sería
            // decirle al ciudadano que prepare un documento que quizá no existe.
            if ($naturaleza === null) {
                continue;
            }

            foreach (ClienteFichaGovCo::lista($accion->cruda['data'] ?? []) as $cruda) {
                $requisito = $this->requisito($naturaleza, $cruda, $accion);

                if ($requisito !== null) {
                    $requisitos[] = $requisito;
                }
            }
        }

        return $requisitos;
    }

    /**
     * Un requisito de una acción, según su naturaleza.
     *
     * @param  array<string, mixed>  $cruda
     * @return array<string, mixed>|null
     */
    private function requisito(string $naturaleza, array $cruda, AccionFuente $accion): ?array
    {
        $nota = ClienteFichaGovCo::texto($cruda, 'DocumentoAnotacionAdicional')
            ?? ClienteFichaGovCo::texto($cruda, 'Observacion');

        if ($naturaleza === 'documento') {
            $nombre = ClienteFichaGovCo::texto($cruda, 'DocumentoNombre');

            return $nombre === null ? null : [
                'tipo' => 'documento',
                'descripcion' => $nombre,
                'obligatorio' => true,
                'cantidad' => ClienteFichaGovCo::entero($cruda, 'Cantidad'),
                'unidad_cantidad' => ClienteFichaGovCo::texto($cruda, 'UnidadCantidad'),
                'nota' => $nota,
            ];
        }

        if ($naturaleza === 'verificacion_institucional') {
            $condicion = ClienteFichaGovCo::texto($cruda, 'VerificacionInstDescripcion');

            return $condicion === null ? null : [
                'tipo' => 'verificacion_institucional',
                'descripcion' => $condicion,
                'obligatorio' => true,
                'nota' => $nota,
            ];
        }

        if ($naturaleza === 'solicitud') {
            $canal = $this->canalDeSolicitud(ClienteFichaGovCo::texto($cruda, 'TipoCanal'));
            $descripcion = $this->descripcionDeSolicitud($canal, ClienteFichaGovCo::texto($cruda, 'NombreCanalWeb'));

            return [
                'tipo' => 'solicitud',
                'descripcion' => $descripcion,
                'obligatorio' => true,
                'canal' => $canal,
                'url' => $this->urlAbsoluta(ClienteFichaGovCo::texto($cruda, 'UrlCanalWeb')),
                'correo' => ContactoPublicable::correo(ClienteFichaGovCo::texto($cruda, 'Correo')),
                'nota' => $nota,
            ];
        }

        if ($naturaleza === 'formulario') {
            $url = $this->urlAbsoluta(ClienteFichaGovCo::texto($cruda, 'UrlCanalWeb'));

            return [
                'tipo' => 'formulario',
                'descripcion' => ClienteFichaGovCo::texto($cruda, 'NombreCanalWeb')
                    ?? 'Diligenciar el formulario del trámite.',
                'obligatorio' => true,
                'url' => $url,
                'nota' => $nota,
            ];
        }

        // PAGO. El enunciado sale de lo que la fuente declara; cuando no declara
        // ninguno, el requisito se omite en vez de redactarse a mano, porque
        // redactarlo sería describir el trámite con nuestras palabras y no con las
        // suyas.
        $descripcion = ClienteFichaGovCo::texto($cruda, 'Descripcion')
            ?? ClienteFichaGovCo::texto($accion->cruda, 'PagoDispInstDescripcion');

        return $descripcion === null ? null : [
            'tipo' => 'pago',
            'descripcion' => $descripcion,
            'obligatorio' => true,
            'nota' => $nota,
        ];
    }

    /**
     * El enunciado de una solicitud, con el canal y el nombre que la fuente le da.
     *
     * La fuente declara el canal —`WEB`, `PRESENCIAL`— y, cuando es web, el nombre
     * de la aplicación que lo recibe. Cuando no declara nombre, el enunciado dice
     * sólo el canal: no se le inventa un nombre a la aplicación.
     */
    private function descripcionDeSolicitud(?string $canal, ?string $nombreCanal): string
    {
        $donde = match ($canal) {
            'web' => 'por la web',
            'presencial' => 'presencialmente en las oficinas de la Entidad',
            'correo' => 'por correo electrónico',
            'telefonico' => 'por teléfono',
            default => 'ante la Entidad',
        };

        return $nombreCanal === null
            ? sprintf('Presentar la solicitud %s.', $donde)
            : sprintf('Presentar la solicitud %s: %s.', $donde, $nombreCanal);
    }

    /**
     * La lista de perfiles a los que va dirigido el trámite.
     *
     * @return list<string>
     */
    private function perfiles(FichaFuente $ficha): array
    {
        $perfiles = [];

        foreach ($ficha->perfiles as $perfil) {
            $detalle = ClienteFichaGovCo::texto($perfil, 'detalle');

            if ($detalle !== null) {
                $perfiles[] = $detalle;
            }
        }

        return array_values(array_unique($perfiles));
    }

    /**
     * Los puntos de atención, **sólo con su dato institucional**.
     *
     * El nombre del punto, su dirección, su horario y sus coordenadas. El teléfono
     * sólo pasa si es institucional —un móvil es de una persona y se descarta— y
     * las coordenadas sólo si caen dentro de Colombia, porque la fuente devuelve
     * algunas que no son de ningún sitio. Las dos reglas viven en
     * `ContactoPublicable`, que es donde se pueden probar.
     *
     * @return list<array<string, mixed>>
     */
    private function puntosAtencion(FichaFuente $ficha): array
    {
        $puntos = [];

        foreach ($ficha->puntosAtencion as $crudo) {
            $nombre = ClienteFichaGovCo::texto($crudo, 'PuntoAtencionNombre');
            $direccion = ClienteFichaGovCo::texto($crudo, 'PuntoAtencionDireccion');

            // Un punto sin nombre o sin dirección no sirve para ir: publicarlo
            // como una fila medio vacía ocuparía sitio sin llevar a nadie.
            if ($nombre === null || $direccion === null) {
                continue;
            }

            $coordenadas = ContactoPublicable::coordenadas(
                ClienteFichaGovCo::numeroDecimal($crudo, 'Latitud'),
                ClienteFichaGovCo::numeroDecimal($crudo, 'Longitud'),
            );

            $puntos[] = [
                'nombre' => $nombre,
                'direccion' => $direccion,
                'horario' => ClienteFichaGovCo::texto($crudo, 'HorarioAtencion'),
                'telefono' => ContactoPublicable::telefono(ClienteFichaGovCo::texto($crudo, 'PuntoAtencionTelefono')),
                'municipio' => ClienteFichaGovCo::texto($crudo, 'Municipio'),
                'departamento' => ClienteFichaGovCo::texto($crudo, 'Departamento'),
                'latitud' => $coordenadas['latitud'],
                'longitud' => $coordenadas['longitud'],
            ];
        }

        return $puntos;
    }

    /**
     * La normativa que faculta el trámite.
     *
     * @return list<array<string, mixed>>
     */
    private function normativa(FichaFuente $ficha): array
    {
        $normas = [];

        foreach ($ficha->normativa as $crudo) {
            $tipo = ClienteFichaGovCo::texto($crudo, 'TipoNorma');

            if ($tipo === null) {
                continue;
            }

            $anio = ClienteFichaGovCo::entero($crudo, 'AnoNorma');

            $normas[] = [
                'tipo' => $tipo,
                'numero' => ClienteFichaGovCo::texto($crudo, 'NumeroNorma'),
                // Un año fuera de rango se descarta en vez de publicarse: la fuente
                // devuelve el campo en blanco en algunas fichas y un año cero en una
                // ficha se lee como una norma que no existe.
                'anio' => $anio !== null && $anio >= 1800 && $anio <= 2200 ? $anio : null,
                'articulos' => ClienteFichaGovCo::texto($crudo, 'Articulos'),
                'url' => $this->urlAbsoluta(ClienteFichaGovCo::texto($crudo, 'UrlNorma')),
                'url_descarga' => $this->urlAbsoluta(ClienteFichaGovCo::texto($crudo, 'UrlDescarga')),
            ];
        }

        return $normas;
    }

    /**
     * Por dónde se puede preguntar por el estado de una solicitud.
     *
     * Los canales de la fuente van primero y con `habilitado: true`: la Entidad los
     * declara como suyos y ya atienden. Después va **el canal de la Sede**, con
     * `habilitado: false`, porque la consulta por radicado en `/seguimiento`
     * todavía no existe. Ese `false` es el punto entero del campo: la sede anuncia
     * que el canal está previsto y que hoy no atiende, en vez de ofrecer un
     * formulario que no consulta nada. Un canal declarado y no habilitado es
     * información; uno declarado y fingido es una llamada perdida.
     *
     * @return list<array<string, mixed>>
     */
    private function canalesDeConsulta(FichaFuente $ficha): array
    {
        $canales = [];

        foreach ([...$ficha->seguimientoPersonal, ...$ficha->seguimientoNoPersonal] as $crudo) {
            $declarado = ClienteFichaGovCo::texto($crudo, 'TipoCanal');

            if ($declarado === null) {
                continue;
            }

            $url = ClienteFichaGovCo::texto($crudo, 'UrlCanalWeb');
            $correo = ContactoPublicable::correo(ClienteFichaGovCo::texto($crudo, 'Correo'));

            $canales[] = [
                'canal' => $this->canalDeSeguimiento($declarado),
                'habilitado' => true,
                'nombre' => ClienteFichaGovCo::texto($crudo, 'NombreCanalWeb') ?? $declarado,
                // `url` no exige forma absoluta en el contrato: la fuente declara
                // rutas relativas y descartarlas perdería el canal entero.
                'url' => $url,
                'correo' => $correo,
                'telefono' => ContactoPublicable::telefono(ClienteFichaGovCo::texto($crudo, 'NumeroTelefono')),
                'horario' => ClienteFichaGovCo::texto($crudo, 'HorarioAtencionTelef'),
            ];
        }

        $canales[] = [
            'canal' => 'sede',
            'habilitado' => false,
            'nombre' => 'Seguimiento de la Sede Electrónica',
            'url' => '/seguimiento',
            'correo' => null,
            'telefono' => null,
            'horario' => null,
        ];

        // Un canal de la fuente que no dice nada no es un canal: es una fila
        // vacía. Pero «no dice nada» es no traer **ningún** detalle *y* no ser
        // ninguno de los canales que el contrato reconoce: la fuente declara
        // «PRESENCIAL» sin URL, sin correo y sin teléfono, y eso no es una fila
        // vacía —es decir que se puede preguntar en ventanilla—. Lo que se cae es
        // «Aprenda con tutoriales», que la fuente mete en el mismo campo y que no
        // es un canal por el que nadie pregunte nada.
        return array_values(array_filter(
            $canales,
            static fn (array $canal): bool => $canal['canal'] !== 'otro'
                || $canal['url'] !== null
                || $canal['correo'] !== null
                || $canal['telefono'] !== null,
        ));
    }

    /**
     * El canal de una solicitud, según el nombre que le da la fuente.
     *
     * Se reconoce **por el texto que contiene** y no por una lista de valores
     * exactos, porque la fuente escribe el campo a mano y no siempre igual:
     * «Correo electrónico:» con dos puntos al final, «PRESENCIAL 1» con un índice
     * pegado, «PRESENCIAL» a secas. Comparar contra una lista cerrada dejaría
     * todos esos casos fuera y los publicaría como «otro», que es una categoría
     * que el contrato declara para lo que de verdad no es un canal —«Aprenda con
     * tutoriales»— y no para un correo mal escrito.
     */
    private function canalDeSolicitud(?string $declarado): ?string
    {
        if ($declarado === null) {
            return null;
        }

        $clave = mb_strtoupper(trim($declarado));

        return match (true) {
            str_contains($clave, 'WEB'),
            str_contains($clave, 'VIRTUAL'),
            str_contains($clave, 'LINEA'),
            str_contains($clave, 'LÍNEA'),
            str_contains($clave, 'PAGINA'),
            str_contains($clave, 'PÁGINA') => 'web',
            str_contains($clave, 'PRESENCIAL') => 'presencial',
            str_contains($clave, 'CORREO'),
            str_contains($clave, 'EMAIL') => 'correo',
            str_contains($clave, 'TELEF'),
            str_contains($clave, 'TELÉFONO'),
            str_contains($clave, 'TELEFONO') => 'telefonico',
            default => null,
        };
    }

    /** El canal de seguimiento, según el nombre que le da la fuente. */
    private function canalDeSeguimiento(string $declarado): string
    {
        return match ($this->canalDeSolicitud($declarado)) {
            'web' => 'web',
            'presencial' => 'presencial',
            'correo' => 'correo',
            'telefonico' => 'telefonico',
            // La fuente usa el campo para nombres que no son canales —«Aprenda con
            // tutoriales»—. Se declaran como «otro» en vez de meterse en una
            // categoría que no les corresponde.
            default => 'otro',
        };
    }

    /**
     * Una URL que se puede publicar como URL, o nulo.
     *
     * La fuente usa el campo para textos que no son direcciones —«Ver tutoriales»
     * aparece dentro de `UrlCanalWeb`— y publicar eso como enlace daría un enlace
     * roto. Se comprueba la forma antes de aceptarla.
     */
    private function urlAbsoluta(?string $declarado): ?string
    {
        if ($declarado === null) {
            return null;
        }

        return filter_var($declarado, FILTER_VALIDATE_URL) === false ? null : $declarado;
    }
}
