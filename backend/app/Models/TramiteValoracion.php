<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * La valoración «¿Cómo fue tu experiencia?» de una ficha: la rápida FÁCIL/DIFÍCIL (RF-B2-033/034),
 * la encuesta de 1 a 5 estrellas (RF-B1-094), o las dos, con un comentario opcional.
 *
 * @property int $id
 * @property int $tramite_id
 * @property string|null $valor
 * @property int|null $estrellas
 * @property string|null $comentario
 * @property Carbon $created_at
 */
#[Fillable(['tramite_id', 'valor', 'estrellas', 'comentario'])]
final class TramiteValoracion extends Model
{
    public const FACIL = 'facil';

    public const DIFICIL = 'dificil';

    protected $table = 'tramite_valoraciones';

    public const UPDATED_AT = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['estrellas' => 'integer'];
    }
}
