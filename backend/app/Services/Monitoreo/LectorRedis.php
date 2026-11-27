<?php

declare(strict_types=1);

namespace App\Services\Monitoreo;

/** Lo que `monitoreo:redis` necesita saber de Redis. Solo lecturas. Lanza una excepción si Redis no responde. */
interface LectorRedis
{
    public function memoriaUsada(): int;

    /** `maxmemory` en bytes; 0 si no hay tope. */
    public function memoriaMaxima(): int;

    /** `evicted_keys` de `INFO stats` (acumulado desde el arranque de Redis). */
    public function expulsadas(): int;

    /** Suma de `MEMORY USAGE` de las claves `sitio:*` (SCAN, sin bloquear Redis). */
    public function bytesClavesSitio(): int;

    /** Suma de `count` de las entradas de `ACL LOG` del usuario (acumulado; el registro guarda las últimas 128). */
    public function rechazosDelUsuario(string $usuario): int;
}
