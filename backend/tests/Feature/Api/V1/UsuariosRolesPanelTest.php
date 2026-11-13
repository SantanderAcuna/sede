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
 * Usuarios, roles y permisos desde el panel (Fase 5).
 *
 * Es la superficie de mayor riesgo del panel: lo que se protege es que nadie pueda darse
 * (ni dar a otro) más poder del que tiene, que no se pueda dejar el sistema sin un
 * súper administrador activo, que cada cambio deje huella y que el servidor —no la
 * interfaz— haga cumplir todo.
 */
final class UsuariosRolesPanelTest extends TestCase
{
    use RefreshDatabase;

    private const CLAVE = 'Clave-Segura-2026!';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function superAdmin(string $correo = 'super@example.com'): User
    {
        $usuario = User::factory()->create(['name' => 'Titular Prueba', 'email' => $correo, 'estado' => 'activo']);
        $usuario->assignRole(Role::findOrCreate('super-admin', 'web'));

        return $usuario;
    }

    /** Administrador: gestiona usuarios y noticias, nada más. */
    private function admin(): User
    {
        $usuario = User::factory()->create(['estado' => 'activo']);
        $usuario->givePermissionTo(CatalogoPermisos::de('usuarios', 'roles', 'noticias'));

        return $usuario;
    }

    private function recargar(User $usuario): User
    {
        return User::query()->findOrFail($usuario->id);
    }

    private function rolEditor(): Role
    {
        /** @var Role $rol */
        $rol = Role::findOrCreate('editor', 'web');
        $rol->syncPermissions(CatalogoPermisos::de('noticias'));

        return $rol;
    }

    public function test_sin_sesion_es_401_y_sin_permiso_es_403_en_todo_el_modulo(): void
    {
        foreach (['usuarios', 'roles', 'permisos'] as $recurso) {
            $this->getJson("/api/v1/panel/{$recurso}")->assertUnauthorized();
        }

        $sinPermiso = User::factory()->create();
        $sinPermiso->givePermissionTo(CatalogoPermisos::de('noticias'));

        foreach (['usuarios', 'roles', 'permisos'] as $recurso) {
            $this->actingAs($sinPermiso)->getJson("/api/v1/panel/{$recurso}")->assertForbidden();
        }
        $this->actingAs($sinPermiso)->postJson('/api/v1/panel/usuarios', [])->assertForbidden();
    }

    public function test_el_listado_filtra_por_texto_estado_y_rol_y_nunca_expone_la_clave(): void
    {
        $this->rolEditor();
        $ana = User::factory()->create(['name' => 'Ana Pérez', 'email' => 'ana@example.com', 'estado' => 'activo']);
        $ana->assignRole('editor');
        User::factory()->create(['name' => 'Beto Ruiz', 'email' => 'beto@example.com', 'estado' => 'inactivo']);

        $actor = $this->superAdmin();

        $porTexto = $this->actingAs($actor)->getJson('/api/v1/panel/usuarios?q=ana')->assertOk();
        $this->assertSame(['ana@example.com'], array_column($porTexto->json('data'), 'email'));

        $porEstado = $this->actingAs($actor)->getJson('/api/v1/panel/usuarios?estado=inactivo')->assertOk();
        $this->assertSame(['beto@example.com'], array_column($porEstado->json('data'), 'email'));

        $porRol = $this->actingAs($actor)->getJson('/api/v1/panel/usuarios?rol=editor')->assertOk();
        $this->assertSame(['ana@example.com'], array_column($porRol->json('data'), 'email'));
        $this->assertSame(['editor'], $porRol->json('data.0.roles'));

        $this->assertStringNotContainsString('password', (string) $porRol->getContent());
        $this->assertStringNotContainsString('remember_token', (string) $porRol->getContent());
    }

    public function test_crea_un_usuario_con_clave_cifrada_roles_y_deja_huella(): void
    {
        $this->rolEditor();

        $respuesta = $this->actingAs($this->admin())->postJson('/api/v1/panel/usuarios', [
            'name' => 'Carla Gómez',
            'email' => 'carla@example.com',
            'password' => self::CLAVE,
            'roles' => ['editor'],
        ])->assertCreated()->assertJsonPath('data.estado', 'activo')->assertJsonPath('data.roles.0', 'editor');

        $this->assertStringNotContainsString(self::CLAVE, (string) $respuesta->getContent());

        $guardado = User::query()->where('email', 'carla@example.com')->firstOrFail();
        $this->assertNotSame(self::CLAVE, $guardado->password);
        $this->assertTrue(password_verify(self::CLAVE, $guardado->password));
        $this->assertTrue($guardado->hasRole('editor'));

        $huella = AuditLog::query()->where('accion', 'usuario_creado')->first();
        $this->assertNotNull($huella);
        $this->assertStringNotContainsString(self::CLAVE, json_encode($huella->cambios, JSON_THROW_ON_ERROR));
    }

    public function test_valida_correo_unico_y_clave_de_al_menos_doce_caracteres(): void
    {
        User::factory()->create(['email' => 'repetido@example.com']);

        $this->actingAs($this->admin())->postJson('/api/v1/panel/usuarios', [
            'name' => 'X',
            'email' => 'repetido@example.com',
            'password' => 'corta',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password'], 'errors');
    }

    public function test_un_admin_no_puede_dar_el_rol_super_admin_ni_un_rol_con_permisos_que_no_tiene(): void
    {
        Role::findOrCreate('super-admin', 'web');
        $rolPoderoso = Role::findOrCreate('poderoso', 'web');
        $rolPoderoso->syncPermissions(CatalogoPermisos::de('noticias', 'entidad'));

        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/api/v1/panel/usuarios', [
            'name' => 'Intruso', 'email' => 'i1@example.com', 'password' => self::CLAVE, 'roles' => ['super-admin'],
        ])->assertForbidden();

        $this->actingAs($admin)->postJson('/api/v1/panel/usuarios', [
            'name' => 'Intruso', 'email' => 'i2@example.com', 'password' => self::CLAVE, 'roles' => ['poderoso'],
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'i1@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'i2@example.com']);
    }

    public function test_un_admin_no_puede_tocar_a_un_super_administrador(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($this->admin())->patchJson("/api/v1/panel/usuarios/{$super->uuid}", [
            'estado' => 'inactivo',
        ])->assertForbidden();

        $this->assertSame('activo', $this->recargar($super)->estado);
    }

    public function test_nadie_cambia_sus_propios_roles_ni_su_estado(): void
    {
        $this->rolEditor();
        $admin = $this->admin();

        $this->actingAs($admin)->patchJson("/api/v1/panel/usuarios/{$admin->uuid}", [
            'roles' => ['editor'],
        ])->assertForbidden();

        $this->actingAs($admin)->patchJson("/api/v1/panel/usuarios/{$admin->uuid}", [
            'estado' => 'inactivo',
        ])->assertForbidden();

        $this->assertSame('activo', $this->recargar($admin)->estado);
    }

    public function test_actualiza_nombre_roles_y_clave_y_deja_huella_sin_la_clave(): void
    {
        $this->rolEditor();
        $objetivo = User::factory()->create(['name' => 'Viejo', 'estado' => 'activo']);

        $this->actingAs($this->admin())->patchJson("/api/v1/panel/usuarios/{$objetivo->uuid}", [
            'name' => 'Nuevo nombre',
            'roles' => ['editor'],
            'password' => 'Otra-Clave-Segura-1',
        ])->assertOk()->assertJsonPath('data.name', 'Nuevo nombre')->assertJsonPath('data.roles.0', 'editor');

        $this->assertTrue(password_verify('Otra-Clave-Segura-1', $this->recargar($objetivo)->password));
        $huella = AuditLog::query()->where('accion', 'usuario_actualizado')->first();
        $this->assertNotNull($huella);
        $this->assertStringNotContainsString('Otra-Clave', json_encode($huella->cambios, JSON_THROW_ON_ERROR));
        $this->assertContains('clave', (array) ($huella->cambios['campos'] ?? []));
    }

    public function test_desactivar_cierra_las_sesiones_abiertas_del_usuario(): void
    {
        $objetivo = User::factory()->create(['estado' => 'activo']);
        $objetivo->createToken('panel');
        $this->assertSame(1, $objetivo->tokens()->count());

        $this->actingAs($this->admin())->patchJson("/api/v1/panel/usuarios/{$objetivo->uuid}", [
            'estado' => 'bloqueado',
        ])->assertOk()->assertJsonPath('data.estado', 'bloqueado');

        $this->assertSame(0, $objetivo->tokens()->count());
        $this->assertNotNull(AuditLog::query()->where('accion', 'usuario_estado_cambiado')->first());
    }

    public function test_no_se_puede_dejar_el_sistema_sin_un_super_administrador_activo(): void
    {
        $unico = $this->superAdmin('unico@example.com');
        $actorInactivo = $this->superAdmin('inactivo@example.com');
        $actorInactivo->update(['estado' => 'inactivo']);

        $this->actingAs($actorInactivo)->patchJson("/api/v1/panel/usuarios/{$unico->uuid}", [
            'estado' => 'inactivo',
        ])->assertUnprocessable();

        $this->actingAs($actorInactivo)->patchJson("/api/v1/panel/usuarios/{$unico->uuid}", [
            'roles' => [],
        ])->assertUnprocessable();

        $this->assertSame('activo', $this->recargar($unico)->estado);
        $this->assertTrue($this->recargar($unico)->hasRole('super-admin'));
    }

    public function test_usuario_inexistente_es_404(): void
    {
        $this->actingAs($this->admin())->getJson('/api/v1/panel/usuarios/00000000-0000-0000-0000-000000000000')->assertNotFound();
    }

    public function test_el_catalogo_de_permisos_viene_agrupado_y_en_lenguaje_llano(): void
    {
        $respuesta = $this->actingAs($this->admin())->getJson('/api/v1/panel/permisos')->assertOk();

        $todos = [];
        foreach ($respuesta->json('data') as $grupo) {
            $this->assertNotEmpty($grupo['grupo']);
            foreach ($grupo['permisos'] as $permiso) {
                $this->assertNotEmpty($permiso['etiqueta']);
                $this->assertStringNotContainsString('.', $permiso['etiqueta']);
                $todos[] = $permiso['nombre'];
            }
        }
        foreach (['noticias.ver', 'noticias.editar', 'tramites.publicar', 'roles.eliminar', 'usuarios.crear'] as $esperado) {
            $this->assertContains($esperado, $todos);
        }
        $this->assertNotContains('noticias.gestionar', $todos);
    }

    public function test_crea_un_rol_con_permisos_y_el_admin_solo_otorga_los_que_tiene(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/api/v1/panel/roles', [
            'name' => 'redactor', 'permisos' => ['noticias.ver', 'noticias.editar'],
        ])->assertCreated()->assertJsonPath('data.permisos', ['noticias.editar', 'noticias.ver']);

        $this->actingAs($admin)->postJson('/api/v1/panel/roles', [
            'name' => 'demasiado', 'permisos' => ['entidad.ver', 'entidad.editar'],
        ])->assertForbidden();

        $this->assertDatabaseMissing('roles', ['name' => 'demasiado']);
        $this->assertNotNull(AuditLog::query()->where('accion', 'rol_creado')->first());
    }

    public function test_valida_nombre_unico_y_permisos_existentes(): void
    {
        Role::findOrCreate('repetido', 'web');

        $this->actingAs($this->admin())->postJson('/api/v1/panel/roles', [
            'name' => 'repetido', 'permisos' => ['no.existe'],
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'permisos.0'], 'errors');
    }

    public function test_el_rol_super_admin_es_intocable_para_todos(): void
    {
        $rol = Role::findOrCreate('super-admin', 'web');
        $super = $this->superAdmin();

        $this->actingAs($super)->patchJson("/api/v1/panel/roles/{$rol->id}", ['permisos' => []])->assertForbidden();
        $this->actingAs($super)->deleteJson("/api/v1/panel/roles/{$rol->id}")->assertForbidden();
        $this->actingAs($super)->postJson('/api/v1/panel/roles', ['name' => 'super-admin'])->assertUnprocessable();
    }

    public function test_actualiza_permisos_de_un_rol_y_el_admin_no_puede_quitar_lo_que_no_tiene(): void
    {
        $rol = Role::findOrCreate('mixto', 'web');
        $rol->syncPermissions(['noticias.ver', 'entidad.ver']);
        $admin = $this->admin();

        // Quitar `entidad.ver` sin tenerlo también es tocar poder ajeno.
        $this->actingAs($admin)->patchJson("/api/v1/panel/roles/{$rol->id}", [
            'permisos' => ['noticias.ver'],
        ])->assertForbidden();

        $this->actingAs($this->superAdmin())->patchJson("/api/v1/panel/roles/{$rol->id}", [
            'permisos' => ['noticias.ver'],
        ])->assertOk()->assertJsonPath('data.permisos', ['noticias.ver']);

        $this->assertNotNull(AuditLog::query()->where('accion', 'rol_actualizado')->first());
    }

    public function test_no_se_elimina_un_rol_con_usuarios_y_si_sin_ellos(): void
    {
        $rol = $this->rolEditor();
        $usuario = User::factory()->create();
        $usuario->assignRole($rol);
        $super = $this->superAdmin();

        $this->actingAs($super)->deleteJson("/api/v1/panel/roles/{$rol->id}")->assertStatus(409);
        $this->assertDatabaseHas('roles', ['name' => 'editor']);

        $usuario->removeRole($rol);
        $this->actingAs($super)->deleteJson("/api/v1/panel/roles/{$rol->id}")->assertOk();
        $this->assertDatabaseMissing('roles', ['name' => 'editor']);
        $this->assertNotNull(AuditLog::query()->where('accion', 'rol_eliminado')->first());
    }

    public function test_el_listado_de_roles_trae_cuantos_usuarios_tiene_cada_uno(): void
    {
        $rol = $this->rolEditor();
        foreach (User::factory()->count(2)->create() as $usuario) {
            $usuario->assignRole($rol);
        }

        $respuesta = $this->actingAs($this->superAdmin())->getJson('/api/v1/panel/roles')->assertOk();
        /** @var list<array<string, mixed>> $datos */
        $datos = $respuesta->json('data');
        $filas = array_column($datos, null, 'name');
        $this->assertSame(2, $filas['editor']['usuarios']);
        $this->assertFalse($filas['editor']['protegido']);
    }

    public function test_la_siembra_crea_el_rol_admin_con_gestion_de_usuarios_y_no_pisa_sus_cambios(): void
    {
        $admin = Role::findByName('admin', 'web');
        foreach (CatalogoPermisos::nombres() as $nombre) {
            $this->assertTrue($admin->hasPermissionTo($nombre), $nombre);
        }
        $this->assertFalse($admin->name === 'super-admin');

        $admin->revokePermissionTo('reportes.ver');
        $this->seed(PermissionSeeder::class);

        $this->assertFalse(Role::findByName('admin', 'web')->hasPermissionTo('reportes.ver'));
    }
}
