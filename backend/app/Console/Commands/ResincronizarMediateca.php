<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Mediateca\Resincronizador;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Repite la resincronización de la mediateca sin migrar (0D, D9).
 *
 * Copia de lo viejo a lo nuevo las imágenes principales, su alt, los usos de imágenes en las noticias
 * y `subido_por`. NO toca documentos de política a propósito: «quitar» un documento solo lo desvincula
 * y una copia viejo→nuevo lo resucitaría. Es idempotente; si hay una imagen rota o un archivo sin dueño
 * vinculable, lo lista y no cambia nada.
 */
final class ResincronizarMediateca extends Command
{
    protected $signature = 'mediateca:resincronizar';

    protected $description = 'Vuelve a copiar de lo viejo a lo nuevo las imágenes, su alt y sus usos (no toca documentos)';

    public function handle(Resincronizador $resincronizador): int
    {
        try {
            $resumen = $resincronizador->ejecutar(false);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Imágenes principales corregidas: {$resumen['imagenes']}.");
        $this->info("Usos de imágenes en noticias ajustados: {$resumen['usos']}.");
        $this->info("subido_por ajustado: {$resumen['subido_por']}.");
        $this->line('Los documentos de política no se tocan.');

        return self::SUCCESS;
    }
}
