<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\ArchivoEnUso;
use App\Models\FileMedia;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Borra las imágenes de contenido que ninguna noticia usa (0D, D9).
 *
 * Las sube el editor del panel y, si el autor las quita del cuerpo o abandona el borrador, quedan sin dueño
 * útil. Solo se tocan las de la colección «contenido» que NO aparecen en ninguna de las cuatro relaciones
 * (imagen principal de noticia, banner, imágenes incrustadas, documentos de política) y que llevan más de 7
 * días sin tocarse: `coalesce(updated_at, created_at)`, porque al desvincularlas se renueva `updated_at`. Sin
 * ninguna fecha, nunca se borran. Las huérfanas de `imagen` solo se LISTAN y los documentos oficiales no se
 * tocan jamás (Ley 594/2000). El corte se calcula en PHP para que no dependa del reloj de la base.
 * Se borra primero la fila (la base impide borrar lo que alguien acaba de usar) y después el disco.
 */
final class LimpiarArchivosSinUso extends Command
{
    private const DIAS = 7;

    protected $signature = 'archivos:limpiar-sin-uso {--simular : Solo lista lo que borraría}';

    protected $description = 'Borra las imágenes de contenido sin uso desde hace más de 7 días y lista las huérfanas de imagen.';

    public function handle(): int
    {
        $corte = now()->subDays(self::DIAS);
        $simular = (bool) $this->option('simular');
        $borrados = 0;
        $enUso = 0;

        $candidatos = $this->sinUso()->where('collection', 'contenido')
            ->whereRaw('coalesce(updated_at, created_at) < ?', [$corte])
            ->orderBy('uuid')->pluck('uuid');

        foreach ($candidatos as $uuid) {
            if ($simular) {
                $this->line("  se borraría: {$uuid}");

                continue;
            }

            $archivo = FileMedia::query()->where('uuid', $uuid)->first();
            if ($archivo === null) {
                continue;
            }

            try {
                $archivo->deleteFully();
                $borrados++;
            } catch (ArchivoEnUso) {
                $enUso++;
            }
        }

        $this->info($simular
            ? 'Simulación: '.count($candidatos).' archivo(s) se borrarían.'
            : "Borrados: {$borrados}. Conservados por quedar en uso: {$enUso}.");

        $huerfanas = $this->sinUso()->where('collection', 'imagen')->orderBy('uuid')->pluck('uuid');
        if ($huerfanas->isNotEmpty()) {
            $this->warn($huerfanas->count().' imagen(es) de noticia o banner sin uso (no se borran, revísalas):');
            foreach ($huerfanas as $uuid) {
                $this->line("  - {$uuid}");
            }
        }

        return self::SUCCESS;
    }

    /** @return Builder */
    private function sinUso()
    {
        return DB::table('file_media')
            ->whereNotExists(fn ($q) => $q->from('noticias')->whereColumn('noticias.imagen_principal_uuid', 'file_media.uuid'))
            ->whereNotExists(fn ($q) => $q->from('banners')->whereColumn('banners.imagen_uuid', 'file_media.uuid'))
            ->whereNotExists(fn ($q) => $q->from('noticia_imagenes')->whereColumn('noticia_imagenes.file_media_uuid', 'file_media.uuid'))
            ->whereNotExists(fn ($q) => $q->from('politica_documentos')->whereColumn('politica_documentos.file_media_uuid', 'file_media.uuid'))
            ->select('uuid');
    }
}
