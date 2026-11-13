<?php

declare(strict_types=1);

namespace Tests\Feature\Operacion;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Roles de la base de datos (Fase 0A, decisión D-0.1).
 *
 * `sede` es el dueño y sólo migra. La aplicación, Horizon y el planificador usan
 * `sede_app` (DML, sin DDL); las copias y los reportes usan `sede_lectura` (SELECT).
 * Un compromiso de la aplicación no debe poder borrar ni alterar el esquema.
 *
 * Sin `RefreshDatabase` a propósito: las conexiones de los otros roles son sesiones
 * distintas y no ven lo que una transacción de prueba no ha confirmado. La tabla de
 * apoyo se crea y se borra dentro de cada prueba.
 */
final class RolesBaseDeDatosTest extends TestCase
{
    private const TABLA = 'zz_prueba_roles';

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Los roles de base de datos sólo existen en PostgreSQL.');
        }

        $this->migrarRoles();
        DB::unprepared('DROP TABLE IF EXISTS '.self::TABLA);
        DB::unprepared('CREATE TABLE '.self::TABLA.' (id serial primary key, dato text)');
        DB::unprepared('INSERT INTO '.self::TABLA." (dato) VALUES ('semilla')");
    }

    protected function tearDown(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared('DROP TABLE IF EXISTS '.self::TABLA);
        }

        parent::tearDown();
    }

    private function migrarRoles(): void
    {
        $migracion = require database_path('migrations/2026_11_19_000000_crear_roles_de_base_de_datos.php');
        $migracion->up();
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

    public function test_la_migracion_se_puede_correr_dos_veces(): void
    {
        $this->migrarRoles();
        $this->migrarRoles();

        $this->assertSame(2, (int) DB::scalar(
            "select count(*) from pg_roles where rolname in ('sede_app','sede_lectura')"
        ));
    }

    public function test_el_comando_aplica_de_nuevo_y_rota_la_clave(): void
    {
        // Se compara el hash guardado y no un inicio de sesión: según el `pg_hba.conf`
        // (la imagen oficial confía en 127.0.0.1) la clave puede no comprobarse.
        $antes = DB::scalar("select rolpassword from pg_authid where rolname = 'sede_app'");

        Config::set('database.roles.app_password', 'clave-rotada');
        $this->artisan('bd:sincronizar-roles')->assertSuccessful();

        $despues = DB::scalar("select rolpassword from pg_authid where rolname = 'sede_app'");
        $this->assertNotSame($antes, $despues);
        $this->assertStringStartsWith('SCRAM-SHA-256$', $despues);

        // Se deja la clave de pruebas como estaba.
        Config::set('database.roles.app_password', null);
        $this->artisan('bd:sincronizar-roles')->assertSuccessful();
    }

    public function test_la_clave_se_comprueba_de_verdad(): void
    {
        // Esta prueba SÓLO vale si el servidor autentica desde este origen. La imagen oficial
        // de PostgreSQL confía en 127.0.0.1 (`trust`) y entonces cualquier clave entra: en
        // ese caso se omite, y no se da por buena una autenticación que no ocurrió. En CI el
        // servicio llega por la red de Docker y sí autentica; en local, usar la IP del
        // contenedor en `DB_HOST` (ver docs/entorno-local.md).
        $mala = $this->como('sede_app', 'clave-incorrecta');
        try {
            $mala->scalar('select 1');
        } catch (QueryException) {
            $this->assertSame(1, (int) $this->como('sede_app', 'prueba-app')->scalar('select 1'));
            $this->expectException(QueryException::class);
            $this->como('sede_lectura', 'prueba-app')->scalar('select 1');

            return;
        }

        $this->markTestSkipped('El servidor confía en este origen (trust): no autentica claves.');
    }

    public function test_los_roles_no_tienen_privilegios_de_administracion(): void
    {
        $filas = DB::select(
            "select rolname, rolsuper, rolcreatedb, rolcreaterole, rolreplication, rolbypassrls, rolcanlogin
               from pg_roles where rolname in ('sede_app','sede_lectura')"
        );

        $this->assertCount(2, $filas);
        foreach ($filas as $f) {
            $this->assertFalse($f->rolsuper, $f->rolname.' no puede ser superusuario');
            $this->assertFalse($f->rolcreatedb, $f->rolname);
            $this->assertFalse($f->rolcreaterole, $f->rolname);
            $this->assertFalse($f->rolreplication, $f->rolname);
            $this->assertFalse($f->rolbypassrls, $f->rolname);
            $this->assertTrue($f->rolcanlogin, $f->rolname.' tiene que poder iniciar sesión');
        }
    }

    public function test_sede_app_hace_dml_pero_no_ddl(): void
    {
        $app = $this->como('sede_app', 'prueba-app');
        $t = self::TABLA;

        $app->insert("insert into $t (dato) values ('nuevo')");
        $app->update("update $t set dato = 'cambiado' where dato = 'nuevo'");
        $this->assertSame(2, (int) $app->scalar("select count(*) from $t"));
        $app->delete("delete from $t where dato = 'cambiado'");

        foreach (['create table zz_no_debe_existir (id int)', "drop table $t", "truncate $t", "alter table $t add column x int"] as $sql) {
            try {
                $app->unprepared($sql);
                $this->fail("sede_app pudo ejecutar: $sql");
            } catch (QueryException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_sede_app_usa_las_secuencias_de_las_tablas_nuevas(): void
    {
        // `serial` crea una secuencia: sin USAGE el INSERT falla aunque la tabla sea escribible.
        $app = $this->como('sede_app', 'prueba-app');
        $app->insert('insert into '.self::TABLA." (dato) values ('con-secuencia')");

        $this->assertTrue(true);
    }

    public function test_sede_lectura_lee_pero_no_escribe(): void
    {
        $lectura = $this->como('sede_lectura', 'prueba-lectura');
        $t = self::TABLA;

        $this->assertSame(1, (int) $lectura->scalar("select count(*) from $t"));

        $this->expectException(QueryException::class);
        $lectura->insert("insert into $t (dato) values ('no')");
    }

    public function test_public_no_puede_crear_objetos_en_el_esquema(): void
    {
        $this->assertFalse((bool) DB::scalar(
            "select has_schema_privilege('public', 'public', 'CREATE')"
        ));
    }

    public function test_sin_secreto_fuera_de_pruebas_la_migracion_falla(): void
    {
        Config::set('database.roles.app_password', null);
        $this->app['env'] = 'production';

        $this->expectException(\RuntimeException::class);
        $this->migrarRoles();
    }
}
