<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Eloquent;

use App\Models\Tramite;
use App\Models\TramiteCategoria;
use App\Repositories\Eloquent\TramiteRepository;
use App\Support\Tramites\FiltrosTramite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unidad de TramiteRepository sobre el modelo relacional: filtros facetados, facetas y ficha.
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
    private function publicar(array $override = [], ?string $categoriaSlug = null): Tramite
    {
        $categoriaId = $categoriaSlug === null
            ? null
            : TramiteCategoria::query()->firstOrCreate(['slug' => $categoriaSlug], ['nombre' => ucfirst($categoriaSlug)])->id;

        return Tramite::factory()->published()->create(array_merge([
            'nombre' => 'Trámite de prueba',
            'modalidad' => 'parcialmente_en_linea',
            'tiene_costo' => 'gratuito',
            'costo' => null,
            'tiempo_solucion_dias' => 15,
            'canal_inicio' => 'propio',
            'consulta_estado' => '/seguimiento?radicado=…',
            'categoria_id' => $categoriaId,
        ], $override));
    }

    private function filtros(
        ?string $tipo = null,
        ?string $buscar = null,
        ?string $categoria = null,
        ?string $modalidad = null,
        ?string $costo = null,
        ?int $dependencia = null,
        ?string $solicitante = null,
    ): FiltrosTramite {
        return new FiltrosTramite(
            pagina: 1, porPagina: 10, buscar: $buscar, categoria: $categoria, tipo: $tipo,
            modalidad: $modalidad, costo: $costo, dependencia: $dependencia, solicitante: $solicitante,
        );
    }

    public function test_paginar_del_grupo_tramites_devuelve_los_publicados(): void
    {
        $this->publicar(['slug' => 'tramite-1']);
        $this->publicar(['slug' => 'tramite-2']);

        $resultado = $this->repository->paginar($this->filtros(tipo: 'tramites'));

        $this->assertSame(2, $resultado->total());
    }

    public function test_paginar_filtra_por_tipo(): void
    {
        $this->publicar(['slug' => 'tramite-1', 'type' => 'tramites']);
        $this->publicar(['slug' => 'opa-2', 'type' => 'opa']);

        $resultado = $this->repository->paginar($this->filtros(tipo: 'tramites'));

        $this->assertSame(1, $resultado->total());
        $this->assertNotNull($resultado->first());
        $this->assertSame('tramite-1', $resultado->first()->slug);
    }

    public function test_paginar_filtra_por_categoria(): void
    {
        $this->publicar(['slug' => 'tramite-1'], categoriaSlug: 'finanzas');
        $this->publicar(['slug' => 'tramite-2'], categoriaSlug: 'salud');

        $resultado = $this->repository->paginar($this->filtros(tipo: 'tramites', categoria: 'finanzas'));

        $this->assertSame(1, $resultado->total());
        $this->assertNotNull($resultado->first());
        $this->assertSame('finanzas', $resultado->first()->categoria?->slug);
    }

    public function test_paginar_filtra_por_modalidad_y_costo(): void
    {
        $this->publicar(['slug' => 'en-linea', 'modalidad' => 'en_linea', 'tiene_costo' => 'gratuito']);
        $this->publicar(['slug' => 'presencial', 'modalidad' => 'presencial', 'tiene_costo' => 'con_costo']);

        $this->assertSame(1, $this->repository->paginar($this->filtros(tipo: 'tramites', modalidad: 'en_linea'))->total());
        $this->assertSame(1, $this->repository->paginar($this->filtros(tipo: 'tramites', costo: 'con_costo'))->total());
    }

    public function test_facetas_cuentan_por_grupo_modalidad_y_costo(): void
    {
        $this->publicar(['slug' => 't1', 'modalidad' => 'en_linea', 'tiene_costo' => 'gratuito']);
        $this->publicar(['slug' => 't2', 'modalidad' => 'presencial', 'tiene_costo' => 'con_costo']);
        $this->publicar(['slug' => 'o1', 'type' => 'opa']);

        $facetas = $this->repository->facetas($this->filtros(tipo: 'tramites'));

        // El tipo cuenta sobre todos los grupos; el resto, sobre el grupo activo (tramites: 2).
        $tipos = collect($facetas['tipos'])->pluck('total', 'valor')->all();
        $this->assertSame(2, $tipos['tramites']);
        $this->assertSame(1, $tipos['opa']);
        $this->assertCount(2, $facetas['modalidades']);
        $this->assertCount(2, $facetas['costos']);
    }

    public function test_por_slug_devuelve_la_ficha_con_relaciones(): void
    {
        $tramite = $this->publicar(['slug' => 'mi-slug']);

        $encontrado = $this->repository->porSlug('mi-slug');

        $this->assertNotNull($encontrado);
        $this->assertSame($tramite->id, $encontrado->id);
        $this->assertTrue($encontrado->relationLoaded('requisitos'));
        $this->assertTrue($encontrado->requisitos->isNotEmpty());
    }

    public function test_por_slug_devuelve_null_cuando_no_existe(): void
    {
        $this->assertNull($this->repository->porSlug('no-existe'));
    }

    public function test_por_slug_devuelve_null_cuando_esta_sin_publicar(): void
    {
        $this->publicar(['slug' => 'borrador', 'publicado_en' => null]);

        $this->assertNull($this->repository->porSlug('borrador'));
    }
}
