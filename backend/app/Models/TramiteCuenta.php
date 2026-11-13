<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fila hija de un trámite (tramite_cuentas): se escribe siempre junto al trámite y no tiene vida propia.
 *
 * @property int $id
 * @property int $tramite_id
 * @property int $orden
 * @property string|null $banco
 * @property string|null $tipo
 * @property string|null $numero
 * @property string|null $titular
 */
#[Fillable(['orden', 'banco', 'tipo', 'numero', 'titular'])]
final class TramiteCuenta extends Model
{
    protected $table = 'tramite_cuentas';

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
