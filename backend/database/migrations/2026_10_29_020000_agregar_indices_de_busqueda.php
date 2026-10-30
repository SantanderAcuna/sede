<?php

declare(strict_types=1);

use App\Support\Busqueda\TextoBuscable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los índices de la búsqueda (T-22, H-23).
 *
 * Tolerar erratas («licensia» → «licencia») no se resuelve con `LIKE`: éste sólo
 * encuentra el texto exacto. `pg_trgm` compara por trigramas —grupos de tres
 * letras— y devuelve una similitud entre 0 y 1; con un índice GIN la comparación
 * no recorre la tabla. Con el catálogo real (124 trámites), medido en PostgreSQL
 * 16: la búsqueda actual acierta 0 de 10 consultas con errata; con trigramas,
 * 10 de 10. `BancoDeErratasTest` repite la medición en el PostgreSQL de CI (18);
 * el detalle y sus límites están en `docs/busqueda.md`.
 *
 * ## Qué hace
 *
 * 1. Crea la extensión `pg_trgm`. Es una extensión **de confianza** (`trusted`)
 *    desde PostgreSQL 13: la puede crear el dueño de la base de datos, sin
 *    superusuario. Se comprobó con un rol sin ese privilegio.
 * 2. Añade `busqueda_titulo` a `tramites` y `busqueda_titulo` + `busqueda` a
 *    `politicas`. El título va aparte para poder pesarlo más que el cuerpo.
 * 3. Rellena lo ya guardado con la MISMA función que usan los modelos al guardar
 *    (`TextoBuscable`): una fila sembrada antes de esta migración no puede
 *    quedar fuera de la búsqueda.
 * 4. Crea los índices GIN sobre `gin_trgm_ops`. Se crean DESPUÉS del relleno
 *    para no mantenerlos fila por fila durante él.
 *
 * ## Lo que NO hace
 *
 * No instala `unaccent`: el texto se guarda ya normalizado (ver `TextoBuscable`).
 * Tampoco borra la extensión al revertir: otras consultas podrían haberla
 * adoptado, y quitarla rompería sus índices.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        Schema::table('tramites', function (Blueprint $tabla): void {
            $tabla->text('busqueda_titulo')->default('');
        });

        Schema::table('politicas', function (Blueprint $tabla): void {
            $tabla->text('busqueda_titulo')->default('');
            $tabla->text('busqueda')->default('');
        });

        DB::table('tramites')->select(['id', 'nombre'])->orderBy('id')->chunkById(200, function ($filas): void {
            foreach ($filas as $fila) {
                DB::table('tramites')->where('id', $fila->id)->update([
                    'busqueda_titulo' => TextoBuscable::normalizar((string) $fila->nombre),
                ]);
            }
        });

        DB::table('politicas')->select(['id', 'nombre', 'secciones'])->orderBy('id')->chunkById(50, function ($filas): void {
            foreach ($filas as $fila) {
                /** @var list<array{id?: string, titulo?: string, parrafos?: list<string>}> $secciones */
                $secciones = json_decode((string) $fila->secciones, true) ?? [];

                DB::table('politicas')->where('id', $fila->id)->update([
                    'busqueda_titulo' => TextoBuscable::normalizar((string) $fila->nombre),
                    'busqueda' => TextoBuscable::dePolitica((string) $fila->nombre, $secciones),
                ]);
            }
        });

        DB::statement('CREATE INDEX tramites_busqueda_trgm ON tramites USING gin (busqueda gin_trgm_ops)');
        DB::statement('CREATE INDEX tramites_busqueda_titulo_trgm ON tramites USING gin (busqueda_titulo gin_trgm_ops)');
        DB::statement('CREATE INDEX politicas_busqueda_trgm ON politicas USING gin (busqueda gin_trgm_ops)');
        DB::statement('CREATE INDEX politicas_busqueda_titulo_trgm ON politicas USING gin (busqueda_titulo gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS tramites_busqueda_trgm');
        DB::statement('DROP INDEX IF EXISTS tramites_busqueda_titulo_trgm');
        DB::statement('DROP INDEX IF EXISTS politicas_busqueda_trgm');
        DB::statement('DROP INDEX IF EXISTS politicas_busqueda_titulo_trgm');

        Schema::table('tramites', function (Blueprint $tabla): void {
            $tabla->dropColumn('busqueda_titulo');
        });

        Schema::table('politicas', function (Blueprint $tabla): void {
            $tabla->dropColumn(['busqueda_titulo', 'busqueda']);
        });
    }
};
