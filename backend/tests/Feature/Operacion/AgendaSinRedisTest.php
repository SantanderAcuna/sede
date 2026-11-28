<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Tests\Support\AlmacenCacheCaido;
use Tests\TestCase;

/**
 * La agenda sigue en pie con Redis caído (DISP-01, ADR-0009).
 *
 * En staging y producción la caché por omisión es Redis (`CACHE_STORE=redis`). Laravel 13 la usa en dos
 * sitios antes de ejecutar cualquier tarea:
 *
 *  1. `schedule:run` pregunta a la caché si la agenda está en pausa (`illuminate:schedule:paused`), en
 *     cada corrida, haya o no tareas pendientes;
 *  2. cada tarea con `withoutOverlapping()` pide su candado a la caché antes de arrancar.
 *
 * Con Redis caído las dos cosas lanzan, y el resultado medido en la pila local (2026-11-27, Laravel
 * 13.40.0, `sede-redis-1` detenido) fue `schedule:run` con salida 1 por `Redis::connect()`: no corría
 * ninguna tarea. Ni `monitoreo:redis`, que existe justamente para avisar de un Redis enfermo, ni
 * `backup:run`. Estas pruebas sustituyen la caché por omisión por un almacén que lanza siempre
 * ({@see AlmacenCacheCaido}) y exigen que la agenda no dependa de ella.
 */
final class AgendaSinRedisTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // `schedule:list` fuerza el arranque de las rutas de consola, donde se define la agenda. Se
        // hace ANTES de romper la caché porque solo interesa lo que pasa al ejecutar, no al listar.
        Artisan::call('schedule:list');

        // Candados que dejó una corrida anterior interrumpida: sin esto, el primer `assertFalse` de abajo
        // fallaría por una prueba que ya no existe.
        $this->soltarCandados();

        Cache::extend('caido', fn (): Repository => Cache::repository(new AlmacenCacheCaido));
        config([
            'cache.stores.caido' => ['driver' => 'caido'],
            'cache.default' => 'caido',
        ]);

        // El contenedor y la fachada ya resolvieron la caché por omisión (array, en pruebas): se
        // olvidan para que la siguiente resolución entregue el almacén caído, como en el servidor.
        Cache::forgetDriver();
        $this->app->forgetInstance('cache.store');
        Facade::clearResolvedInstance('cache.store');
    }

    /** @return list<Event> */
    private function tareasSinSolapamiento(): array
    {
        return array_values(array_filter(
            app(Schedule::class)->events(),
            static fn (Event $evento): bool => $evento->withoutOverlapping,
        ));
    }

    public function test_la_prueba_rompe_de_verdad_la_cache_por_omision(): void
    {
        // Sin esto, las demás pruebas podrían pasar porque el almacén caído nunca se instaló.
        $this->expectExceptionMessage(AlmacenCacheCaido::MENSAJE);

        app('cache.store')->get('cualquier-clave');
    }

    public function test_schedule_run_no_consulta_la_cache_por_omision(): void
    {
        // 10:07 de Bogotá: ninguna tarea toca (las diarias son de madrugada y la de Redis va cada
        // 15 minutos). Aun así, sin el arreglo, `schedule:run` consulta la pausa en la caché y lanza.
        // Elegir un minuto sin tareas evita lanzar subprocesos reales desde la prueba.
        Carbon::setTestNow(Carbon::parse('2026-11-27 10:07:00', 'America/Bogota'));

        $salida = Artisan::call('schedule:run');

        $this->assertSame(0, $salida);
        $this->assertStringContainsString('No scheduled commands are ready to run', Artisan::output());
    }

    public function test_el_candado_de_cada_tarea_no_vive_en_la_cache_por_omision(): void
    {
        $tareas = $this->tareasSinSolapamiento();

        // La agenda tiene hoy 6 tareas con `withoutOverlapping()`; si un día no hubiera ninguna, la
        // prueba no comprobaría nada, así que se exige al menos la de Redis.
        $this->assertNotEmpty($tareas);
        $comandos = array_map(static fn (Event $evento): string => (string) $evento->command, $tareas);
        $this->assertNotEmpty(array_filter($comandos, static fn (string $c): bool => str_ends_with($c, 'monitoreo:redis')));

        foreach ($tareas as $tarea) {
            // Tomar el candado no debe lanzar ni dar la tarea por ocupada.
            $this->assertFalse($tarea->shouldSkipDueToOverlapping(), "La tarea {$tarea->command} se daría por ocupada.");
            // Y el candado sigue sirviendo: una segunda corrida mientras la primera lo tiene se salta.
            // Sin esto, «no lanzar» se cumpliría también quitando `withoutOverlapping()`, y dos copias o
            // dos verificaciones de discos podrían correr a la vez.
            $this->assertTrue($tarea->shouldSkipDueToOverlapping(), "La tarea {$tarea->command} podría correr dos veces a la vez.");
            // Se suelta enseguida para no dejar candados en disco que bloquearían otra prueba.
            $tarea->mutex->forget($tarea);
        }
    }

    /** Quita de disco los candados de todas las tareas, también si una aserción falló a medias. */
    private function soltarCandados(): void
    {
        foreach ($this->tareasSinSolapamiento() as $tarea) {
            $tarea->mutex->forget($tarea);
        }
    }

    public function test_la_agenda_no_se_pausa_ni_se_interrumpe_desde_la_cache(): void
    {
        // `schedule:pause` y `schedule:interrupt` escriben en la caché; con Redis caído no se puede leer,
        // así que la agenda las ignora a propósito (ADR-0009). Esta prueba fija esa decisión: quien la
        // cambie debe saber que `schedule:pause` dejaría de ser inofensivo. Para detener la agenda se
        // detiene el servicio `scheduler`.
        $this->assertFalse(Schedule::$pausable);
        $this->assertFalse(Schedule::$interruptible);
    }

    protected function tearDown(): void
    {
        $this->soltarCandados();
        Carbon::setTestNow();

        parent::tearDown();
    }
}
