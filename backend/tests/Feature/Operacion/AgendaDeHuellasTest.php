<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

use DateTimeZone;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * La huella de los archivos se completa sola (Fase 0B, T8).
 *
 * El despliegue calcula la huella de lo que ya existía, pero un archivo subido por la versión
 * anterior durante la ventana de despliegue (o con el disco caído en ese momento) queda sin
 * ella. El comando es idempotente —solo toca filas sin huella—, así que la agenda lo repite
 * cada noche y nadie tiene que acordarse de hacerlo a mano.
 */
final class AgendaDeHuellasTest extends TestCase
{
    private function tarea(): ?Event
    {
        // `schedule:list` fuerza el arranque de las rutas de consola, donde está la agenda.
        Artisan::call('schedule:list');

        /** @var Event|null $tarea */
        $tarea = collect(app(Schedule::class)->events())
            ->first(static fn (Event $evento): bool => str_ends_with((string) $evento->command, 'archivos:calcular-hash'));

        return $tarea;
    }

    public function test_la_agenda_completa_las_huellas_cada_noche_fuera_de_la_ventana_de_copias(): void
    {
        $tarea = $this->tarea();

        $this->assertNotNull($tarea, 'Falta archivos:calcular-hash en la agenda.');
        // 03:00 de Bogotá: después de limpiar (01:00) y copiar (01:30), antes de vigilar (06:00),
        // para que la lectura de discos no compita con la copia.
        $this->assertSame('0 3 * * *', $tarea->expression);
        $zona = $tarea->timezone;
        $this->assertSame('America/Bogota', $zona instanceof DateTimeZone ? $zona->getName() : $zona);
        $this->assertTrue($tarea->withoutOverlapping, 'Dos ejecuciones a la vez calcularían dos veces lo mismo.');
    }
}
