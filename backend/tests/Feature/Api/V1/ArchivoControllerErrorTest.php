<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Contracts\Services\FilesMediaServiceInterface;
use App\Models\Entidad;
use App\Models\User;
use Database\Seeders\EntidadSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

/**
 * Paths de error en ArchivoController.
 *
 * Cubre los catch (\Throwable) que devuelven 500 cuando el servicio falla.
 */
final class ArchivoControllerErrorTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Entidad $entidad;

    protected function setUp(): void
    {
        parent::setUp();

        $clave = 'Prueba.Entidad.2026';
        config(['superadmin.password' => $clave]);

        $this->seed([
            EntidadSeeder::class,
            PermissionSeeder::class,
            SuperAdminSeeder::class,
        ]);

        $this->superAdmin = User::query()->where('email', config('superadmin.email'))->firstOrFail();
        $this->entidad = Entidad::firstOrFail();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_show_devuelve_500_cuando_servicio_lanza_excepcion(): void
    {
        $mock = Mockery::mock(FilesMediaServiceInterface::class);
        $mock->shouldReceive('findByUuid')
            ->andThrow(new \Exception('Error de conexión'));

        $this->app->instance(FilesMediaServiceInterface::class, $mock);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v1/panel/archivos/00000000-0000-0000-0000-000000000001');

        $response->assertStatus(500)
            ->assertJsonPath('success', false);
    }

    public function test_destroy_devuelve_500_cuando_servicio_lanza_excepcion(): void
    {
        $mock = Mockery::mock(FilesMediaServiceInterface::class);
        $mock->shouldReceive('findByUuid')
            ->andThrow(new \Exception('Error de conexión'));

        $this->app->instance(FilesMediaServiceInterface::class, $mock);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->deleteJson('/api/v1/panel/archivos/00000000-0000-0000-0000-000000000001');

        $response->assertStatus(500)
            ->assertJsonPath('success', false);
    }

    public function test_index_devuelve_422_cuando_collection_es_null(): void
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/v1/panel/entidad/{$this->entidad->uuid}/archivos");

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'El parámetro collection es obligatorio.');
    }

    public function test_index_devuelve_500_cuando_servicio_lanza_excepcion(): void
    {
        $mock = Mockery::mock(FilesMediaServiceInterface::class);
        $mock->shouldReceive('getByCollection')
            ->andThrow(new \Exception('Error de base de datos'));

        $this->app->instance(FilesMediaServiceInterface::class, $mock);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/v1/panel/entidad/{$this->entidad->uuid}/archivos?collection=logos");

        $response->assertStatus(500)
            ->assertJsonPath('success', false);
    }

    public function test_store_devuelve_500_cuando_servicio_lanza_excepcion(): void
    {
        $mock = Mockery::mock(FilesMediaServiceInterface::class);
        $mock->shouldReceive('upload')
            ->andThrow(new \Exception('Error al guardar archivo'));

        $this->app->instance(FilesMediaServiceInterface::class, $mock);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v1/panel/entidad/{$this->entidad->uuid}/archivos", [
                'archivo' => UploadedFile::fake()->create('test.pdf', 1024),
                'collection' => 'logos',
            ]);

        $response->assertStatus(500)
            ->assertJsonPath('success', false);
    }
}
