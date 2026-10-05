<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Api;

use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

/**
 * Métodos estáticos de ApiResponse.
 *
 * Cubre los métodos que no se invocan desde ningún controlador (created, forbidden)
 * y que por tanto no se alcanzan desde los tests de integración.
 */
final class ApiResponseTest extends TestCase
{
    public function test_created_devuelve_201_con_data_y_mensaje_por_defecto(): void
    {
        $response = ApiResponse::created(['id' => 1, 'nombre' => 'Test']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertTrue($content['success']);
        $this->assertSame('Recurso creado.', $content['message']);
        $this->assertSame(['id' => 1, 'nombre' => 'Test'], $content['data']);
        $this->assertNull($content['errors']);
    }

    public function test_created_devuelve_201_con_mensaje_personalizado(): void
    {
        $response = ApiResponse::created(['id' => 1], 'Entidad creada correctamente.');

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertSame('Entidad creada correctamente.', $content['message']);
    }

    public function test_created_sin_data_devuelve_null_en_data(): void
    {
        $response = ApiResponse::created();

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertNull($content['data']);
    }

    public function test_forbidden_devuelve_403(): void
    {
        $response = ApiResponse::forbidden('No tienes permiso.');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(403, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertFalse($content['success']);
        $this->assertSame('No tienes permiso.', $content['message']);
    }

    public function test_forbidden_devuelve_mensaje_por_defecto(): void
    {
        $response = ApiResponse::forbidden();

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertSame('No autorizado.', $content['message']);
    }
}
