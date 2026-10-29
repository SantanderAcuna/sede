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
