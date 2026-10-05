<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Eloquent;

use App\Models\Tramite;
use App\Repositories\Eloquent\TramiteRepository;
use App\Support\Tramites\FiltrosTramite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unidad de TramiteRepository.
 */
final class TramiteRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TramiteRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TramiteRepository;
    }

    /** @param array<string, mixed> $override */
    private function publicar(array $override = []): Tramite
    {
        return Tramite::factory()->published()->create(array_merge([
            'nombre' => 'Trámite de prueba',
            'slug' => 'tramite-prueba',
            'codigo' => 'T00001',
            'modalidad' => 'parcialmente_en_linea',
            'tiene_costo' => 'gratuito',
            'costo' => null,
            'tiempo_solucion_dias' => 15,
            'canal_inicio' => 'propio',
            'consulta_estado' => '/seguimiento?radicado=…',
            'requisitos' => [['descripcion' => 'Cédula', 'obligatorio' => true]],
            'url_ficha_gov_co' => 'https://www.gov.co/ficha/T00001',
            'categoria_slug' => 'finanzas',
            'publicado_en' => now(),
        ], $override));
    }

    public function test_paginar_sin_filtros_devuelve_todos_los_publicados(): void
    {
        $this->publicar(['slug' => 'tramite-1', 'codigo' => 'T00001']);
        $this->publicar(['slug' => 'tramite-2', 'codigo' => 'T00002']);

        $filtros = new FiltrosTramite(pagina: 1, porPagina: 10, buscar: null, categoria: null, tipo: null);
        $resultado = $this->repository->paginar($filtros);

        $this->assertSame(2, $resultado->total());
    }

    public function test_paginar_filtra_por_tipo(): void
    {
        // Línea 25: where('type', $filtros->tipo)
        $this->publicar(['slug' => 'tramite-1', 'codigo' => 'T00001', 'type' => 'tramites']);
        $this->publicar(['slug' => 'tramite-2', 'codigo' => 'T00002', 'type' => 'opa']);

        $filtros = new FiltrosTramite(pagina: 1, porPagina: 10, buscar: null, categoria: null, tipo: 'tramites');
        $resultado = $this->repository->paginar($filtros);

        $this->assertSame(1, $resultado->total());
        $this->assertNotNull($resultado->first());
        $this->assertSame('tramite-1', $resultado->first()->slug);
    }

    public function test_paginar_filtra_por_categoria(): void
    {
        // Línea 29: where('categoria_slug', $filtros->categoria)
        $this->publicar(['slug' => 'tramite-1', 'codigo' => 'T00001', 'categoria_slug' => 'finanzas']);
        $this->publicar(['slug' => 'tramite-2', 'codigo' => 'T00002', 'categoria_slug' => 'salud']);

        $filtros = new FiltrosTramite(pagina: 1, porPagina: 10, buscar: null, categoria: 'finanzas', tipo: null);
        $resultado = $this->repository->paginar($filtros);

        $this->assertSame(1, $resultado->total());
        $this->assertNotNull($resultado->first());
        $this->assertSame('finanzas', $resultado->first()->categoria_slug);
    }

    public function test_por_slug_devuelve_tramite_publicado(): void
    {
        $tramite = $this->publicar(['slug' => 'mi-slug', 'codigo' => 'T00001']);

        $encontrado = $this->repository->porSlug('mi-slug');

        $this->assertNotNull($encontrado);
        $this->assertSame($tramite->id, $encontrado->id);
    }

    public function test_por_slug_devuelve_null_cuando_no_existe(): void
    {
        $this->assertNull($this->repository->porSlug('no-existe'));
    }

    public function test_por_slug_devuelve_null_cuando_esta_sin_publicar(): void
    {
        $this->publicar(['slug' => 'borrador', 'codigo' => 'T00001', 'publicado_en' => null]);

        $this->assertNull($this->repository->porSlug('borrador'));
    }
}
