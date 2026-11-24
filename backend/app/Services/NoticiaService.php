<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\NoticiaRepositoryInterface;
use App\Contracts\Services\FilesMediaServiceInterface;
use App\Contracts\Services\NoticiaServiceInterface;
use App\Exceptions\NoticiaIncompleta;
use App\Models\FileMedia;
use App\Models\Noticia;
use App\Support\Html\SanitizadorHtml;
use App\Support\Noticias\ContenidoNoticia;
use App\Support\Noticias\FiltrosNoticiasPanel;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class NoticiaService implements NoticiaServiceInterface
{
    public function __construct(
        private readonly NoticiaRepositoryInterface $noticias,
        private readonly FilesMediaServiceInterface $archivos,
        private readonly SanitizadorHtml $sanitizador,
    ) {}

    public function publicadas(int $porPagina, ?bool $destacada): LengthAwarePaginator
    {
        return $this->noticias->paginarPublicadas($porPagina, $destacada);
    }

    public function publicadaPorSlug(string $slug): ?Noticia
    {
        return $this->noticias->publicadaPorSlug($slug);
    }

    public function todas(int $porPagina, FiltrosNoticiasPanel $filtros): LengthAwarePaginator
    {
        return $this->noticias->paginarTodas($porPagina, $filtros);
    }

    public function porUuid(string $uuid): ?Noticia
    {
        return $this->noticias->porUuid($uuid);
    }

    public function crear(array $datos, ?int $autorId): Noticia
    {
        unset($datos['imagen_alt']);
        $datos = $this->conContenidoLimpio($datos);

        $noticia = new Noticia($datos);
        $noticia->slug = $this->slugUnico((string) $datos['titulo']);
        $noticia->activo = (bool) ($datos['activo'] ?? false);
        $noticia->destacada = (bool) ($datos['destacada'] ?? false);
        $noticia->autor_id = $autorId;

        $this->exigirCompleta($noticia);
        $usadas = $this->imagenesDelCuerpo($datos);

        return DB::transaction(function () use ($noticia, $usadas): Noticia {
            $guardada = $this->noticias->guardar($noticia);
            if ($usadas !== null) {
                $this->sincronizarUsos($guardada, $usadas);
            }

            return $guardada->refresh();
        });
    }

    public function actualizar(Noticia $noticia, array $datos): Noticia
    {
        $alt = $datos['imagen_alt'] ?? null;
        unset($datos['imagen_alt']);
        $datos = $this->conContenidoLimpio($datos);

        $noticia->fill($datos);

        // El texto alternativo pertenece al uso de la imagen (la noticia), no al archivo.
        if ($alt !== null && $noticia->imagenPrincipal !== null) {
            $noticia->imagen_principal_alt = trim((string) $alt);
        }

        // Antes de guardar nada: si queda publicada e incompleta, no cambia ni el texto ni el alt.
        $this->exigirCompleta($noticia);

        $usadas = $this->imagenesDelCuerpo($datos);

        DB::transaction(function () use ($noticia, $usadas): void {
            $this->noticias->guardar($noticia);
            if ($usadas !== null) {
                $this->sincronizarUsos($noticia, $usadas);
            }
        });

        return $noticia->refresh();
    }

    public function guardarImagen(Noticia $noticia, UploadedFile $imagen, string $alt): Noticia
    {
        $anterior = $noticia->imagenPrincipal;

        // 1) Primero se sube la nueva: si falla, la anterior sigue en su sitio.
        $nueva = $this->archivos->upload(
            file: $imagen,
            uso: FileMedia::USO_IMAGEN,
        );

        // 2) Transacción corta, sin E/S de archivos: el vínculo nuevo y su alt (lo viejo ya quedó escrito por `upload`).
        DB::transaction(function () use ($noticia, $nueva, $alt): void {
            $noticia->imagen_principal_uuid = $nueva->uuid;
            $noticia->imagen_principal_alt = $alt;
            $noticia->save();
        });

        // 3) Después del commit, se borra la anterior. Si esto falla queda un archivo sin uso, que la limpieza
        //    lista; la noticia ya tiene su imagen nueva y no se pierde nada.
        if ($anterior !== null && $anterior->uuid !== $nueva->uuid) {
            try {
                $this->archivos->delete($anterior->uuid);
            } catch (Throwable $e) {
                Log::warning('No se pudo borrar la imagen anterior de la noticia.', ['archivo' => $anterior->uuid, 'exception' => $e]);
            }
        }

        return $noticia->refresh()->unsetRelation('imagenPrincipal');
    }

    public function eliminar(Noticia $noticia): void
    {
        $this->noticias->eliminar($noticia);
    }

    /**
     * El cuerpo se sanea AL ESCRIBIR: es el control principal contra XSS almacenado, porque
     * quien llama a la API directamente no pasa por el editor. Al leer se vuelve a sanear.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function conContenidoLimpio(array $datos): array
    {
        if (isset($datos['contenido']) && is_string($datos['contenido'])) {
            $datos['contenido'] = ContenidoNoticia::limpio($datos['contenido'], $this->sanitizador);
        }

        return $datos;
    }

    /**
     * Las imágenes que el cuerpo (ya saneado) incrusta, o `null` si esta escritura no toca el cuerpo.
     * Cada una debe existir y ser de la colección «contenido»: si no, 422 y no se guarda nada.
     *
     * @param  array<string, mixed>  $datos
     * @return list<string>|null
     */
    private function imagenesDelCuerpo(array $datos): ?array
    {
        if (! isset($datos['contenido']) || ! is_string($datos['contenido'])) {
            return null;
        }

        $usadas = $this->sanitizador->imagenesUsadas($datos['contenido']);
        if ($usadas === []) {
            return [];
        }

        $validas = DB::table('file_media')->whereIn('uuid', $usadas)->where('uso', FileMedia::USO_CONTENIDO)->pluck('uuid')->all();
        $faltan = array_values(array_diff($usadas, $validas));
        if ($faltan !== []) {
            throw ValidationException::withMessages([
                'contenido' => ['El contenido incrusta imágenes que no existen o no se subieron desde el editor: '.implode(', ', $faltan).'.'],
            ]);
        }

        return $usadas;
    }

    /**
     * Deja `noticia_imagenes` igual al conjunto exacto de imágenes del cuerpo. Lo que se desvincula toca
     * `file_media.updated_at` (hora de PHP): la limpieza cuenta sus 7 días desde que quedó sin uso.
     *
     * @param  list<string>  $usadas
     */
    private function sincronizarUsos(Noticia $noticia, array $usadas): void
    {
        $actuales = DB::table('noticia_imagenes')->where('noticia_id', $noticia->id)->pluck('file_media_uuid')->all();
        $quitar = array_values(array_diff($actuales, $usadas));
        $poner = array_values(array_diff($usadas, $actuales));

        if ($quitar !== []) {
            DB::table('noticia_imagenes')->where('noticia_id', $noticia->id)->whereIn('file_media_uuid', $quitar)->delete();
            DB::table('file_media')->whereIn('uuid', $quitar)->update(['updated_at' => now()]);
        }

        if ($poner !== []) {
            try {
                // Punto de guardado propio: si otro proceso borró el archivo en medio, el error no aborta la transacción.
                DB::transaction(fn () => DB::table('noticia_imagenes')->insert(array_map(
                    static fn (string $u): array => ['noticia_id' => $noticia->id, 'file_media_uuid' => $u, 'created_at' => now()],
                    $poner,
                )));
            } catch (QueryException $e) {
                if (($e->errorInfo[0] ?? null) === '23503') {
                    throw ValidationException::withMessages(['contenido' => ['Una imagen del contenido dejó de existir. Vuelve a subirla.']]);
                }

                throw $e;
            }
        }
    }

    /**
     * @throws NoticiaIncompleta
     */
    private function exigirCompleta(Noticia $noticia): void
    {
        if (! $noticia->activo) {
            return;
        }

        $problemas = $noticia->problemas();
        if ($problemas !== []) {
            throw new NoticiaIncompleta($problemas);
        }
    }

    /** Un slug que no choque con otro —ni con uno eliminado: la restricción única lo cuenta—. */
    private function slugUnico(string $titulo): string
    {
        $base = Str::limit(Str::slug($titulo), 190, '') ?: 'noticia';
        $slug = $base;

        for ($n = 2; $this->noticias->existeSlug($slug); $n++) {
            $slug = $base.'-'.$n;
        }

        return $slug;
    }
}
