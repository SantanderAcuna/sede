<?php

declare(strict_types=1);

namespace Tests\Feature\Datos;

use App\Models\Entidad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Staging tenía la fila de la entidad con `uuid` NULL: existía antes de que la
 * migración `add_uuid_columns` añadiera la columna, y esa migración solo rellenaba
 * `users`. El sembrador usa `updateOrCreate(['id' => 1])`, que no toca el `uuid` de
 * una fila que ya existe. Resultado: `GET /api/v1/entidad` devolvía `uuid: null`, y
 * toda ruta que identifica a la entidad por su uuid (`/panel/entidad/{uuid}/archivos`)
 * fallaba con un 500 al consultar `where uuid = null`.
 *
 * La migración `poblar_uuid_faltantes` rellena las filas sin uuid de todas las tablas
 * que lo añadieron como columna opcional.
 */
final class UuidsFaltantesTest extends TestCase
{
    use RefreshDatabase;

    private const TABLAS = ['users', 'entidads', 'menus', 'tramites'];

    private function poblar(): void
    {
        $migracion = require database_path('migrations/2026_11_10_000000_poblar_uuid_faltantes.php');
        $migracion->up();
    }

    public function test_rellena_el_uuid_de_las_filas_que_lo_tenian_nulo(): void
    {
        $entidad = Entidad::factory()->create();
        $usuario = User::factory()->create();
        DB::table('entidads')->where('id', $entidad->id)->update(['uuid' => null]);
        DB::table('users')->where('id', $usuario->id)->update(['uuid' => null]);

        $this->poblar();

        foreach (self::TABLAS as $tabla) {
            $this->assertSame(0, DB::table($tabla)->whereNull('uuid')->count(), "«{$tabla}» sigue con uuid nulo");
        }
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            (string) DB::table('entidads')->where('id', $entidad->id)->value('uuid'),
        );
    }

    public function test_no_cambia_los_uuid_que_ya_existen(): void
    {
        $entidad = Entidad::factory()->create();
        $antes = (string) DB::table('entidads')->where('id', $entidad->id)->value('uuid');
        $this->assertNotSame('', $antes);

        $this->poblar();

        $this->assertSame($antes, (string) DB::table('entidads')->where('id', $entidad->id)->value('uuid'));
    }

    public function test_la_api_publica_de_la_entidad_entrega_un_uuid_tras_poblar(): void
    {
        $entidad = Entidad::factory()->create();
        DB::table('entidads')->where('id', $entidad->id)->update(['uuid' => null]);

        $this->poblar();

        $this->getJson('/api/v1/entidad')
            ->assertOk()
            ->assertJsonPath('data.uuid', DB::table('entidads')->where('id', $entidad->id)->value('uuid'));
    }
}
