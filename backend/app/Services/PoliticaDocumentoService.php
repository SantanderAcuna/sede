<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\FilesMediaServiceInterface;
use App\Models\FileMedia;
use App\Models\Politica;
use App\Models\User;
use App\Support\Acceso\Huella;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Los documentos adjuntos de una política (0D, D9; decisión 4, opción A).
 *
 * Una política puede tener VARIOS documentos: el `oficial` es el que el sitio publica (el más reciente) y los
 * demás son `anterior` (historial a la vista en el panel). Subir uno nuevo pasa al `oficial` actual a `anterior`
 * y publica el nuevo; quitar uno solo lo DESVINCULA: el archivo se conserva en la mediateca (Ley 594/2000, a la
 * espera de lo que diga jurídica) y, si era el oficial, el `anterior` más reciente pasa a oficial.
 *
 * Cada operación bloquea la fila de la política (`FOR UPDATE`) para que dos subidas o quitar simultáneos no
 * choquen con el índice único de «un solo oficial» ni dejen a la política sin publicado por una carrera. El
 * orden dentro de la transacción es fijo: primero se baja al oficial, después se inserta o promueve.
 * Adjuntar y quitar dejan constancia en la bitácora.
 */
final class PoliticaDocumentoService
{
    public function __construct(private readonly FilesMediaServiceInterface $archivos) {}

    /**
     * El `oficial` primero y después los `anterior`, del más reciente al más antiguo.
     *
     * @return Collection<int, FileMedia>
     */
    public function listar(Politica $politica): Collection
    {
        return $politica->documentos();
    }

    public function adjuntar(Politica $politica, UploadedFile $archivo, User $actor): FileMedia
    {
        // Primero el archivo (disco y fila): si la transacción siguiente falla queda un documento «sin uso», no una pérdida.
        $nuevo = $this->archivos->upload(file: $archivo, uso: FileMedia::USO_DOCUMENTO);

        DB::transaction(function () use ($politica, $nuevo, $actor): void {
            Politica::query()->whereKey($politica->getKey())->lockForUpdate()->first();

            DB::table('politica_documentos')
                ->where('politica_id', $politica->id)->where('rol', 'oficial')
                ->update(['rol' => 'anterior']);
            DB::table('politica_documentos')->insert([
                'politica_id' => $politica->id, 'rol' => 'oficial', 'file_media_uuid' => $nuevo->uuid,
            ]);

            Huella::registrar($actor, 'politica_documento_adjuntado', 'politicas', $politica->id, [
                'archivo' => $nuevo->uuid, 'nombre' => $nuevo->original_filename,
            ]);
        });

        return $nuevo;
    }

    /** Desvincula el documento; el archivo NO se borra. */
    public function quitar(Politica $politica, FileMedia $archivo, User $actor): void
    {
        DB::transaction(function () use ($politica, $archivo, $actor): void {
            Politica::query()->whereKey($politica->getKey())->lockForUpdate()->first();

            $rol = DB::table('politica_documentos')
                ->where('politica_id', $politica->id)->where('file_media_uuid', $archivo->uuid)
                ->value('rol');
            if ($rol === null) {
                return;
            }

            DB::table('politica_documentos')
                ->where('politica_id', $politica->id)->where('file_media_uuid', $archivo->uuid)
                ->delete();

            if ($rol === 'oficial') {
                $siguiente = DB::table('politica_documentos as pd')
                    ->join('file_media as f', 'f.uuid', '=', 'pd.file_media_uuid')
                    ->where('pd.politica_id', $politica->id)
                    ->orderByRaw('f.created_at desc nulls last')
                    ->orderBy('f.uuid')
                    ->value('pd.file_media_uuid');

                if ($siguiente !== null) {
                    DB::table('politica_documentos')
                        ->where('politica_id', $politica->id)->where('file_media_uuid', $siguiente)
                        ->update(['rol' => 'oficial']);
                }
            }

            Huella::registrar($actor, 'politica_documento_quitado', 'politicas', $politica->id, [
                'archivo' => $archivo->uuid, 'nombre' => $archivo->original_filename, 'era' => $rol,
            ]);
        });
    }
}
