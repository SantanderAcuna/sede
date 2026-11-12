<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fila hija de un trámite (tramite_canales): se escribe siempre junto al trámite y no tiene vida propia.
 *
 * @property int $id
 * @property int $tramite_id
 * @property int $orden
 * @property string $canal
 * @property bool $habilitado
 * @property string|null $nombre
 * @property string|null $url
 * @property string|null $correo
 * @property string|null $telefono
 * @property string|null $horario
 */
#[Fillable(['orden', 'canal', 'habilitado', 'nombre', 'url', 'correo', 'telefono', 'horario'])]
final class TramiteCanal extends Model
{
    protected $table = 'tramite_canales';

    public $timestamps = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['orden' => 'integer', 'habilitado' => 'boolean'];
    }

    /** @return BelongsTo<Tramite, $this> */
    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class);
    }
}
