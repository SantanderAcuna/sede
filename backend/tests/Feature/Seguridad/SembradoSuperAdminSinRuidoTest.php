<?php

declare(strict_types=1);

namespace Tests\Feature\Seguridad;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\EntidadSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La siembra corre en cada despliegue. Si desasignara y volviera a asignar el rol aunque
 * nada cambió, cada despliegue dejaría dos filas falsas («Quitó/Asignó un rol») en una
 * bitácora que no se puede borrar, y taparían los cambios de permisos reales.
 */
final class SembradoSuperAdminSinRuidoTest extends TestCase
{
    use RefreshDatabase;

    private function filasDeRol(): int
    {
        return AuditLog::query()->whereIn('accion', ['role_attached', 'role_detached'])->count();
    }

    public function test_repetir_la_siembra_no_deja_huellas_de_rol(): void
    {
        $this->seed([EntidadSeeder::class, PermissionSeeder::class, SuperAdminSeeder::class]);
        $tras_la_primera = $this->filasDeRol();

        $this->seed(SuperAdminSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $this->assertSame($tras_la_primera, $this->filasDeRol());
    }

    public function test_si_la_cuenta_tiene_un_rol_de_mas_la_siembra_lo_corrige(): void
    {
        $this->seed([EntidadSeeder::class, PermissionSeeder::class, SuperAdminSeeder::class]);
        $admin = User::query()->where('email', config('superadmin.email'))->firstOrFail();
        $admin->assignRole(Role::findOrCreate('editor-de-prueba', 'web'));

        $this->seed(SuperAdminSeeder::class);

        $this->assertSame(['super-admin'], $admin->fresh()?->roles()->pluck('name')->all());
    }
}
