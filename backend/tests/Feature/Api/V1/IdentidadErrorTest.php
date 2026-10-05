<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Casos de error del servicio de identidad y archivos.
 *
 * Cubre los paths donde la entidad no está configurada en la base de datos,
 * o donde el modelo referenciado no existe.
 */
final class IdentidadErrorTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $clave = 'Prueba.Entidad.2026';
        config(['superadmin.password' => $clave]);

        $this->seed([
            PermissionSeeder::class,
            SuperAdminSeeder::class,
        ]);

        $this->superAdmin = User::query()->where('email', config('superadmin.email'))->firstOrFail();
    }

    public function test_top_bar_devuelve_404_cuando_no_hay_entidad(): void
    {
        // DB está vacía (RefreshDatabase) y no se ha sembrado EntidadSeeder
        $response = $this->getJson('/api/v1/identidad/top-bar');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_footer_devuelve_404_cuando_no_hay_entidad(): void
    {
        $response = $this->getJson('/api/v1/identidad/footer');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_listar_archivos_devuelve_404_cuando_entidad_no_existe(): void
    {
        // UUID de entidad que no existe
        $uuidInexistente = '00000000-0000-0000-0000-000000000000';

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/v1/panel/entidad/{$uuidInexistente}/archivos?collection=logos");

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_subir_archivo_devuelve_404_cuando_entidad_no_existe(): void
    {
        $uuidInexistente = '00000000-0000-0000-0000-000000000000';

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v1/panel/entidad/{$uuidInexistente}/archivos", [
                'archivo' => UploadedFile::fake()->create('test.pdf', 1024),
                'collection' => 'logos',
            ]);

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
