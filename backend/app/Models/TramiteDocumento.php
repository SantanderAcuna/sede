<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fila hija de un trámite (tramite_documentos): se escribe siempre junto al trámite y no tiene vida propia.
 *
 * @property int $id
 * @property int $tramite_id
 * @property int $orden
 * @property string $nombre
 * @property string|null $url
 * @property string|null $formato
 */
#[Fillable(['orden', 'nombre', 'url', 'formato'])]
final class TramiteDocumento extends Model
{
    protected $table = 'tramite_documentos';

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
