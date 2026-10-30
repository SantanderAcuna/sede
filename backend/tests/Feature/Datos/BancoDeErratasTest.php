<?php

declare(strict_types=1);

namespace Tests\Feature\Datos;

use App\Models\Tramite;
use App\Support\Busqueda\TextoBuscable;
use Database\Seeders\TramiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use stdClass;
use Tests\TestCase;

/**
 * La premisa de la búsqueda, medida sobre el catálogo real (AC-22.3, fase 1).
 *
 * T-22 se apoya en una hipótesis: que `pg_trgm` basta, con el catálogo que hay, para
 * tolerar erratas sin añadir un servicio de búsqueda. Esta prueba la comprueba en
 * el motor de CI —el mismo PostgreSQL que corre en despliegue— con los 124 trámites
 * sembrados desde la fuente oficial. **No prueba la API** (todavía no existe): prueba
 * la consulta que la API va a ejecutar. La fase siguiente la mueve a un repositorio
 * y añade las pruebas HTTP sobre el mismo banco.
 *
 * El umbral de aciertos (8 de 10) es el de AC-22.3 y está marcado en la auditoría
 * como «se revisa tras medir».
 */
final class BancoDeErratasTest extends TestCase
{
    use RefreshDatabase;

    /** Pertinencia mínima para entrar en los resultados. Se calibra en la fase 2. */
    private const UMBRAL = 0.4;

    /** @var list<array{consulta: string, esperado: list<string>, errata: string}> */
    private array $banco;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TramiteSeeder::class);

        /** @var list<array{consulta: string, esperado: list<string>, errata: string}> $banco */
        $banco = require __DIR__.'/../../Support/banco-erratas.php';
        $this->banco = $banco;
    }

    public function test_el_banco_se_refiere_a_tramites_que_existen(): void
    {
        // Si el sembrador descarta uno de los esperados, el banco mediría contra
        // la nada y el resultado sería un falso rojo (o un falso verde).
        foreach ($this->banco as $caso) {
            foreach ($caso['esperado'] as $codigo) {
                $this->assertTrue(
                    Tramite::query()->where('codigo', $codigo)->exists(),
                    "El trámite {$codigo} del banco no está en el catálogo sembrado.",
                );
            }
        }
    }

    public function test_la_busqueda_actual_con_like_no_tolera_ninguna_errata(): void
    {
        // La línea base: lo que hace hoy `TramiteRepository` con `busqueda LIKE %q%`.
        // Documenta POR QUÉ existe T-22; si alguna vez acierta, el banco ya no sirve.
        $aciertos = 0;

        foreach ($this->banco as $caso) {
            $codigos = Tramite::query()
                ->where('busqueda', 'like', '%'.TextoBuscable::normalizar($caso['consulta']).'%')
                ->pluck('codigo')
                ->all();

            if (array_intersect($codigos, $caso['esperado']) !== []) {
                $aciertos++;
            }
        }

        $this->assertSame(0, $aciertos, 'El LIKE ya acierta consultas con errata: revisar el banco.');
    }

    public function test_los_trigramas_ponen_el_resultado_esperado_entre_los_tres_primeros_en_8_de_10(): void
    {
        $aciertos = 0;
        $fallos = [];

        foreach ($this->banco as $caso) {
            $codigos = $this->primerosTres(TextoBuscable::normalizar($caso['consulta']));

            if (array_intersect($codigos, $caso['esperado']) !== []) {
                $aciertos++;
            } else {
                $fallos[] = "{$caso['consulta']} → [".implode(', ', $codigos).'] esperaba ['.implode(', ', $caso['esperado']).']';
            }
        }

        $this->assertGreaterThanOrEqual(
            8,
            $aciertos,
            "Aciertos: {$aciertos}/10.\n".implode("\n", $fallos),
        );
    }

    public function test_una_consulta_sin_sentido_no_devuelve_nada(): void
    {
        $this->assertSame([], $this->primerosTres('xyzxyz'));
    }

    public function test_el_operador_de_palabra_usa_el_indice_gin_con_un_umbral_por_transaccion(): void
    {
        // El umbral por defecto de pg_trgm (0.6) descartaría «licensia» (0.556): hay
        // que fijarlo por transacción. Y la forma con operador (`<%`) es la única que
        // puede apoyarse en el índice; la función `word_similarity()` recorre la tabla.
        DB::statement("select set_config('pg_trgm.word_similarity_threshold', '0.5', true)");
        DB::statement("select set_config('enable_seqscan', 'off', true)");

        $plan = collect(DB::select(
            "explain select codigo from tramites where cast('licensia' as text) <% busqueda_titulo",
        ))->map(fn (stdClass $fila): string => (string) ((array) $fila)['QUERY PLAN'])->implode("\n");

        $this->assertStringContainsString('tramites_busqueda_titulo_trgm', $plan, "El plan no usa el índice:\n{$plan}");

        $conErrata = DB::select(
            "select codigo from tramites where cast('licensia' as text) <% busqueda_titulo",
        );

        $this->assertNotEmpty($conErrata, '«licensia» no encontró ninguna licencia con umbral 0.5.');
    }

    /**
     * La consulta que la API ejecutará, en su primera forma: el mejor entre la
     * similitud con el título y la del texto completo (éste pesa 0.6), con un
     * pequeño premio a lo que empieza o contiene el término, y desempate por la
     * similitud de la cadena entera —que favorece al título más corto.
     *
     * @return list<string>
     */
    private function primerosTres(string $q): array
    {
        $filas = DB::select(
            'select codigo from tramites
             where greatest(word_similarity(cast(:q as text), busqueda_titulo),
                            0.6 * word_similarity(cast(:q as text), busqueda)) >= :umbral
             order by greatest(word_similarity(cast(:q as text), busqueda_titulo),
                               0.6 * word_similarity(cast(:q as text), busqueda))
                      + case when busqueda_titulo like cast(:q as text) || \'%\' then 0.25
                             when busqueda_titulo like \'%\' || cast(:q as text) || \'%\' then 0.15
                             else 0 end desc,
                      similarity(cast(:q as text), busqueda_titulo) desc,
                      nombre
             limit 3',
            ['q' => $q, 'umbral' => self::UMBRAL],
        );

        return array_map(fn (stdClass $fila): string => (string) $fila->codigo, $filas);
    }
}
