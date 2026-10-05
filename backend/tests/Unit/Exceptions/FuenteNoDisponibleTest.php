<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\FuenteNoDisponible;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Exceptions\FuenteNoDisponible
 */
final class FuenteNoDisponibleTest extends TestCase
{
    public function test_por_respuesta_genera_mensaje_correcto(): void
    {
        $ex = FuenteNoDisponible::porRespuesta('T2621', '/ficha/T2621', 503, 3);

        $this->assertSame('La fuente no respondió para el trámite T2621: /ficha/T2621 devolvió HTTP 503 tras 3 intentos.', $ex->getMessage());
        $this->assertInstanceOf(FuenteNoDisponible::class, $ex);
    }

    public function test_por_conexion_genera_mensaje_correcto(): void
    {
        $ex = FuenteNoDisponible::porConexion('T2621', '/ficha/T2621', 'Connection refused', 5);

        $this->assertSame('No se pudo alcanzar la fuente para el trámite T2621: /ficha/T2621 — Connection refused (tras 5 intentos).', $ex->getMessage());
        $this->assertInstanceOf(FuenteNoDisponible::class, $ex);
    }

    public function test_por_cuerpo_ilegible_genera_mensaje_correcto(): void
    {
        $ex = FuenteNoDisponible::porCuerpoIlegible('T2621', '/ficha/T2621');

        $this->assertSame('La fuente respondió algo que no es JSON para el trámite T2621: /ficha/T2621.', $ex->getMessage());
        $this->assertInstanceOf(FuenteNoDisponible::class, $ex);
    }
}
