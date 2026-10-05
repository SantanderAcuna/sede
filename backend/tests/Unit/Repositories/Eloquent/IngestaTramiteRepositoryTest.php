<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Eloquent;

use App\Models\IngestaTramite;
use App\Repositories\Eloquent\IngestaTramiteRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unidad de IngestaTramiteRepository.
 */
final class IngestaTramiteRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private IngestaTramiteRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new IngestaTramiteRepository;
    }

    public function test_intentos_devuelve_cero_cuando_no_existe(): void
    {
        // Línea 66: value() en consulta vacía devuelve null → cast a int = 0
        $this->assertSame(0, $this->repository->intentos('NO-EXISTE'));
    }

    public function test_intentos_devuelve_cantidad_de_intentos(): void
    {
        IngestaTramite::create([
            'codigo' => 'T12345',
            'intentos' => 3,
            'estado' => 'pendiente',
            'terminado_en' => null,
        ]);

        $this->assertSame(3, $this->repository->intentos('T12345'));
    }

    public function test_olvidar_elimina_todos_los_registros(): void
    {
        IngestaTramite::create([
            'codigo' => 'T1',
            'intentos' => 1,
            'estado' => 'pendiente',
            'terminado_en' => null,
        ]);
        IngestaTramite::create([
            'codigo' => 'T2',
            'intentos' => 2,
            'estado' => 'pendiente',
            'terminado_en' => null,
        ]);

        $eliminados = $this->repository->olvidar();

        $this->assertSame(2, $eliminados);
        $this->assertSame(0, IngestaTramite::query()->count());
    }
}
