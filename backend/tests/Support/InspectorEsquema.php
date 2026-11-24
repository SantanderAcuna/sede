<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

/**
 * Lee el catálogo de PostgreSQL y devuelve los defectos de diseño del esquema
 * `public` como cadenas estables `categoria|objeto` (0D, D1; archivo 0007 de docs/adr).
 *
 * Categorías: `polimorfica`, `json`, `fecha_sin_zona`, `fk_sin_indice`,
 * `enum_sin_check` y `clave_compuesta` (clave candidata de 3 o más columnas,
 * que la 5FN obliga a revisar a mano; las de 2 columnas son binarias y están
 * en 5FN por definición).
 */
final class InspectorEsquema
{
    /** Columnas de texto que por su nombre son una enumeración. */
    private const ENUM_NOMBRES = '^(tipo|type|estado|modalidad|canal|collection|disk|moneda|valor|tiene_costo|consulta_estado)$|(_tipo|_estado|_moneda|_modalidad|_valor)$';

    /** @return list<string> */
    public static function hallazgos(): array
    {
        $todos = [
            ...self::polimorficas(),
            ...self::json(),
            ...self::fechasSinZona(),
            ...self::fkSinIndice(),
            ...self::enumeracionesSinCheck(),
            ...self::clavesCompuestas(),
        ];
        $todos = array_values(array_unique($todos));
        sort($todos);

        return $todos;
    }

    /** @return list<string> */
    private static function polimorficas(): array
    {
        $filas = DB::select(<<<'SQL'
            select t.table_name, regexp_replace(t.column_name, '_type$', '') as prefijo
            from information_schema.columns t
            join information_schema.columns i
              on i.table_schema = t.table_schema
             and i.table_name = t.table_name
             and i.column_name = regexp_replace(t.column_name, '_type$', '') || '_id'
            where t.table_schema = 'public' and t.column_name like '%\_type'
              and t.table_name <> 'migrations'
        SQL);

        return array_values(array_map(fn ($f): string => "polimorfica|{$f->table_name}.{$f->prefijo}", $filas));
    }

    /** @return list<string> */
    private static function json(): array
    {
        $filas = DB::select(<<<'SQL'
            select table_name, column_name from information_schema.columns
            where table_schema = 'public' and data_type in ('json', 'jsonb')
        SQL);

        return array_values(array_map(fn ($f): string => "json|{$f->table_name}.{$f->column_name}", $filas));
    }

    /** @return list<string> */
    private static function fechasSinZona(): array
    {
        $filas = DB::select(<<<'SQL'
            select table_name, column_name from information_schema.columns
            where table_schema = 'public' and data_type = 'timestamp without time zone'
        SQL);

        return array_values(array_map(fn ($f): string => "fecha_sin_zona|{$f->table_name}.{$f->column_name}", $filas));
    }

    /** @return list<string> */
    private static function fkSinIndice(): array
    {
        $filas = DB::select(<<<'SQL'
            select c.conrelid::regclass::text as tabla,
                   (select string_agg(a.attname, ',' order by k.ord)
                      from unnest(c.conkey) with ordinality k(attnum, ord)
                      join pg_attribute a on a.attrelid = c.conrelid and a.attnum = k.attnum) as columnas
            from pg_constraint c
            join pg_namespace n on n.oid = c.connamespace and n.nspname = 'public'
            where c.contype = 'f'
              and not exists (
                  select 1 from pg_index i
                  where i.indrelid = c.conrelid and i.indisvalid
                    and i.indnkeyatts >= cardinality(c.conkey)
                    and (select array_agg(x order by o) from unnest(i.indkey::int2[]) with ordinality u(x, o)
                          where o <= cardinality(c.conkey)) @> c.conkey::int2[]
                    and cardinality(c.conkey) = (select count(*) from unnest(i.indkey::int2[]) with ordinality u(x, o)
                          where o <= cardinality(c.conkey) and x = any (c.conkey))
              )
        SQL);

        return array_values(array_map(fn ($f): string => "fk_sin_indice|{$f->tabla}.{$f->columnas}", $filas));
    }

    /** @return list<string> */
    private static function enumeracionesSinCheck(): array
    {
        $filas = DB::select(<<<'SQL'
            select c.table_name, c.column_name
            from information_schema.columns c
            where c.table_schema = 'public'
              and c.table_name <> 'migrations'
              and c.data_type in ('character varying', 'character', 'text')
              and c.column_name ~ ?
              and not exists (
                  select 1 from pg_constraint k
                  join pg_attribute a on a.attrelid = k.conrelid and a.attnum = any (k.conkey)
                  where k.contype = 'c' and k.conrelid = format('public.%I', c.table_name)::regclass
                    and a.attname = c.column_name
              )
        SQL, [self::ENUM_NOMBRES]);

        return array_values(array_map(fn ($f): string => "enum_sin_check|{$f->table_name}.{$f->column_name}", $filas));
    }

    /** @return list<string> */
    private static function clavesCompuestas(): array
    {
        $filas = DB::select(<<<'SQL'
            select i.indrelid::regclass::text as tabla,
                   (select string_agg(a.attname, ',' order by u.ord)
                      from unnest(i.indkey::int2[]) with ordinality u(attnum, ord)
                      join pg_attribute a on a.attrelid = i.indrelid and a.attnum = u.attnum
                     where u.ord <= i.indnkeyatts) as columnas
            from pg_index i
            join pg_class r on r.oid = i.indrelid
            join pg_namespace n on n.oid = r.relnamespace and n.nspname = 'public'
            where i.indisunique and i.indisvalid and i.indpred is null
              and i.indnkeyatts >= 3 and 0 <> all (i.indkey::int2[])
        SQL);

        return array_values(array_map(fn ($f): string => "clave_compuesta|{$f->tabla}({$f->columnas})", $filas));
    }
}
