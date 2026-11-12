<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Exceptions\NoticiaIncompleta;
use App\Models\Noticia;
use App\Support\Noticias\FiltrosNoticiasPanel;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Los casos de uso de las noticias (M01-6, RF-01-013).
 *
 * La regla que importa vive aquí: una noticia sólo se publica si está completa
 * (`Noticia::problemas()`: título, descripción, contenido, imagen y su `alt`). Está
 * en el servicio y no en el controlador para que cualquier entrada futura —un
 * comando, una importación— pase por la misma puerta.
 */
interface NoticiaServiceInterface
{
    /**
     * @return LengthAwarePaginator<int, Noticia>
     */
    public function publicadas(int $porPagina, ?bool $destacada): LengthAwarePaginator;

    public function publicadaPorSlug(string $slug): ?Noticia;

    /**
     * @return LengthAwarePaginator<int, Noticia>
     */
    public function todas(int $porPagina, FiltrosNoticiasPanel $filtros): LengthAwarePaginator;

    public function porUuid(string $uuid): ?Noticia;

    /**
     * Crea la noticia con un slug único derivado del título.
     *
     * @param  array<string, mixed>  $datos  Ya validados por `GuardarNoticiaRequest`
     *
     * @throws NoticiaIncompleta si se pide publicarla y no está completa
     */
    public function crear(array $datos, ?int $autorId): Noticia;

    /**
     * Edita la noticia. El slug no cambia. Si `imagen_alt` viene, cambia el texto
     * alternativo de la imagen ya subida.
     *
     * @param  array<string, mixed>  $datos  Ya validados por `GuardarNoticiaRequest`
     *
     * @throws NoticiaIncompleta si queda publicada y no está completa
     */
    public function actualizar(Noticia $noticia, array $datos): Noticia;

    /** Sube la imagen con su `alt` y reemplaza la anterior: hay una sola por noticia. */
    public function guardarImagen(Noticia $noticia, UploadedFile $imagen, string $alt): Noticia;

    public function eliminar(Noticia $noticia): void;
}
