<?php

declare(strict_types=1);

use App\Models\Politica;
use Illuminate\Database\Migrations\Migration;

/**
 * Completa las políticas que ya estaban sembradas con lo que RF-01-019 y RF-01-020
 * piden del contenido (M01-5).
 *
 * El sembrador solo crea las que faltan (`firstOrCreate`) para no pisar lo que la
 * Entidad edite, así que un texto nuevo del sembrador no llega a una base que ya
 * tiene las filas. Esta migración lo lleva, con tres límites:
 *
 *  - Solo toca políticas que siguen siendo un ejemplo (`es_ejemplo = true`). Una
 *    que la Entidad ya adoptó, con su acto, no se toca nunca; si le falta algo, el
 *    panel se lo dirá al guardarla.
 *  - Solo AÑADE: el apartado de jurisdicción a los términos y el párrafo de plazos
 *    a los reclamos. No reescribe ningún texto existente, y si el apartado de
 *    reclamos ya trae el plazo, no añade nada.
 *  - Es idempotente: una segunda ejecución no cambia nada.
 *
 * Se guarda con el modelo y no con SQL directo para que el índice de búsqueda
 * (`busqueda`) se recalcule con el texto nuevo.
 *
 * Los textos están repetidos aquí a propósito: una migración describe lo que se hizo
 * en su fecha y no debe cambiar si mañana cambia el sembrador.
 */
return new class extends Migration
{
    private const TERMINOS = 'terminos-y-condiciones-de-uso';

    private const DATOS = 'proteccion-y-tratamiento-de-datos-personales';

    private const JURISDICCION = 'Estos términos se rigen por las leyes de la República de Colombia. Las controversias que surjan de su aplicación se resolverán ante la autoridad competente [por definir: autoridad y mecanismo para resolver controversias, definidos por la Entidad].';

    // Ley 1581 de 2012: art. 15 (reclamos: 15 días hábiles, prórroga hasta 8) y art. 14 (consultas: 10, prórroga 5).
    private const PLAZOS = 'Los reclamos se atienden en un máximo de quince (15) días hábiles contados desde el día siguiente a su recibo; si no es posible, se informa al titular el motivo de la demora y la fecha de respuesta, que no superará ocho (8) días hábiles más (Ley 1581 de 2012, art. 15). Las consultas se atienden en un máximo de diez (10) días hábiles, prorrogables por cinco (5) días hábiles más (art. 14).';

    private const PLAZO_YA_PRESENTE = '/(?<![\d.,])(?:quince|15)\s*(?:\(\s*15\s*\)\s*)?d[ií]as\s+h[aá]biles/iu';

    public function up(): void
    {
        $terminos = Politica::query()->where('slug', self::TERMINOS)->where('es_ejemplo', true)->first();
        if ($terminos !== null) {
            $secciones = $terminos->secciones;
            $ids = array_column($secciones, 'id');

            if (! in_array('jurisdiccion', $ids, true)) {
                $secciones[] = ['id' => 'jurisdiccion', 'titulo' => 'Jurisdicción y ley aplicable', 'parrafos' => [self::JURISDICCION]];
                $terminos->secciones = $secciones;
                $terminos->save();
            }
        }

        $datos = Politica::query()->where('slug', self::DATOS)->where('es_ejemplo', true)->first();
        if ($datos !== null) {
            $secciones = $datos->secciones;
            $cambio = false;

            foreach ($secciones as $i => $seccion) {
                if (($seccion['id'] ?? '') !== 'reclamos') {
                    continue;
                }
                $texto = implode(' ', $seccion['parrafos'] ?? []);
                if (preg_match(self::PLAZO_YA_PRESENTE, $texto) !== 1) {
                    $secciones[$i]['parrafos'][] = self::PLAZOS;
                    $cambio = true;
                }
            }

            if ($cambio) {
                $datos->secciones = $secciones;
                $datos->save();
            }
        }
    }

    public function down(): void
    {
        // Sin marcha atrás: quitar el apartado podría borrar texto que la Entidad ya haya trabajado.
    }
};
