<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Una política de la sede (T-20).
 *
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property string $nombre
 * @property int $version
 * @property Carbon|null $vigente_desde
 * @property string|null $acto_tipo
 * @property string|null $acto_numero
 * @property Carbon|null $acto_fecha
 * @property list<array{id: string, titulo: string, parrafos: list<string>}> $secciones
 * @property bool $es_ejemplo
 * @property Carbon $updated_at
 */
final class Politica extends Model
{
    use HasUuids;

    /** Colección de `file_media` donde se adjunta el documento oficial aprobado. */
    public const COLECCION_DOCUMENTO = 'documento-oficial';

    /**
     * Apartados mínimos de cada política: `slug => [id => rótulo]`.
     *
     * Es una PROPUESTA de TIC, tomada de lo que cada tema exige de forma habitual
     * (Resolución 1519 de 2020 para la sede; Ley 1581 de 2012 para datos
     * personales). Jurídica / el Oficial de Protección de Datos deben confirmarla.
     * La misma lista valida lo que se guarda desde el panel.
     */
    public const COMPONENTES = [
        'terminos-y-condiciones-de-uso' => [
            'condiciones-y-alcance' => 'Condiciones y alcance de uso',
            'limites-de-uso' => 'Límites de uso',
            'derechos-del-usuario' => 'Derechos del usuario',
            'deberes-del-usuario' => 'Deberes del usuario',
            'responsabilidad-de-la-entidad' => 'Responsabilidad de la Entidad',
            'contacto' => 'Contacto',
            'privacidad-y-seguridad' => 'Referencia a la política de privacidad y seguridad',
        ],
        'seguridad-y-privacidad' => [
            'categorias-de-datos' => 'Categorías de datos',
            'finalidades' => 'Finalidades',
            'derechos-arco' => 'Derechos de acceso, actualización, rectificación, supresión y revocatoria',
            'responsable' => 'Contacto del responsable',
            'reclamos' => 'Mecanismo de reclamo',
        ],
        'proteccion-y-tratamiento-de-datos-personales' => [
            'categorias-de-datos' => 'Categorías de datos',
            'finalidades' => 'Finalidades',
            'derechos-arco' => 'Derechos de acceso, actualización, rectificación, supresión y revocatoria',
            'responsable' => 'Contacto del responsable',
            'reclamos' => 'Mecanismo de reclamo',
        ],
        'uso-de-cookies' => [
            'cookies-que-usa' => 'Qué cookies usa el sitio',
            'finalidad' => 'Finalidad de cada una',
            'duracion' => 'Duración',
            'consentimiento' => 'Cómo se administra el consentimiento',
        ],
        'derechos-de-autor-y-uso-sobre-contenidos' => [
            'titularidad' => 'Titularidad de los contenidos',
            'reutilizacion' => 'Condiciones de reutilización',
            'licencia' => 'Licencia que los acompaña',
            'como-citar' => 'Cómo citarlos',
        ],
    ];

    /** Marcador de dato por completar: «[por asignar]», «[por definir: ...]». */
    public const MARCADOR = '/\[\s*por\s[^\]]*\]/iu';

    protected $table = 'politicas';

    protected $fillable = [
        'slug', 'nombre', 'version', 'vigente_desde', 'acto_tipo', 'acto_numero',
        'acto_fecha', 'secciones', 'es_ejemplo',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'vigente_desde' => 'date',
            'acto_fecha' => 'date',
            'secciones' => 'array',
            'es_ejemplo' => 'boolean',
        ];
    }

    /**
     * Qué le falta a la política para estar completa. Lista vacía = completa.
     *
     * Siempre se exigen todos los apartados con texto y que no diga «en
     * preparación» (AC-20.7). Si ya no es un ejemplo, además: versión, vigencia y
     * acto administrativo completo y sin marcadores «[...]» (AC-20.6).
     *
     * @return list<string>
     */
    public function problemas(): array
    {
        $problemas = [];

        $porId = [];
        foreach ($this->secciones as $seccion) {
            $porId[$seccion['id'] ?? ''] = $seccion;
        }

        foreach (self::COMPONENTES[$this->slug] ?? [] as $id => $rotulo) {
            $seccion = $porId[$id] ?? null;
            if ($seccion === null) {
                $problemas[] = "Falta el apartado «{$rotulo}».";

                continue;
            }
            $texto = trim(implode(' ', $seccion['parrafos'] ?? []));
            if (trim((string) ($seccion['titulo'] ?? '')) === '' || $texto === '') {
                $problemas[] = "El apartado «{$rotulo}» está vacío.";
            }
        }

        $textos = [];
        foreach ($this->secciones as $seccion) {
            $textos[] = (string) ($seccion['titulo'] ?? '');
            foreach ($seccion['parrafos'] ?? [] as $parrafo) {
                $textos[] = (string) $parrafo;
            }
        }
        if (preg_match('/en preparaci[oó]n/iu', implode("\n", $textos)) === 1) {
            $problemas[] = 'El texto contiene «en preparación».';
        }

        if (! $this->es_ejemplo) {
            if (preg_match(self::MARCADOR, implode("\n", $textos)) === 1) {
                $problemas[] = 'El texto todavía tiene marcadores «[por ...]» sin completar.';
            }
            if ($this->vigente_desde === null) {
                $problemas[] = 'Falta la fecha desde la que rige.';
            }
            foreach (['el tipo' => $this->acto_tipo, 'el número' => $this->acto_numero] as $que => $valor) {
                $valor = trim((string) $valor);
                if ($valor === '') {
                    $problemas[] = "Falta {$que} del acto administrativo.";
                } elseif (preg_match(self::MARCADOR, $valor) === 1) {
                    $problemas[] = "Del acto administrativo, {$que} todavía es un marcador «[...]».";
                }
            }
            if ($this->acto_fecha === null) {
                $problemas[] = 'Falta la fecha del acto administrativo.';
            }
        }

        return $problemas;
    }

    /**
     * El documento oficial adjunto (el acto administrativo o el texto aprobado),
     * guardado en la tabla común de archivos del sistema, `file_media`. Si se
     * subieron varios, vale el más reciente.
     */
    public function documentoOficial(): ?FileMedia
    {
        return FileMedia::query()
            ->where('model_type', self::class)
            ->where('model_uuid', $this->uuid)
            ->where('collection', self::COLECCION_DOCUMENTO)
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Lo que el sitio necesita para enlazar el documento oficial; `null` si no hay.
     * La dirección es estable (`/politicas/<slug>/documento`) y no una URL firmada
     * que caduca.
     *
     * @return array{nombre: string, tipo: string, tamano: int, url: string}|null
     */
    private function resumenDocumentoOficial(): ?array
    {
        $archivo = $this->documentoOficial();

        return $archivo === null ? null : [
            'nombre' => $archivo->original_filename,
            'tipo' => $archivo->mime_type,
            'tamano' => $archivo->size,
            'url' => '/politicas/'.$this->slug.'/documento',
        ];
    }

    /**
     * Forma pública del documento.
     *
     * @return array<string, mixed>
     */
    public function comoArreglo(): array
    {
        return [
            'id' => $this->id,
            'type' => 'politica',
            'slug' => $this->slug,
            'nombre' => $this->nombre,
            'version' => $this->version,
            'vigente_desde' => $this->vigente_desde?->toDateString(),
            'acto' => [
                'tipo' => $this->acto_tipo,
                'numero' => $this->acto_numero,
                'fecha' => $this->acto_fecha?->toDateString(),
            ],
            'es_ejemplo' => $this->es_ejemplo,
            'documento_oficial' => $this->resumenDocumentoOficial(),
            'actualizada_en' => $this->updated_at->toDateString(),
            'secciones' => $this->secciones,
        ];
    }
}
