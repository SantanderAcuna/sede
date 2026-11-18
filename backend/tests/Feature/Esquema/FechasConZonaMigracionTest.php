<?php

declare(strict_types=1);

namespace Tests\Feature\Esquema;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 0D, D2: las fechas pasan a `timestamptz(0)` sin cambiar el instante que guardan.
 * La tabla de prueba es `password_reset_tokens` (dos columnas obligatorias y una fecha).
 */
final class FechasConZonaMigracionTest extends TestCase
{
    use RefreshDatabase;

    private function tipo(): string
    {
        return (string) DB::scalar(<<<'SQL'
            select data_type from information_schema.columns
            where table_schema = 'public' and table_name = 'password_reset_tokens' and column_name = 'created_at'
        SQL);
    }

    public function test_el_esquema_no_conserva_fechas_sin_zona(): void
    {
        $this->assertSame(0, (int) DB::scalar(
            "select count(*) from information_schema.columns where table_schema = 'public' and data_type = 'timestamp without time zone'",
        ));
        $this->assertSame('timestamp with time zone', $this->tipo());
    }

    public function test_up_conserva_el_instante_y_se_lee_igual_desde_otra_zona(): void
    {
        $migracion = require database_path('migrations/2026_11_19_300000_fechas_con_zona_horaria.php');

        $migracion->down();
        $this->assertSame('timestamp without time zone', $this->tipo());
        DB::statement("insert into password_reset_tokens (email, token, created_at) values ('a@b.co', 't', '2026-11-18 15:00:00')");

        $migracion->up();

        $this->assertSame('timestamp with time zone', $this->tipo());
        DB::statement("set time zone 'America/Bogota'");
        $this->assertSame(
            '2026-11-18 10:00:00-05',
            DB::scalar("select created_at::text from password_reset_tokens where email = 'a@b.co'"),
        );
        $this->assertTrue((bool) DB::scalar(
            "select created_at = timestamptz '2026-11-18 15:00:00+00' from password_reset_tokens where email = 'a@b.co'",
        ), 'El instante guardado cambió.');
    }

    public function test_down_devuelve_la_hora_en_utc(): void
    {
        DB::statement("insert into password_reset_tokens (email, token, created_at) values ('c@d.co', 't', '2026-11-18 15:00:00+00')");
        $migracion = require database_path('migrations/2026_11_19_300000_fechas_con_zona_horaria.php');

        DB::statement("set time zone 'America/Bogota'");
        $migracion->down();

        $this->assertSame(
            '2026-11-18 15:00:00',
            DB::scalar("select created_at::text from password_reset_tokens where email = 'c@d.co'"),
        );
    }
}
