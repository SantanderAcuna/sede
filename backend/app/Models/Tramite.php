<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CanalInicioTramite;
use App\Enums\CostoTramite;
use App\Enums\ModalidadTramite;
use App\Models\Traits\HasUuids;
use App\Support\Busqueda\TextoBuscable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Un elemento del catálogo de la Entidad: un trámite, una OPA o una consulta de acceso a la
 * información pública.
 *
 * **Contenido propio de la sede, con base relacional.** La ficha ya no viaja en bloques JSON ni
 * se copia de un visor externo: cada parte —pasos, requisitos, documentos, puntos de atención,
 * normas, canales y cuentas— vive en su tabla hija y se administra desde el panel. El código
 * (`T41040`…) y el `slug` son estables porque ya circulan en enlaces; `url_ficha_gov_co` es un
 * enlace de referencia **opcional**, no una dependencia.
 *
 * Lo que este modelo protege:
 *
 * - **Qué se publica lo decide el contenido propio, no una fuente externa.** La regla de
 *   `scopePublicados` y `problemasParaPublicar` exige los datos que la Guía §5.1.3 pide de una
 *   ficha (modalidad, costo, tiempo, canal, cómo consultar el estado y al menos un requisito).
 *   La ficha de GOV.CO dejó de ser condición: un trámite propio sin registro nacional también se
 *   publica.
 * - **El texto buscable se calcula solo** en cada escritura, a partir del nombre y el resumen;
 *   rellenarlo a mano dejaría el índice mintiendo tras la primera corrección.
 * - **Nada es inborrable.** Todo elemento se edita y se envía a la papelera (soft delete); no hay
 *   candado por origen porque ya no hay origen externo que proteger.
 *
 * @property int $id
 * @property string $uuid
 * @property string $codigo
 * @property string $slug
 * @property string $nombre
 * @property string|null $resumen
 * @property string $type tramites | opa | consultas
 * @property ModalidadTramite|null $modalidad
 * @property CostoTramite|null $tiene_costo
 * @property string|null $costo
 * @property int|null $tiempo_solucion_dias
 * @property CanalInicioTramite|null $canal_inicio
 * @property string|null $url_inicio
 * @property string|null $url_manual_tramite_en_linea
 * @property string|null $consulta_estado
 * @property string|null $costo_tipo_valor
 * @property string|null $costo_moneda
 * @property string|null $costo_url_pago
 * @property string|null $costo_descripcion
 * @property string|null $resultado
 * @property string|null $observaciones_resultado
 * @property bool|null $fecha_cualquiera
 * @property string|null $cuando_se_puede_realizar
 * @property string|null $url_calendario
 * @property string|null $palabras_relacionadas
 * @property list<string> $medios_resultado
 * @property int|null $categoria_id
 * @property int|null $dependencia_id
 * @property int|null $nivel_transformacion
 * @property string $estado_estandarizado
 * @property string $nivel_autenticacion_requerido
 * @property bool $aplica_sap
 * @property int|null $termino_sap_dias
 * @property string $tipo_silencio_administrativo
 * @property bool $requiere_concepto_dafp
 * @property int|null $solicitudes_por_anio
 * @property int|null $bloque_digitalizacion
 * @property string|null $fase_digitalizacion
 * @property int $version_ficha
 * @property int $suit_version
 * @property Carbon|null $fecha_ultima_actualizacion_suit
 * @property string|null $url_ficha_gov_co
 * @property string $busqueda
 * @property string $busqueda_titulo
 * @property Carbon|null $publicado_en
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read TramiteCategoria|null $categoria
 * @property-read Dependencia|null $dependencia
 * @property-read Collection<int, TramitePaso> $pasos
 * @property-read Collection<int, TramiteRequisito> $requisitos
 * @property-read Collection<int, TramiteDocumento> $documentos
 * @property-read Collection<int, TramitePuntoAtencion> $puntosAtencion
 * @property-read Collection<int, TramiteNorma> $normas
 * @property-read Collection<int, TramiteCanal> $canales
 * @property-read Collection<int, TramiteAudiencia> $audiencias
 * @property-read Collection<int, TramiteCuenta> $cuentas
 * @property-read Collection<int, TramiteValoracion> $valoraciones
 */
#[Fillable([
    'codigo',
    'slug',
    'nombre',
    'resumen',
    'type',
    'modalidad',
    'tiene_costo',
    'costo',
    'tiempo_solucion_dias',
    'canal_inicio',
    'url_inicio',
    'url_manual_tramite_en_linea',
    'consulta_estado',
    'costo_tipo_valor',
    'costo_moneda',
    'costo_url_pago',
    'costo_descripcion',
    'resultado',
    'observaciones_resultado',
    'fecha_cualquiera',
    'cuando_se_puede_realizar',
    'url_calendario',
    'palabras_relacionadas',
    'medios_resultado',
    'categoria_id',
    'dependencia_id',
    'nivel_transformacion',
    'estado_estandarizado',
    'nivel_autenticacion_requerido',
    'aplica_sap',
    'termino_sap_dias',
    'tipo_silencio_administrativo',
    'requiere_concepto_dafp',
    'solicitudes_por_anio',
    'bloque_digitalizacion',
    'fase_digitalizacion',
    'url_ficha_gov_co',
    'publicado_en',
])]
/**
 * @use HasFactory<TramiteFactory>
 */
final class Tramite extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /** Los tres grupos del Anexo 2.1 que la sección publica. */
    public const TIPOS = ['tramites', 'opa', 'consultas'];

    /** El prefijo del código por tipo, para la generación automática al crear. */
    public const PREFIJO_CODIGO = ['tramites' => 'T', 'opa' => 'O', 'consultas' => 'C'];

    /**
     * Cuántas palabras caben en el resumen.
     *
     * El resumen sale del propósito oficial, que puede ser bastante más largo; se recorta a lo
     * que el contrato admite al escribir para que lo publicado se pueda volver a escribir sin
     * perder texto.
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
            'medios_resultado' => 'array',
            'fecha_cualquiera' => 'boolean',
            'aplica_sap' => 'boolean',
            'requiere_concepto_dafp' => 'boolean',
            'nivel_transformacion' => 'integer',
            'termino_sap_dias' => 'integer',
            'solicitudes_por_anio' => 'integer',
            'bloque_digitalizacion' => 'integer',
            'version_ficha' => 'integer',
            'suit_version' => 'integer',
            'fecha_ultima_actualizacion_suit' => 'date',
            'publicado_en' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::saving(function (Tramite $tramite): void {
            $tramite->busqueda = self::textoBuscable($tramite->nombre, $tramite->resumen);
            // Sólo el nombre, aparte: un término que coincide con el título pesa más que uno que
            // aparece en el resumen (ver `docs/busqueda.md`).
            $tramite->busqueda_titulo = TextoBuscable::normalizar($tramite->nombre);
        });
    }

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    /** @return BelongsTo<TramiteCategoria, $this> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(TramiteCategoria::class);
    }

    /** @return BelongsTo<Dependencia, $this> */
    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class);
    }

    /** @return HasMany<TramitePaso, $this> */
    public function pasos(): HasMany
    {
        return $this->hasMany(TramitePaso::class)->orderBy('orden');
    }

    /** @return HasMany<TramiteRequisito, $this> */
    public function requisitos(): HasMany
    {
        return $this->hasMany(TramiteRequisito::class)->orderBy('orden');
    }

    /** @return HasMany<TramiteDocumento, $this> */
    public function documentos(): HasMany
    {
        return $this->hasMany(TramiteDocumento::class)->orderBy('orden');
    }

    /** @return HasMany<TramitePuntoAtencion, $this> */
    public function puntosAtencion(): HasMany
    {
        return $this->hasMany(TramitePuntoAtencion::class)->orderBy('orden');
    }

    /** @return HasMany<TramiteNorma, $this> */
    public function normas(): HasMany
    {
        return $this->hasMany(TramiteNorma::class)->orderBy('orden');
    }

    /** @return HasMany<TramiteCanal, $this> */
    public function canales(): HasMany
    {
        return $this->hasMany(TramiteCanal::class)->orderBy('orden');
    }

    /** @return HasMany<TramiteAudiencia, $this> */
    public function audiencias(): HasMany
    {
        return $this->hasMany(TramiteAudiencia::class);
    }

    /** @return HasMany<TramiteCuenta, $this> */
    public function cuentas(): HasMany
    {
        return $this->hasMany(TramiteCuenta::class)->orderBy('orden');
    }

    /** @return HasMany<TramiteValoracion, $this> */
    public function valoraciones(): HasMany
    {
        return $this->hasMany(TramiteValoracion::class);
    }

    /** Las relaciones hijas que componen la ficha, para cargar y copiar en bloque. */
    public const RELACIONES_FICHA = [
        'pasos', 'requisitos', 'documentos', 'puntosAtencion', 'normas', 'canales', 'audiencias', 'cuentas',
    ];

    // -------------------------------------------------------------------------
    // Publicación
    // -------------------------------------------------------------------------

    /** ¿Este elemento exige ficha en GOV.CO para publicarse? Trámites y OPA sí; consultas no. */
    public function exigeFichaGovCo(): bool
    {
        // Las consultas de acceso a información pública no son trámites y se suprimen del SUIT
        // (RN-B2-002): no tienen ficha nacional. Trámites y OPA sí la tienen (RF-B1-021).
        return $this->type !== 'consultas';
    }

    /**
     * ¿Tiene al menos un requisito? Usa el conteo ya cargado (`withCount`) o la relación cargada
     * cuando están, para no disparar una consulta por fila en los listados.
     */
    public function tieneRequisitos(): bool
    {
        if ($this->relationLoaded('requisitos')) {
            return $this->requisitos->isNotEmpty();
        }
        $contado = $this->getAttribute('requisitos_count');
        if ($contado !== null) {
            return (int) $contado > 0;
        }

        return $this->requisitos()->exists();
    }

    /**
     * Qué le falta para publicarse. Lista vacía = se puede publicar.
     *
     * La regla mezcla el **contenido propio** de la ficha (Guía §5.1.3: modalidad, costo, tiempo,
     * canal, cómo se consulta el estado y al menos un requisito) con la **integración GOV.CO**: un
     * trámite o una OPA se publica con su ficha nacional (RF-B1-021); una consulta no la exige. Es
     * la misma regla de `scopePublicados`, dicha con palabras para quien edita.
     *
     * @return list<string>
     */
    public function problemasParaPublicar(): array
    {
        $problemas = [];

        if (trim((string) $this->resumen) === '') {
            $problemas[] = 'Falta un resumen de qué es el trámite.';
        }
        if ($this->modalidad === null) {
            $problemas[] = 'Falta la modalidad (en línea, parcialmente en línea o presencial).';
        }
        if ($this->tiene_costo === null) {
            $problemas[] = 'Falta decir si el trámite tiene costo o es gratuito.';
        }
        if ($this->tiempo_solucion_dias === null) {
            $problemas[] = 'Falta el tiempo de solución, en días.';
        }
        if ($this->canal_inicio === null) {
            $problemas[] = 'Falta el canal por el que se inicia el trámite.';
        }
        if (trim((string) $this->consulta_estado) === '') {
            $problemas[] = 'Falta cómo se consulta el estado del trámite.';
        }
        if ($this->exigeFichaGovCo() && trim((string) $this->url_ficha_gov_co) === '') {
            $problemas[] = 'Falta la dirección de la ficha en GOV.CO (obligatoria para trámites y OPA).';
        }
        if (! $this->tieneRequisitos()) {
            $problemas[] = 'Falta al menos un requisito.';
        }

        return $problemas;
    }

    /**
     * Los elementos del catálogo que el ciudadano puede ver.
     *
     * La regla vive en el modelo —y no repartida entre quienes consultan— porque por ella pasan las
     * dos puertas del ciudadano al catálogo (el listado y la búsqueda): si cada una escribiera su
     * copia, bastaría con que una olvidara una condición para publicar un elemento incompleto.
     *
     * @param  Builder<Tramite>  $consulta
     */
    public function scopePublicados(Builder $consulta): void
    {
        $consulta
            ->whereNotNull('publicado_en')
            ->whereNotNull('modalidad')
            ->whereNotNull('tiene_costo')
            ->whereNotNull('tiempo_solucion_dias')
            ->whereNotNull('canal_inicio')
            ->whereNotNull('consulta_estado')
            ->where('consulta_estado', '!=', '')
            ->whereHas('requisitos')
            // Integración GOV.CO: trámites y OPA se publican con su ficha nacional; las consultas de
            // acceso a información pública no están en el SUIT (RN-B2-002), así que no la exigen.
            ->where(function (Builder $q): void {
                $q->where(function (Builder $w): void {
                    $w->whereNotNull('url_ficha_gov_co')->where('url_ficha_gov_co', '!=', '');
                })->orWhere('type', 'consultas');
            });
    }

    // -------------------------------------------------------------------------
    // Búsqueda
    // -------------------------------------------------------------------------

    /**
     * El texto contra el que se compara el término de búsqueda.
     *
     * Se usa la normalización de `TextoBuscable` (sin tildes, en minúsculas, sin comodines de
     * `LIKE`), la misma por la que pasa el término del ciudadano, que es lo que garantiza que la
     * comparación sea justa.
     */
    public static function normalizar(string $texto): string
    {
        return TextoBuscable::normalizar($texto);
    }

    /** El texto contra el que se compara el término de búsqueda de un trámite. */
    public static function textoBuscable(string $nombre, ?string $resumen): string
    {
        return TextoBuscable::deTramite($nombre, $resumen);
    }

    /**
     * Recorta un texto largo sin partir la última palabra.
     *
     * Se corta en el último espacio anterior al límite y se marca con puntos suspensivos, para que
     * se lea que el texto continúa en vez de parecer una frase terminada a la mitad.
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
