<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Roles de la base de datos (Fase 0A, decisión D-0.1).
 *
 *  - `sede`          dueño de todo; sólo corre migraciones (desde el despliegue).
 *  - `sede_app`      DML y secuencias. Sin DDL, sin TRUNCATE. Aplicación, Horizon, planificador.
 *  - `sede_lectura`  SELECT. Copia de seguridad y reportes.
 *
 * Idempotente: los roles son del CLÚSTER, no de la base. Se invoca desde la migración
 * (primera vez) y desde `bd:sincronizar-roles` (cada despliegue y al ROTAR una clave:
 * una migración ya corrida no se vuelve a ejecutar, así que no sirve para rotar).
 *
 * Las claves vienen de `database.roles.*`. Sólo en el entorno `testing` hay valores por
 * defecto; en cualquier otro, una clave ausente es un error y no se toca nada.
 *
 * Operación: `CREATE/ALTER ROLE … PASSWORD` envía la clave al servidor en texto; con
 * `log_statement` en `ddl`/`all` queda en el registro de PostgreSQL.
 */
final class RolesBaseDeDatos
{
    private const ATRIBUTOS = 'LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION NOBYPASSRLS';

    public static function aplicar(?string $conexion = null): void
    {
        $bd = DB::connection($conexion);

        if ($bd->getDriverName() !== 'pgsql') {
            return;
        }

        $claves = [
            'sede_app' => self::clave('app_password', 'prueba-app'),
            'sede_lectura' => self::clave('lectura_password', 'prueba-lectura'),
        ];

        foreach ($claves as $rol => $clave) {
            self::fijarRol($bd, $rol, $clave);
        }

        $bd->unprepared(<<<'SQL'
        DO $$
        BEGIN
            EXECUTE format('REVOKE CONNECT, TEMPORARY ON DATABASE %I FROM PUBLIC', current_database());
            EXECUTE format('GRANT CONNECT ON DATABASE %I TO sede_app, sede_lectura', current_database());
        END $$;
        SQL);

        $bd->unprepared(<<<'SQL'
        REVOKE CREATE ON SCHEMA public FROM PUBLIC;
        GRANT USAGE ON SCHEMA public TO sede_app, sede_lectura;

        GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO sede_app;
        GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO sede_app;
        GRANT SELECT ON ALL TABLES IN SCHEMA public TO sede_lectura;
        GRANT SELECT ON ALL SEQUENCES IN SCHEMA public TO sede_lectura;

        -- Lo que cree después el rol que migra hereda los mismos permisos.
        ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO sede_app;
        ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO sede_app;
        ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO sede_lectura;
        ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON SEQUENCES TO sede_lectura;
        SQL);
    }

    private static function fijarRol(Connection $bd, string $rol, string $clave): void
    {
        $existe = (bool) $bd->scalar('select 1 from pg_roles where rolname = ?', [$rol]);
        $verbo = $existe ? 'ALTER ROLE %I WITH' : 'CREATE ROLE %I WITH';

        // `format` del servidor cita identificador (%I) y literal (%L): sin concatenar a mano.
        $sql = $bd->scalar(
            "select format('$verbo PASSWORD %L ".self::ATRIBUTOS."', ?::text, ?::text)",
            [$rol, $clave],
        );
        $bd->unprepared((string) $sql);
    }

    private static function clave(string $nombre, string $porDefectoEnPruebas): string
    {
        $valor = config("database.roles.$nombre");

        if (is_string($valor) && $valor !== '') {
            return $valor;
        }

        if (app()->environment('testing')) {
            return $porDefectoEnPruebas;
        }

        throw new RuntimeException(
            'Falta database.roles.'.$nombre.' (variable DB_'.strtoupper(substr($nombre, 0, -9)).'_PASSWORD). '
            .'Sin ella no se puede crear el rol de base de datos.'
        );
    }
}
