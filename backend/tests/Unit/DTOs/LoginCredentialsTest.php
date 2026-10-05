<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

use App\DTOs\Auth\LoginCredentials;
use PHPUnit\Framework\TestCase;

/**
 * DTO de credenciales de acceso.
 *
 * Estructura de datos pura: los tests verifican el contrato de construcción
 * y el acceso a sus propiedades.
 */
final class LoginCredentialsTest extends TestCase
{
    public function test_se_construye_con_email_y_password(): void
    {
        $credentials = new LoginCredentials(
            email: 'admin@santamarta.gov.co',
            password: 'Prueba.Entidad.2026',
        );

        $this->assertSame('admin@santamarta.gov.co', $credentials->email);
        $this->assertSame('Prueba.Entidad.2026', $credentials->password);
    }

    public function test_es_inmutable(): void
    {
        $credentials = new LoginCredentials(
            email: 'admin@santamarta.gov.co',
            password: 'Prueba.Entidad.2026',
        );

        $this->assertEquals('admin@santamarta.gov.co', $credentials->email);
        $this->assertEquals('Prueba.Entidad.2026', $credentials->password);
    }
}
