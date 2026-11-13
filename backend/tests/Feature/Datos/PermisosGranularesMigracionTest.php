<?php

declare(strict_types=1);

namespace Tests\Feature\Datos;

use App\Models\User;
use App\Support\Acceso\CatalogoPermisos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * La migración parte «X.gestionar» en acciones separadas sin que nadie gane ni pierda acceso.
 */
final class PermisosGranularesMigracionTest extends TestCase
{
    use RefreshDatabase;

    /** Una base «de antes»: la migración ya sembró los permisos nuevos al migrar, así que se parte de cero. */
    protected function setUp(): void
    {
        parent::setUp();
        DB::table('role_has_permissions')->delete();
        DB::table('model_has_permissions')->delete();
        DB::table('permissions')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function correr(): void
    {
        (require base_path('database/migrations/2026_11_17_000000_permisos_granulares.php'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function rol(string $nombre, string ...$permisos): Role
    {
        $rol = Role::findOrCreate($nombre, 'web');
        foreach ($permisos as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $rol->syncPermissions($permisos);

        return $rol;
    }

    /** @return list<string> */
    private function de(Role $rol): array
    {
        $nombres = $rol->fresh()?->permissions->pluck('name')->sort()->values()->all() ?? [];

        return array_values(array_map('strval', $nombres));
    }

    public function test_quien_gestionaba_un_modulo_recibe_todas_sus_acciones_y_el_permiso_viejo_desaparece(): void
    {
        $redactor = $this->rol('redactor', 'noticias.gestionar');

        $this->correr();

        $esperado = CatalogoPermisos::de('noticias');
        sort($esperado);
        $this->assertSame($esperado, $this->de($redactor));
        $this->assertFalse(Permission::query()->where('name', 'noticias.gestionar')->exists());
    }

    public function test_usuarios_gestionar_daba_usuarios_y_roles(): void
    {
        $rol = $this->rol('rrhh', 'usuarios.gestionar');

        $this->correr();

        $this->assertEqualsCanonicalizing(
            ['usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'roles.ver', 'roles.crear', 'roles.editar', 'roles.eliminar'],
            $this->de($rol),
        );
    }

    public function test_lo_que_no_tenia_gestionar_no_recibe_nada_nuevo(): void
    {
        $lector = $this->rol('lector', 'auditoria.ver', 'archivos.ver');

        $this->correr();

        $this->assertSame(['archivos.ver', 'auditoria.ver'], $this->de($lector));
    }

    public function test_tramites_ver_de_antes_era_solicitudes_y_el_nuevo_tramites_ver_solo_lo_da_gestionar(): void
    {
        $atencion = $this->rol('atencion', 'tramites.ver');
        $catalogo = $this->rol('catalogo', 'tramites.gestionar');

        $this->correr();

        $this->assertSame(['solicitudes.ver'], $this->de($atencion));
        $this->assertContains('tramites.ver', $this->de($catalogo));
        $this->assertNotContains('solicitudes.ver', $this->de($catalogo));
    }

    public function test_portal_y_sede_publicar_ahora_traen_ver(): void
    {
        $rol = $this->rol('editor-portal', 'portal.publicar');

        $this->correr();

        $this->assertEqualsCanonicalizing(['portal.ver', 'portal.publicar'], $this->de($rol));
    }

    public function test_un_usuario_con_permiso_directo_tambien_se_migra(): void
    {
        Permission::findOrCreate('banners.gestionar', 'web');
        $usuario = User::factory()->create();
        $usuario->givePermissionTo('banners.gestionar');

        $this->correr();

        $this->assertEqualsCanonicalizing(CatalogoPermisos::de('banners'), array_values(array_map('strval', $usuario->fresh()?->getDirectPermissions()->pluck('name')->all() ?? [])));
    }

    public function test_el_rol_admin_recibe_todos_los_permisos_del_catalogo(): void
    {
        $admin = $this->rol('admin', 'noticias.gestionar');

        $this->correr();

        $this->assertEqualsCanonicalizing(CatalogoPermisos::nombres(), $this->de($admin));
    }

    public function test_es_idempotente_y_no_pisa_ajustes_posteriores(): void
    {
        $this->rol('lector-catalogo', 'tramites.gestionar');
        $this->correr();
        $solo = Role::findByName('lector-catalogo', 'web');
        $solo->syncPermissions(['tramites.ver']);

        $this->correr();

        $this->assertSame(['tramites.ver'], $this->de($solo));
        $this->assertSame(1, DB::table('permissions')->where('name', 'tramites.ver')->count());
    }
}
