<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Tramites;

use App\Support\Tramites\TiempoEnDias;
use Tests\TestCase;

/**
 * Unidad de TiempoEnDias.
 */
final class TiempoEnDiasTest extends TestCase
{
    public function test_dia_devuelve_cantidad_sin_nota(): void
    {
        $resultado = TiempoEnDias::desde('10 DIA(S) HÁBIL(ES)');

        $this->assertNotNull($resultado);
        $this->assertSame(10, $resultado['dias']);
        $this->assertNull($resultado['nota']);
    }

    public function test_hora_convierte_a_dias(): void
    {
        $resultado = TiempoEnDias::desde('2 HORA(S)');

        $this->assertNotNull($resultado);
        $this->assertSame(1, $resultado['dias']); // 2/8 = 0.25 → ceil = 1
        $this->assertStringContainsString('2 HORA(S)', (string) $resultado['nota']);
    }

    public function test_mes_convierte_a_dias(): void
    {
        $resultado = TiempoEnDias::desde('3 MES(ES)');

        $this->assertNotNull($resultado);
        $this->assertSame(90, $resultado['dias']); // 3 * 30
        $this->assertStringContainsString('3 MES(ES)', (string) $resultado['nota']);
    }

    public function test_nulo_devuelve_null(): void
    {
        $this->assertNull(TiempoEnDias::desde(null));
        $this->assertNull(TiempoEnDias::desde(''));
        $this->assertNull(TiempoEnDias::desde('   '));
    }

    public function test_formato_desconocido_devuelve_null(): void
    {
        // Línea 51: regex no hace match → retorna null
        $this->assertNull(TiempoEnDias::desde('No se entiende'));
        $this->assertNull(TiempoEnDias::desde('10 AÑO(S)'));
        $this->assertNull(TiempoEnDias::desde('días hábiles'));
    }

    public function test_en_dias_formatea_plural(): void
    {
        $this->assertSame('1 día hábil', TiempoEnDias::enDias(1));
        $this->assertSame('5 días hábiles', TiempoEnDias::enDias(5));
    }
}
