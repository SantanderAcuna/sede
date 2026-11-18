<?php

declare(strict_types=1);

namespace Tests\Feature\Esquema;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** La migración de D3 crea los cinco índices, se puede retroceder y repetir. */
final class IndicesDeClavesForaneasMigracionTest extends TestCase
{
    use RefreshDatabase;

    private const INDICES = [
        'noticias_autor_id_index',
        'role_has_permissions_role_id_index',
        'tramite_requisitos_paso_id_index',
        'tramites_categoria_id_index',
        'tramites_dependencia_id_index',
    ];

    /** @return list<string> */
    private function existentes(): array
    {
        return array_values(array_map(
            fn ($f): string => $f->indexname,
            DB::select('select indexname from pg_indexes where schemaname = ? and indexname = any (?)', [
                'public',
                '{'.implode(',', self::INDICES).'}',
            ]),
        ));
    }

    public function test_up_crea_los_cinco_indices(): void
    {
        $this->assertEqualsCanonicalizing(self::INDICES, $this->existentes());
    }

    public function test_down_los_borra_y_up_los_vuelve_a_crear(): void
    {
        $migracion = require database_path('migrations/2026_11_19_200000_indices_de_claves_foraneas.php');

        $migracion->down();
        $this->assertSame([], $this->existentes());

        $migracion->up();
        $this->assertEqualsCanonicalizing(self::INDICES, $this->existentes());

        $migracion->up(); // repetible
        $this->assertEqualsCanonicalizing(self::INDICES, $this->existentes());
    }
}
