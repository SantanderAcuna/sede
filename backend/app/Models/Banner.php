<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasUuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Una diapositiva del carrusel de la portada.
 *
 * @property int $id
 * @property string $uuid
 * @property string $titulo
 * @property string|null $descripcion
 * @property string|null $enlace
 * @property int $orden
 * @property bool $activo
 * @property Carbon|null $vigente_desde
 * @property Carbon|null $vigente_hasta
 * @property FileMedia|null $imagenPrincipal
 */
final class Banner extends Model
{
    use HasUuids, SoftDeletes;

    public const COLECCION_IMAGEN = 'imagen';

    public const TITULO_MAX = 80;

    public const DESCRIPCION_MAX = 200;

    public const ALT_MAX = 250;

    public const BORRADOR = 'borrador';

    public const PROGRAMADO = 'programado';

    public const VIGENTE = 'vigente';

    public const VENCIDO = 'vencido';

    /** @var list<string> */
    protected $fillable = ['titulo', 'descripcion', 'enlace', 'orden', 'activo', 'vigente_desde', 'vigente_hasta'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
            'vigente_desde' => 'datetime',
            'vigente_hasta' => 'datetime',
        ];
    }

    /** @return HasOne<FileMedia, $this> */
    public function imagenPrincipal(): HasOne
    {
        return $this->hasOne(FileMedia::class, 'model_uuid', 'uuid')
            ->where('model_type', self::class)
            ->where('collection', self::COLECCION_IMAGEN);
    }

    /**
     * Las que el sitio puede mostrar: activas, dentro de su vigencia y sin eliminar.
     *
     * @param  Builder<Banner>  $consulta
     * @return Builder<Banner>
     */
    public function scopeVigentes(Builder $consulta): Builder
    {
        return $consulta
            ->where('activo', true)
            ->where(fn (Builder $q) => $q->whereNull('vigente_desde')->orWhere('vigente_desde', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>', now()))
            ->orderBy('orden')
            ->orderBy('id');
    }

    /** Borrador, programado, vigente o vencido: lo que el panel dice con palabras. */
    public function estado(): string
    {
        if (! $this->activo) {
            return self::BORRADOR;
        }
        if ($this->vigente_desde !== null && $this->vigente_desde->isFuture()) {
            return self::PROGRAMADO;
        }
        if ($this->vigente_hasta !== null && $this->vigente_hasta->lte(now())) {
            return self::VENCIDO;
        }

        return self::VIGENTE;
    }

    public function imagenAlt(): ?string
    {
        $alt = $this->imagenPrincipal?->metadata['alt'] ?? null;

        return is_string($alt) && trim($alt) !== '' ? $alt : null;
    }

    /**
     * Lo que le falta para poder publicarse (Res. 1519/2020, Anexo 2: texto alternativo).
     *
     * @return list<string>
     */
    public function problemas(): array
    {
        $problemas = [];
        if ($this->imagenPrincipal === null) {
            $problemas[] = 'Falta la imagen de la diapositiva.';
        } elseif ($this->imagenAlt() === null) {
            $problemas[] = 'Falta el texto alternativo de la imagen.';
        }

        return $problemas;
    }
}
