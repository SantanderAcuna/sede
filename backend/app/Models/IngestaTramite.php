<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * El punto de control de la ingesta de un trámite.
 *
 * Una fila por trámite del catálogo, con el estado en que quedó el último intento
 * de traer su ficha oficial. Es lo que hace **reanudable** la ingesta: la fuente
 * del Estado limita la tasa y una recolección completa hace más de mil peticiones,
 * así que una ejecución que se corta a mitad es lo normal y volver a empezar de
 * cero cada vez significa no terminar nunca.
 *
 * **No guarda el contenido que se trajo**, y es deliberado. La respuesta de la
 * fuente trae el teléfono móvil de algunos puntos de atención y los buzones del
 * área responsable, y la ingesta los descarta al publicar. Copiarlos aquí «por si
 * hay que reanudar» los dejaría guardados en un segundo sitio donde nadie los
 * mira, que es lo contrario de descartarlos. Reanudar vuelve a pedir el trámite
 * que se quedó a medias y salta entero el que ya terminó.
 *
 * @property int $id
 * @property string $codigo
 * @property string $estado
 * @property int $intentos
 * @property string|null $error
 * @property Carbon|null $terminado_en
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'codigo',
    'estado',
    'intentos',
    'error',
    'terminado_en',
])]
final class IngestaTramite extends Model
{
    /** Todavía no se ha traído, o se intentó y no se terminó. */
    public const PENDIENTE = 'pendiente';

    /** Se trajo entera y ya está en el catálogo. Es la marca que salta la reanudación. */
    public const COMPLETO = 'completo';

    /** Se intentó y no se pudo. Se distingue de `pendiente` para que un fallo no parezca una espera. */
    public const FALLIDO = 'fallido';

    protected $table = 'ingesta_tramites';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'intentos' => 'integer',
            'terminado_en' => 'datetime',
        ];
    }
}
