<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Retención del registro de consentimientos de cookies (T-26). Corre una vez al día
// siempre que el servidor ejecute `schedule:run` cada minuto.
Schedule::command('consentimientos:depurar')->daily();

// Copias de seguridad de la base (docs/copias-de-seguridad.md). Las ejecuta el servicio
// `scheduler`, que es el único proceso con la agenda y con el volumen de copias montado.
//
// El ORDEN importa y las horas no son casuales: primero se LIMPIA (01:00) y después se
// copia (01:30), así la limpieza nunca borra una copia recién hecha para cumplir un
// límite de espacio; y se VIGILA (06:00) cuando la de la noche ya debió terminar.
// America/Bogota no tiene horario de verano, así que no hay una hora que se repita o
// desaparezca.
//
// `withoutOverlapping` evita que una copia lenta se pise con la siguiente; `--only-db`
// deja fuera los archivos hasta que T-12 decida dónde viven.
Schedule::command('backup:clean')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('backup:run --only-db')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('06:00');
