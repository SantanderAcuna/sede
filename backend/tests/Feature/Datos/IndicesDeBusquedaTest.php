<?php

declare(strict_types=1);

namespace Tests\Feature\Datos;

use App\Models\Politica;
use App\Models\Tramite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * La capa de datos de la búsqueda (T-22, fase 1).
 *
 * Lo que protege: que la extensión y los cuatro índices existan **con el tipo
 * correcto** —un índice B-tree sobre `busqueda` existiría y no serviría de nada
 * para la similitud—, y que el texto buscable se mantenga solo en cada escritura,
 * porque un índice derivado que se desfasa hace fallar la búsqueda justo en lo
 * que se acaba de corregir.
 */
final class IndicesDeBusquedaTest extends TestCase
{
    use RefreshDatabase;

    /** Indice => [tabla, columna]. */
    private const INDICES = [
        'tramites_busqueda_trgm' => ['tramites', 'busqueda'],
        'tramites_busqueda_titulo_trgm' => ['tramites', 'busqueda_titulo'],
        'politicas_busqueda_trgm' => ['politicas', 'busqueda'],
        'politicas_busqueda_titulo_trgm' => ['politicas', 'busqueda_titulo'],
    ];

    public function test_la_extension_pg_trgm_esta_instalada(): void
    {
        $this->assertSame(
            1,
            (int) DB::scalar("select count(*) from pg_extension where extname = 'pg_trgm'"),
            'Falta la extensión pg_trgm: sin ella no hay similitud por trigramas.',
        );
    }

    public function test_cada_indice_es_gin_con_trigramas_sobre_su_columna(): void
    {
        foreach (self::INDICES as $indice => [$tabla, $columna]) {
            $definicion = (string) DB::scalar(
                'select indexdef from pg_indexes where tablename = ? and indexname = ?',
                [$tabla, $indice],
            );

            $this->assertNotSame('', $definicion, "Falta el índice {$indice}.");
            $this->assertStringContainsString('USING gin', $definicion, "{$indice} no es GIN.");
            $this->assertStringContainsString("{$columna} gin_trgm_ops", $definicion, "{$indice} no indexa {$columna} con trigramas.");
        }
    }

    public function test_el_tramite_mantiene_su_texto_buscable_al_guardarse_y_al_corregirse(): void
    {
        $tramite = Tramite::factory()->create([
            'nombre' => 'Expedición certificado(s)  de Residencia',
            'resumen' => 'Constancia de Domicilio',
        ]);

        $this->assertSame('expedicion certificado(s)  de residencia', $tramite->busqueda_titulo);
        $this->assertSame('expedicion certificado(s)  de residencia constancia de domicilio', $tramite->busqueda);

        // La corrección desde el panel no debe dejar el índice con el nombre viejo.
        $tramite->update(['nombre' => 'Certificado de Vecindad']);
        $tramite->refresh();

        $this->assertSame('certificado de vecindad', $tramite->busqueda_titulo);
        $this->assertStringStartsWith('certificado de vecindad', $tramite->busqueda);
    }

    public function test_la_politica_mantiene_su_texto_buscable_con_el_texto_de_sus_apartados(): void
    {
        $politica = Politica::query()->create([
            'slug' => 'uso-de-cookies',
            'nombre' => 'Uso de Cookies',
            'secciones' => [
                ['id' => 'duracion', 'titulo' => 'Duración', 'parrafos' => ['Un año, renovable.']],
            ],
        ]);

        $this->assertSame('uso de cookies', $politica->busqueda_titulo);
        $this->assertSame('uso de cookies duracion un ano, renovable.', $politica->busqueda);

        $politica->update(['secciones' => [
            ['id' => 'duracion', 'titulo' => 'Vigencia', 'parrafos' => ['Seis meses.']],
        ]]);
        $politica->refresh();

        $this->assertSame('uso de cookies vigencia seis meses.', $politica->busqueda);
    }

    public function test_el_texto_buscable_no_se_puede_rellenar_a_mano(): void
    {
        // No está en $fillable: se calcula siempre desde el nombre y el texto.
        $politica = Politica::query()->create([
            'slug' => 'uso-de-cookies',
            'nombre' => 'Uso de Cookies',
            'secciones' => [],
            'busqueda' => 'texto inventado',
        ]);

        $this->assertSame('uso de cookies', $politica->busqueda);
    }
}
