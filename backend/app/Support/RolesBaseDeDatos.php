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
 *                    `audit_log` es de solo inserción: allí sólo INSERT y SELECT (0D, D6).
 *                    Trae límites de tiempo por omisión (0D, D7): ver `database.limites`.
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

        // La bitácora es de solo inserción (0D, D6). Va AQUÍ y no en una migración porque el
        // `GRANT … ON ALL TABLES` de arriba corre en cada despliegue y la volvería a abrir.
        $bd->unprepared(<<<'SQL'
        DO $$
        BEGIN
            IF to_regclass('public.audit_log') IS NOT NULL THEN
                REVOKE UPDATE, DELETE, TRUNCATE ON public.audit_log FROM sede_app;
            END IF;
        END $$;
        SQL);

        self::fijarLimites($bd);
    }

    /**
     * Límites de tiempo de `sede_app` (0D, D7), como valor por omisión del rol EN ESTA BASE.
     *
     * `IN DATABASE`: los roles son del clúster y sin eso otra base del mismo servidor heredaría los
     * límites de la sede. Va aquí y no en una migración por lo mismo que la bitácora: `aplicar()` corre
     * en cada despliegue y deja el valor vigente aunque alguien lo haya cambiado a mano. Sólo afecta a
     * las sesiones NUEVAS: las ya abiertas (Horizon, el planificador) los toman al reiniciarse.
     */
    private static function fijarLimites(Connection $bd): void
    {
        $limites = config('database.limites.web');

        if (! is_array($limites)) {
            return;
        }

        // Lista cerrada en código: el nombre del parámetro nunca viene de fuera.
        foreach (['statement_timeout', 'lock_timeout', 'idle_in_transaction_session_timeout'] as $parametro) {
            $milisegundos = max(0, (int) ($limites[$parametro] ?? 0));

            // `format` cita el identificador (%I) y el literal (%L) en el servidor, como en `fijarRol`.
            $sql = $bd->scalar(
                "select format('ALTER ROLE sede_app IN DATABASE %I SET %s = %L', current_database(), ?::text, ?::text)",
                [$parametro, $milisegundos.'ms'],
            );
            $bd->statement((string) $sql);
        }
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
        // El SQL lo cita el propio servidor (`format` con %I/%L); no hay concatenación de entrada.
        // @phpstan-ignore argument.type
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
