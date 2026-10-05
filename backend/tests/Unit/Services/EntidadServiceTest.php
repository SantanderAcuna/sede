<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\Repositories\EntidadRepositoryInterface;
use App\Services\EntidadService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Unidad de EntidadService.
 */
final class EntidadServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_obtener_entidad_lanza_runtime_exception_cuando_no_hay_entidad(): void
    {
        /** @var MockInterface&EntidadRepositoryInterface $mockRepo */
        $mockRepo = Mockery::mock(EntidadRepositoryInterface::class);
        $mockRepo->shouldReceive('findFirst')->andReturn(null);

        $service = new EntidadService($mockRepo);

        $this->expectException(\RuntimeException::class);
        $service->obtenerEntidad();
    }
}
