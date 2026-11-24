<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Acceso\CatalogoPermisos;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Renombrar un rol devolvía 500 en staging («No se guardó. Server Error»): el servicio cargaba el rol
 * con el conteo de usuarios (`users_count`, que no es una columna) y al guardar el cambio de nombre
 * Eloquent intentaba escribirlo en la tabla.
 */
final class RolesRenombrarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    public function test_renombrar_un_rol_funciona_y_deja_huella(): void
    {
        $gestor = User::factory()->createOne();
        $gestor->givePermissionTo(CatalogoPermisos::de('roles'));
        $rol = Role::create(['name' => 'zz uno', 'guard_name' => 'web']);
        $rol->givePermissionTo('noticias.ver');

        $this->actingAs($gestor)->patchJson("/api/v1/panel/roles/{$rol->id}", ['name' => 'zz dos'])
            ->assertOk()
            ->assertJsonPath('data.name', 'zz dos')
            ->assertJsonPath('data.permisos', ['noticias.ver']);

        $this->assertSame('zz dos', Role::query()->findOrFail($rol->id)->name);
        $this->assertTrue(AuditLog::query()->where('accion', 'rol_actualizado')->where('recurso_id', $rol->id)->exists());
    }

    public function test_renombrar_y_cambiar_permisos_a_la_vez_funciona(): void
    {
        $gestor = User::factory()->createOne();
        $gestor->givePermissionTo(CatalogoPermisos::de('roles'));
        $gestor->givePermissionTo(CatalogoPermisos::de('noticias'));
        $rol = Role::create(['name' => 'zz tres', 'guard_name' => 'web']);

        $this->actingAs($gestor)->patchJson("/api/v1/panel/roles/{$rol->id}", ['name' => 'zz cuatro', 'permisos' => ['noticias.ver']])
            ->assertOk()
            ->assertJsonPath('data.name', 'zz cuatro');

        $this->assertSame(['noticias.ver'], Role::query()->findOrFail($rol->id)->permissions->pluck('name')->all());
    }
}
