<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Un candado de `withoutOverlapping()` que sobrevive a su tarea no puede tragarse la corrida siguiente.
 *
 * Laravel suelta el candado al terminar la tarea (y con `pcntl`, al recibir SIGTERM). Un SIGKILL, por
 * ejemplo la falta de memoria del contenedor `scheduler` (256 MB, compose.yaml), lo deja puesto hasta que
 * caduque. Los candados viven en disco (`storage`, un volumen que sobrevive a los reinicios), y la
 * caducidad por omisión es de 1440 minutos: una tarea diaria que arrancó a las 01:30:05 deja un candado
 * que sigue vigente a las 01:30:00 del día siguiente, así que la corrida de mañana se salta sin aviso.
 *
 * Estas pruebas exigen que toda tarea con candado caduque ANTES de su siguiente corrida.
 */
final class AgendaCandadosTest extends TestCase
{
    /** Segundos que tarda `schedule:work` en lanzar una tarea después de empezar el minuto. */
    private const RETRASO_DE_ARRANQUE = 5;

    /** @var list<Event> */
    private array $tareas = [];

    protected function setUp(): void
    {
        parent::setUp();

        // `schedule:list` fuerza el arranque de las rutas de consola, donde se define la agenda.
        Artisan::call('schedule:list');

        $this->tareas = array_values(array_filter(
            app(Schedule::class)->events(),
            static fn (Event $evento): bool => $evento->withoutOverlapping,
        ));
        $this->soltarCandados();
    }

    protected function tearDown(): void
    {
        $this->soltarCandados();
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** Quita los candados de disco que dejó una corrida anterior (o esta, si una aserción falló a medias). */
    private function soltarCandados(): void
    {
        foreach ($this->tareas as $tarea) {
            $tarea->mutex->forget($tarea);
        }
    }

    public function test_hay_tareas_con_candado_que_comprobar(): void
    {
        $this->assertGreaterThanOrEqual(6, count($this->tareas));
    }

    public function test_el_candado_de_cada_tarea_caduca_antes_de_su_siguiente_corrida(): void
    {
        foreach ($this->tareas as $tarea) {
            $primera = $tarea->nextRunDate('2026-11-27 00:00:00')->addSeconds(self::RETRASO_DE_ARRANQUE);
            $siguiente = $tarea->nextRunDate($primera, 0);

            // Candado tomado por una corrida que murió con SIGKILL justo al arrancar.
            Carbon::setTestNow($primera);
            $this->assertFalse($tarea->shouldSkipDueToOverlapping(), "No se pudo tomar el candado de {$tarea->command}.");

            // Llega la hora de la siguiente corrida: el candado muerto no debe frenarla.
            Carbon::setTestNow($siguiente);
            $this->assertFalse(
                $tarea->shouldSkipDueToOverlapping(),
                "El candado de {$tarea->command} sigue vigente a las {$siguiente->toDateTimeString()}: "
                ."la corrida se saltaría. Caduca a los {$tarea->expiresAt} min.",
            );

            $tarea->mutex->forget($tarea);
        }
    }
}
