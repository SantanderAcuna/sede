<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

use App\Mail\AvisoRedis;
use App\Services\Monitoreo\LectorRedis;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\Support\LectorRedisFalso;
use Tests\TestCase;

/**
 * DISP-01, señales internas de Redis (ADR-0009): `monitoreo:redis` vigila lo que la sonda de fuera no ve.
 *
 *  1. las claves `sitio:*` pasan del 10 % de `maxmemory` (umbral de José, estimación sin datos de la Entidad);
 *  2. `evicted_keys` crece (daño real: una sesión del panel o una copia expulsadas);
 *  3. el usuario ACL `sitio` recibe rechazos nuevos en `ACL LOG`.
 *
 * Con destinatario avisa por correo; sin él, solo deja el error en el registro. Sin SMTP el correo queda en
 * el registro (`MAIL_MAILER=log`), y eso no se esconde: el comando termina con error igual.
 */
final class VigilarRedisTest extends TestCase
{
    private LectorRedisFalso $lector;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Mail::fake();
        $this->lector = new LectorRedisFalso;
        $this->app->instance(LectorRedis::class, $this->lector);
        config(['monitoreo.correo' => 'alguien@example.test']);
    }

    private function correr(): int
    {
        return Artisan::call('monitoreo:redis');
    }

    public function test_todo_normal_termina_bien_y_no_envia_nada(): void
    {
        $this->assertSame(0, $this->correr());
        Mail::assertNothingSent();
        $this->assertStringContainsString('sin novedades', Artisan::output());
    }

    public function test_sitio_por_encima_del_umbral_avisa(): void
    {
        $this->lector->bytesSitio = (int) (448 * 1024 * 1024 * 0.11);

        $this->assertSame(1, $this->correr());
        Mail::assertSent(AvisoRedis::class, fn (AvisoRedis $m): bool => $m->hasTo('alguien@example.test')
            && str_contains(implode(' ', $m->senales), 'sitio:*'));
    }

    public function test_justo_en_el_umbral_no_avisa(): void
    {
        $this->lector->bytesSitio = (int) (448 * 1024 * 1024 * 0.10);

        $this->assertSame(0, $this->correr());
    }

    public function test_el_umbral_se_lee_de_la_configuracion(): void
    {
        config(['monitoreo.umbral_sitio' => 0.5]);
        $this->lector->bytesSitio = (int) (448 * 1024 * 1024 * 0.4);

        $this->assertSame(0, $this->correr());
    }

    public function test_sin_tope_de_memoria_no_divide_por_cero_ni_avisa_por_porcentaje(): void
    {
        $this->lector->maxima = 0;
        $this->lector->bytesSitio = 10_000_000;

        $this->assertSame(0, $this->correr());
    }

    public function test_la_alarma_de_memoria_no_se_repite_en_cada_corrida(): void
    {
        $this->lector->bytesSitio = (int) (448 * 1024 * 1024 * 0.2);

        $this->assertSame(1, $this->correr());
        $this->assertSame(0, $this->correr(), 'la condición sigue, pero el aviso ya salió: no se repite hasta pasadas 6 h');
        Mail::assertSent(AvisoRedis::class, 1);
    }

    public function test_la_primera_corrida_solo_toma_la_medida_de_expulsadas(): void
    {
        $this->lector->expulsadas = 500;

        $this->assertSame(0, $this->correr());
        Mail::assertNothingSent();
    }

    public function test_expulsadas_que_crecen_avisan_y_cada_aumento_vuelve_a_avisar(): void
    {
        $this->correr();
        $this->lector->expulsadas = 3;
        $this->assertSame(1, $this->correr());

        $this->assertSame(0, $this->correr(), 'sin aumento nuevo no hay aviso');

        $this->lector->expulsadas = 4;
        $this->assertSame(1, $this->correr());
        Mail::assertSent(AvisoRedis::class, 2);
    }

    public function test_si_redis_se_reinicia_y_el_contador_baja_no_avisa_y_se_re_toma(): void
    {
        $this->lector->expulsadas = 50;
        $this->correr();
        $this->lector->expulsadas = 0;

        $this->assertSame(0, $this->correr());

        $this->lector->expulsadas = 1;
        $this->assertSame(1, $this->correr());
    }

    public function test_rechazos_nuevos_del_usuario_sitio_avisan(): void
    {
        $this->correr();
        $this->lector->rechazos = 2;

        $this->assertSame(1, $this->correr());
        $this->assertSame(0, $this->correr(), 'los mismos rechazos no avisan otra vez');
    }

    public function test_si_el_registro_de_rechazos_se_vacia_no_avisa(): void
    {
        $this->lector->rechazos = 5;
        $this->correr();
        $this->lector->rechazos = 0;

        $this->assertSame(0, $this->correr());
    }

    public function test_varias_senales_juntas_van_en_un_solo_correo(): void
    {
        $this->correr();
        $this->lector->expulsadas = 9;
        $this->lector->rechazos = 3;
        $this->lector->bytesSitio = (int) (448 * 1024 * 1024 * 0.3);

        $this->assertSame(1, $this->correr());
        Mail::assertSent(AvisoRedis::class, fn (AvisoRedis $m): bool => count($m->senales) === 3);
        Mail::assertSent(AvisoRedis::class, 1);
    }

    public function test_sin_destinatario_deja_el_error_en_el_registro_y_no_envia_correo(): void
    {
        config(['monitoreo.correo' => '']);
        $this->lector->bytesSitio = (int) (448 * 1024 * 1024 * 0.3);
        $registro = Log::spy();

        $this->assertSame(1, $this->correr());
        Mail::assertNothingSent();
        $registro->shouldHaveReceived('error')->atLeast()->once();
    }

    public function test_si_redis_no_responde_avisa_y_termina_con_error(): void
    {
        $this->lector->caido = true;

        $this->assertSame(1, $this->correr());
        Mail::assertSent(AvisoRedis::class, fn (AvisoRedis $m): bool => str_contains(implode(' ', $m->senales), 'No se pudo leer Redis'));
    }

    public function test_si_el_correo_falla_el_comando_termina_con_error_y_lo_registra(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP caído'));
        $this->lector->bytesSitio = (int) (448 * 1024 * 1024 * 0.3);
        $registro = Log::spy();

        $this->assertSame(1, $this->correr());
        $registro->shouldHaveReceived('error')->atLeast()->twice();
    }

    public function test_esta_en_la_agenda_cada_15_minutos_sin_solaparse(): void
    {
        Artisan::call('schedule:list');

        /** @var Event|null $tarea */
        $tarea = collect(app(Schedule::class)->events())
            ->first(fn (Event $e): bool => str_contains((string) $e->command, 'monitoreo:redis'));

        $this->assertNotNull($tarea, 'monitoreo:redis no está en la agenda');
        $this->assertSame('*/15 * * * *', $tarea->expression);
        $this->assertTrue($tarea->withoutOverlapping);
        // El candado caduca antes de la corrida siguiente. Con `pcntl` se suelta al recibir SIGTERM, pero un
        // SIGKILL (falta de memoria en el contenedor de 256 MB, `docker kill`) lo deja puesto: con los 1440
        // minutos por omisión de Laravel, la vigilancia de Redis quedaría muda un día entero.
        $this->assertSame(10, $tarea->expiresAt);
    }
}
