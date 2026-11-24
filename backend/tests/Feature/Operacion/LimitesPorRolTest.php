<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

use App\Support\RolesBaseDeDatos;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use Throwable;

/**
 * Límites de tiempo por rol de la base (0D, D7, R12).
 *
 * Sin límites, una consulta atascada, un bloqueo que nadie suelta o una transacción abierta
 * por olvido retienen una de las `max_connections` hasta que nginx (60 s) corta al cliente:
 * en un pico de tráfico eso agota las conexiones para todos.
 *
 *  - `sede_app` (web) lleva los límites ESTRICTOS como valor por omisión del rol, y los
 *    pone `RolesBaseDeDatos::aplicar()`, que corre en cada despliegue (lo mismo que D6: una
 *    migración sola se desharía con el siguiente `aplicar()`).
 *  - El planificador y Horizon corren como `sede_app` pero con límites MAYORES por
 *    `PGOPTIONS` en su servicio de `compose.yaml` (el borrado diario de consentimientos
 *    crece con el tráfico).
 *  - `sede` (dueño, migra) y `sede_lectura` (copia con `pg_dump`) NO llevan límites.
 *
 * Aclaración honesta: un valor por omisión de rol NO es un techo. Cualquier sesión puede
 * subirlo con `SET`. Protege de errores y de la lentitud, no de una aplicación comprometida.
 *
 * Sin `RefreshDatabase` a propósito, por la misma razón que `RolesBaseDeDatosTest`: las
 * conexiones de otros roles son sesiones distintas y no ven lo no confirmado.
 */
final class LimitesPorRolTest extends TestCase
{
    private const TABLA = 'zz_prueba_limites';

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Los límites por rol sólo existen en PostgreSQL.');
        }

        $migracion = require database_path('migrations/2026_11_19_000000_crear_roles_de_base_de_datos.php');
        $migracion->up();

        DB::unprepared('DROP TABLE IF EXISTS '.self::TABLA);
        DB::unprepared('CREATE TABLE '.self::TABLA.' (id serial primary key, dato text)');
        DB::unprepared('INSERT INTO '.self::TABLA." (dato) VALUES ('semilla')");
    }

    protected function tearDown(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared('DROP TABLE IF EXISTS '.self::TABLA);
            // Los ajustes son del rol en el clúster: se dejan como los pone el despliegue.
            RolesBaseDeDatos::aplicar();
        }

        parent::tearDown();
    }

    private function como(string $rol, string $clave): Connection
    {
        $base = Config::get('database.connections.pgsql');
        Config::set('database.connections.'.$rol, array_merge($base, [
            'url' => null,
            'username' => $rol,
            'password' => $clave,
        ]));
        DB::purge($rol);

        return DB::connection($rol);
    }

    /** Valor efectivo de un parámetro en esa sesión, en milisegundos (`pg_settings.setting`). */
    private function ms(Connection $bd, string $parametro): int
    {
        return (int) $bd->scalar('select setting from pg_settings where name = ?', [$parametro]);
    }

    /** @param array{statement_timeout: int, lock_timeout: int, idle_in_transaction_session_timeout: int} $web */
    private function conLimitesDeWeb(array $web): void
    {
        Config::set('database.limites.web', $web);
        RolesBaseDeDatos::aplicar();
    }

    public function test_sede_app_trae_los_limites_de_la_web(): void
    {
        $app = $this->como('sede_app', 'prueba-app');

        $this->assertSame(5000, $this->ms($app, 'statement_timeout'));
        $this->assertSame(2000, $this->ms($app, 'lock_timeout'));
        $this->assertSame(15000, $this->ms($app, 'idle_in_transaction_session_timeout'));
    }

    public function test_el_dueno_y_la_lectura_no_llevan_limites(): void
    {
        $lectura = $this->como('sede_lectura', 'prueba-lectura');

        foreach (['statement_timeout', 'lock_timeout', 'idle_in_transaction_session_timeout'] as $parametro) {
            $this->assertSame(0, $this->ms(DB::connection(), $parametro), "El dueño no puede llevar {$parametro}: las migraciones corren con él.");
            $this->assertSame(0, $this->ms($lectura, $parametro), "sede_lectura no puede llevar {$parametro}: pg_dump se cortaría a medias.");
        }
    }

    public function test_los_limites_son_de_esta_base_y_no_de_todo_el_cluster(): void
    {
        // Los roles son del clúster. Sin `IN DATABASE`, otra base del mismo servidor heredaría los
        // límites de la sede.
        $filas = DB::select(
            "select s.setdatabase, d.datname from pg_db_role_setting s
               join pg_roles r on r.oid = s.setrole
               left join pg_database d on d.oid = s.setdatabase
              where r.rolname = 'sede_app'"
        );

        $this->assertNotEmpty($filas, 'sede_app no tiene ajustes.');
        foreach ($filas as $fila) {
            $this->assertNotSame(0, (int) $fila->setdatabase, 'El ajuste vale para todas las bases del clúster.');
            $this->assertSame(DB::connection()->getDatabaseName(), $fila->datname);
        }
    }

    public function test_aplicar_de_nuevo_deja_los_mismos_limites(): void
    {
        RolesBaseDeDatos::aplicar();
        RolesBaseDeDatos::aplicar();

        $app = $this->como('sede_app', 'prueba-app');
        $this->assertSame(5000, $this->ms($app, 'statement_timeout'));
        $this->assertSame(1, (int) DB::scalar(
            "select count(*) from pg_db_role_setting s join pg_roles r on r.oid = s.setrole where r.rolname = 'sede_app'"
        ), 'Debe haber una sola fila de ajustes del rol, no una por cada aplicación.');
    }

    public function test_una_consulta_lenta_se_corta_con_57014(): void
    {
        $this->conLimitesDeWeb(['statement_timeout' => 300, 'lock_timeout' => 2000, 'idle_in_transaction_session_timeout' => 15000]);
        $app = $this->como('sede_app', 'prueba-app');

        $inicio = microtime(true);
        try {
            $app->select('select pg_sleep(3)');
            $this->fail('La consulta lenta no se cortó.');
        } catch (QueryException $e) {
            $this->assertSame('57014', $e->errorInfo[0] ?? null);
        }
        $this->assertLessThan(2.0, microtime(true) - $inicio, 'Tardó lo que la consulta, no lo que el límite.');
    }

    public function test_un_bloqueo_que_no_se_suelta_se_corta_con_55_p03(): void
    {
        $this->conLimitesDeWeb(['statement_timeout' => 5000, 'lock_timeout' => 300, 'idle_in_transaction_session_timeout' => 15000]);
        $app = $this->como('sede_app', 'prueba-app');

        DB::beginTransaction();
        try {
            DB::select('select * from '.self::TABLA.' where id = 1 for update');

            // Red de seguridad de la PRUEBA, no del rol: sin límite de bloqueo esta sesión esperaría
            // para siempre a la de arriba (misma prueba, mismo proceso). Con el límite del rol (300 ms)
            // el corte llega antes y es el 55P03 que se comprueba.
            $app->statement("set statement_timeout = '4s'");

            try {
                $app->update('update '.self::TABLA." set dato = 'x' where id = 1");
                $this->fail('La actualización esperó el bloqueo sin límite.');
            } catch (QueryException $e) {
                $this->assertSame('55P03', $e->errorInfo[0] ?? null);
            }
        } finally {
            DB::rollBack();
        }
    }

    public function test_una_transaccion_olvidada_se_cierra_sola(): void
    {
        $this->conLimitesDeWeb(['statement_timeout' => 5000, 'lock_timeout' => 2000, 'idle_in_transaction_session_timeout' => 300]);
        $app = $this->como('sede_app', 'prueba-app');

        $app->beginTransaction();
        $app->select('select 1');
        usleep(900_000);

        try {
            $app->select('select 1');
            $this->fail('La transacción siguió abierta después del límite.');
        } catch (Throwable $e) {
            $this->assertMatchesRegularExpression('/idle-in-transaction|terminating connection|server closed|SSL connection has been closed|no connection to the server/i', $e->getMessage());
        } finally {
            DB::purge('sede_app');
        }
    }

    public function test_los_procesos_largos_suben_los_limites_con_pgoptions(): void
    {
        // HIPÓTESIS de D7 que esta prueba decide: el controlador `pdo_pgsql` respeta PGOPTIONS
        // (libpq la lee si la cadena de conexión no trae `options`). Si no, hace falta un rol propio.
        $c = Config::get('database.connections.pgsql');
        $codigo = '$p = new PDO(sprintf("pgsql:host=%s;port=%s;dbname=%s", $argv[1], $argv[2], $argv[3]), "sede_app", "prueba-app");'
            .'foreach (["statement_timeout","lock_timeout","idle_in_transaction_session_timeout"] as $n) {'
            .'echo $n, "=", $p->query("select setting from pg_settings where name = \'$n\'")->fetchColumn(), "\n"; }';

        $proceso = new Process(
            [PHP_BINARY, '-r', $codigo, '--', (string) $c['host'], (string) $c['port'], (string) $c['database']],
            null,
            ['PGOPTIONS' => $this->pgoptionsDeProcesos()],
        );
        $proceso->mustRun();

        $this->assertStringContainsString('statement_timeout=120000', $proceso->getOutput());
        $this->assertStringContainsString('lock_timeout=5000', $proceso->getOutput());
        $this->assertStringContainsString('idle_in_transaction_session_timeout=60000', $proceso->getOutput());
    }

    public function test_el_planificador_y_horizon_llevan_los_limites_de_procesos_en_el_compose(): void
    {
        $valores = $this->pgoptionsPorServicio();

        foreach (['scheduler', 'horizon', 'copia'] as $servicio) {
            $this->assertSame(
                $this->pgoptionsDeProcesos(),
                $valores[$servicio] ?? null,
                "{$servicio} debe traer PGOPTIONS con los límites de procesos en compose.yaml.",
            );
        }

        // La web NO lleva PGOPTIONS: usa lo que el rol trae de fábrica.
        $this->assertArrayNotHasKey('app', $valores);
    }

    /**
     * `PGOPTIONS` de cada servicio de `compose.yaml`. Sin analizador YAML (no es dependencia del
     * proyecto): se recorre el archivo por servicios, que es una lista de bloques a dos espacios.
     *
     * @return array<string, string>
     */
    private function pgoptionsPorServicio(): array
    {
        $lineas = file(base_path('../compose.yaml'), FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lineas);

        $enServicios = false;
        $servicio = null;
        $valores = [];

        foreach ($lineas as $linea) {
            if (preg_match('/^\S/', $linea) === 1) {
                $enServicios = $linea === 'services:';
                $servicio = null;
            } elseif ($enServicios && preg_match('/^  ([a-z][a-z0-9_-]*):\s*$/', $linea, $m) === 1) {
                $servicio = $m[1];
            } elseif ($servicio !== null && preg_match('/^\s+PGOPTIONS:\s*"?([^"#]*?)"?\s*(#.*)?$/', $linea, $m) === 1) {
                $valores[$servicio] = $m[1];
            }
        }

        return $valores;
    }

    private function pgoptionsDeProcesos(): string
    {
        return '-c statement_timeout=120000 -c lock_timeout=5000 -c idle_in_transaction_session_timeout=60000';
    }
}
