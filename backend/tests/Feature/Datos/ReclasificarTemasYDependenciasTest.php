<?php

declare(strict_types=1);

namespace Tests\Feature\Datos;

use App\Models\Dependencia;
use App\Models\Tramite;
use App\Models\TramiteCategoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * La migración que corrige los datos ya cargados: arregla lo automático y respeta lo que una
 * persona fijó en el panel.
 */
final class ReclasificarTemasYDependenciasTest extends TestCase
{
    use RefreshDatabase;

    private function correr(): void
    {
        (require base_path('database/migrations/2026_11_15_000000_reclasificar_temas_y_asignar_dependencias.php'))->up();
    }

    private function tema(string $slug): TramiteCategoria
    {
        return TramiteCategoria::query()->firstOrCreate(['slug' => $slug], ['nombre' => $slug]);
    }

    private function tramite(string $nombre, TramiteCategoria $tema, string ...$puntos): Tramite
    {
        $t = Tramite::factory()->create(['nombre' => $nombre, 'categoria_id' => $tema->id]);
        foreach ($puntos as $i => $punto) {
            $t->puntosAtencion()->create(['orden' => $i + 1, 'nombre' => $punto, 'direccion' => 'Calle 14 No. 3-00']);
        }

        return $t;
    }

    public function test_corrige_el_tema_de_lo_que_nadie_edito(): void
    {
        $t = $this->tramite('Registro matrícula(s) de vehículos automotores.', $this->tema('educacion'));

        $this->correr();

        $this->assertSame('transito-y-movilidad', $t->fresh()?->categoria?->slug);
    }

    public function test_no_pisa_el_tema_que_una_persona_fijo(): void
    {
        $t = $this->tramite('Registro matrícula(s) de vehículos automotores.', $this->tema('educacion'));
        DB::table('tramites')->where('id', $t->id)->update(['updated_at' => now()->addMinute()]);

        $this->correr();

        $this->assertSame('educacion', $t->fresh()?->categoria?->slug);
    }

    public function test_asigna_dependencia_solo_con_evidencia_y_no_cambia_la_elegida(): void
    {
        $tema = $this->tema('urbanismo-y-construccion');
        $evidente = $this->tramite('Asignación nomenclatura del predio', $tema, 'Secretaría de Planeación');
        $ambiguo = $this->tramite('Expedición concepto', $tema, 'Secretaría de Planeación', 'Secretaría de Salud');
        $elegido = $this->tramite('Ajuste de áreas', $tema, 'Secretaría de Planeación');
        $hacienda = Dependencia::query()->where('nombre', 'Hacienda')->firstOrFail();
        $elegido->forceFill(['dependencia_id' => $hacienda->id])->save();

        $this->correr();

        $this->assertSame('Planeación', $evidente->fresh()?->dependencia?->nombre);
        $this->assertNull($ambiguo->fresh()?->dependencia_id);
        $this->assertSame($hacienda->id, $elegido->fresh()?->dependencia_id);
    }

    public function test_es_idempotente(): void
    {
        $t = $this->tramite('Asignación nomenclatura del predio', $this->tema('educacion'), 'Secretaría de Planeación');

        $this->correr();
        $primero = $t->fresh()?->only(['categoria_id', 'dependencia_id']);
        $this->correr();

        $this->assertSame($primero, $t->fresh()?->only(['categoria_id', 'dependencia_id']));
        $this->assertSame('urbanismo-y-construccion', $t->fresh()?->categoria?->slug);
    }
}
