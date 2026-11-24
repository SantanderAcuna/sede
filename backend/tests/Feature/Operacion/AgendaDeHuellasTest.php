<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

use DateTimeZone;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * La huella de los archivos se vigila sola (Fase 0B T8; D10c).
 *
 * Hasta D10b la agenda completaba cada noche las huellas que faltaran (`archivos:calcular-hash`).
 * Con D10b la huella es obligatoria y no hay nada que completar: lo que la agenda hace ahora es
 * comparar cada huella con el contenido del disco (`archivos:verificar`), para descubrir un archivo
 * alterado o desaparecido. `archivos:calcular-hash` sigue existiendo (lo usa el despliegue y es el
 * remedio que nombra D10b), pero ya no va en la agenda.
 */
final class AgendaDeHuellasTest extends TestCase
{
    private function tarea(string $comando = 'archivos:verificar'): ?Event
    {
        // `schedule:list` fuerza el arranque de las rutas de consola, donde está la agenda.
        Artisan::call('schedule:list');

        /** @var Event|null $tarea */
        $tarea = collect(app(Schedule::class)->events())
            ->first(static fn (Event $evento): bool => str_ends_with((string) $evento->command, $comando));

        return $tarea;
    }

    public function test_la_agenda_verifica_las_huellas_cada_noche_fuera_de_la_ventana_de_copias(): void
    {
        $tarea = $this->tarea();

        $this->assertNotNull($tarea, 'Falta archivos:verificar en la agenda.');
        // 03:00 de Bogotá: después de limpiar (01:00) y copiar (01:30), antes de vigilar (06:00),
        // para que la lectura de discos no compita con la copia.
        $this->assertSame('0 3 * * *', $tarea->expression);
        $zona = $tarea->timezone;
        $this->assertSame('America/Bogota', $zona instanceof DateTimeZone ? $zona->getName() : $zona);
        $this->assertTrue($tarea->withoutOverlapping, 'Dos ejecuciones a la vez leerían dos veces todos los discos.');
    }

    public function test_el_calculo_de_huellas_ya_no_esta_en_la_agenda(): void
    {
        $this->assertNull($this->tarea('archivos:calcular-hash'), 'Con D10b no queda nada que calcular; verificar lo reemplaza.');
    }
}
