<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Contracts\Services\FilesMediaServiceInterface;
use App\Models\Entidad;
use App\Models\FileMedia;
use App\Models\User;
use Database\Seeders\EntidadSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Sistema de archivos subidos (FilesMedia).
 *
 * Cubre el modelo FileMedia y FilesMediaService a través de la API.
 */
final class FileMediaFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->seed([
            EntidadSeeder::class,
            PermissionSeeder::class,
            SuperAdminSeeder::class,
        ]);

        $this->superAdmin = User::query()->where('email', config('superadmin.email'))->firstOrFail();
    }

    public function test_obtener_un_archivo_por_uuid(): void
    {
        $archivo = FileMedia::factory()->create([
            'original_filename' => 'acta.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
            'collection' => 'documentos',
            'disk' => 'public',
            'path' => 'file_media/Entidad/documentos/acta.pdf',
        ]);

        $respuesta = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/v1/panel/archivos/{$archivo->uuid}");

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.uuid', $archivo->uuid)
            ->assertJsonPath('data.original_filename', 'acta.pdf');
    }

    public function test_obtener_archivo_inexistente_devuelve_404(): void
    {
        $uuid = (string) Str::uuid();

        $respuesta = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/v1/panel/archivos/{$uuid}");

        $respuesta->assertNotFound();
    }

    public function test_listar_archivos_por_coleccion(): void
    {
        $entidad = Entidad::firstOrFail();

        FileMedia::factory()->count(3)->create([
            'model_type' => Entidad::class,
            'model_uuid' => $entidad->uuid,
            'model_id' => $entidad->id,
            'collection' => 'logos',
            'disk' => 'public',
        ]);

        FileMedia::factory()->count(2)->create([
            'model_type' => Entidad::class,
            'model_uuid' => $entidad->uuid,
            'model_id' => $entidad->id,
            'collection' => 'firmas',
            'disk' => 'public',
        ]);

        $respuesta = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/v1/panel/entidad/{$entidad->uuid}/archivos?collection=logos");

        $respuesta->assertOk()
            ->assertJsonPath('success', true);

        /** @var list<array<string, mixed>> $data */
        $data = $respuesta->json('data');
        $this->assertCount(3, $data);
    }

    public function test_eliminar_un_archivo(): void
    {
        $archivo = FileMedia::factory()->create([
            'disk' => 'public',
            'path' => 'file_media/Entidad/logos/test.png',
        ]);
        Storage::disk('public')->put($archivo->path, 'contenido');

        $respuesta = $this->actingAs($this->superAdmin, 'sanctum')
            ->deleteJson("/api/v1/panel/archivos/{$archivo->uuid}");

        $respuesta->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('file_media', ['uuid' => $archivo->uuid]);
    }

    public function test_eliminar_archivo_inexistente_devuelve_404(): void
    {
        $uuid = (string) Str::uuid();

        $respuesta = $this->actingAs($this->superAdmin, 'sanctum')
            ->deleteJson("/api/v1/panel/archivos/{$uuid}");

        $respuesta->assertNotFound();
    }

    public function test_subir_un_archivo_a_una_entidad(): void
    {
        $entidad = Entidad::firstOrFail();
        $archivo = UploadedFile::fake()->create('logo.png', 1024, 'image/png');

        $respuesta = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v1/panel/entidad/{$entidad->uuid}/archivos", [
                'archivo' => $archivo,
                'collection' => 'logos',
            ]);

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => ['uuid', 'filename', 'original_filename', 'collection'],
            ]);

        $this->assertDatabaseHas('file_media', [
            'model_uuid' => $entidad->uuid,
            'collection' => 'logos',
        ]);
    }

    public function test_un_usuario_no_autenticado_no_puede_acceder_a_archivos(): void
    {
        $uuid = (string) Str::uuid();

        $respuesta = $this->getJson("/api/v1/panel/archivos/{$uuid}");
        $respuesta->assertUnauthorized();
    }

    public function test_archivo_pertenece_a_entidad_a_traves_del_model_polimorfico(): void
    {
        $entidad = Entidad::firstOrFail();

        $archivo = FileMedia::factory()->create([
            'model_type' => Entidad::class,
            'model_id' => $entidad->id,
            'model_uuid' => $entidad->uuid,
            'disk' => 'public',
            'path' => 'test/polymorphic.txt',
        ]);

        // Línea 76: morphTo() — la relación polimórfica funciona
        $this->assertInstanceOf(Entidad::class, $archivo->model);
        $this->assertSame($entidad->id, $archivo->model->id);
    }

    public function test_delete_by_collection_elimina_todos_los_archivos_de_la_coleccion(): void
    {
        $entidad = Entidad::firstOrFail();

        // Crear archivos en dos colecciones usando model_uuid (no model_id)
        // Los archivos de logos tienen collection='logos'
        FileMedia::factory()->count(2)->create([
            'model_type' => Entidad::class,
            'model_uuid' => $entidad->uuid,
            'model_id' => $entidad->id,
            'collection' => 'logos',
            'disk' => 'public',
        ]);
        FileMedia::factory()->count(3)->create([
            'model_type' => Entidad::class,
            'model_uuid' => $entidad->uuid,
            'model_id' => $entidad->id,
            'collection' => 'firmas',
            'disk' => 'public',
        ]);

        $this->assertSame(5, FileMedia::query()->count());

        // Llamar deleteByCollection para la colección 'logos'
        $service = app(FilesMediaServiceInterface::class);
        $service->deleteByCollection($entidad, 'logos');

        // Quedan los de 'firmas' nomas
        $this->assertSame(3, FileMedia::query()->count());
        $this->assertSame(0, FileMedia::query()->where('collection', 'logos')->count());
    }
}
