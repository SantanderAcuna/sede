<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Support\Carbon;

/**
 * Guarda las fechas con el desfase que traen, en UTC.
 *
 * El panel manda «2026-11-19T10:00:00-05:00». El cast `datetime` de Eloquent la lee bien, pero al
 * escribirla la formatea con su propio desfase y descarta la zona: guardaba «10:00» como si fuera UTC,
 * cinco horas antes de lo que la persona eligió. Aquí se pasa a la zona de la aplicación (UTC) antes de
 * formatear. Un valor ya en UTC, o una fecha sin hora, queda igual.
 */
trait GuardaFechasEnUtc
{
    /**
     * @param  mixed  $value
     */
    public function fromDateTime($value)
    {
        if ($value === null) {
            return $value;
        }

        $fecha = $this->asDateTime($value);

        return Carbon::instance($fecha)->setTimezone((string) config('app.timezone'))->format($this->getDateFormat());
    }
}
