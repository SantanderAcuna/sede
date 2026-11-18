<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Filas de dueños y de `file_media` escritas con el constructor de consultas (T8).
 *
 * No pasan por el servicio ni por los modelos a propósito: los casos que importan aquí son
 * justo los que el servicio nunca produce (filas viejas, sin dueño, incoherentes).
 */
trait CreaFilasDeArchivos
{
    /** @return array{id: int, uuid: string} */
    private function banner(): array
    {
        $uuid = (string) Str::uuid();
        $id = (int) DB::table('banners')->insertGetId([
            'uuid' => $uuid, 'titulo' => 'Banner de prueba', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return ['id' => $id, 'uuid' => $uuid];
    }

    /** @return array{id: int, uuid: string} */
    private function politica(): array
    {
        $uuid = (string) Str::uuid();
        $id = (int) DB::table('politicas')->insertGetId([
            'uuid' => $uuid, 'slug' => 'politica-'.Str::random(8), 'nombre' => 'Política de prueba',
            'secciones' => '[]', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return ['id' => $id, 'uuid' => $uuid];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return string uuid de la fila creada
     */
    private function fila(array $extra = []): string
    {
        $uuid = (string) Str::uuid();
        $archivo = Str::uuid()->toString().'.png';

        DB::table('file_media')->insert(array_merge([
            'uuid' => $uuid,
            'model_type' => 'App\\Models\\Banner',
            'model_id' => null,
            'model_uuid' => null,
            'collection' => 'imagen',
            'filename' => $archivo,
            'original_filename' => 'foto.png',
            'mime_type' => 'image/png',
            'size' => 4,
            'disk' => 'local',
            'path' => "file_media/prueba/{$archivo}",
            'created_at' => now(),
            'updated_at' => now(),
        ], $extra));

        return $uuid;
    }
}
