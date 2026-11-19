<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Acceso\TextosDeAuditoria;
use Database\Seeders\EntidadSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Quien lee la auditoría ve frases, no nombres internos: «Eliminó una noticia», no `noticia_eliminada`.
 * El texto sale del servidor (una sola fuente, probada), y el panel solo lo muestra.
 */
final class AuditoriaTextosTest extends TestCase
{
    use RefreshDatabase;

    /** @return iterable<string, array{string, string}> */
    public static function frases(): iterable
    {
        yield 'noticia eliminada' => ['noticia_eliminada', 'Eliminó una noticia'];
        yield 'política creada' => ['politica_creada', 'Creó una política'];
        yield 'documento quitado' => ['politica_documento_quitado', 'Quitó el documento de una política (el archivo se conserva)'];
        yield 'carrusel reordenado' => ['banners_reordenados', 'Cambió el orden del carrusel'];
        yield 'login fallido' => ['login_failed', 'Intento fallido de inicio de sesión'];
        yield 'acceso denegado' => ['acceso_denegado', 'Intento sin permiso suficiente'];
        yield 'datos rechazados' => ['datos_rechazados', 'Cambio rechazado por datos inválidos o una regla del sistema'];
        yield 'conflicto' => ['conflicto_rechazado', 'Cambio rechazado: el contenido había cambiado'];
    }

    #[DataProvider('frases')]
    public function test_cada_accion_se_dice_con_palabras(string $accion, string $esperada): void
    {
        $this->assertSame($esperada, TextosDeAuditoria::descripcion($accion));
    }

    public function test_una_accion_desconocida_no_se_oculta_ni_se_inventa(): void
    {
        $this->assertSame('Acción sin descripción: cosa_rara', TextosDeAuditoria::descripcion('cosa_rara'));
    }

    public function test_los_modulos_tienen_nombre_y_lo_demas_pasa_igual(): void
    {
        $this->assertSame('Carrusel de la portada', TextosDeAuditoria::recurso('banners'));
        $this->assertSame('Políticas', TextosDeAuditoria::recurso('politicas'));
        $this->assertSame('admin', TextosDeAuditoria::recurso('admin'));
    }

    /**
     * Cada acción que el código puede registrar tiene frase. Si alguien agrega una acción nueva sin su texto,
     * esta prueba falla, y el panel nunca muestra un nombre interno por descuido.
     */
    public function test_toda_accion_que_el_codigo_registra_tiene_frase(): void
    {
        $encontradas = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path(), \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $archivo) {
            if (! $archivo instanceof \SplFileInfo || $archivo->getExtension() !== 'php') {
                continue;
            }
            $codigo = (string) file_get_contents($archivo->getPathname());
            preg_match_all("/Huella::(?:registrar|deSesion)\(\s*(?:\\\$actor,\s*)?'([a-z_]+)'/", $codigo, $a);
            preg_match_all("/'accion'\s*=>\s*'([a-z_]+)'/", $codigo, $b);
            if (str_contains($archivo->getPathname(), 'AuditarRechazosDelPanel')) {
                preg_match_all("/=>\s*'([a-z_]+_(?:denegado|rechazados|rechazado))'/", $codigo, $c);
            }
            $encontradas = array_merge($encontradas, $a[1], $b[1], $c[1] ?? []);
        }
        $encontradas = array_values(array_unique($encontradas));

        $this->assertGreaterThan(20, count($encontradas), 'El rastreo no encontró las acciones: la prueba perdió su sentido.');
        foreach ($encontradas as $accion) {
            $this->assertArrayHasKey($accion, TextosDeAuditoria::ACCIONES, "Falta la frase de la acción «{$accion}».");
        }
    }

    public function test_la_api_devuelve_la_descripcion_y_el_nombre_del_recurso(): void
    {
        $this->seed([EntidadSeeder::class, PermissionSeeder::class, SuperAdminSeeder::class]);
        $admin = User::query()->where('email', config('superadmin.email'))->firstOrFail();
        AuditLog::query()->create(['accion' => 'noticia_eliminada', 'recurso' => 'noticias', 'recurso_id' => 5, 'cambios' => ['titulo' => 'X'], 'causer_id' => $admin->id]);

        $filas = $this->actingAs($admin, 'web')->getJson('/api/v1/panel/auditoria?accion=noticia_eliminada')->assertOk()->json('data');
        $fila = $filas[0];

        $this->assertSame('Eliminó una noticia', $fila['descripcion']);
        $this->assertSame('Noticias', $fila['recurso_nombre']);
        $this->assertSame('noticia_eliminada', $fila['accion']);
    }

    public function test_se_puede_filtrar_por_accion(): void
    {
        $this->seed([EntidadSeeder::class, PermissionSeeder::class, SuperAdminSeeder::class]);
        $admin = User::query()->where('email', config('superadmin.email'))->firstOrFail();
        AuditLog::query()->create(['accion' => 'noticia_eliminada', 'recurso' => 'noticias', 'causer_id' => $admin->id]);
        AuditLog::query()->create(['accion' => 'noticia_creada', 'recurso' => 'noticias', 'causer_id' => $admin->id]);

        $acciones = array_column($this->actingAs($admin, 'web')->getJson('/api/v1/panel/auditoria?accion=noticia_creada')->assertOk()->json('data'), 'accion');

        $this->assertSame(['noticia_creada'], $acciones);
    }

    public function test_el_recurso_se_arma_sin_consultar_de_mas(): void
    {
        $fila = new AuditLog(['accion' => 'login', 'recurso' => 'session']);
        $datos = (new AuditLogResource($fila))->toArray(Request::create('/'));

        $this->assertSame('Inició sesión', $datos['descripcion']);
    }
}
