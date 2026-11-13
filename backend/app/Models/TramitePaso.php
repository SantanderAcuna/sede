<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fila hija de un trámite (tramite_pasos): se escribe siempre junto al trámite y no tiene vida propia.
 *
 * @property int $id
 * @property int $tramite_id
 * @property int $orden
 * @property string $titulo
 * @property string|null $descripcion
 */
#[Fillable(['orden', 'titulo', 'descripcion'])]
final class TramitePaso extends Model
{
    protected $table = 'tramite_pasos';

    public $timestamps = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['orden' => 'integer'];
    }

    /** @return BelongsTo<Tramite, $this> */
    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class);
    }
}
