<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un evento de consentimiento de cookies (T-26, AC-26.4).
 *
 * @property int $id
 * @property string $evento
 * @property list<string> $categorias
 * @property int $version_politica
 * @property \Illuminate\Support\Carbon $registrado_en
 */
final class Consentimiento extends Model
{
    /** Lo que pasó: el ciudadano aceptó, rechazó o revocó; o su consentimiento caducó. */
    public const EVENTOS = ['aceptado', 'rechazado', 'revocado', 'caducado'];

    /** Categorías opcionales de cookies. Las necesarias no se registran: no se eligen. */
    public const CATEGORIAS = ['analitica', 'preferencias'];

    protected $table = 'consentimientos';

    // La única fecha es `registrado_en`, que pone el servidor al guardar.
    public $timestamps = false;

    protected $fillable = ['evento', 'categorias', 'version_politica', 'registrado_en'];

    protected function casts(): array
    {
        return [
            'categorias' => 'array',
            'version_politica' => 'integer',
            'registrado_en' => 'datetime',
        ];
    }
}
