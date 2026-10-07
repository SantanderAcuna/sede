<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\EntidadSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Auditoría: endpoint GET /panel/auditoria y listener de eventos.
 */
final class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            EntidadSeeder::class,
            PermissionSeeder::class,
            SuperAdminSeeder::class,
        ]);

        $this->user = User::query()->where('email', config('superadmin.email'))->firstOrFail();
    }

    public function test_get_auditoria_sin_sesion_devuelve_401(): void
    {
        $this->getJson('/api/v1/panel/auditoria')
            ->assertStatus(401);
    }

    public function test_get_auditoria_con_sesion_devuelve_200(): void
    {
        $this->actingAs($this->user, 'web')
            ->getJson('/api/v1/panel/auditoria')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links',
            ]);
    }

    public function test_asignar_permiso_a_rol_crea_permission_attached_y_detached(): void
    {
        $role = Role::findByName('super-admin', 'web');
        $permission = Permission::findByName('entidad.gestionar', 'web');

        $this->actingAs($this->user, 'web');

        // Quitar primero para que el posterior givePermissionTo genere un evento
        $role->revokePermissionTo($permission);

        // Verificar que se registró el permission_detached sobre el rol
        $this->assertDatabaseHas('audit_log', [
            'accion' => 'permission_detached',
            'recurso' => 'entidad.gestionar',
        ]);

        // Volver a asignar
        $role->givePermissionTo($permission);

        $this->assertDatabaseHas('audit_log', [
            'accion' => 'permission_attached',
            'recurso' => 'entidad.gestionar',
        ]);
    }

    public function test_quitar_permiso_de_rol_crea_permission_detached(): void
    {
        $role = Role::findByName('super-admin', 'web');
        $permission = Permission::findByName('entidad.gestionar', 'web');

        $this->actingAs($this->user, 'web');

        // El rol ya tiene el permiso por el seeder — lo quitamos
        $role->revokePermissionTo($permission);

        $this->assertDatabaseHas('audit_log', [
            'accion' => 'permission_detached',
            'recurso' => 'entidad.gestionar',
        ]);
    }

    public function test_asignar_permiso_directo_a_usuario_crea_audit_log(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->user, 'web');

        $user->givePermissionTo('entidad.gestionar');

        // Spatie dispara PermissionAttachedEvent para asignación directa a usuario
        $this->assertDatabaseHas('audit_log', [
            'accion' => 'permission_attached',
        ]);
    }

    public function test_auditoria_filtra_por_usuario(): void
    {
        // Limpiar eventos previos y crear un registro directo
        AuditLog::withoutEvents(function (): void {
            AuditLog::create([
                'accion' => 'login',
                'recurso' => 'session',
                'causer_id' => $this->user->id,
                'ip_origen' => '127.0.0.1',
                'created_at' => now()->subDay(),
            ]);
        });

        $response = $this->actingAs($this->user, 'web')
            ->getJson('/api/v1/panel/auditoria?usuario_id='.$this->user->id);

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_auditoria_filtra_por_fecha_desde(): void
    {
        // Limpiar todo antes de insertar los registros controladas
        AuditLog::query()->delete();

        $fechaCorte = now()->subDays(5)->toDateString();

        AuditLog::withoutEvents(function (): void {
            AuditLog::create([
                'accion' => 'login',
                'recurso' => 'session',
                'causer_id' => $this->user->id,
                'ip_origen' => '127.0.0.1',
                'created_at' => now()->subDays(10),
            ]);

            AuditLog::create([
                'accion' => 'logout',
                'recurso' => 'session',
                'causer_id' => $this->user->id,
                'ip_origen' => '127.0.0.1',
                'created_at' => now(),
            ]);
        });

        $response = $this->actingAs($this->user, 'web')
            ->getJson('/api/v1/panel/auditoria?fecha_desde='.$fechaCorte);

        $response->assertOk();

        // Solo el registro reciente (logout de hoy) debe aparecer
        $total = $response->json('meta.total');
        $this->assertGreaterThanOrEqual(1, $total, 'Debe haber al menos 1 registro desde la fecha de corte');

        // Y ninguno de los registros debe ser el login antiguo
        foreach ($response->json('data') as $item) {
            $this->assertNotEquals('login', $item['accion']);
        }
    }
}
