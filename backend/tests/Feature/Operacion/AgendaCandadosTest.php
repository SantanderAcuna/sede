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

    /** Minutos que el candado debe dejar libres antes de la corrida siguiente, para absorber un arranque tardío. */
    private const MARGEN_MINUTOS = 2;

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

    public function test_el_candado_sigue_tomado_a_mitad_de_una_corrida_legitima(): void
    {
        // El límite inferior: una caducidad demasiado corta (por ejemplo `withoutOverlapping(1)` en `backup:run`)
        // dejaría arrancar una segunda copia mientras la primera, lenta pero viva, sigue trabajando. Se exige que el
        // candado aguante al menos medio intervalo entre corridas: si una tarea tarda más que eso, ya se pisa con la
        // siguiente y el problema es otro.
        foreach ($this->tareas as $tarea) {
            $primera = $tarea->nextRunDate('2026-11-27 00:00:00')->addSeconds(self::RETRASO_DE_ARRANQUE);
            $intervalo = (int) abs($tarea->nextRunDate($primera, 0)->diffInSeconds($primera));
            $mitad = $primera->copy()->addSeconds(intdiv($intervalo, 2));

            Carbon::setTestNow($primera);
            $this->assertFalse($tarea->shouldSkipDueToOverlapping(), "No se pudo tomar el candado de {$tarea->command}.");

            // A mitad del intervalo la primera corrida sigue viva: una segunda debe saltarse.
            Carbon::setTestNow($mitad);
            $this->assertTrue(
                $tarea->shouldSkipDueToOverlapping(),
                "El candado de {$tarea->command} ya caducó a las {$mitad->toDateTimeString()}, a mitad del intervalo: "
                ."una segunda corrida podría pisar a la primera. Caduca a los {$tarea->expiresAt} min.",
            );

            $tarea->mutex->forget($tarea);
        }
    }

    public function test_el_candado_caduca_con_margen_antes_de_la_siguiente_corrida(): void
    {
        // La prueba anterior supone que la tarea arranca 5 s después del minuto. `schedule:run` lanza las tareas en
        // primer plano una tras otra, así que una tarea que comparte minuto con otra lenta puede arrancar más tarde.
        // El margen exige que el candado de un arranque a tiempo ya esté libre MARGEN_MINUTOS antes de la corrida
        // siguiente, para que un retraso de ese tamaño no se trague la corrida.
        foreach ($this->tareas as $tarea) {
            $primera = $tarea->nextRunDate('2026-11-27 00:00:00')->addSeconds(self::RETRASO_DE_ARRANQUE);
            $conMargen = $tarea->nextRunDate($primera, 0)->subMinutes(self::MARGEN_MINUTOS);

            Carbon::setTestNow($primera);
            $this->assertFalse($tarea->shouldSkipDueToOverlapping(), "No se pudo tomar el candado de {$tarea->command}.");

            Carbon::setTestNow($conMargen);
            $this->assertFalse(
                $tarea->shouldSkipDueToOverlapping(),
                "El candado de {$tarea->command} sigue vigente a las {$conMargen->toDateTimeString()}, "
                .self::MARGEN_MINUTOS." min antes de la corrida siguiente. Caduca a los {$tarea->expiresAt} min.",
            );

            $tarea->mutex->forget($tarea);
        }
    }
}
