<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Entidad;
use App\Models\User;
use Database\Seeders\EntidadSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRUD de la entidad institucional.
 *
 * La entidad es un singleton: no existe DELETE ni creación. Las pruebas cubren:
 * - GET /api/v1/entidad       → lectura pública
 * - PATCH /api/v1/panel/entidad → actualización autenticada
 */
final class EntidadTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private string $clave;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clave = 'Prueba.Entidad.2026';
        config(['superadmin.password' => $this->clave]);

        $this->seed([
            EntidadSeeder::class,
            PermissionSeeder::class,
            SuperAdminSeeder::class,
        ]);

        $this->superAdmin = User::query()->where('email', config('superadmin.email'))->firstOrFail();
    }

    public function test_la_entidad_se_puede_leer_de_forma_publica(): void
    {
        $respuesta = $this->getJson('/api/v1/entidad');

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', null)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'nombre',
                    'sigla',
                    'nit',
                    'direccion',
                    'municipio',
                    'departamento',
                    'pais',
                    'telefono',
                    'correo_atencion',
                    'redes',
                    'politicas',
                    'datos_por_confirmar',
                ],
                'errors',
            ]);
    }

    public function test_el_super_admin_puede_actualizar_la_entidad(): void
    {
        $datos = [
            'nombre' => 'Alcaldía Modificada SAS',
            'sigla' => 'AM.SAS',
            'telefono' => '+57 601 555 1234',
        ];

        $respuesta = $this->actingAs($this->superAdmin, 'web')
            ->patchJson('/api/v1/panel/entidad', $datos);

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre', 'Alcaldía Modificada SAS')
            ->assertJsonPath('data.sigla', 'AM.SAS')
            ->assertJsonPath('data.telefono', '+57 601 555 1234');

        $this->assertDatabaseHas('entidads', [
            'nombre' => 'Alcaldía Modificada SAS',
            'sigla' => 'AM.SAS',
        ]);
    }

    public function test_la_actualizacion_es_parcial(): void
    {
        $entidad = Entidad::query()->firstOrFail();
        $nombreOriginal = $entidad->nombre;

        $respuesta = $this->actingAs($this->superAdmin, 'web')
            ->patchJson('/api/v1/panel/entidad', ['telefono' => '+57 601 999 0000']);

        $respuesta->assertOk()
            ->assertJsonPath('data.nombre', $nombreOriginal)
            ->assertJsonPath('data.telefono', '+57 601 999 0000');
    }

    public function test_se_pueden_actualizar_redes_sociales(): void
    {
        $redes = [
            ['red' => 'facebook', 'url' => 'https://facebook.com/alcaldia'],
            ['red' => 'x', 'url' => 'https://x.com/alcaldia'],
        ];

        $respuesta = $this->actingAs($this->superAdmin, 'web')
            ->patchJson('/api/v1/panel/entidad', ['redes' => $redes]);

        $respuesta->assertOk()
            ->assertJsonPath('data.redes', $redes);
    }

    public function test_se_pueden_actualizar_politicas(): void
    {
        $politicas = [
            ['slug' => 'terminos-y-condiciones', 'nombre' => 'Términos y Condiciones'],
            ['slug' => 'privacidad', 'nombre' => 'Política de Privacidad'],
        ];

        $respuesta = $this->actingAs($this->superAdmin, 'web')
            ->patchJson('/api/v1/panel/entidad', ['politicas' => $politicas]);

        $respuesta->assertOk()
            ->assertJsonPath('data.politicas', $politicas);
    }

    public function test_un_usuario_no_autenticado_recibe_401(): void
    {
        $respuesta = $this->patchJson('/api/v1/panel/entidad', ['nombre' => 'Test']);

        $respuesta->assertUnauthorized();
    }

    public function test_los_campos_invalidos_retornan_422(): void
    {
        // 'nit' solo valida como string, pero 'correo_atencion' exige email válido
        $respuesta = $this->actingAs($this->superAdmin, 'web')
            ->patchJson('/api/v1/panel/entidad', ['correo_atencion' => 'no-es-email']);

        $respuesta->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['correo_atencion']]);
    }
}
