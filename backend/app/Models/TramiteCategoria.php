<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Un tema del catálogo de trámites («Educación», «Hacienda e impuestos»…). Lo administra la Entidad.
 *
 * @property int $id
 * @property string $slug
 * @property string $nombre
 * @property int $orden
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['slug', 'nombre', 'orden'])]
final class TramiteCategoria extends Model
{
    protected $table = 'tramite_categorias';

    /** @return HasMany<Tramite, $this> */
    public function tramites(): HasMany
    {
        return $this->hasMany(Tramite::class, 'categoria_id');
    }
}
