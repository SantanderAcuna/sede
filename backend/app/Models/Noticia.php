<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasUuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Una noticia del home (M01-6, RF-01-013).
 *
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property string $titulo
 * @property string $descripcion_corta
 * @property string $contenido
 * @property Carbon $fecha_publicacion
 * @property bool $destacada
 * @property bool $activo
 * @property int|null $autor_id
 * @property Carbon $updated_at
 * @property FileMedia|null $imagenPrincipal
 */
final class Noticia extends Model
{
    use HasUuids, SoftDeletes;

    /** Colección de `file_media` donde se adjunta la imagen de la noticia. */
    public const COLECCION_IMAGEN = 'imagen';

    /** Límites del requisito RF-01-013. */
    public const TITULO_MAX = 150;

    public const DESCRIPCION_MAX = 200;

    /** El texto alternativo describe la imagen; más de esto ya no es «alternativo». */
    public const ALT_MAX = 250;

    /** @var list<string> */
    protected $fillable = [
        'slug', 'titulo', 'descripcion_corta', 'contenido',
        'fecha_publicacion', 'destacada', 'activo', 'autor_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'fecha_publicacion' => 'datetime',
            'destacada' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /**
     * Las que el sitio puede mostrar: publicadas, con fecha ya cumplida y sin
     * eliminar (las eliminadas las excluye `SoftDeletes`). Orden del requisito:
     * la más reciente primero; con la misma fecha, la de `id` mayor.
     *
     * @param  Builder<Noticia>  $consulta
     * @return Builder<Noticia>
     */
    public function scopePublicadas(Builder $consulta): Builder
    {
        return $consulta
            ->where('activo', true)
            ->where('fecha_publicacion', '<=', now())
            ->orderByDesc('fecha_publicacion')
            ->orderByDesc('id');
    }

    /**
     * La imagen de la noticia en la tabla común de archivos. Se une por `uuid`
     * (como el resto de adjuntos) y se puede precargar para evitar una consulta
     * por noticia en el listado.
     *
     * @return HasOne<FileMedia, $this>
     */
    public function imagenPrincipal(): HasOne
    {
        return $this->hasOne(FileMedia::class, 'model_uuid', 'uuid')
            ->where('model_type', self::class)
            ->where('collection', self::COLECCION_IMAGEN);
    }

    /** El texto alternativo de la imagen, o nulo si no hay imagen o no lo tiene. */
    public function imagenAlt(): ?string
    {
        $alt = $this->imagenPrincipal?->metadata['alt'] ?? null;

        return is_string($alt) ? $alt : null;
    }

    /**
     * Lo que le falta a la noticia para poder publicarse.
     *
     * Una noticia publicada sin imagen o sin su `alt` incumple el requisito y la
     * Res. 1519/2020 (Anexo 2), así que el panel y la API no la dejan pasar.
     *
     * @return list<string>
     */
    public function problemas(): array
    {
        $problemas = [];

        if (trim($this->titulo) === '' || mb_strlen($this->titulo) > self::TITULO_MAX) {
            $problemas[] = 'El título debe tener entre 1 y '.self::TITULO_MAX.' caracteres.';
        }
        if (trim($this->descripcion_corta) === '' || mb_strlen($this->descripcion_corta) > self::DESCRIPCION_MAX) {
            $problemas[] = 'La descripción debe tener entre 1 y '.self::DESCRIPCION_MAX.' caracteres.';
        }
        if (trim($this->contenido) === '') {
            $problemas[] = 'La noticia no tiene contenido.';
        }
        if ($this->imagenPrincipal === null) {
            $problemas[] = 'Falta la imagen de la noticia.';
        } elseif (trim((string) $this->imagenAlt()) === '') {
            $problemas[] = 'La imagen no tiene texto alternativo (alt).';
        }

        return $problemas;
    }
}
