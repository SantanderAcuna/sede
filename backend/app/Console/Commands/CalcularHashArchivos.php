<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use stdClass;
use Throwable;

/**
 * Rellena `file_media.hash_sha256` de los archivos que ya existían (Fase 0B, T8).
 *
 * Lee cada archivo de su disco y guarda el SHA-256 de su contenido. Si el archivo no está (o el
 * disco no responde) no inventa nada: deja el hash vacío, lo lista y termina con error para que
 * se note. Es **idempotente**: solo toca las filas sin hash.
 */
final class CalcularHashArchivos extends Command
{
    protected $signature = 'archivos:calcular-hash';

    protected $description = 'Calcula el SHA-256 de los archivos de file_media que aún no lo tienen.';

    public function handle(): int
    {
        $calculados = 0;
        $ausentes = [];

        DB::table('file_media')->whereNull('hash_sha256')->orderBy('uuid')
            ->chunkById(200, function ($filas) use (&$calculados, &$ausentes): void {
                foreach ($filas as $fila) {
                    $hash = $this->hashDe($fila);

                    if ($hash === null) {
                        $ausentes[] = $fila;

                        continue;
                    }

                    DB::table('file_media')->where('uuid', $fila->uuid)->update(['hash_sha256' => $hash]);
                    $calculados++;
                }
            }, 'uuid');

        $this->info("Hash calculado en {$calculados} archivo(s).");

        if ($ausentes === []) {
            return self::SUCCESS;
        }

        $this->error(count($ausentes).' archivo(s) no se pudieron leer; su hash queda vacío:');
        foreach ($ausentes as $fila) {
            $this->line("  - {$fila->uuid} (disco {$fila->disk}, {$fila->path})");
        }

        return self::FAILURE;
    }

    private function hashDe(stdClass $fila): ?string
    {
        try {
            $flujo = Storage::disk((string) $fila->disk)->readStream((string) $fila->path);

            if (! is_resource($flujo)) {
                return null;
            }

            $contexto = hash_init('sha256');
            hash_update_stream($contexto, $flujo);
            fclose($flujo);

            return hash_final($contexto);
        } catch (Throwable) {
            return null;
        }
    }
}
