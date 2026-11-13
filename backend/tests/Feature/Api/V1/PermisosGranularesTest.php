<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Tramite;
use App\Models\User;
use App\Support\Acceso\CatalogoPermisos;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Permisos modulares y granulares: cada ruta del panel exige exactamente el permiso de SU acción;
 * «ver» no da «editar», «eliminar» ni «publicar».
 */
final class PermisosGranularesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /** @return array<string, bool|string> */
    private function noticiaCompleta(string $titulo, bool $activo = false): array
    {
        return ['titulo' => $titulo, 'descripcion_corta' => 'Resumen', 'contenido' => 'Un párrafo.', 'fecha_publicacion' => '2026-01-01', 'activo' => $activo];
    }

    private function conPermisos(string ...$permisos): User
    {
        $usuario = User::factory()->create(['estado' => 'activo']);
        $usuario->givePermissionTo($permisos);

        return $usuario;
    }

    /** @return list<string> */
    private function todosMenos(string $permiso): array
    {
        return array_values(array_diff(CatalogoPermisos::nombres(), [$permiso]));
    }

    /** @return array<string, array{0: string, 1: string, 2: string}> */
    public static function rutasYSuPermiso(): array
    {
        $u = '00000000-0000-4000-8000-000000000000';

        return [
            'auditoría: ver' => ['GET', '/api/v1/panel/auditoria', 'auditoria.ver'],
            'entidad: editar' => ['PATCH', '/api/v1/panel/entidad', 'entidad.editar'],
            'políticas: ver' => ['GET', '/api/v1/panel/politicas', 'politicas.ver'],
            'políticas: editar' => ['PATCH', '/api/v1/panel/politicas/x', 'politicas.editar'],
            'trámites: ver (lista)' => ['GET', '/api/v1/panel/tramites', 'tramites.ver'],
            'trámites: crear' => ['POST', '/api/v1/panel/tramites', 'tramites.crear'],
            'trámites: ver (uno)' => ['GET', "/api/v1/panel/tramites/{$u}", 'tramites.ver'],
            'trámites: editar' => ['PATCH', "/api/v1/panel/tramites/{$u}", 'tramites.editar'],
            'trámites: eliminar' => ['DELETE', "/api/v1/panel/tramites/{$u}", 'tramites.eliminar'],
            'banners: ver' => ['GET', '/api/v1/panel/banners', 'banners.ver'],
            'banners: crear' => ['POST', '/api/v1/panel/banners', 'banners.crear'],
            'banners: ordenar es editar' => ['PUT', '/api/v1/panel/banners/orden', 'banners.editar'],
            'banners: editar' => ['PATCH', "/api/v1/panel/banners/{$u}", 'banners.editar'],
            'banners: eliminar' => ['DELETE', "/api/v1/panel/banners/{$u}", 'banners.eliminar'],
            'usuarios: ver' => ['GET', '/api/v1/panel/usuarios', 'usuarios.ver'],
            'usuarios: crear' => ['POST', '/api/v1/panel/usuarios', 'usuarios.crear'],
            'usuarios: editar' => ['PATCH', "/api/v1/panel/usuarios/{$u}", 'usuarios.editar'],
            'roles: ver' => ['GET', '/api/v1/panel/roles', 'roles.ver'],
            'permisos: ver roles' => ['GET', '/api/v1/panel/permisos', 'roles.ver'],
            'roles: crear' => ['POST', '/api/v1/panel/roles', 'roles.crear'],
            'roles: editar' => ['PATCH', '/api/v1/panel/roles/999999', 'roles.editar'],
            'roles: eliminar' => ['DELETE', '/api/v1/panel/roles/999999', 'roles.eliminar'],
            'noticias: ver' => ['GET', '/api/v1/panel/noticias', 'noticias.ver'],
            'noticias: crear' => ['POST', '/api/v1/panel/noticias', 'noticias.crear'],
            'noticias: editar' => ['PATCH', "/api/v1/panel/noticias/{$u}", 'noticias.editar'],
            'noticias: eliminar' => ['DELETE', "/api/v1/panel/noticias/{$u}", 'noticias.eliminar'],
            'archivos: ver' => ['GET', "/api/v1/panel/archivos/{$u}", 'archivos.ver'],
            'archivos: eliminar' => ['DELETE', "/api/v1/panel/archivos/{$u}", 'archivos.eliminar'],
        ];
    }

    #[DataProvider('rutasYSuPermiso')]
    public function test_la_ruta_se_niega_sin_su_permiso_aunque_se_tengan_todos_los_demas(string $metodo, string $ruta, string $permiso): void
    {
        $usuario = $this->conPermisos(...$this->todosMenos($permiso));

        $this->actingAs($usuario)->json($metodo, $ruta, [])->assertForbidden();
    }

    #[DataProvider('rutasYSuPermiso')]
    public function test_con_solo_su_permiso_la_ruta_deja_pasar(string $metodo, string $ruta, string $permiso): void
    {
        $usuario = $this->conPermisos($permiso);

        $this->assertNotSame(403, $this->actingAs($usuario)->json($metodo, $ruta, [])->getStatusCode(), "$metodo $ruta con solo $permiso");
    }

    public function test_el_gestor_de_antes_pero_solo_con_ver_lee_y_no_escribe(): void
    {
        $lector = $this->conPermisos('noticias.ver', 'tramites.ver', 'banners.ver');
        $this->actingAs($lector);

        $this->getJson('/api/v1/panel/noticias')->assertOk();
        $this->getJson('/api/v1/panel/tramites')->assertOk();
        $this->getJson('/api/v1/panel/banners')->assertOk();
        $this->postJson('/api/v1/panel/noticias', ['titulo' => 'X'])->assertForbidden();
        $this->postJson('/api/v1/panel/tramites', ['nombre' => 'X'])->assertForbidden();
        $this->postJson('/api/v1/panel/banners', ['titulo' => 'X'])->assertForbidden();
    }

    public function test_crear_no_basta_para_publicar_una_noticia(): void
    {
        $sinPublicar = $this->conPermisos('noticias.ver', 'noticias.crear');
        $this->actingAs($sinPublicar)->postJson('/api/v1/panel/noticias', $this->noticiaCompleta('Borrador'))->assertCreated();
        $this->actingAs($sinPublicar)->postJson('/api/v1/panel/noticias', $this->noticiaCompleta('Pública', true))->assertForbidden();
    }

    public function test_publicar_o_retirar_una_noticia_exige_publicar_pero_editar_sin_cambiar_el_estado_no(): void
    {
        $uuid = (string) $this->actingAs($this->conPermisos(...CatalogoPermisos::nombres()))
            ->postJson('/api/v1/panel/noticias', $this->noticiaCompleta('Base'))->assertCreated()->json('data.uuid');
        $editor = $this->conPermisos('noticias.ver', 'noticias.editar');

        // Guardar con el mismo estado (lo que hace el formulario) sí se permite.
        $this->actingAs($editor)->patchJson("/api/v1/panel/noticias/{$uuid}", ['titulo' => 'Nuevo título', 'activo' => false])->assertOk();
        // Cambiar el estado, no.
        $this->actingAs($editor)->patchJson("/api/v1/panel/noticias/{$uuid}", ['activo' => true])->assertForbidden();

        $jefe = $this->conPermisos('noticias.ver', 'noticias.editar', 'noticias.publicar');
        $this->assertNotSame(403, $this->actingAs($jefe)->patchJson("/api/v1/panel/noticias/{$uuid}", ['activo' => true])->getStatusCode());
    }

    public function test_publicar_o_retirar_un_banner_o_un_tramite_exige_publicar(): void
    {
        $uuidBanner = (string) $this->actingAs($this->conPermisos(...CatalogoPermisos::nombres()))
            ->postJson('/api/v1/panel/banners', ['titulo' => 'Banner'])->assertCreated()->json('data.uuid');
        $editor = $this->conPermisos('banners.ver', 'banners.editar', 'tramites.ver', 'tramites.editar');
        $this->actingAs($editor)->patchJson("/api/v1/panel/banners/{$uuidBanner}", ['activo' => true])->assertForbidden();

        $tramite = Tramite::factory()->create(['publicado_en' => null]);
        $this->actingAs($editor)->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", ['publicado' => true])->assertForbidden();
        $this->actingAs($editor)->patchJson("/api/v1/panel/tramites/{$tramite->uuid}", ['publicado' => false, 'nombre' => 'Otro nombre'])->assertOk();
    }

    public function test_un_rol_con_una_accion_pero_sin_ver_se_rechaza_y_con_ver_se_acepta(): void
    {
        $admin = $this->conPermisos(...CatalogoPermisos::nombres());

        $this->actingAs($admin)->postJson('/api/v1/panel/roles', ['name' => 'sin ver', 'permisos' => ['noticias.editar']])
            ->assertUnprocessable()->assertJsonValidationErrors(['permisos'], 'errors');
        $this->assertDatabaseMissing('roles', ['name' => 'sin ver']);

        $this->actingAs($admin)->postJson('/api/v1/panel/roles', ['name' => 'con ver', 'permisos' => ['noticias.ver', 'noticias.editar']])->assertCreated();

        $rol = Role::findByName('con ver', 'web');
        $this->actingAs($admin)->patchJson("/api/v1/panel/roles/{$rol->id}", ['permisos' => ['noticias.eliminar']])->assertUnprocessable();
    }

    public function test_el_catalogo_expone_modulo_accion_y_requisitos_para_pintar_la_matriz(): void
    {
        $admin = $this->conPermisos('roles.ver');
        $datos = $this->actingAs($admin)->getJson('/api/v1/panel/permisos')->assertOk()->json('data');

        $filas = [];
        foreach ($datos as $grupo) {
            foreach ($grupo['permisos'] as $p) {
                $filas[$p['nombre']] = $p;
            }
        }
        $this->assertSame('noticias', $filas['noticias.editar']['modulo']);
        $this->assertSame('editar', $filas['noticias.editar']['accion']);
        $this->assertSame(['noticias.ver'], $filas['noticias.editar']['requiere']);
        $this->assertSame([], $filas['noticias.ver']['requiere']);
    }
}
