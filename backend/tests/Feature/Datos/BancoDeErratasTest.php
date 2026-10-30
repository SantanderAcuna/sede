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
 * La tolerancia a erratas de la búsqueda, medida a través de la API sobre el catálogo
 * real (AC-22.3 y AC-22.8; TC-BUS-18, 20 y 24).
 *
 * T-22 se apoya en una hipótesis: que `pg_trgm` basta, con el catálogo que hay, para
 * tolerar erratas sin añadir un servicio de búsqueda. En la primera entrega se midió la
 * consulta; en la segunda se mide **el producto**: cada caso llama a
 * `GET /api/v1/busqueda/sugerencias` y comprueba que el trámite esperado queda entre
 * los tres primeros, con los 124 trámites sembrados desde la fuente oficial.
 *
 * Tres bancos, con papeles distintos:
 *  - `banco-erratas` (30): con él se calibraron los parámetros. Exige 27 de 30 (90 %).
 *  - `banco-negativo` (12): consultas que no deben devolver nada. Exige 12 de 12.
 *  - `banco-validacion` (10 + 5 + 1): escrito después y corrido con los parámetros ya
 *    congelados; mide si la calibración generaliza. Exige 8 de 10.
 *
 * **Límite declarado:** los tres los escribió quien calibró, con el catálogo a la vista;
 * miden lo que es técnicamente posible, no cómo escriben los ciudadanos.
 */
final class BancoDeErratasTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> código SUIT => slug */
    private array $slugs = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TramiteSeeder::class);

        // El banco recorre decenas de consultas seguidas desde una misma IP: el límite
        // de producción (120 por minuto) no es lo que se está midiendo aquí.
        config(['busqueda.limite_por_minuto' => 100000]);

        /** @var array<string, string> $slugs */
        $slugs = Tramite::query()->pluck('slug', 'codigo')->all();
        $this->slugs = $slugs;
    }

    /**
     * @return list<array{consulta: string, esperado: list<string>, errata: string}>
     */
    private function bancoDeErratas(): array
    {
        /** @var list<array{consulta: string, esperado: list<string>, errata: string}> $banco */
        $banco = require __DIR__.'/../../Support/banco-erratas.php';

        return $banco;
    }

    /**
     * @return array{positivos: list<array{consulta: string, esperado: list<string>, errata: string}>, negativos: list<string>, limite_conocido: list<string>}
     */
    private function bancoDeValidacion(): array
    {
        /** @var array{positivos: list<array{consulta: string, esperado: list<string>, errata: string}>, negativos: list<string>, limite_conocido: list<string>} $banco */
        $banco = require __DIR__.'/../../Support/banco-validacion.php';

        return $banco;
    }

    public function test_los_bancos_se_refieren_a_tramites_que_existen(): void
    {
        // Si el sembrador descarta uno de los esperados, el banco mediría contra
        // la nada y el resultado sería un falso rojo (o un falso verde).
        $casos = array_merge($this->bancoDeErratas(), $this->bancoDeValidacion()['positivos']);

        foreach ($casos as $caso) {
            foreach ($caso['esperado'] as $codigo) {
                $this->assertArrayHasKey($codigo, $this->slugs, "El trámite {$codigo} del banco no está en el catálogo sembrado.");
            }
        }
    }

    public function test_los_bancos_tienen_el_tamano_que_prometen_los_criterios(): void
    {
        $this->assertCount(30, $this->bancoDeErratas(), 'AC-22.3 exige un banco de al menos 30 consultas.');
        $this->assertCount(12, $this->bancoNegativo(), 'AC-22.8 exige el banco negativo completo.');
        $this->assertCount(10, $this->bancoDeValidacion()['positivos']);
    }

    public function test_la_busqueda_actual_con_like_no_tolera_ninguna_errata(): void
    {
        // La línea base: lo que hace el filtro `buscar` del catálogo con `busqueda LIKE
        // %q%`. Documenta POR QUÉ existe T-22; los casos de control (sin errata) sí
        // acierta con LIKE, así que se excluyen: lo que se mide es la errata.
        $aciertosConErrata = 0;

        foreach ($this->bancoDeErratas() as $caso) {
            if (str_contains($caso['errata'], 'control') || str_contains($caso['errata'], 'sin errata')) {
                continue;
            }

            $codigos = Tramite::query()
                ->where('busqueda', 'like', '%'.TextoBuscable::normalizar($caso['consulta']).'%')
                ->pluck('codigo')
                ->all();

            if (array_intersect($codigos, $caso['esperado']) !== []) {
                $aciertosConErrata++;
            }
        }

        $this->assertSame(0, $aciertosConErrata, 'El LIKE ya acierta consultas con errata: revisar el banco.');
    }

    public function test_el_banco_de_erratas_pone_el_resultado_esperado_entre_los_tres_primeros_en_27_de_30(): void
    {
        [$aciertos, $fallos] = $this->medir($this->bancoDeErratas());

        $this->assertGreaterThanOrEqual(
            27,
            $aciertos,
            "Aciertos: {$aciertos}/30.\n".implode("\n", $fallos),
        );
    }

    public function test_el_banco_de_validacion_generaliza_8_de_10(): void
    {
        [$aciertos, $fallos] = $this->medir($this->bancoDeValidacion()['positivos']);

        $this->assertGreaterThanOrEqual(
            8,
            $aciertos,
            "Aciertos en validación: {$aciertos}/10.\n".implode("\n", $fallos),
        );
    }

    public function test_el_banco_negativo_no_devuelve_nada_por_ninguna_de_las_dos_operaciones(): void
    {
        $conRuido = [];

        foreach ($this->bancoNegativo() as $consulta) {
            if ($this->sugerencias($consulta) !== []) {
                $conRuido[] = "sugerencias: {$consulta}";
            }

            if ($this->getJson('/api/v1/buscar?q='.rawurlencode($consulta))->json('meta.total') !== 0) {
                $conRuido[] = "buscar: {$consulta}";
            }
        }

        $this->assertSame([], $conRuido, "Consultas que no debían devolver nada:\n".implode("\n", $conRuido));
    }

    public function test_los_negativos_de_validacion_estan_limpios_y_el_falso_positivo_conocido_sigue_documentado(): void
    {
        $validacion = $this->bancoDeValidacion();

        foreach ($validacion['negativos'] as $consulta) {
            $this->assertSame([], $this->sugerencias($consulta), "«{$consulta}» no debía devolver nada.");
        }

        // El límite conocido se fija en las dos direcciones: si deja de ocurrir, la
        // mejora tiene que quedar escrita en la documentación, no pasar desapercibida.
        foreach ($validacion['limite_conocido'] as $consulta) {
            $this->assertNotSame([], $this->sugerencias($consulta), "«{$consulta}» ya no devuelve el falso positivo conocido: actualice docs/busqueda.md.");
        }
    }

    public function test_una_consulta_sin_sentido_no_devuelve_nada(): void
    {
        $this->assertSame([], $this->sugerencias('xyzxyz'));
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
     * @return list<string>
     */
    private function bancoNegativo(): array
    {
        /** @var list<string> $banco */
        $banco = require __DIR__.'/../../Support/banco-negativo.php';

        return $banco;
    }

    /**
     * Los slugs de las tres primeras sugerencias para una consulta.
     *
     * @return list<string>
     */
    private function sugerencias(string $consulta): array
    {
        /** @var list<array{url: string}> $datos */
        $datos = $this->getJson('/api/v1/busqueda/sugerencias?q='.rawurlencode($consulta))
            ->assertOk()
            ->json('data');

        return array_values(array_map(
            fn (array $dato): string => basename($dato['url']),
            $datos,
        ));
    }

    /**
     * @param  list<array{consulta: string, esperado: list<string>, errata: string}>  $banco
     * @return array{0: int, 1: list<string>}
     */
    private function medir(array $banco): array
    {
        $aciertos = 0;
        $fallos = [];

        foreach ($banco as $caso) {
            $primeros = array_slice($this->sugerencias($caso['consulta']), 0, 3);
            $esperados = array_map(fn (string $codigo): string => $this->slugs[$codigo], $caso['esperado']);

            if (array_intersect($primeros, $esperados) !== []) {
                $aciertos++;
            } else {
                $fallos[] = "{$caso['consulta']} → [".implode(', ', $primeros).'] esperaba ['.implode(', ', $esperados).']';
            }
        }

        return [$aciertos, $fallos];
    }
}
