<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fila hija de un trámite (tramite_normas): se escribe siempre junto al trámite y no tiene vida propia.
 *
 * @property int $id
 * @property int $tramite_id
 * @property int $orden
 * @property string $tipo
 * @property string|null $numero
 * @property int|null $anio
 * @property string|null $articulos
 * @property string|null $url
 */
#[Fillable(['orden', 'tipo', 'numero', 'anio', 'articulos', 'url'])]
final class TramiteNorma extends Model
{
    protected $table = 'tramite_normas';

    public $timestamps = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['orden' => 'integer', 'anio' => 'integer'];
    }

    /** @return BelongsTo<Tramite, $this> */
    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class);
    }
}
