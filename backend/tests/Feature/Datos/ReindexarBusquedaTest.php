<?php

declare(strict_types=1);

namespace Tests\Feature\Datos;

use App\Models\Politica;
use App\Models\Tramite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * `php artisan busqueda:reindexar` (T-22; TC-BUS-22).
 *
 * El texto buscable es una columna derivada. El comando existe para el día en que
 * cambie la normalización o en que alguien toque filas con SQL directo: lo que se
 * protege aquí es que repare lo desfasado, que sea idempotente y que no toque nada más.
 */
final class ReindexarBusquedaTest extends TestCase
{
    use RefreshDatabase;

    public function test_repara_el_texto_desfasado_de_tramites_y_politicas(): void
    {
        $tramite = $this->tramite();
        $politica = $this->politica();

        // SQL directo: los modelos habrían recalculado el texto al guardar.
        DB::table('tramites')->where('id', $tramite->id)->update(['busqueda' => 'texto viejo', 'busqueda_titulo' => 'titulo viejo']);
        DB::table('politicas')->where('id', $politica->id)->update(['busqueda' => 'texto viejo', 'busqueda_titulo' => 'titulo viejo']);

        $this->artisan('busqueda:reindexar')
            ->expectsOutputToContain('trámites 1 de 1, políticas 1 de 1')
            ->assertSuccessful();

        $tramite->refresh();
        $politica->refresh();

        $this->assertSame('certificado de residencia', $tramite->busqueda_titulo);
        $this->assertSame('certificado de residencia constancia de domicilio', $tramite->busqueda);
        $this->assertSame('uso de cookies', $politica->busqueda_titulo);
        $this->assertSame('uso de cookies duracion un ano, renovable.', $politica->busqueda);
    }

    public function test_es_idempotente_una_segunda_ejecucion_no_cambia_nada(): void
    {
        $tramite = $this->tramite();
        $this->politica();
        DB::table('tramites')->where('id', $tramite->id)->update(['busqueda' => 'texto viejo']);

        $this->artisan('busqueda:reindexar')->expectsOutputToContain('trámites 1 de 1')->assertSuccessful();
        $this->artisan('busqueda:reindexar')->expectsOutputToContain('trámites 0 de 1, políticas 0 de 1')->assertSuccessful();
    }

    public function test_no_toca_las_otras_columnas_ni_la_fecha_de_actualizacion(): void
    {
        $tramite = $this->tramite();
        DB::table('tramites')->where('id', $tramite->id)->update([
            'busqueda' => 'texto viejo',
            'updated_at' => '2020-01-01 00:00:00',
        ]);

        $this->artisan('busqueda:reindexar')->assertSuccessful();

        $despues = DB::table('tramites')->where('id', $tramite->id)->first();

        $this->assertNotNull($despues);
        $this->assertSame('Certificado de residencia', $despues->nombre);
        $this->assertSame('Constancia de domicilio', $despues->resumen);
        $this->assertStringStartsWith('2020-01-01', (string) $despues->updated_at);
    }

    public function test_simular_cuenta_sin_escribir(): void
    {
        $tramite = $this->tramite();
        DB::table('tramites')->where('id', $tramite->id)->update(['busqueda' => 'texto viejo']);

        $this->artisan('busqueda:reindexar --simular')
            ->expectsOutputToContain('trámites 1 de 1')
            ->assertSuccessful();

        $this->assertSame('texto viejo', DB::table('tramites')->where('id', $tramite->id)->value('busqueda'));
    }

    public function test_un_catalogo_vacio_no_es_un_error(): void
    {
        $this->artisan('busqueda:reindexar')
            ->expectsOutputToContain('trámites 0 de 0, políticas 0 de 0')
            ->assertSuccessful();
    }

    private function tramite(): Tramite
    {
        return Tramite::create([
            'codigo' => 'T00001',
            'slug' => 'certificado-de-residencia',
            'nombre' => 'Certificado de residencia',
            'resumen' => 'Constancia de domicilio',
            'modalidad' => 'parcialmente_en_linea',
            'tiene_costo' => 'gratuito',
            'tiempo_solucion_dias' => 15,
            'canal_inicio' => 'propio',
            'consulta_estado' => '/seguimiento?radicado=…',
            'requisitos' => [['descripcion' => 'Cédula de ciudadanía', 'obligatorio' => true]],
            'url_ficha_gov_co' => 'https://www.gov.co/ficha-tramites-y-servicios/T00001',
            'procedencia_fuente' => 'SUIT — Función Pública (entidad 0043)',
            'procedencia_url' => 'https://www.funcionpublica.gov.co/suit',
            'procedencia_obtenido_en' => '2026-09-30',
            'publicado_en' => now(),
        ]);
    }

    private function politica(): Politica
    {
        return Politica::query()->create([
            'slug' => 'uso-de-cookies',
            'nombre' => 'Uso de cookies',
            'secciones' => [
                ['id' => 'duracion', 'titulo' => 'Duración', 'parrafos' => ['Un año, renovable.']],
            ],
        ]);
    }
}
