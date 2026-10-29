<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Consentimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Registro del consentimiento de cookies (T-26, AC-26.4).
 *
 * Lo que se exige: que guarde categorías, versión y fecha, y que **no guarde
 * nada que identifique a quien consintió**. Lo segundo se prueba por la forma de
 * la tabla, no por lo que hoy hace el controlador: si alguien añade una columna
 * de IP o de agente de usuario, esta prueba falla.
 */
final class ConsentimientosTest extends TestCase
{
    use RefreshDatabase;

    private const RUTA = '/api/v1/consentimientos';

    public function test_guarda_categorias_version_y_fecha_del_servidor(): void
    {
        $respuesta = $this->postJson(self::RUTA, [
            'evento' => 'aceptado',
            'categorias' => ['analitica'],
            'version_politica' => 1,
        ]);

        $respuesta->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.evento', 'aceptado')
            ->assertJsonPath('data.categorias', ['analitica'])
            ->assertJsonPath('data.version_politica', 1);

        $registro = Consentimiento::query()->firstOrFail();
        $this->assertSame(['analitica'], $registro->categorias);
        $this->assertSame(1, $registro->version_politica);
        $this->assertTrue($registro->registrado_en->diffInSeconds(now(), true) < 60);
    }

    public function test_aceptar_sin_categorias_opcionales_es_valido(): void
    {
        $this->postJson(self::RUTA, ['evento' => 'rechazado', 'categorias' => [], 'version_politica' => 1])
            ->assertCreated()
            ->assertJsonPath('data.categorias', []);
    }

    public function test_no_acepta_la_fecha_que_escriba_el_navegador(): void
    {
        $this->postJson(self::RUTA, [
            'evento' => 'aceptado',
            'categorias' => [],
            'version_politica' => 1,
            'registrado_en' => '2001-01-01 00:00:00',
        ])->assertCreated();

        $this->assertGreaterThan(2020, Consentimiento::query()->firstOrFail()->registrado_en->year);
    }

    public function test_la_tabla_no_tiene_ninguna_columna_que_identifique_a_quien_consintio(): void
    {
        $this->assertEqualsCanonicalizing(
            ['id', 'evento', 'categorias', 'version_politica', 'registrado_en'],
            Schema::getColumnListing('consentimientos'),
            'No se guardan IP, agente de usuario ni identificadores: si hace falta otra columna, '
            .'revísese antes con el Oficial de Protección de Datos.'
        );
    }

    public function test_la_peticion_no_deja_la_ip_ni_el_agente_en_el_registro(): void
    {
        $this->withHeaders(['User-Agent' => 'NavegadorDePrueba/1.0'])
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->postJson(self::RUTA, ['evento' => 'aceptado', 'categorias' => [], 'version_politica' => 1])
            ->assertCreated();

        $volcado = json_encode(Consentimiento::query()->firstOrFail()->getAttributes(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('203.0.113.7', $volcado);
        $this->assertStringNotContainsString('NavegadorDePrueba', $volcado);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function cuerposInvalidos(): array
    {
        return [
            'evento desconocido' => [['evento' => 'otro', 'categorias' => [], 'version_politica' => 1], 'evento'],
            'sin evento' => [['categorias' => [], 'version_politica' => 1], 'evento'],
            'categoria desconocida' => [['evento' => 'aceptado', 'categorias' => ['marketing'], 'version_politica' => 1], 'categorias.0'],
            'categoria repetida' => [['evento' => 'aceptado', 'categorias' => ['analitica', 'analitica'], 'version_politica' => 1], 'categorias.0'],
            'sin categorias' => [['evento' => 'aceptado', 'version_politica' => 1], 'categorias'],
            'versión cero' => [['evento' => 'aceptado', 'categorias' => [], 'version_politica' => 0], 'version_politica'],
            'versión no numérica' => [['evento' => 'aceptado', 'categorias' => [], 'version_politica' => 'uno'], 'version_politica'],
            'sin versión' => [['evento' => 'aceptado', 'categorias' => []], 'version_politica'],
        ];
    }

    /**
     * @param  array<string, mixed>  $cuerpo
     */
    #[DataProvider('cuerposInvalidos')]
    public function test_rechaza_los_cuerpos_invalidos_sin_guardar_nada(array $cuerpo, string $campo): void
    {
        $this->postJson(self::RUTA, $cuerpo)
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors([$campo], 'errors');

        $this->assertSame(0, Consentimiento::query()->count());
    }

    public function test_pasado_el_limite_hay_429(): void
    {
        $cuerpo = ['evento' => 'rechazado', 'categorias' => [], 'version_politica' => 1];

        for ($i = 0; $i < 30; $i++) {
            $this->postJson(self::RUTA, $cuerpo)->assertCreated();
        }

        $this->postJson(self::RUTA, $cuerpo)->assertStatus(429);
        $this->assertSame(30, Consentimiento::query()->count());
    }

    public function test_depurar_borra_lo_que_supera_la_retencion_y_conserva_lo_reciente(): void
    {
        $viejo = Consentimiento::query()->create(['evento' => 'aceptado', 'categorias' => [], 'version_politica' => 1]);
        $viejo->forceFill(['registrado_en' => now()->subDays(1100)])->save();
        $reciente = Consentimiento::query()->create(['evento' => 'aceptado', 'categorias' => [], 'version_politica' => 1]);
        $reciente->forceFill(['registrado_en' => now()->subDays(1000)])->save();

        $this->assertSame(0, Artisan::call('consentimientos:depurar', ['--simular' => true]));
        $this->assertSame(2, Consentimiento::query()->count(), 'Simular no borra.');

        $this->assertSame(0, Artisan::call('consentimientos:depurar'));
        $this->assertSame([$reciente->id], Consentimiento::query()->pluck('id')->all());
    }

    public function test_depurar_rechaza_una_retencion_menor_a_un_dia(): void
    {
        $this->assertNotSame(0, Artisan::call('consentimientos:depurar', ['--dias' => 0]));
    }
}
