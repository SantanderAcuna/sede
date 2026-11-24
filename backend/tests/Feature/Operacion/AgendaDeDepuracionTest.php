<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

use DateTimeZone;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * La depuración de consentimientos corre de madrugada, hora de Bogotá (0D, D7).
 *
 * Con `->daily()` y la zona de la aplicación en UTC corría a las 00:00 UTC, es decir a las 19:00
 * de Bogotá: en plena tarde, borrando de una vez todo lo que pasó la retención (3 años de
 * registros) mientras la sede tiene tráfico. Se vio con `schedule:list` en staging.
 */
final class AgendaDeDepuracionTest extends TestCase
{
    private function tarea(string $comando): ?Event
    {
        Artisan::call('schedule:list');

        /** @var Event|null $tarea */
        $tarea = collect(app(Schedule::class)->events())
            ->first(static fn (Event $evento): bool => str_ends_with((string) $evento->command, $comando));

        return $tarea;
    }

    public function test_la_depuracion_corre_a_las_0215_de_bogota_y_sin_pisarse(): void
    {
        $tarea = $this->tarea('consentimientos:depurar');

        $this->assertNotNull($tarea, 'Falta consentimientos:depurar en la agenda.');
        // Después de limpiar (01:00) y de copiar (01:30, que puede tardar), antes del hash (03:00).
        $this->assertSame('15 2 * * *', $tarea->expression);
        $zona = $tarea->timezone;
        $this->assertSame('America/Bogota', $zona instanceof DateTimeZone ? $zona->getName() : $zona);
        $this->assertTrue($tarea->withoutOverlapping, 'Dos depuraciones a la vez se pisarían el borrado.');
    }
}
