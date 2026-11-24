<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * La sesión de PostgreSQL trabaja siempre en UTC, la zona de la aplicación.
 *
 * Laravel entrega las fechas a la base como texto SIN zona («2026-11-18 01:05:00», en la zona de la
 * aplicación: UTC). Las columnas `timestamptz` (`fecha_publicacion` de las noticias, la vigencia de los
 * banners) interpretan ese texto en la zona de la SESIÓN de la base. Si la sesión no está en UTC, cada
 * fecha se guarda desplazada y una noticia «publicada hace una hora» queda programada para dentro de
 * unas horas. Hasta ahora eso dependía de la zona con que arrancara el servidor de PostgreSQL (la
 * imagen oficial usa UTC y por eso no se notaba): era una dependencia de infraestructura que nadie
 * había escrito. Ahora la aplicación la fija en las tres conexiones.
 */
final class ZonaHorariaDeLaBaseTest extends TestCase
{
    public function test_las_tres_conexiones_fijan_la_zona_utc(): void
    {
        foreach (['pgsql', 'pgsql_migracion', 'pgsql_copia'] as $conexion) {
            $this->assertSame(
                'UTC',
                config("database.connections.{$conexion}.timezone"),
                "La conexión {$conexion} debe fijar la zona UTC de la sesión.",
            );
        }
    }

    public function test_la_sesion_de_la_base_esta_en_utc(): void
    {
        $this->assertSame('UTC', DB::selectOne("select current_setting('TimeZone') as zona")->zona);
    }

    public function test_una_fecha_con_zona_se_guarda_y_se_lee_sin_desplazarse(): void
    {
        $esperada = now()->subHour();

        DB::beginTransaction();
        try {
            $guardada = DB::selectOne('select ?::timestamptz as t', [$esperada->format('Y-m-d H:i:s')])->t;

            $this->assertSame(
                $esperada->format('Y-m-d H:i:s'),
                CarbonImmutable::parse((string) $guardada)->utc()->format('Y-m-d H:i:s'),
                'Una fecha en UTC no debe cambiar de hora al pasar por timestamptz.',
            );
        } finally {
            DB::rollBack();
        }
    }
}
