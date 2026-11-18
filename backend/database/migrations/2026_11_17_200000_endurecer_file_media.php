<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `file_media`: `model_id` obligatorio y `hash_sha256` (Fase 0B, T8).
 *
 * `model_id` admitía NULL y el índice único lo incluye: en PostgreSQL los NULL no chocan entre
 * sí, así que esas filas eludían la unicidad. Se rellena desde `model_uuid` con el dueño real
 * y se vuelve NOT NULL.
 *
 * Regla de fondo: lo que no se puede inferir con certeza no se arregla ni se borra. Si queda una
 * fila sin dueño, o con un `model_id` que contradice a su `model_uuid`, la migración **se detiene,
 * lista cada fila y no cambia nada** (todo corre en una transacción). `model_uuid` y el campo del
 * contrato se retiran en una entrega posterior (expandir-contraer).
 *
 * `hash_sha256` nace vacío: el hash de los archivos que ya existen se calcula desde el disco con
 * `php artisan archivos:calcular-hash`, no aquí (una migración no debe leer discos).
 */
return new class extends Migration
{
    private const MAXIMO_LISTADO = 50;

    public function up(): void
    {
        DB::transaction(function (): void {
            Schema::table('file_media', function (Blueprint $tabla): void {
                $tabla->char('hash_sha256', 64)->nullable();
            });

            foreach ($this->duenos() as $tipo => $tabla) {
                DB::update(
                    "UPDATE file_media f SET model_id = d.id FROM {$tabla} d
                     WHERE f.model_id IS NULL AND f.model_type = ? AND f.model_uuid = d.uuid",
                    [$tipo],
                );
            }

            $problemas = array_merge($this->filasSinDueno(), $this->filasIncoherentes());

            if ($problemas !== []) {
                throw new RuntimeException($this->mensaje($problemas));
            }

            DB::statement('ALTER TABLE file_media ALTER COLUMN model_id SET NOT NULL');
        });
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE file_media ALTER COLUMN model_id DROP NOT NULL');

        Schema::table('file_media', function (Blueprint $tabla): void {
            $tabla->dropColumn('hash_sha256');
        });
    }

    /**
     * Los tipos de dueño que existen de verdad, con su tabla ya escapada para SQL.
     * El nombre sale del código de cada modelo, nunca de los datos de la fila.
     *
     * @return array<string, string> clase => tabla
     */
    private function duenos(): array
    {
        $tipos = DB::table('file_media')->distinct()->pluck('model_type');
        $dueños = [];

        foreach ($tipos as $tipo) {
            $clase = (string) $tipo;

            if (! class_exists($clase) || ! is_subclass_of($clase, Model::class)) {
                continue;
            }

            $tabla = (new $clase)->getTable();

            if (Schema::hasColumn($tabla, 'uuid') && Schema::hasColumn($tabla, 'id')) {
                $dueños[$clase] = DB::getQueryGrammar()->wrapTable($tabla);
            }
        }

        return $dueños;
    }

    /** @return list<array{string, string}> */
    private function filasSinDueno(): array
    {
        return DB::table('file_media')->whereNull('model_id')->orderBy('uuid')
            ->get(['uuid', 'model_type', 'collection', 'path'])
            ->map(fn (object $f): array => [
                "{$f->uuid} ({$f->model_type}, {$f->collection}, {$f->path})",
                'no se pudo inferir su model_id: el dueño no existe o su tipo no es un modelo',
            ])->all();
    }

    /** @return list<array{string, string}> */
    private function filasIncoherentes(): array
    {
        $problemas = [];

        foreach ($this->duenos() as $tipo => $tabla) {
            $filas = DB::select(
                "SELECT f.uuid, f.collection, f.path, f.model_id, d.id AS id_del_dueno
                 FROM file_media f JOIN {$tabla} d ON d.uuid = f.model_uuid
                 WHERE f.model_type = ? AND f.model_id <> d.id ORDER BY f.uuid",
                [$tipo],
            );

            foreach ($filas as $f) {
                $problemas[] = [
                    "{$f->uuid} ({$tipo}, {$f->collection}, {$f->path})",
                    "model_id {$f->model_id} contradice a model_uuid, cuyo dueño tiene id {$f->id_del_dueno}",
                ];
            }
        }

        return $problemas;
    }

    /** @param  list<array{string, string}>  $problemas */
    private function mensaje(array $problemas): string
    {
        $lineas = array_map(
            static fn (array $p): string => "  - {$p[0]}: {$p[1]}",
            array_slice($problemas, 0, self::MAXIMO_LISTADO),
        );
        $resto = count($problemas) - count($lineas);

        return sprintf(
            "La migración de file_media se detuvo SIN cambiar nada: %d fila(s) sin dueño verificable.\n%s%s\n"
            .'Revise cada fila (¿el dueño fue borrado? ¿la fila es de prueba?), corríjala o retírela con criterio humano y vuelva a migrar.',
            count($problemas),
            implode("\n", $lineas),
            $resto > 0 ? "\n  … y {$resto} más." : '',
        );
    }
};
