<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\Monitoreo\LectorRedis;
use RuntimeException;

/** Lector con valores a mano para probar la lógica de `monitoreo:redis` sin un Redis. */
final class LectorRedisFalso implements LectorRedis
{
    public bool $caido = false;

    public function __construct(
        public int $usada = 2_000_000,
        public int $maxima = 448 * 1024 * 1024,
        public int $expulsadas = 0,
        public int $bytesSitio = 41_546,
        public int $rechazos = 0,
    ) {}

    private function leer(): void
    {
        if ($this->caido) {
            throw new RuntimeException('Connection refused');
        }
    }

    public function memoriaUsada(): int
    {
        $this->leer();

        return $this->usada;
    }

    public function memoriaMaxima(): int
    {
        $this->leer();

        return $this->maxima;
    }

    public function expulsadas(): int
    {
        $this->leer();

        return $this->expulsadas;
    }

    public function bytesClavesSitio(): int
    {
        $this->leer();

        return $this->bytesSitio;
    }

    public function rechazosDelUsuario(string $usuario): int
    {
        $this->leer();

        return $this->rechazos;
    }
}
