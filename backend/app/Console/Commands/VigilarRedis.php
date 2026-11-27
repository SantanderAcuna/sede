<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\AvisoRedis;
use App\Services\Monitoreo\LectorRedis;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * DISP-01, señales internas de Redis (ADR-0009). Solo LEE Redis; no corrige nada.
 *
 *  1. `sitio:*` ocupa más del umbral de `maxmemory` (se avisa como mucho una vez cada 6 h: es una condición, no un evento);
 *  2. `evicted_keys` creció desde la corrida anterior (la primera solo toma la medida; si el contador baja, Redis se reinició);
 *  3. el usuario ACL `sitio` sumó rechazos en `ACL LOG` desde la corrida anterior (la primera solo toma la medida).
 *
 * Con destinatario (`MONITOREO_NOTIFICACION_CORREO`) envía UN correo con todas las señales; siempre deja el
 * error en el registro y termina con error, para que se note aunque no haya SMTP. Las medidas anteriores
 * viven en la caché: si se pierden, se vuelve a tomar la medida y no se inventa un aumento.
 */
final class VigilarRedis extends Command
{
    private const CLAVE_EXPULSADAS = 'monitoreo:redis:expulsadas';

    private const CLAVE_RECHAZOS = 'monitoreo:redis:rechazos';

    private const CLAVE_MEMORIA = 'monitoreo:redis:aviso-memoria';

    protected $signature = 'monitoreo:redis';

    protected $description = 'Vigila Redis: memoria de sitio:*, claves expulsadas y rechazos ACL del usuario sitio.';

    public function handle(LectorRedis $lector): int
    {
        try {
            $senales = $this->senales($lector);
        } catch (Throwable $e) {
            $senales = ['No se pudo leer Redis: '.$e->getMessage()];
        }

        if ($senales === []) {
            $this->info('Redis: sin novedades.');

            return self::SUCCESS;
        }

        foreach ($senales as $senal) {
            $this->error($senal);
            Log::error('monitoreo:redis '.$senal);
        }

        $this->avisar($senales);

        return self::FAILURE;
    }

    /** @return list<string> */
    private function senales(LectorRedis $lector): array
    {
        $senales = [];

        $maxima = $lector->memoriaMaxima();
        $sitio = $lector->bytesClavesSitio();
        $umbral = (float) config('monitoreo.umbral_sitio');
        if ($maxima > 0 && $sitio > $maxima * $umbral && $this->primeraVez(self::CLAVE_MEMORIA)) {
            $senales[] = sprintf(
                'Las claves sitio:* ocupan %s (%.1f %% de maxmemory %s); el umbral es %.0f %%.',
                $this->mib($sitio), $sitio / $maxima * 100, $this->mib($maxima), $umbral * 100,
            );
        }

        $expulsadas = $lector->expulsadas();
        $antes = Cache::get(self::CLAVE_EXPULSADAS);
        Cache::forever(self::CLAVE_EXPULSADAS, $expulsadas);
        if (is_int($antes) && $expulsadas > $antes) {
            $senales[] = sprintf('evicted_keys subió de %d a %d: Redis expulsó claves (sesiones, caché o copias del sitio).', $antes, $expulsadas);
        }

        $usuario = (string) config('monitoreo.usuario_acl');
        $rechazos = $lector->rechazosDelUsuario($usuario);
        $visto = Cache::get(self::CLAVE_RECHAZOS);
        Cache::forever(self::CLAVE_RECHAZOS, $rechazos);
        if (is_int($visto) && $rechazos > $visto) {
            $senales[] = sprintf('ACL LOG: el usuario %s suma %d rechazo(s) nuevo(s) (de %d a %d).', $usuario, $rechazos - $visto, $visto, $rechazos);
        }

        return $senales;
    }

    /** @param list<string> $senales */
    private function avisar(array $senales): void
    {
        $correo = (string) config('monitoreo.correo');
        if ($correo === '') {
            return;
        }

        try {
            Mail::to($correo)->send(new AvisoRedis($senales));
        } catch (Throwable $e) {
            $this->error('No se pudo enviar el aviso por correo: '.$e->getMessage());
            Log::error('monitoreo:redis no se pudo enviar el correo: '.$e->getMessage());
        }
    }

    /** true la primera vez en 6 h; si la caché falla, avisa (mejor repetir que callar). */
    private function primeraVez(string $clave): bool
    {
        try {
            return Cache::add($clave, 1, now()->addHours(6));
        } catch (Throwable) {
            return true;
        }
    }

    private function mib(int $bytes): string
    {
        return number_format($bytes / 1048576, 1, ',', '.').' MiB';
    }
}
