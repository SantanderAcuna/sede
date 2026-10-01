<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CanalInicioTramite;
use App\Enums\CostoTramite;
use App\Enums\ModalidadTramite;
use App\Models\Tramite;
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
 * trámites que la Alcaldía Distrital de Santa Marta tiene registrados. La copia
 * congelada de esa recolección vive en `database/datos/tramites-0043-suit.json`,
 * dentro del proyecto, porque el directorio donde se recogió no se versiona y
 * sin una copia propia ni la integración continua ni un despliegue nuevo podrían
 * sembrar nada. Los nombres de los campos del archivo son los de la fuente, sin
 * traducir, para que este mapeo se pueda auditar contra ella.
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

    /** La copia congelada de la recolección, dentro del proyecto. */
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
     * La ruta de la copia se puede indicar, y por defecto es la del proyecto.
     *
     * Sirve para sembrar una recolección nueva —una copia descargada aparte—
     * sin tocar el código, y para poder probar los descartes con una fuente
     * pequeña y conocida en vez de con la buena.
     */
    public function __construct(private readonly string $archivo = self::ARCHIVO) {}

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
            Tramite::updateOrCreate(['codigo' => $codigo], $atributos);

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
        $nombre = $this->texto($fila, 'titulo');
        $enlace = $this->texto($fila, 'urlTramiteEnLinea');
        $ficha = $this->texto($fila, 'link_govco');

        return [
            'slug' => $this->slug($nombre, $codigo, $usados),
            'nombre' => $nombre,
            'resumen' => Tramite::recortarResumen($this->texto($fila, 'proposito')),
            'modalidad' => $this->modalidad($fila),
            'tiene_costo' => $this->tieneCosto($fila),
            // El importe no lo publica la fuente para ningún trámite: viaja en
            // nulo y lo declara la Entidad. El atributo obligatorio es si el
            // trámite tiene costo, no cuánto cuesta.
            'costo' => null,
            'tiempo_solucion_dias' => $tiempo['dias'] ?? null,
            'canal_inicio' => $this->canal($fila),
            'url_inicio' => $enlace,
            'consulta_estado' => self::CONSULTA_ESTADO,
            'requisitos' => [
                ['descripcion' => self::REQUISITO, 'obligatorio' => true],
            ],
            'documentos' => [
                ['nombre' => self::DOCUMENTO, 'url' => $ficha, 'formato' => 'HTML'],
            ],
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
     * Las filas de la fuente congelada.
     *
     * @return list<array<string, mixed>>
     */
    private function leerFuente(): array
    {
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
