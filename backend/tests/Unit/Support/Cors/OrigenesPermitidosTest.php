<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Cors;

use App\Support\Cors\OrigenesPermitidos;
use PHPUnit\Framework\TestCase;

/**
 * Qué orígenes autoriza CORS según el entorno (H-15, AC-14.1).
 *
 * `supports_credentials` está en `true`, así que cada origen de esta lista puede
 * leer respuestas con la sesión del usuario. Lo que la prueba defiende es que los
 * orígenes de desarrollo no viajen a producción.
 */
final class OrigenesPermitidosTest extends TestCase
{
    public function test_en_produccion_solo_los_origenes_declarados(): void
    {
        $origenes = OrigenesPermitidos::para('production', 'https://sede.santamarta.gov.co', null);

        $this->assertSame(['https://sede.santamarta.gov.co'], $origenes);
    }

    public function test_en_produccion_no_hay_localhost_ni_0_0_0_0(): void
    {
        $origenes = OrigenesPermitidos::para('production', 'https://a.example, https://b.example', 'https://c.example');

        foreach ($origenes as $origen) {
            $this->assertStringNotContainsString('localhost', $origen);
            $this->assertStringNotContainsString('127.0.0.1', $origen);
            $this->assertStringNotContainsString('0.0.0.0', $origen);
        }
        $this->assertSame(['https://a.example', 'https://b.example', 'https://c.example'], $origenes);
    }

    public function test_un_entorno_con_otro_nombre_tampoco_recibe_los_de_desarrollo(): void
    {
        $this->assertSame([], OrigenesPermitidos::para('staging', null, null));
    }

    public function test_en_local_y_testing_si_se_anaden_los_de_desarrollo(): void
    {
        foreach (['local', 'testing'] as $entorno) {
            $origenes = OrigenesPermitidos::para($entorno, null, null);

            $this->assertContains('http://localhost:5190', $origenes);
            $this->assertContains('http://localhost:3001', $origenes);
        }
    }

    public function test_el_comodin_y_los_vacios_se_descartan_y_no_hay_duplicados(): void
    {
        $origenes = OrigenesPermitidos::para('production', '*, ,https://a.example/,https://a.example', 'https://a.example');

        $this->assertSame(['https://a.example'], $origenes);
    }
}
