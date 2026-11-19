<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use stdClass;
use Throwable;

/**
 * Compara la huella guardada de cada archivo con el contenido que hay hoy en su disco (0D, D10c).
 *
 * Con D10b `hash_sha256` es obligatoria, así que ya no hay huellas que completar: lo que esta
 * verificación descubre es un archivo que cambió o desapareció del disco después de subirse.
 * Solo LEE. No corrige la huella ni borra nada: corregirla sería aceptar el cambio sin que nadie
 * lo vea. Informa TODOS los problemas (no se detiene en el primero) y termina con error si hay
 * alguno, para que se note.
 */
final class VerificarArchivos extends Command
{
    protected $signature = 'archivos:verificar';

    protected $description = 'Compara la huella SHA-256 guardada de cada archivo de file_media con su contenido en disco.';

    public function handle(): int
    {
        $verificados = 0;
        $distintos = [];
        $ausentes = [];

        DB::table('file_media')->orderBy('uuid')
            ->chunkById(200, function ($filas) use (&$verificados, &$distintos, &$ausentes): void {
                foreach ($filas as $fila) {
                    $actual = $this->hashDe($fila);

                    if ($actual === null) {
                        $ausentes[] = $fila;
                    } elseif (! hash_equals((string) $fila->hash_sha256, $actual)) {
                        $distintos[] = $fila;
                    } else {
                        $verificados++;
                    }
                }
            }, 'uuid');

        $this->info("{$verificados} archivo(s) verificado(s) sin diferencias.");

        if ($distintos === [] && $ausentes === []) {
            return self::SUCCESS;
        }

        if ($distintos !== []) {
            $this->error(count($distintos).' archivo(s) con contenido distinto al de su huella:');
            foreach ($distintos as $fila) {
                $this->line("  - {$fila->uuid} (disco {$fila->disk}, {$fila->path})");
            }
        }

        if ($ausentes !== []) {
            $this->error(count($ausentes).' archivo(s) que no se pudieron leer del disco:');
            foreach ($ausentes as $fila) {
                $this->line("  - {$fila->uuid} (disco {$fila->disk}, {$fila->path})");
            }
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
