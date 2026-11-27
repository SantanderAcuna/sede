<?php

declare(strict_types=1);

namespace App\Services\Monitoreo;

use Illuminate\Support\Facades\Redis;
use RuntimeException;

/**
 * Lee de Redis con comandos CRUDOS (`rawCommand`): el prefijo de claves de Laravel no se aplica y se ven
 * las claves `sitio:*`, que escribe el sitio sin ese prefijo. Solo comandos de lectura (INFO, SCAN, MEMORY USAGE, ACL LOG).
 */
final class LectorRedisReal implements LectorRedis
{
    public function memoriaUsada(): int
    {
        return (int) ($this->info('memory')['used_memory'] ?? 0);
    }

    public function memoriaMaxima(): int
    {
        return (int) ($this->info('memory')['maxmemory'] ?? 0);
    }

    public function expulsadas(): int
    {
        return (int) ($this->info('stats')['evicted_keys'] ?? 0);
    }

    public function bytesClavesSitio(): int
    {
        $total = 0;
        $cursor = '0';

        do {
            $respuesta = $this->cliente()->rawCommand('SCAN', $cursor, 'MATCH', 'sitio:*', 'COUNT', '500');
            if (! is_array($respuesta) || ! isset($respuesta[0], $respuesta[1]) || ! is_array($respuesta[1])) {
                throw new RuntimeException('Respuesta inesperada de SCAN.');
            }
            $cursor = (string) $respuesta[0];

            foreach ($respuesta[1] as $clave) {
                $total += (int) $this->cliente()->rawCommand('MEMORY', 'USAGE', (string) $clave);
            }
        } while ($cursor !== '0');

        return $total;
    }

    public function rechazosDelUsuario(string $usuario): int
    {
        $entradas = $this->cliente()->rawCommand('ACL', 'LOG');
        if (! is_array($entradas)) {
            throw new RuntimeException('Respuesta inesperada de ACL LOG.');
        }

        $suma = 0;
        foreach ($entradas as $entrada) {
            if (! is_array($entrada)) {
                continue;
            }
            // RESP2 las entrega como lista plana [campo, valor, campo, valor...].
            $campos = [];
            for ($i = 0; $i + 1 < count($entrada); $i += 2) {
                $campos[(string) $entrada[$i]] = $entrada[$i + 1];
            }
            if (($campos['username'] ?? null) === $usuario) {
                $suma += (int) ($campos['count'] ?? 1);
            }
        }

        return $suma;
    }

    /** @return array<string, string> */
    private function info(string $seccion): array
    {
        $texto = $this->cliente()->rawCommand('INFO', $seccion);
        if (! is_string($texto)) {
            throw new RuntimeException("Respuesta inesperada de INFO {$seccion}.");
        }

        $datos = [];
        foreach (preg_split('/\r?\n/', $texto) ?: [] as $linea) {
            if ($linea === '' || $linea[0] === '#' || ! str_contains($linea, ':')) {
                continue;
            }
            [$k, $v] = explode(':', $linea, 2);
            $datos[$k] = $v;
        }

        return $datos;
    }

    private function cliente(): \Redis
    {
        $cliente = Redis::connection('default')->client();
        if (! $cliente instanceof \Redis) {
            throw new RuntimeException('monitoreo:redis necesita el cliente phpredis (REDIS_CLIENT=phpredis).');
        }

        return $cliente;
    }
}
