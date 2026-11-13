<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Anula las URL de normativa que el panel rechazaría al guardar la ficha.
 *
 * La carga desde SUIT dejó, p. ej. en T2636, `http://file:///C:/Users/…/Downloads/….pdf`: una ruta
 * del PC de quien la registró, inservible para la ciudadanía y que impedía al gestor guardar el
 * trámite (422 «debe ser una URL válida»). `ConversorFichaJson` ya no las deja pasar; esta
 * migración repara las filas existentes. Solo toca URL que no cumplen `url:http,https`.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tramite_normas')->whereNotNull('url')->orderBy('id')->chunkById(200, function ($filas): void {
            foreach ($filas as $fila) {
                if (! Str::isUrl((string) $fila->url, ['http', 'https'])) {
                    DB::table('tramite_normas')->where('id', $fila->id)->update(['url' => null]);
                }
            }
        });
    }

    public function down(): void
    {
        // Corrección de datos: la ruta local anulada no tiene valor que restaurar.
    }
};
