<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use Database\Seeders\EntidadSeeder;
use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Servicio de identidad institucional.
 *
 * Prueba de integración: cubre TopBar, Footer y Menú desde la capa
 * del servicio real con base de datos en memoria.
 */
final class IdentidadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([EntidadSeeder::class, MenuSeeder::class]);
    }

    public function test_obtener_top_bar_devuelve_recurso_cuando_existe_entidad(): void
    {
        $response = $this->getJson('/api/v1/identidad/top-bar');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'type', 'logo_path', 'url_govco'],
            ]);
    }

    public function test_obtener_footer_devuelve_recurso_cuando_existe_entidad(): void
    {
        $response = $this->getJson('/api/v1/identidad/footer');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'type', 'nombre_autoridad', 'nit'],
            ]);
    }

    public function test_obtener_menu_sin_rol_devuelve_menu_publico(): void
    {
        $response = $this->getJson('/api/v1/identidad/menu');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'uuid', 'type', 'slug', 'etiqueta', 'ruta'],
                ],
            ]);
    }

    public function test_obtener_menu_con_rol_administrador(): void
    {
        $response = $this->getJson('/api/v1/identidad/menu?rol=admin');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_obtener_menu_con_rol_panel(): void
    {
        $response = $this->getJson('/api/v1/identidad/menu?rol=panel');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_obtener_menu_con_rol_sitio(): void
    {
        $response = $this->getJson('/api/v1/identidad/menu?rol=sitio');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }
}
