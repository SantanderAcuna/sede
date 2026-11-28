<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Contracts\Cache\Store;
use RuntimeException;

/**
 * Almacén de caché que se comporta como Redis caído: toda operación lanza, como lanza phpredis cuando no
 * puede conectar («Redis::connect(): getaddrinfo for redis failed»).
 *
 * Sirve para probar que lo que debe seguir funcionando sin Redis (la agenda, y con ella la vigilancia de
 * Redis y las copias) no depende de la caché por omisión, sin detener un Redis de verdad.
 */
final class AlmacenCacheCaido implements Store
{
    public const MENSAJE = 'Redis no responde (almacén de prueba)';

    /** Lanza siempre: un Redis caído no responde a ninguna orden. */
    private function caer(): never
    {
        throw new RuntimeException(self::MENSAJE);
    }

    public function get($key): mixed
    {
        $this->caer();
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public function many(array $keys): array
    {
        $this->caer();
    }

    public function put($key, $value, $seconds): bool
    {
        $this->caer();
    }

    /** @param array<string, mixed> $values */
    public function putMany(array $values, $seconds): bool
    {
        $this->caer();
    }

    public function increment($key, $value = 1): int|bool
    {
        $this->caer();
    }

    public function decrement($key, $value = 1): int|bool
    {
        $this->caer();
    }

    public function forever($key, $value): bool
    {
        $this->caer();
    }

    public function touch($key, $seconds): bool
    {
        $this->caer();
    }

    public function forget($key): bool
    {
        $this->caer();
    }

    public function flush(): bool
    {
        $this->caer();
    }

    public function getPrefix(): string
    {
        return '';
    }
}
