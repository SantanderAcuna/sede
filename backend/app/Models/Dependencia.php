<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Una dependencia de la Alcaldía responsable de trámites (Hacienda, Movilidad, Planeación…).
 *
 * @property int $id
 * @property string $nombre
 * @property int $orden
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['nombre', 'orden'])]
final class Dependencia extends Model
{
    protected $table = 'dependencias';

    /** @return HasMany<Tramite, $this> */
    public function tramites(): HasMany
    {
        return $this->hasMany(Tramite::class, 'dependencia_id');
    }
}
