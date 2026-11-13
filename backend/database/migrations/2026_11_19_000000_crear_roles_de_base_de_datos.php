<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Roles de la base de datos (Fase 0A, decisión D-0.1).
 *
 *  - `sede`          dueño de todo; sólo corre migraciones (desde el despliegue).
 *  - `sede_app`      DML (SELECT/INSERT/UPDATE/DELETE) y secuencias. Sin DDL, sin TRUNCATE.
 *                    Lo usan la aplicación, Horizon y el planificador.
 *  - `sede_lectura`  SELECT. Lo usan la copia de seguridad y los reportes.
 *
 * Los roles son del CLÚSTER, no de la base: por eso la migración es idempotente (otra
 * base del mismo servidor, o un `migrate:fresh`, ya los encuentra creados) y `down()`
 * no los borra (borrar un rol compartido rompería a quien lo use en otra base).
 *
 * Las claves vienen de la configuración (`database.roles.*`). Sólo en el entorno
 * `testing` hay valores por defecto; en cualquier otro, una clave ausente es un error
 * y la migración se detiene antes de tocar nada.
 *
 * Nota de operación: `CREATE/ALTER ROLE … PASSWORD` envía la clave al servidor en texto;
 * con `log_statement` en `ddl` o `all` queda en el registro de PostgreSQL. Mantener
 * `log_statement` en `none`/`mod` o pasar el hash SCRAM ya calculado en la variable.
 */
return new class extends Migration
{
    private const ATRIBUTOS = 'LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION NOBYPASSRLS';

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $claves = [
            'sede_app' => $this->clave('app_password', 'prueba-app'),
            'sede_lectura' => $this->clave('lectura_password', 'prueba-lectura'),
        ];

        foreach ($claves as $rol => $clave) {
            $existe = (bool) DB::scalar('select 1 from pg_roles where rolname = ?', [$rol]);
            $verbo = $existe ? 'ALTER ROLE %I WITH' : 'CREATE ROLE %I WITH';
            // `format` del servidor cita identificador (%I) y literal (%L): sin concatenar a mano.
            $sql = DB::scalar("select format('$verbo PASSWORD %L ".self::ATRIBUTOS."', ?::text, ?::text)", [$rol, $clave]);
            DB::unprepared($sql);
        }

        // La base: por defecto PUBLIC puede conectarse. Sólo los tres roles.
        DB::unprepared(<<<'SQL'
        DO $$
        BEGIN
            EXECUTE format('REVOKE CONNECT, TEMPORARY ON DATABASE %I FROM PUBLIC', current_database());
            EXECUTE format('GRANT CONNECT ON DATABASE %I TO sede_app, sede_lectura', current_database());
        END $$;
        SQL);

        DB::unprepared(<<<'SQL'
        REVOKE CREATE ON SCHEMA public FROM PUBLIC;
        GRANT USAGE ON SCHEMA public TO sede_app, sede_lectura;

        GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO sede_app;
        GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO sede_app;
        GRANT SELECT ON ALL TABLES IN SCHEMA public TO sede_lectura;
        GRANT SELECT ON ALL SEQUENCES IN SCHEMA public TO sede_lectura;

        -- Lo que se cree después (por el rol que migra) hereda los mismos permisos.
        ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO sede_app;
        ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO sede_app;
        ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO sede_lectura;
        ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON SEQUENCES TO sede_lectura;
        SQL);
    }

    public function down(): void
    {
        // Deliberadamente vacío: los roles son del clúster y pueden estar en uso por otra base.
    }

    private function clave(string $nombre, string $porDefectoEnPruebas): string
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
};
