<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las cinco políticas de la sede, editables desde el panel (T-20, H-21).
 *
 * El texto vive en base de datos y no en el código del sitio: la Entidad lo
 * aprueba y lo cambia sin pasar por un despliegue. Mientras `es_ejemplo` sea
 * verdadero, el contenido es un texto de ejemplo y el sitio lo dice; al cargar
 * el texto aprobado se desmarca y la política exige su acto administrativo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('politicas', function (Blueprint $tabla): void {
            $tabla->id();
            // Identificador público; es también por donde se le adjuntan archivos (`file_media`).
            $tabla->uuid('uuid')->unique();
            $tabla->string('slug', 100)->unique();
            $tabla->string('nombre', 200);
            $tabla->unsignedSmallInteger('version')->default(1);
            $tabla->date('vigente_desde')->nullable();
            // El acto administrativo que adopta la política. Nulos mientras sea un ejemplo.
            $tabla->string('acto_tipo', 60)->nullable();
            $tabla->string('acto_numero', 60)->nullable();
            $tabla->date('acto_fecha')->nullable();
            // [{id, titulo, parrafos: [texto, ...]}, ...]
            $tabla->json('secciones');
            $tabla->boolean('es_ejemplo')->default(true);
            $tabla->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('politicas');
    }
};
