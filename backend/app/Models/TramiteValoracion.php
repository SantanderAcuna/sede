<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * La valoración «¿Cómo fue tu experiencia?» de una ficha (RF-B2-033/034): fácil o difícil, y un comentario.
 *
 * @property int $id
 * @property int $tramite_id
 * @property string $valor
 * @property string|null $comentario
 * @property Carbon $created_at
 */
#[Fillable(['tramite_id', 'valor', 'comentario'])]
final class TramiteValoracion extends Model
{
    public const FACIL = 'facil';

    public const DIFICIL = 'dificil';

    protected $table = 'tramite_valoraciones';

    public const UPDATED_AT = null;
}
