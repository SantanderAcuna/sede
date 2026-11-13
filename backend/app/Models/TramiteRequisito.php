<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fila hija de un trámite (tramite_requisitos): se escribe siempre junto al trámite y no tiene vida propia.
 *
 * @property int $id
 * @property int $tramite_id
 * @property int|null $paso_id
 * @property int $orden
 * @property string $tipo
 * @property string $descripcion
 * @property bool $obligatorio
 * @property string|null $nota
 * @property string|null $url
 */
#[Fillable(['paso_id', 'orden', 'tipo', 'descripcion', 'obligatorio', 'nota', 'url'])]
final class TramiteRequisito extends Model
{
    protected $table = 'tramite_requisitos';

    public $timestamps = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['paso_id' => 'integer', 'orden' => 'integer', 'obligatorio' => 'boolean'];
    }

    /** @return BelongsTo<Tramite, $this> */
    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class);
    }
}
