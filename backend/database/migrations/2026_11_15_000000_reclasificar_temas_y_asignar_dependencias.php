<?php

declare(strict_types=1);

use App\Support\Tramites\DependenciaDeTramite;
use App\Support\Tramites\TemaDeTramite;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige la clasificación inicial de los trámites ya cargados y les asigna dependencia.
 *
 * - **Tema:** se vuelve a calcular con la regla corregida (`TemaDeTramite`) solo en los trámites que
 *   nadie ha editado (`updated_at` igual a `created_at`): lo que una persona fijó en el panel no
 *   se pisa. Un tema que ya coincide no se toca.
 * - **Dependencia:** se asigna solo donde está vacía y los puntos de atención del trámite la nombran
 *   sin ambigüedad (`DependenciaDeTramite`). Una dependencia elegida en el panel nunca se cambia.
 *
 * Los trámites sin evidencia suficiente quedan sin dependencia: los asigna la Entidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        $temas = DB::table('tramite_categorias')->pluck('id', 'slug')->all();
        $dependencias = DB::table('dependencias')->pluck('id', 'nombre')->all();

        DB::table('tramites')->whereNull('deleted_at')->orderBy('id')->chunkById(100, function ($filas) use ($temas, $dependencias): void {
            foreach ($filas as $fila) {
                $cambios = [];

                $tema = $temas[TemaDeTramite::slugPara((string) $fila->nombre)] ?? null;
                $sinEditar = $fila->updated_at === $fila->created_at;
                if ($sinEditar && $tema !== null && (int) $fila->categoria_id !== (int) $tema) {
                    $cambios['categoria_id'] = $tema;
                }

                if ($fila->dependencia_id === null) {
                    $puntos = DB::table('tramite_puntos_atencion')->where('tramite_id', $fila->id)->pluck('nombre')->map(static fn ($n): string => (string) $n)->all();
                    $dependencia = DependenciaDeTramite::para($puntos);
                    if ($dependencia !== null && isset($dependencias[$dependencia])) {
                        $cambios['dependencia_id'] = $dependencias[$dependencia];
                    }
                }

                if ($cambios !== []) {
                    // Sin tocar `updated_at`: esta corrección no cuenta como una edición de la Entidad.
                    DB::table('tramites')->where('id', $fila->id)->update($cambios);
                }
            }
        });
    }

    public function down(): void
    {
        // Es una corrección de datos: no hay un estado anterior que restaurar con certeza.
    }
};
