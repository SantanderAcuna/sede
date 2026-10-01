<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CanalInicioTramite;
use App\Enums\CostoTramite;
use App\Enums\ModalidadTramite;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Un trámite del catálogo de la Entidad.
 *
 * El modelo guarda lo que el contrato publica, con los mismos nombres: la fila y
 * la respuesta dicen lo mismo, y una diferencia entre las dos se ve en un solo
 * sitio.
 *
 * Lo que este modelo protege y no se lee en las columnas:
 *
 * - **Lo que no tiene ficha en GOV.CO no se publica.** No lo decide el modelo
 *   al guardar, sino la consulta del repositorio, que es la única puerta por la
 *   que el ciudadano llega al catálogo. Guardar un trámite incompleto es
 *   legítimo —mientras la Entidad consigue la ficha—; publicarlo no lo es.
 * - **El texto buscable se calcula solo.** `busqueda` no es un dato del trámite
 *   sino un índice derivado de su nombre y su resumen, así que lo mantiene el
 *   modelo en cada escritura: si se rellena a mano, cualquier corrección
 *   posterior desde el panel dejaría el índice mintiendo, y la búsqueda fallaría
 *   justo en lo que se acaba de corregir.
 */
/**
 * @property int $id
 * @property string $codigo
 * @property string $slug
 * @property string $nombre
 * @property string|null $resumen
 *                                Los seis atributos obligatorios son anulables en la base y **no lo son para
 *                                publicarse**. La distinción es la misma que ya valía para `url_ficha_gov_co`: un
 *                                trámite al que la fuente no le declara la modalidad puede existir mientras la
 *                                Entidad lo arregla, y no puede salir al catálogo. Quien lo comprueba es el
 *                                repositorio, que es la única puerta por la que el ciudadano llega al catálogo.
 * @property ModalidadTramite|null $modalidad
 * @property CostoTramite|null $tiene_costo
 * @property string|null $costo
 * @property int|null $tiempo_solucion_dias
 * @property CanalInicioTramite|null $canal_inicio
 * @property string|null $url_inicio
 * @property string|null $consulta_estado
 * @property list<array{descripcion: string, obligatorio?: bool}>|null $requisitos
 * @property list<array{nombre: string, url?: string|null, formato?: string|null}>|null $documentos
 * @property string|null $costo_tipo_valor
 * @property string|null $costo_moneda
 * @property string|null $costo_url_pago
 * @property string|null $costo_descripcion
 * @property list<array<string, string|null>> $costo_cuentas
 * @property string|null $resultado
 * @property list<string> $perfiles
 * @property list<array<string, mixed>> $puntos_atencion
 * @property list<array<string, mixed>> $normativa
 * @property list<array<string, mixed>> $canales_consulta_estado
 * @property string|null $categoria_slug
 * @property string|null $categoria_nombre
 * @property string|null $url_ficha_gov_co
 * @property string $busqueda
 * @property string|null $procedencia_fuente
 * @property string|null $procedencia_url
 * @property Carbon|null $procedencia_obtenido_en
 * @property string|null $procedencia_nota
 * @property string|null $procedencia_api
 * @property array<string, string> $procedencia_origen_por_campo
 * @property list<array{campo: string, regla: string}> $procedencia_derivados
 * @property list<string> $procedencia_faltantes
 * @property Carbon|null $publicado_en
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'codigo',
    'slug',
    'nombre',
    'resumen',
    'modalidad',
    'tiene_costo',
    'costo',
    'tiempo_solucion_dias',
    'canal_inicio',
    'url_inicio',
    'consulta_estado',
    'requisitos',
    'documentos',
    'resultado',
    'perfiles',
    'puntos_atencion',
    'normativa',
    'canales_consulta_estado',
    'costo_tipo_valor',
    'costo_moneda',
    'costo_url_pago',
    'costo_descripcion',
    'costo_cuentas',
    'categoria_slug',
    'categoria_nombre',
    'url_ficha_gov_co',
    'procedencia_fuente',
    'procedencia_url',
    'procedencia_obtenido_en',
    'procedencia_nota',
    'procedencia_api',
    'procedencia_origen_por_campo',
    'procedencia_derivados',
    'procedencia_faltantes',
    'publicado_en',
])]
final class Tramite extends Model
{
    /**
     * Cuántas palabras caben en el resumen.
     *
     * El resumen sale del propósito oficial, que puede ser bastante más largo;
     * se recorta a lo que el contrato admite al escribir (`TramiteInput`) para
     * que lo publicado se pueda volver a escribir sin perder texto.
     */
    public const MAXIMO_RESUMEN = 500;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'modalidad' => ModalidadTramite::class,
            'tiene_costo' => CostoTramite::class,
            'canal_inicio' => CanalInicioTramite::class,
            'tiempo_solucion_dias' => 'integer',
            'costo' => 'decimal:2',
            'requisitos' => 'array',
            'documentos' => 'array',
            'perfiles' => 'array',
            'puntos_atencion' => 'array',
            'normativa' => 'array',
            'canales_consulta_estado' => 'array',
            'costo_cuentas' => 'array',
            'procedencia_origen_por_campo' => 'array',
            'procedencia_derivados' => 'array',
            'procedencia_faltantes' => 'array',
            'publicado_en' => 'datetime',
            'procedencia_obtenido_en' => 'date',
        ];
    }

    protected static function booted(): void
    {
        self::saving(function (Tramite $tramite): void {
            $tramite->busqueda = self::textoBuscable($tramite->nombre, $tramite->resumen);
        });
    }

    /**
     * El texto contra el que se compara el término de búsqueda.
     *
     * Se usa `Str::ascii` y no `Normalizer` porque la extensión `intl` no está
     * garantizada en todos los entornos donde corre esto, y una búsqueda que
     * funciona en un servidor y no en otro es peor que una que falla en los dos.
     *
     * Los comodines de `LIKE` —`%` y `_`— se quitan en los dos lados: un `%`
     * suelto en el buscador devolvería el catálogo entero haciéndolo pasar por el
     * resultado de una búsqueda. Fuera de eso, el término del ciudadano y el
     * texto guardado pasan por la misma función, que es lo que garantiza que la
     * comparación sea justa.
     */
    public static function normalizar(string $texto): string
    {
        return str_replace(['%', '_'], '', Str::lower(Str::ascii(trim($texto))));
    }

    /** El texto contra el que se compara el término de búsqueda de un trámite. */
    public static function textoBuscable(string $nombre, ?string $resumen): string
    {
        return self::normalizar($nombre.' '.($resumen ?? ''));
    }

    /**
     * Recorta un texto largo sin partir la última palabra.
     *
     * Se corta en el último espacio anterior al límite y se marca con puntos
     * suspensivos, para que se lea que el texto continúa en la ficha oficial en
     * vez de parecer una frase terminada a la mitad.
     */
    public static function recortarResumen(?string $texto): ?string
    {
        $texto = $texto === null ? null : trim($texto);

        if ($texto === null || $texto === '') {
            return null;
        }

        if (mb_strlen($texto) <= self::MAXIMO_RESUMEN) {
            return $texto;
        }

        $corte = mb_substr($texto, 0, self::MAXIMO_RESUMEN - 1);
        $espacio = mb_strrpos($corte, ' ');

        return rtrim($espacio === false ? $corte : mb_substr($corte, 0, $espacio)).'…';
    }
}
