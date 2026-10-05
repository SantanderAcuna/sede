<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Eloquent;

use App\Models\Entidad;
use App\Repositories\Eloquent\EntidadRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unidad de EntidadRepository.
 */
final class EntidadRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EntidadRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EntidadRepository;
    }

    public function test_find_first_devuelve_entidad_cuando_existe(): void
    {
        Entidad::factory()->create(['nombre' => 'Mi Entidad']);

        $entidad = $this->repository->findFirst();

        $this->assertNotNull($entidad);
        $this->assertSame('Mi Entidad', $entidad->nombre);
    }

    public function test_find_first_devuelve_null_cuando_no_existe(): void
    {
        $entidad = $this->repository->findFirst();

        $this->assertNull($entidad);
    }

    public function test_update_actualiza_entidad_existente(): void
    {
        Entidad::factory()->create(['nombre' => 'Viejo']);

        $actualizada = $this->repository->update(['nombre' => 'Nuevo']);

        $this->assertSame('Nuevo', $actualizada->nombre);
    }

    public function test_update_lanza_excepcion_cuando_no_existe_entidad(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No existe la entidad configurada.');

        $this->repository->update(['nombre' => 'Nuevo']);
    }
}
