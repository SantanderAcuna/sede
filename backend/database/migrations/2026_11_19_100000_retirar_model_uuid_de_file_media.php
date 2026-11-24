<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retira `file_media.model_uuid` (Fase 0B, entrega B; ADR 0005).
 *
 * Desde T8 el dueño de un archivo se reconoce por `model_type` + `model_id` (obligatorio) y la
 * entrega A dejó de escribir y de exponer `model_uuid`. Es la columna que queda sin uso.
 *
 * Lo único que se perdería con información es un `model_uuid` que apunte a un dueño distinto del
 * que dice `model_id`: ese caso detiene la migración, lista las filas y **no cambia nada** (todo
 * corre en una transacción). Un `model_uuid` sin dueño, o vacío, no contradice nada y no la detiene.
 *
 * `down()` devuelve la columna con su índice y la rellena desde el dueño (`model_id` → su `uuid`),
 * así que el retroceso no exige restaurar una copia; las filas escritas por la entrega A, que no la
 * llevaban, quedan igualmente rellenadas.
 */
return new class extends Migration
{
    private const MAXIMO_LISTADO = 50;

    public function up(): void
    {
        DB::transaction(function (): void {
            $problemas = $this->contradicciones($this->duenos());

            if ($problemas !== []) {
                throw new RuntimeException($this->mensaje($problemas));
            }

            // PostgreSQL borra con la columna el índice que depende de ella.
            Schema::table('file_media', function (Blueprint $tabla): void {
                $tabla->dropColumn('model_uuid');
            });
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            Schema::table('file_media', function (Blueprint $tabla): void {
                $tabla->uuid('model_uuid')->nullable()->index();
            });

            foreach ($this->duenos() as $tipo => $dueno) {
                if (! $dueno['uuid']) {
                    continue;
                }

                DB::update(
                    "UPDATE file_media f SET model_uuid = d.uuid
                     FROM {$dueno['tabla']} d WHERE f.model_type = ? AND f.model_id = d.id",
                    [$tipo],
                );
            }
        });
    }

    /**
     * Dueños posibles, leídos de los tipos que hay en la tabla. Solo modelos propios: el tipo sale
     * de una fila de datos y no debe llevar a cargar ni instanciar una clase cualquiera.
     *
     * @return array<string, array{tabla: string, uuid: bool}> clase => dueño
     */
    private function duenos(): array
    {
        $dueños = [];

        foreach (DB::table('file_media')->distinct()->pluck('model_type') as $tipo) {
            $clase = (string) $tipo;

            if (! str_starts_with($clase, 'App\\Models\\') || ! class_exists($clase) || ! is_subclass_of($clase, Model::class)) {
                continue;
            }

            $tabla = (new $clase)->getTable();

            if (Schema::hasColumn($tabla, 'id')) {
                $dueños[$clase] = [
                    'tabla' => DB::getQueryGrammar()->wrapTable($tabla),
                    'uuid' => Schema::hasColumn($tabla, 'uuid'),
                ];
            }
        }

        return $dueños;
    }

    /**
     * @param  array<string, array{tabla: string, uuid: bool}>  $duenos
     * @return list<string>
     */
    private function contradicciones(array $duenos): array
    {
        $problemas = [];

        foreach ($duenos as $tipo => $dueno) {
            if (! $dueno['uuid']) {
                continue;
            }

            $filas = DB::select(
                "SELECT f.uuid, f.collection, f.path, f.model_id, d.id AS id_del_dueno
                 FROM file_media f JOIN {$dueno['tabla']} d ON d.uuid = f.model_uuid
                 WHERE f.model_type = ? AND f.model_id <> d.id ORDER BY f.uuid",
                [$tipo],
            );

            foreach ($filas as $f) {
                $problemas[] = "{$f->uuid} ({$tipo}, {$f->collection}, {$f->path}): model_id {$f->model_id} contradice a model_uuid, cuyo dueño tiene id {$f->id_del_dueno}";
            }
        }

        return $problemas;
    }

    /** @param  list<string>  $problemas */
    private function mensaje(array $problemas): string
    {
        $lineas = array_map(static fn (string $p): string => "  - {$p}", array_slice($problemas, 0, self::MAXIMO_LISTADO));
        $resto = count($problemas) - count($lineas);

        return 'Retiro de model_uuid detenido SIN cambiar nada: '.count($problemas)." fila(s) donde model_uuid contradice a model_id:\n"
            .implode("\n", $lineas)
            .($resto > 0 ? "\n  … y {$resto} más." : '')
            ."\nCorrige model_id (o model_uuid) de esas filas y repite la migración.";
    }
};
