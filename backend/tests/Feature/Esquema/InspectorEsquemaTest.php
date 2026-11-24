<?php

declare(strict_types=1);

namespace Tests\Feature\Esquema;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\InspectorEsquema;
use Tests\TestCase;

/**
 * El detector de la guarda del esquema (0D, D1) se prueba con una tabla que
 * trae a propósito cada defecto: si el detector no los ve, la guarda sería
 * un semáforo que siempre da verde.
 */
final class InspectorEsquemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('create table defectuosa_padre (id bigserial primary key)');
        DB::statement(<<<'SQL'
            create table defectuosa (
                id bigserial primary key,
                padre_id bigint not null references defectuosa_padre (id),
                duenio_type varchar(255) not null,
                duenio_id bigint not null,
                datos json null,
                escrita_en timestamp(0) without time zone null,
                estado varchar(20) not null,
                a int not null, b int not null, c int not null,
                unique (a, b, c)
            )
        SQL);
    }

    /** @return list<string> */
    private function hallazgos(string $categoria): array
    {
        return array_values(array_filter(
            InspectorEsquema::hallazgos(),
            fn (string $h): bool => str_starts_with($h, $categoria.'|defectuosa'),
        ));
    }

    public function test_detecta_la_asociacion_polimorfica(): void
    {
        $this->assertSame(['polimorfica|defectuosa.duenio'], $this->hallazgos('polimorfica'));
    }

    public function test_detecta_la_columna_json(): void
    {
        $this->assertSame(['json|defectuosa.datos'], $this->hallazgos('json'));
    }

    public function test_detecta_la_fecha_sin_zona(): void
    {
        $this->assertSame(['fecha_sin_zona|defectuosa.escrita_en'], $this->hallazgos('fecha_sin_zona'));
    }

    public function test_detecta_la_clave_foranea_sin_indice(): void
    {
        $this->assertSame(['fk_sin_indice|defectuosa.padre_id'], $this->hallazgos('fk_sin_indice'));
    }

    public function test_detecta_la_enumeracion_sin_check(): void
    {
        $this->assertSame(['enum_sin_check|defectuosa.estado'], $this->hallazgos('enum_sin_check'));
    }

    public function test_detecta_la_clave_candidata_de_tres_columnas(): void
    {
        $this->assertSame(['clave_compuesta|defectuosa(a,b,c)'], $this->hallazgos('clave_compuesta'));
    }

    public function test_lo_corregido_deja_de_detectarse(): void
    {
        DB::statement('create index on defectuosa (padre_id)');
        DB::statement("alter table defectuosa add check (estado in ('a','b'))");
        DB::statement('alter table defectuosa alter column escrita_en type timestamptz');

        $this->assertSame([], $this->hallazgos('fk_sin_indice'));
        $this->assertSame([], $this->hallazgos('enum_sin_check'));
        $this->assertSame([], $this->hallazgos('fecha_sin_zona'));
    }

    public function test_una_fk_cubierta_por_un_indice_compuesto_que_la_encabeza_no_se_marca(): void
    {
        DB::statement('create index on defectuosa (padre_id, a)');

        $this->assertSame([], $this->hallazgos('fk_sin_indice'));
    }
}
