<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\FileMedia;
use App\Models\Noticia;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * El acceso a las noticias.
 *
 * Qué es «publicada» lo decide `Noticia::scopePublicadas`: el sitio sólo ve por
 * aquí lo que ese filtro deja pasar, así que nadie puede listar un borrador sin
 * saltarse a propósito este contrato.
 */
interface NoticiaRepositoryInterface
{
    /**
     * Las publicadas, de la más reciente a la más antigua, con su imagen precargada.
     *
     * @return LengthAwarePaginator<int, Noticia>
     */
    public function paginarPublicadas(int $porPagina, ?bool $destacada): LengthAwarePaginator;

    /** La noticia publicada con ese slug, con su imagen, o nulo. */
    public function publicadaPorSlug(string $slug): ?Noticia;

    /**
     * Todas —borradores y programadas incluidas—, las más recientes primero.
     *
     * @return LengthAwarePaginator<int, Noticia>
     */
    public function paginarTodas(int $porPagina): LengthAwarePaginator;

    /** La noticia con ese uuid (publicada o no), con su imagen, o nulo. */
    public function porUuid(string $uuid): ?Noticia;

    /** ¿Existe ese slug? Cuenta también las eliminadas: la restricción única las incluye. */
    public function existeSlug(string $slug): bool;

    public function guardar(Noticia $noticia): Noticia;

    /** Archivar (borrado lógico). */
    public function eliminar(Noticia $noticia): void;

    /** Guarda el cambio de metadatos (el `alt`) de la imagen de una noticia. */
    public function guardarImagen(FileMedia $imagen): void;
}
