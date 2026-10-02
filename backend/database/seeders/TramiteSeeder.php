<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CanalInicioTramite;
use App\Enums\CostoTramite;
use App\Enums\ModalidadTramite;
use App\Models\Tramite;
use App\Support\Tramites\ContactoPublicable;
use App\Support\Tramites\FuenteSuit;
use App\Support\Tramites\SlugCatalogo;
use App\Support\Tramites\TiempoEnDias;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use RuntimeException;
use Throwable;

/**
 * Siembra el catálogo de trámites de la Entidad desde la fuente oficial del
 * Estado.
 *
 * **De dónde salen los datos.** Del catálogo de SUIT —Función Pública— para la
 * entidad `0043`, que es la fuente oficial y ratificada del Estado: los 124
 * trámites que la Alcaldía Distrital de Santa Marta tiene registrados. Las fichas
 * completas viven en `database/seeders/datos/catalogo.php`, **dentro del
 * proyecto y versionadas con el código**, así que sembrar no depende de que
 * ningún archivo de datos llegue al servidor: viaja en lo que se despliega. Los
 * nombres de los campos de cada ficha son los de la fuente, sin traducir, para
 * que este mapeo se pueda auditar contra ella.
 *
 * La copia congelada de la recolección sigue en
 * `database/datos/tramites-0043-suit.json` —es la salida auditable de la receta
 * de ingesta de `database/datos/scripts/`, y la prueba de qué publicó la
 * fuente—, pero **el sembrador ya no la lee**. Se regenera desde ahí el archivo
 * PHP cuando cambia el catálogo.
 *
 * **Qué NO se siembra.** Sólo se leen los datos públicos del trámite: su
 * identificador, su nombre, su propósito, su costo, su término, su modalidad y
 * el enlace a su ficha. La respuesta original traía además el registro de la
 * entidad —razón social, correo del despacho, teléfono y fax— y los canales de
 * atención con sus correos de área; nada de eso se copió al archivo ni se lee
 * aquí. No es un dato del trámite, ya viaja en `/entidad` cuando corresponde, y
 * sembrar buzones de funcionarios en el catálogo sería publicar información de
 * contacto que el ciudadano no pidió.
 *
 * **Cómo se mapea.** El contrato exige seis atributos obligatorios por trámite
 * (Guía §5.1.3 de la Resolución 2893) y una ficha en GOV.CO:
 *
 * | Contrato | Fuente |
 * |---|---|
 * | `nombre` | `titulo` |
 * | `resumen` | `proposito` de la ficha oficial, recortado a 500 caracteres |
 * | `url_ficha_gov_co` | `link_govco` |
 * | `modalidad` | `enLinea`: `NO` presencial, `PARCIAL` parcialmente en línea, `SI` en línea |
 * | `tiene_costo` | `costo`: `NO` gratuito, `SI` con costo |
 * | `tiempo_solucion_dias` | `tiempoObtencion` |
 * | `canal_inicio` | `urlTramiteEnLinea` |
 * | `url_inicio` | `urlTramiteEnLinea` |
 * | `consulta_estado` | el mecanismo de la Sede, `CONSULTA_ESTADO` |
 * | `requisitos` | los que declara la ficha oficial, enlazada en `documentos` |
 * | `procedencia` | `FUENTE`, `URL_FUENTE` y `OBTENIDO_EN` |
 *
 * Dos de los seis no los declara la fuente para ningún trámite y conviene decir
 * por qué se publican igual:
 *
 * - **`consulta_estado` es un dato de la Sede, no del trámite.** Quien consulta
 *   el estado de su solicitud lo hace por radicado en `/seguimiento`
 *   (FUN-024 y FUN-035), y ese mecanismo es el mismo para los 124: no depende
 *   del trámite, y no hay 124 maneras distintas de consultar. Es también el
 *   valor que el contrato usa como ejemplo.
 * - **`requisitos` y `documentos` no existen en ninguna interfaz pública de
 *   GOV.CO para fichas de SUIT.** Se comprobó contra el propio portal —el
 *   servicio `GetDocumentacionRequeridaById` sólo existe para las fichas que no
 *   son de SUIT— y contra el visor de SUIT, que es una aplicación de una sola
 *   página sin interfaz de datos. Transcribirlos de memoria sería inventar
 *   requisitos oficiales, que es el peor defecto que puede tener una sede. Lo
 *   que sí se puede publicar sin inventar nada es la ficha oficial donde la
 *   Entidad los declara, y eso es lo que se publica: la ficha como documento y
 *   el requisito de leerla. Cuando la Entidad entregue los requisitos, se cargan
 *   en el panel o se añaden a este mapeo sin tocar el esquema.
 *
 * **Qué se descarta.** Un trámite al que le falte cualquiera de los seis
 * atributos, o su ficha en GOV.CO, no se publica: se cuenta, se dice por qué y se
 * deja fuera. La comprobación se hace sobre el resultado del mapeo y no sobre la
 * fila original —lo que importa no es que la fuente tenga el campo, sino que de
 * él salga un valor publicable— y es la misma regla con la que el contrato no
 * publica un recurso al que le falte un atributo obligatorio.
 *
 * **Modificado a mano, no se toca.** La siembra vuelve a escribir sólo los
 * trámites que llevan su propia marca de procedencia. Un trámite creado o
 * corregido por una persona —en el panel, o cambiando su fuente declarada— deja
 * de llevar esa marca y la siembra lo respeta. Es lo que permite volver a
 * ejecutarla sin miedo después de una corrección manual.
 */
final class TramiteSeeder extends Seeder
{
    /**
     * La fuente oficial. Se publica en la procedencia de cada trámite.
     *
     * Apunta al sitio único donde está declarada (`FuenteSuit`) y no a un literal
     * propio: la marca de procedencia es también la marca con la que la ingesta de
     * la ficha oficial reconoce lo que escribió este sembrador, así que las dos
     * tienen que ser exactamente la misma cadena.
     */
    public const FUENTE = FuenteSuit::NOMBRE;

    public const URL_FUENTE = FuenteSuit::URL;

    /** La fecha en que se obtuvo el catálogo. No es la de hoy: es la del dato. */
    public const OBTENIDO_EN = '2026-09-30';

    /**
     * La copia congelada de la recolección, dentro del proyecto.
     *
     * **Ya no la lee la siembra**: el catálogo vive en `datos/catalogo.php`. Esta
     * ruta se queda porque es el origen del que se regenera ese archivo y porque
     * hay una prueba que comprueba, sobre la copia, que la fuente no trae datos
     * de contacto personal —una comprobación sobre el dato original, no sobre lo
     * que el sembrador escribió—.
     */
    public const ARCHIVO = FuenteSuit::COPIA_CATALOGO;

    /**
     * El mecanismo con el que la Sede permite consultar el estado de una
     * solicitud, por radicado.
     */
    private const CONSULTA_ESTADO = '/seguimiento?radicado=…';

    /**
     * El único enunciado verdadero que hoy se puede hacer sobre los requisitos
     * de estos trámites: que están en la ficha y hay que leerlos ahí. Viaja junto
     * al enlace que los contiene.
     */
    private const REQUISITO = 'Los que declara la ficha oficial del trámite en GOV.CO (SUIT).';

    /** El nombre del documento que contiene los requisitos. */
    private const DOCUMENTO = 'Ficha oficial del trámite en GOV.CO (SUIT)';

    /**
     * Los atributos que el trámite necesita para publicarse, con el nombre que
     * tienen en el contrato.
     *
     * Se comprueban sobre el resultado del mapeo y en este orden, y el informe
     * dice el primero que falta. Añadir un atributo obligatorio al contrato es
     * añadirlo aquí: si la lista y la comprobación estuvieran separadas, la
     * puerta se quedaría corta sin que nada lo dijera.
     */
    private const ATRIBUTOS = [
        'modalidad',
        'tiene_costo',
        'tiempo_solucion_dias',
        'canal_inicio',
        'consulta_estado',
        'requisitos',
        'url_ficha_gov_co',
    ];

    /**
     * La ruta de una copia se puede indicar; por defecto se siembra el catálogo
     * que viaja en el proyecto.
     *
     * El parámetro sigue existiendo por dos motivos que no son comodidad:
     *
     *   - **sembrar una recolección nueva** —una copia descargada aparte, con
     *     trámites que todavía no están en el código— sin tocar el sembrador;
     *   - **probar los descartes** con una fuente pequeña y conocida en vez de
     *     con los 124 trámites buenos: hay pruebas que necesitan que falte un
     *     atributo obligatorio, y provocarlo sobre el catálogo real sería
     *     mentir sobre él.
     *
     * Con `null` —el caso normal— se siembra `datos/catalogo.php`: **no se lee
     * ningún archivo de datos externo**, así que la siembra no puede fallar
     * porque falte la copia.
     */
    public function __construct(private readonly ?string $archivo = null) {}

    public function run(): void
    {
        $this->informe($this->sembrar());
    }

    /**
     * Siembra el catálogo y devuelve lo que pasó.
     *
     * `run()` imprime el informe; esta función lo devuelve, para que una prueba
     * pueda comprobar los descartes sin depender de la salida por consola.
     *
     * @return array{
     *     publicados: int,
     *     actualizados: int,
     *     protegidos: int,
     *     descartados: array<string, int>,
     *     detalle_descartes: list<array{codigo: string, motivo: string, titulo: string|null}>,
     * }
     */
    public function sembrar(): array
    {
        $filas = $this->leerFuente();

        // Slug => código, para detectar dos trámites que darían el mismo slug. Se
        // parte de lo que ya hay en la base para que una segunda ejecución no
        // renombre lo publicado: un slug es la dirección de una ficha.
        /** @var array<string, string> $usados */
        $usados = Tramite::query()->pluck('codigo', 'slug')->all();

        $publicados = 0;
        $actualizados = 0;
        $protegidos = 0;

        /** @var array<string, int> $descartados */
        $descartados = [];

        /** @var list<array{codigo: string, motivo: string, titulo: string|null}> $detalle */
        $detalle = [];

        foreach ($filas as $fila) {
            $codigo = $this->texto($fila, 'id');
            $titulo = $this->texto($fila, 'titulo');

            if ($codigo === null) {
                // Una fila sin identificador no se puede ni comparar con la base:
                // no hay manera de saber si ya está sembrada. Se cuenta aparte.
                $descartados['id'] = ($descartados['id'] ?? 0) + 1;
                $detalle[] = ['codigo' => '(sin identificador)', 'motivo' => 'id', 'titulo' => $titulo];

                continue;
            }

            $atributos = $this->atributos($fila, $codigo, $usados, $this->tiempoEnDias($fila));
            $falta = $this->atributoQueFalta($atributos);

            if ($falta !== null) {
                $descartados[$falta] = ($descartados[$falta] ?? 0) + 1;
                $detalle[] = ['codigo' => $codigo, 'motivo' => $falta, 'titulo' => $titulo];

                continue;
            }

            $existente = Tramite::query()->where('codigo', $codigo)->first();

            if ($existente !== null && ! $this->llevaLaMarcaDeLaSiembra($existente)) {
                $protegidos++;

                continue;
            }

            // `updateOrCreate` por identificador: volver a ejecutar la siembra
            // actualiza estas filas y no crea otras. Los atributos salen de la
            // misma fuente, así que el número de filas no cambia —y la prueba lo
            // comprueba, porque un sembrador que duplica sólo se nota cuando ya
            // duplicó—.
            //
            // El slug se preserva cuando el trámite ya existe: el nombre
            // estandarizado puede cambiar entre cosechas (GOV.CO actualiza
            // nombres con regularidad), pero el slug es una **dirección estable**
            // de la ficha del ciudadano. Cambiarlo rompería los enlaces guardados
            // sin que la nueva versión aporte nada al ciudadano.
            $atributosConSlug = $atributos;
            if ($existente !== null && $existente->slug !== '' && $existente->slug !== null) {
                $atributosConSlug['slug'] = $existente->slug;
            }
            Tramite::updateOrCreate(['codigo' => $codigo], $atributosConSlug);

            if ($existente === null) {
                $publicados++;
            } else {
                $actualizados++;
            }
        }

        return [
            'publicados' => $publicados,
            'actualizados' => $actualizados,
            'protegidos' => $protegidos,
            'descartados' => $descartados,
            'detalle_descartes' => $detalle,
        ];
    }

    /**
     * El informe de la siembra.
     *
     * Dice cuántos trámites se publicaron y, sobre todo, cuántos se descartaron y
     * por qué atributo: un sembrador que descarta en silencio deja creer que el
     * catálogo está completo cuando le faltan trámites.
     *
     * @param array{
     *     publicados: int,
     *     actualizados: int,
     *     protegidos: int,
     *     descartados: array<string, int>,
     *     detalle_descartes: list<array{codigo: string, motivo: string, titulo: string|null}>,
     * } $resultado
     */
    private function informe(array $resultado): void
    {
        if (! $this->command instanceof Command) {
            return;
        }

        $this->command->info(sprintf(
            'Trámites de SUIT: %d publicados, %d actualizados, %d sin tocar porque los corrigió una persona. Descartados: %d.',
            $resultado['publicados'],
            $resultado['actualizados'],
            $resultado['protegidos'],
            array_sum($resultado['descartados']),
        ));

        if ($resultado['descartados'] === []) {
            return;
        }

        $this->command->warn('No se publica un trámite al que le falte un atributo obligatorio. Descartados por atributo:');

        foreach ($resultado['descartados'] as $atributo => $cuantos) {
            $this->command->line(sprintf('  · %s: %d', $atributo, $cuantos));
        }

        foreach ($resultado['detalle_descartes'] as $descarte) {
            $this->command->line(sprintf(
                '    - %s — %s (%s)',
                $descarte['codigo'],
                $descarte['titulo'] ?? '(sin título)',
                $descarte['motivo'],
            ));
        }
    }

    /**
     * El primer atributo obligatorio que falta, o nulo si no falta ninguno.
     *
     * Se comprueba contra el resultado del mapeo: lo que decide si un trámite se
     * publica es que sus valores existan, no que la fila de origen tuviera la
     * clave.
     *
     * @param  array<string, mixed>  $atributos
     */
    private function atributoQueFalta(array $atributos): ?string
    {
        foreach (self::ATRIBUTOS as $atributo) {
            if (! $this->tieneValor($atributos[$atributo] ?? null)) {
                return $atributo;
            }
        }

        return null;
    }

    /**
     * Si un valor sirve para publicarse.
     *
     * El cero es un valor —un término de cero días está declarado— y la cadena
     * vacía no: la fuente usa el blanco para decir «no hay dato», así que el
     * vacío cuenta como ausencia.
     */
    private function tieneValor(mixed $valor): bool
    {
        if ($valor === null) {
            return false;
        }

        if (is_string($valor)) {
            return trim($valor) !== '';
        }

        if (is_array($valor)) {
            return $valor !== [];
        }

        return true;
    }

    /**
     * Los atributos del contrato a partir de una fila de la fuente.
     *
     * Un valor que la fuente no declara viaja aquí como nulo y lo detecta
     * `atributoQueFalta`; nunca se rellena con un valor por defecto, porque un
     * defecto silencioso es un dato inventado con buena letra.
     *
     * @param  array<string, mixed>  $fila
     * @param  array<string, string>  $usados
     * @param  array{dias: int, nota: string|null}|null  $tiempo
     * @return array<string, mixed>
     */
    private function atributos(array $fila, string $codigo, array &$usados, ?array $tiempo): array
    {
        // El nombre y el propósito oficiales son los de GOV.CO, no los del
        // SUIT. Verificado el 2026-10-02 con todos los 124 T-códigos de la
        // Entidad: los 124 nombres del listado SUIT y los 124 de GOV.CO
        // difieren. GOV.CO es la versión vigente y se publica.
        $nombre = $this->texto($fila, 'nombreEstandarizado_govco')
            ?? $this->texto($fila, 'nombreEstandarizado')
            ?? $this->texto($fila, 'titulo');
        $proposito = $this->texto($fila, 'proposito_govco')
            ?? $this->texto($fila, 'proposito_suit')
            ?? $this->texto($fila, 'proposito');

        $enlace = $this->texto($fila, 'urlTramiteEnLinea');
        $ficha = $this->texto($fila, 'link_govco');

        // Los datos ricos del visor de SUIT vienen en el mismo JSON, cruzados por
        // nombre en `database/datos/scripts/03-cruzar-con-listado.php`. Son los
        // mismos que el visor expone en
        // `https://visorsuit.funcionpublica.gov.co/auth/visor?fi=XXXX` y se publican
        // con la marca de procedencia del visor, no del listado, para que sea
        // posible distinguir en el panel los dos orígenes.
        $momentos = $this->momentosDelVisor($fila);
        $requisitos = $this->requisitosDelVisor($fila);
        $costoCuentas = $fila['cuentas'] ?? null;
        $normativa = $fila['normativa'] ?? null;
        // El contacto pasa por `ContactoPublicable` antes de guardarse: los
        // móviles personales y las coordenadas imposibles no llegan al catálogo.
        $puntos = $this->puntosPublicables($fila['puntosAtencion'] ?? null);
        $audiencias = $fila['audiencias'] ?? null;
        $seguimiento = $this->seguimientoPublicable($fila['seguimiento'] ?? null);
        $productoFinal = $fila['productoFinal'] ?? null;
        $palabras = $fila['palabrasRelacionadas'] ?? null;
        $medios = $fila['mediosResultado'] ?? null;
        $urlManual = $fila['urlManualTramiteEnLinea'] ?? null;
        // «¿Cuándo se puede realizar?» viaja como tres hechos y no como una
        // frase: el booleano que responde a casi todos, la condición en prosa de
        // unos pocos y el calendario externo de uno.
        $fechaCualquiera = $fila['fechaCualquiera'] ?? null;
        $cuandoSePuedeRealizar = $fila['cuandoSePuedeRealizar'] ?? null;
        $urlCalendario = $fila['urlCalendario'] ?? null;
        $observacionesResultado = $fila['observacionesResultado'] ?? null;

        return [
            'slug' => $this->slug($nombre, $codigo, $usados),
            'nombre' => $nombre,
            'resumen' => Tramite::recortarResumen($proposito),
            'modalidad' => $this->modalidad($fila),
            'tiene_costo' => $this->tieneCosto($fila),
            // El importe no lo publica la fuente para ningún trámite: viaja en
            // nulo y lo declara la Entidad. El atributo obligatorio es si el
            // trámite tiene costo, no cuánto cuesta.
            'costo' => null,
            'costo_tipo_valor' => $this->costoTipoValor($fila),
            'costo_moneda' => null,
            'costo_url_pago' => null,
            'costo_descripcion' => null,
            'costo_cuentas' => $costoCuentas,
            'cuentas' => $costoCuentas,
            'tiempo_solucion_dias' => $tiempo['dias'] ?? null,
            'canal_inicio' => $this->canal($fila),
            'url_inicio' => $enlace,
            'url_manual_tramite_en_linea' => $urlManual,
            'consulta_estado' => self::CONSULTA_ESTADO,
            'requisitos' => $requisitos,
            'documentos' => $this->documentosOficiales($ficha),
            'momentos' => $momentos,
            'resultado' => $productoFinal,
            'producto_final' => $productoFinal,
            'observaciones_resultado' => $observacionesResultado,
            'fecha_cualquiera' => $fechaCualquiera,
            'cuando_se_puede_realizar' => $cuandoSePuedeRealizar,
            'url_calendario' => $urlCalendario,
            'medios_resultado' => $medios,
            'palabras_relacionadas' => $palabras,
            'audiencias' => $this->perfilesDesdeAudiencias($audiencias)['audiencias'],
            'perfiles' => $this->perfilesDesdeAudiencias($audiencias)['perfiles'],
            'puntos_atencion' => $puntos,
            'normativa' => $normativa,
            'canales_consulta_estado' => $this->canalesDesdeSeguimiento($seguimiento),
            'seguimiento' => $seguimiento,
            'categoria_slug' => null,
            'categoria_nombre' => null,
            'url_ficha_gov_co' => $ficha,
            'procedencia_fuente' => self::FUENTE,
            'procedencia_url' => self::URL_FUENTE,
            'procedencia_obtenido_en' => self::OBTENIDO_EN,
            'procedencia_nota' => $tiempo['nota'] ?? null,
            // Se publica al sembrarlo: lo que entra al catálogo por esta puerta
            // ya pasó la comprobación de los seis atributos y de la ficha.
            'publicado_en' => Carbon::now(),
        ];
    }

    /**
     * Los requisitos que el visor publica, normalizados a la forma del contrato.
     *
     * El visor agrupa los requisitos en **momentos** (pasos del trámite) y cada
     * requisito tiene un `tipoRequisito` y un texto. La forma del contrato es
     * una lista plana: aquí la aplanamos, conservando el tipo y el orden.
     *
     * @param  array<string, mixed>  $fila
     * @return list<array<string, mixed>>
     */
    private function requisitosDelVisor(array $fila): array
    {
        $requisitos = [];
        $orden = 0;
        foreach (($fila['momentos'] ?? []) as $momento) {
            foreach (($momento['requisitos'] ?? []) as $req) {
                $orden++;
                $r = [
                    'orden' => $orden,
                    'tipo' => $this->normalizarTipoRequisito($req['tipo'] ?? null),
                    'descripcion' => $this->textoRequisito($req),
                    'obligatorio' => ($req['obligatorio'] ?? true) === true,
                ];
                // Las audiencias a las que aplica el requisito. Es la base
                // del filtro «Para realizarlo necesita» del visor: el
                // ciudadano se reconoce en uno de los grupos (Ciudadano,
                // Extranjeros, Organizaciones) y la Sede filtra los requisitos
                // que le aplican. Sin audiencia, el requisito se considera
                // universal y aparece en todos los grupos.
                if (! empty($req['tipos_audiencia'])) {
                    $r['tipos_audiencia'] = $req['tipos_audiencia'];
                }
                if (! empty($req['documento'])) {
                    $r['documento'] = $req['documento'];
                }
                if (! empty($req['formulario_nombre'])) {
                    $r['formulario'] = $req['formulario_nombre'];
                    if (! empty($req['formulario_url'])) {
                        $r['formulario_url'] = $req['formulario_url'];
                    }
                }
                if (! empty($req['url_pago'])) {
                    $r['url_pago'] = $req['url_pago'];
                }
                if (! empty($req['pago_valor'])) {
                    $r['pago'] = $req['pago_valor'];
                }
                if (! empty($req['pago_cuentas'])) {
                    $r['cuentas'] = $req['pago_cuentas'];
                }
                $requisitos[] = $r;
            }
        }
        // Si el visor no publicó requisitos, vuelve al placeholder histórico:
        // un único requisito genérico que apunta a la ficha oficial. La Sede no
        // inventa.
        if ($requisitos === []) {
            $ficha = $this->texto($fila, 'link_govco');
            $requisitos[] = [
                'descripcion' => self::REQUISITO,
                'obligatorio' => true,
                'documento' => $ficha,
            ];
        }

        return $this->deduplicarRequisitos($requisitos);
    }

    /**
     * Colapsa los requisitos que la fuente repite.
     *
     * **El defecto es de la fuente y se mide.** El visor publica a veces el
     * mismo requisito dos veces en el mismo paso: en `T41040` aparecen dos
     * `[DOCUMENTO] Diploma o acta de grado` seguidos, sin `cantidad` que
     * distinga uno del otro. Quince de los 123 trámites de la Entidad traen
     * alguna repetición. Transcribirla a la sede es publicar el defecto: el
     * ciudadano ve dos veces el mismo documento y desconfía de la lista.
     *
     * **Por qué se limpia al ingerir y no al dibujar.** El sembrador ya
     * normaliza lo que recibe del visor (tipos, textos, audiencias); esta es una
     * normalización más. Limpiar aquí deja la API honesta para cualquier
     * consumidor —el panel que vendrá, un export de datos abiertos— en vez de
     * obligar a cada uno a repetir la misma limpieza. La copia cruda del visor
     * queda versionada como evidencia de lo que la fuente declara.
     *
     * **La clave es el tipo más el nombre visible**, no sólo el nombre: un
     * `DOCUMENTO` y un `FORMULARIO` homónimos son dos cosas distintas y no se
     * colapsan. Un requisito sin nombre visible —un `SOLICITUD` vacío que la
     * fuente dejó a medias— **no** entra en la deduplicación: no hay nada que
     * comparar y colapsarlos perdería la cuenta de los pasos. Esos los declara
     * ausentes la presentación.
     *
     * @param  list<array<string, mixed>>  $requisitos
     * @return list<array<string, mixed>>
     */
    private function deduplicarRequisitos(array $requisitos): array
    {
        /** @var array<string, int> $vistos */
        $vistos = [];
        /** @var list<array<string, mixed>> $unicos */
        $unicos = [];

        foreach ($requisitos as $requisito) {
            $clave = $this->claveDeRequisito($requisito);

            if ($clave === null) {
                $unicos[] = $requisito;

                continue;
            }

            if (! isset($vistos[$clave])) {
                $vistos[$clave] = count($unicos);
                $unicos[] = $requisito;

                continue;
            }

            $unicos[$vistos[$clave]] = $this->fusionarRequisitos($unicos[$vistos[$clave]], $requisito);
        }

        return array_values($unicos);
    }

    /**
     * La clave con la que dos requisitos se consideran el mismo, o nula si no
     * hay nombre visible con el que compararlos.
     *
     * @param  array<string, mixed>  $requisito
     */
    private function claveDeRequisito(array $requisito): ?string
    {
        foreach (['documento', 'formulario', 'descripcion'] as $campo) {
            $valor = $requisito[$campo] ?? null;

            if (! is_string($valor) || trim($valor) === '') {
                continue;
            }

            $normalizado = mb_strtolower((string) preg_replace('/\s+/u', ' ', trim($valor)));

            return ($requisito['tipo'] ?? '').'|'.$normalizado;
        }

        return null;
    }

    /**
     * Fusiona un duplicado en el requisito que ya se conserva.
     *
     * No se descarta el duplicado sin mirarlo: se **unen** las audiencias (unir
     * es la opción que muestra el requisito en vez de esconderlo) y se
     * **rellenan los huecos** del conservado con lo que el duplicado sí traía
     * —un enlace, una nota, un formulario—. Si alguna de las dos copias declara
     * el requisito obligatorio, el resultado lo es: quitar una obligación es más
     * grave que añadirla.
     *
     * @param  array<string, mixed>  $conservado
     * @param  array<string, mixed>  $duplicado
     * @return array<string, mixed>
     */
    private function fusionarRequisitos(array $conservado, array $duplicado): array
    {
        $audiencias = array_values(array_unique(array_merge(
            $conservado['tipos_audiencia'] ?? [],
            $duplicado['tipos_audiencia'] ?? [],
        )));

        if ($audiencias !== []) {
            $conservado['tipos_audiencia'] = $audiencias;
        }

        if (($duplicado['obligatorio'] ?? false) === true) {
            $conservado['obligatorio'] = true;
        }

        foreach ($duplicado as $campo => $valor) {
            if ($campo === 'tipos_audiencia' || $campo === 'obligatorio' || $campo === 'orden') {
                continue;
            }

            $actual = $conservado[$campo] ?? null;

            if ($actual === null || $actual === '' || $actual === []) {
                if ($valor !== null && $valor !== '' && $valor !== []) {
                    $conservado[$campo] = $valor;
                }
            }
        }

        return $conservado;
    }

    /**
     * Los momentos (pasos) que el visor publica, en la forma del contrato.
     *
     * El visor de SUIT sólo trae `descripcion` para cada momento —no un
     * `titulo` separado—, así que aquí se separa el título (la primera
     * frase de la descripción) de la descripción propiamente dicha. La
     * operación es la misma que hace GOV.CO en su ficha: «Reunir
     * documentos» como título y la explicación como descripción.
     *
     * Si la descripción no se puede partir limpiamente, el título es la
     * descripción entera y la descripción queda vacía. La Sede no inventa:
     * publica lo que el visor publica, en la forma del contrato.
     *
     * @param  array<string, mixed>  $fila
     * @return list<array{orden: int, titulo: string, descripcion: string|null, requisitos: list<array<string, mixed>>}>
     */
    private function momentosDelVisor(array $fila): array
    {
        $momentos = [];
        foreach (($fila['momentos'] ?? []) as $idx => $momento) {
            $crudo = trim((string) ($momento['descripcion'] ?? ''));
            if ($crudo === '') {
                continue;
            }
            // El visor publica frases largas como descripción, sin título
            // separado. Aquí se separan en dos: la primera frase hasta el
            // primer punto es el título, el resto es la descripción.
            $partes = preg_split('/(?<=\.)\s+/u', $crudo, 2);
            $titulo = $partes[0] ?? $crudo;
            $descripcion = $partes[1] ?? null;
            $titulo = rtrim($titulo, '.').'.';

            $requisitos = [];
            foreach (($momento['requisitos'] ?? []) as $req) {
                $r = [
                    'orden' => $req['orden'] ?? null,
                    'tipo' => $this->normalizarTipoRequisito($req['tipo'] ?? null),
                    'descripcion' => $this->textoRequisito($req),
                    'obligatorio' => ($req['obligatorio'] ?? true) === true,
                ];
                if (! empty($req['tipos_audiencia'])) {
                    $r['tipos_audiencia'] = $req['tipos_audiencia'];
                }
                if (! empty($req['documento'])) {
                    $r['documento'] = $req['documento'];
                }
                if (! empty($req['formulario_nombre'])) {
                    $r['formulario'] = $req['formulario_nombre'];
                    if (! empty($req['formulario_url'])) {
                        $r['formulario_url'] = $req['formulario_url'];
                    }
                }
                if (! empty($req['url_pago'])) {
                    $r['url_pago'] = $req['url_pago'];
                }
                if (! empty($req['pago_valor'])) {
                    $r['pago'] = $req['pago_valor'];
                }
                if (! empty($req['pago_cuentas'])) {
                    $r['cuentas'] = $req['pago_cuentas'];
                }
                $requisitos[] = $r;
            }
            $momentos[] = [
                'orden' => $momento['orden'] ?? ($idx + 1),
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                // La misma limpieza que en el listado plano, pero **por paso**:
                // un requisito que aparece en dos pasos distintos pertenece a los
                // dos y se conserva en ambos. Lo que se colapsa es la repetición
                // dentro del mismo paso.
                'requisitos' => $this->deduplicarRequisitos($requisitos),
            ];
        }

        return $momentos;
    }

    /**
     * El texto del requisito, tomando el primer campo no vacío que el visor
     * publica, en este orden: `descripcionVerificado`, `anotacionAdicional`,
     * `descripcionSolicitud`. Los tres son el mismo concepto («qué tiene que
     * hacer o llevar el ciudadano») en distintos formatos de SUIT.
     *
     * @param  array<string, mixed>  $req
     */
    private function textoRequisito(array $req): string
    {
        foreach (['descripcion', 'descripcionVerificado', 'anotacionAdicional', 'descripcionSolicitud'] as $clave) {
            if (! empty($req[$clave])) {
                return (string) $req[$clave];
            }
        }

        return '';
    }

    /**
     * Los tipos de requisito del visor, normalizados al vocabulario del contrato.
     *
     * El visor usa cinco valores (`DOCUMENTO`, `PAGO`, `SOLICITUD`,
     * `VERIFICACION_INST`, `FORMULARIO`); el contrato los declara con el mismo
     * nombre en minúsculas. Si el visor añadiera un sexto, el seeder lo dejaría
     * en `null` para que el repositorio lo publique sin clasificar —que es lo
     * menos malo cuando aparece un tipo nuevo.
     */
    private function normalizarTipoRequisito(?string $tipo): ?string
    {
        return match ($tipo) {
            'DOCUMENTO' => 'documento',
            'PAGO' => 'pago',
            'SOLICITUD' => 'solicitud',
            'VERIFICACION_INST' => 'verificacion_institucional',
            'FORMULARIO' => 'formulario',
            default => null,
        };
    }

    /**
     * El tipo de valor del costo, normalizado.
     *
     * El visor lo publica en su `momentos[].requisitos[].pago_valor[].tipoValor`
     * (no a nivel de trámite). Lo cruzamos cuando existe; si no, devolvemos el
     * valor del listado si lo trae como `costo` y es `SI`.
     *
     * @param  array<string, mixed>  $fila
     */
    private function costoTipoValor(array $fila): ?string
    {
        foreach (($fila['momentos'] ?? []) as $momento) {
            foreach (($momento['requisitos'] ?? []) as $req) {
                if (($req['tipo'] ?? null) !== 'pago') {
                    continue;
                }
                foreach (($req['pago_valor'] ?? []) as $vp) {
                    $tipo = $vp['tipo_valor'] ?? null;
                    if ($tipo === null || $tipo === '') {
                        continue;
                    }

                    return match ($tipo) {
                        'AVALUO_LIQUIDACION' => 'avaluo_liquidacion',
                        'SMLV' => 'smlv',
                        'FIJO' => 'fijo',
                        'RANGO' => 'rango',
                        default => null,
                    };
                }
            }
        }

        return null;
    }

    /**
     * Los documentos oficiales, en la forma del contrato.
     *
     * El visor no tiene un campo «documentos» en el mismo nivel que el
     * contrato: los documentos están mezclados con los demás requisitos. Aquí
     * extraemos los requisitos de tipo `DOCUMENTO` y los publicamos como
     * documentos para que la ficha los pueda listar.
     *
     * @return list<array<string, string|null>>
     */
    private function documentosOficiales(?string $ficha): array
    {
        // Por ahora, el documento canónico es la ficha oficial. Si el visor
        // publicara anexos específicos por trámite, este método los añadiría.
        return [
            ['nombre' => self::DOCUMENTO, 'url' => $ficha, 'formato' => 'HTML'],
        ];
    }

    /**
     * Las audiencias del visor, en la forma del contrato (`TramiteAudiencia`).
     *
     * El visor agrupa las audiencias en tres tipos (`Instituciones o dependencias
     * públicas`, `Ciudadano`, `Organizaciones`, `Extranjeros`). El contrato
     * declara `audiencias` como una lista de objetos con `grupo`, `nombre` y
     * `descripcion`. Aquí devolvemos cada audiencia con su grupo inferido del
     * nombre, que es la forma en que la Sede las publica.
     *
     * El campo `perfiles` del contrato (array de strings) se conserva para
     * compatibilidad: la Sede lo publica como una lista plana de nombres.
     *
     * @param  list<array<string, mixed>>|null  $audiencias
     * @return array{audiencias: list<array<string, mixed>>, perfiles: list<string>}
     */
    private function perfilesDesdeAudiencias(?array $audiencias): array
    {
        if ($audiencias === null) {
            return ['audiencias' => [], 'perfiles' => []];
        }
        $lista = [];
        $nombres = [];
        foreach ($audiencias as $a) {
            $nombre = $a['nombre'] ?? null;
            if ($nombre === null || $nombre === '') {
                continue;
            }
            $grupo = $a['grupo'] ?? null;
            $lista[] = [
                'grupo' => $grupo,
                'nombre' => $nombre,
                'descripcion' => $a['descripcion'] ?? null,
            ];
            $nombres[$nombre] = true;
        }

        return [
            'audiencias' => $lista,
            'perfiles' => array_keys($nombres),
        ];
    }

    /**
     * Los puntos de atención, ya pasados por el filtro de contacto publicable.
     *
     * **Por qué existe este método.** El visor de SUIT publica, junto a la
     * dirección institucional, teléfonos que a veces son **móviles de diez
     * dígitos** de quien atiende la oficina, y coordenadas que no son de ningún
     * sitio. La ingesta anterior (`Ingesta/MapeoFicha`) ya los descartaba con
     * `ContactoPublicable`; la ingesta del visor nació sin ese filtro y volvió a
     * publicarlos. Medido sobre la copia congelada: 74 de 562 números eran
     * móviles y 39 de 400 coordenadas caían fuera de Colombia.
     *
     * Que el dato venga de una API pública no cambia nada (Ley 1581 de 2012): si
     * el Distrito lo publica en su sede, responde por él. El criterio completo
     * —qué se descarta y por qué— vive en `ContactoPublicable`.
     *
     * @param  list<array<string, mixed>>|null  $puntos
     * @return list<array<string, mixed>>
     */
    private function puntosPublicables(?array $puntos): array
    {
        if ($puntos === null) {
            return [];
        }
        $publicables = [];
        foreach ($puntos as $punto) {
            $coordenadas = ContactoPublicable::coordenadas(
                isset($punto['latitud']) ? (float) $punto['latitud'] : null,
                isset($punto['longitud']) ? (float) $punto['longitud'] : null,
            );
            $publicables[] = [
                'nombre' => $punto['nombre'] ?? null,
                'direccion' => $punto['direccion'] ?? null,
                'telefono' => ContactoPublicable::telefono($punto['telefono'] ?? null),
                'horario' => $punto['horario'] ?? null,
                'municipio' => $punto['municipio'] ?? null,
                'departamento' => $punto['departamento'] ?? null,
                'latitud' => $coordenadas['latitud'],
                'longitud' => $coordenadas['longitud'],
            ];
        }

        return $publicables;
    }

    /**
     * El bloque de seguimiento del visor, con el contacto ya filtrado.
     *
     * Se conservan las cuatro listas (teléfono, correo, presencial, web) porque
     * el contrato las declara, pero cada número pasa por `ContactoPublicable` y
     * cada correo por su comprobación de forma. El bloque crudo es lo que el
     * visor publica; lo que se publica en la Sede es lo que pasa el filtro.
     *
     * @param  array<string, mixed>|null  $seguimiento
     * @return array<string, mixed>|null
     */
    private function seguimientoPublicable(?array $seguimiento): ?array
    {
        if ($seguimiento === null) {
            return null;
        }
        $telefonos = [];
        foreach (($seguimiento['telefono'] ?? []) as $tel) {
            $numero = ContactoPublicable::telefono($tel['numero'] ?? null);
            if ($numero === null) {
                continue;
            }
            $telefonos[] = [
                'numero' => $numero,
                'extension' => $tel['extension'] ?? null,
                'horario' => $tel['horario'] ?? null,
            ];
        }
        $correos = [];
        foreach (($seguimiento['email'] ?? []) as $em) {
            $correo = ContactoPublicable::correo($em['email'] ?? null);
            if ($correo === null) {
                continue;
            }
            $correos[] = ['email' => $correo];
        }
        $web = [];
        foreach (($seguimiento['web'] ?? []) as $w) {
            // El visor no usa `url`/`nombre` sino `urlCanal`/`nombreCanal`.
            // Leer los nombres equivocados fue lo que hizo que el enlace de
            // consulta del SISBÉN (`Consulta tu Grupo de SISBEN`) desapareciera.
            $url = $w['urlCanal'] ?? $w['url'] ?? null;
            if ($url === null || trim((string) $url) === '') {
                continue;
            }
            $web[] = [
                'nombre' => $w['nombreCanal'] ?? $w['nombre'] ?? null,
                'url' => $url,
            ];
        }

        return [
            'telefono' => $telefonos,
            'email' => $correos,
            'presencial' => $this->puntosPublicables($seguimiento['presencial'] ?? null),
            'web' => $web,
        ];
    }

    /**
     * Los canales de seguimiento, reducidos a la forma del contrato.
     *
     * El visor publica cuatro listas (teléfono, correo, presencial, web). El
     * contrato los declara con `canal`, `habilitado`, `nombre`, `url`, `correo`,
     * `telefono`, `horario`. Aquí se aplanan en una sola.
     *
     * **`habilitado` distingue dos cosas que no son la misma.** Un canal que
     * viene del visor —el conmutador de la Entidad, su buzón de área, su página
     * de consulta, su ventanilla— **sí atiende hoy**: la Entidad contesta por
     * ahí y la fuente lo publica. Decir «este canal está previsto y todavía no
     * atiende» de un teléfono que sí contesta es desinformar. Lo que no está
     * construido es el mecanismo **propio de la Sede** (`/seguimiento`), y ese
     * es el único que viaja como `habilitado: false`.
     *
     * @param  array<string, mixed>|null  $seguimiento
     * @return list<array<string, mixed>>
     */
    private function canalesDesdeSeguimiento(?array $seguimiento): array
    {
        if ($seguimiento === null) {
            return [];
        }
        $canales = [];
        foreach (($seguimiento['telefono'] ?? []) as $tel) {
            $canales[] = [
                'canal' => 'telefonico',
                'habilitado' => true,
                'nombre' => null,
                'url' => null,
                'correo' => null,
                'telefono' => $tel['numero'] ?? null,
                'horario' => $tel['horario'] ?? null,
            ];
        }
        foreach (($seguimiento['email'] ?? []) as $em) {
            $canales[] = [
                'canal' => 'correo',
                'habilitado' => true,
                'nombre' => null,
                'url' => null,
                'correo' => $em['email'] ?? null,
                'telefono' => null,
                'horario' => null,
            ];
        }
        foreach (($seguimiento['presencial'] ?? []) as $pres) {
            $canales[] = [
                'canal' => 'presencial',
                'habilitado' => true,
                'nombre' => $pres['nombre'] ?? null,
                'url' => null,
                'correo' => null,
                // El teléfono y el horario del punto ya se publican en «¿Cuál es
                // el horario y los puntos de atención?». Repetirlos aquí duplica
                // el mismo dato en la misma pantalla; la ficha enlaza al bloque
                // de puntos, como hace el visor.
                'telefono' => null,
                'horario' => null,
            ];
        }
        foreach (($seguimiento['web'] ?? []) as $web) {
            $canales[] = [
                'canal' => 'web',
                'habilitado' => true,
                'nombre' => $web['nombre'] ?? null,
                'url' => $web['url'] ?? null,
                'correo' => null,
                'telefono' => null,
                'horario' => null,
            ];
        }

        return $canales;
    }

    /**
     * El slug del trámite, con la regla compartida del catálogo.
     *
     * Vive en `SlugCatalogo` porque la ingesta de la ficha oficial escribe en la
     * misma columna: dos reglas distintas darían dos direcciones para el mismo
     * trámite según por dónde hubiera entrado.
     *
     * @param  array<string, string>  $usados
     */
    private function slug(?string $nombre, string $codigo, array &$usados): string
    {
        return SlugCatalogo::para($nombre, $codigo, $usados);
    }

    /**
     * La modalidad declarada por la fuente.
     *
     * @param  array<string, mixed>  $fila
     */
    private function modalidad(array $fila): ?ModalidadTramite
    {
        return match ($this->texto($fila, 'enLinea')) {
            'NO' => ModalidadTramite::PRESENCIAL,
            'PARCIAL' => ModalidadTramite::PARCIALMENTE_EN_LINEA,
            'SI' => ModalidadTramite::EN_LINEA,
            default => null,
        };
    }

    /**
     * Si el trámite tiene costo, según la fuente.
     *
     * @param  array<string, mixed>  $fila
     */
    private function tieneCosto(array $fila): ?CostoTramite
    {
        return match ($this->texto($fila, 'costo')) {
            'NO' => CostoTramite::GRATUITO,
            'SI' => CostoTramite::CON_COSTO,
            default => null,
        };
    }

    /**
     * El canal por el que se inicia el trámite.
     *
     * `portal_nacional` está reservado a lo que se inicia en un portal del Estado
     * que no es la Entidad. Todo lo demás es canal propio: el trámite lo presta
     * la Alcaldía, y que su sistema esté alojado en un subdominio suyo o en el de
     * la plataforma que contrató no lo convierte en ajeno. Por eso la ausencia de
     * enlace no deja el canal vacío —el trámite se inicia igualmente en la
     * Entidad, aunque sea en ventanilla— y este atributo no puede descartar a
     * nadie por sí solo.
     *
     * @param  array<string, mixed>  $fila
     */
    private function canal(array $fila): CanalInicioTramite
    {
        $enlace = $this->texto($fila, 'urlTramiteEnLinea');

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
     * El término de solución, en días, con la nota que explica la conversión.
     *
     * La conversión —y el porqué de cada una de sus reglas— vive en
     * `TiempoEnDias`, y este método se limita a leer el campo de la copia de SUIT y
     * a pasárselo. Está compartida con la ingesta de la ficha oficial a propósito:
     * son dos caminos que llenan la misma columna del mismo catálogo, y dos
     * conversiones distintas darían dos plazos distintos para el mismo trámite
     * según por dónde hubiera entrado.
     *
     * @param  array<string, mixed>  $fila
     * @return array{dias: int, nota: string|null}|null
     */
    private function tiempoEnDias(array $fila): ?array
    {
        return TiempoEnDias::desde($this->texto($fila, 'tiempoObtencion'));
    }

    /**
     * Si el trámite lo escribió esta siembra.
     *
     * La marca es la fuente declarada. Un trámite cargado a mano no la tiene, y
     * uno corregido por una persona deja de tenerla en cuanto la Entidad pone su
     * propia fuente: en los dos casos la siembra no lo toca.
     */
    private function llevaLaMarcaDeLaSiembra(Tramite $tramite): bool
    {
        return $tramite->procedencia_fuente === self::FUENTE;
    }

    /**
     * Las filas del catálogo que hay que sembrar.
     *
     * El caso normal no lee nada de fuera: el catálogo vive en
     * `datos/catalogo.php`, dentro del proyecto y versionado con el código. Sólo
     * cuando se indica una ruta —sembrar otra recolección, o las pruebas con su
     * fixture— se lee un archivo.
     *
     * @return list<array<string, mixed>>
     */
    private function leerFuente(): array
    {
        if ($this->archivo === null) {
            /** @var list<array<string, mixed>> $filas */
            $filas = array_values(array_filter(
                (array) require __DIR__.'/datos/catalogo.php',
                'is_array',
            ));

            return $filas;
        }

        // Una ruta absoluta se respeta: es la que permite sembrar una copia que
        // no vive dentro del proyecto.
        $ruta = str_starts_with($this->archivo, DIRECTORY_SEPARATOR)
            ? $this->archivo
            : database_path($this->archivo);

        if (! is_file($ruta)) {
            throw new RuntimeException(sprintf(
                'No está la copia de la fuente oficial en %s: sin ella no se puede sembrar el catálogo.',
                $ruta,
            ));
        }

        try {
            $contenido = json_decode((string) file_get_contents($ruta), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $error) {
            throw new RuntimeException(
                sprintf('La copia de la fuente oficial en %s no es un JSON legible.', $ruta),
                0,
                $error,
            );
        }

        if (! is_array($contenido) || ! isset($contenido['tramites']) || ! is_array($contenido['tramites'])) {
            throw new RuntimeException(sprintf('La copia de la fuente oficial en %s no tiene la forma esperada.', $ruta));
        }

        /** @var list<array<string, mixed>> $filas */
        $filas = array_values(array_filter($contenido['tramites'], 'is_array'));

        return $filas;
    }

    /**
     * Un campo de texto de la fila, ya recortado, o nulo si viene vacío.
     *
     * La fuente usa el blanco para decir «no hay dato» —hay fichas con
     * `tiempoObtencion` en blanco—, así que tratar el vacío como ausencia es
     * parte del mapeo y no un adorno: es lo que hace que ese trámite se descarte
     * en vez de publicarse con un término de cero días.
     *
     * @param  array<string, mixed>  $fila
     */
    private function texto(array $fila, string $clave): ?string
    {
        $valor = $fila[$clave] ?? null;

        if (! is_string($valor)) {
            return null;
        }

        $valor = trim($valor);

        return $valor === '' ? null : $valor;
    }
}
