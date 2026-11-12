<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fila hija de un trámite (tramite_audiencias): se escribe siempre junto al trámite y no tiene vida propia.
 *
 * @property int $id
 * @property int $tramite_id
 * @property string $grupo
 */
#[Fillable(['grupo'])]
final class TramiteAudiencia extends Model
{
    protected $table = 'tramite_audiencias';

    public $timestamps = false;

    /** @return BelongsTo<Tramite, $this> */
    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class);
    }
}
