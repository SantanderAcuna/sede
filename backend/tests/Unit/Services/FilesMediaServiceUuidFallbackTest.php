<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\FilesMediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests para coverage de FilesMediaService.
 *
 * @covers \App\Services\FilesMediaService
 */
final class FilesMediaServiceUuidFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_get_by_collection_con_solo_uuid_cuando_id_es_null(): void
    {
        // Un objeto sin id pero con uuid (como un modelo que aún no se guarda)
        $objeto = new class
        {
            public string $uuid = 'test-uuid-1234';

            public ?int $id = null;
        };

        $service = new FilesMediaService;
        $resultado = $service->getByCollection($objeto, 'logos');

        $this->assertCount(0, $resultado);
    }

    public function test_get_model_id_usa_property_exists_cuando_no_hay_get_attribute(): void
    {
        // Un objeto stdClass con propiedad id pero sin getAttribute
        $objeto = new \stdClass;
        $objeto->id = 42;

        $service = new FilesMediaService;
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getModelId');
        $method->setAccessible(true);

        $resultado = $method->invoke($service, $objeto);

        $this->assertSame(42, $resultado);
    }

    public function test_get_model_uuid_usa_property_exists_cuando_no_hay_get_attribute(): void
    {
        // Un objeto stdClass con propiedad uuid pero sin getAttribute
        $objeto = new \stdClass;
        $objeto->uuid = 'uuid-desde-property';

        $service = new FilesMediaService;
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getModelUuid');
        $method->setAccessible(true);

        $resultado = $method->invoke($service, $objeto);

        $this->assertSame('uuid-desde-property', $resultado);
    }

    public function test_get_model_uuid_devuelve_null_cuando_no_tiene_uuid_ni_get_attribute(): void
    {
        // Un objeto sin uuid y sin getAttribute
        $objeto = new \stdClass;
        $objeto->nombre = 'sin uuid';

        $service = new FilesMediaService;
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getModelUuid');
        $method->setAccessible(true);

        $resultado = $method->invoke($service, $objeto);

        $this->assertNull($resultado);
    }

    public function test_get_model_uuid_devuelve_null_cuando_uuid_no_es_string(): void
    {
        // Un objeto con uuid como int (no string)
        $objeto = new class
        {
            public int $uuid = 12345;

            public function getAttribute(string $key): mixed
            {
                if ($key === 'uuid') {
                    return 12345; // int, no string
                }

                return null;
            }
        };

        $service = new FilesMediaService;
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getModelUuid');
        $method->setAccessible(true);

        $resultado = $method->invoke($service, $objeto);

        $this->assertNull($resultado);
    }

    public function test_get_model_id_devuelve_null_cuando_id_no_es_numerico(): void
    {
        $objeto = new class
        {
            public string $id = 'no-numerico';

            public function getAttribute(string $key): mixed
            {
                if ($key === 'id') {
                    return 'no-numerico';
                }

                return null;
            }
        };

        $service = new FilesMediaService;
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getModelId');
        $method->setAccessible(true);

        $resultado = $method->invoke($service, $objeto);

        $this->assertNull($resultado);
    }

    public function test_get_model_id_devuelve_null_cuando_no_tiene_id_ni_get_attribute(): void
    {
        // Objeto sin getAttribute y sin propiedad id
        $objeto = new \stdClass;
        $objeto->nombre = 'test';

        $service = new FilesMediaService;
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getModelId');
        $method->setAccessible(true);

        $resultado = $method->invoke($service, $objeto);

        $this->assertNull($resultado);
    }
}
