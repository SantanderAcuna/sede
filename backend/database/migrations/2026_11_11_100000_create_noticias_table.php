<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Las noticias del home (M01-6, RF-01-013).
 *
 * El diccionario de datos declara `noticia` en singular y `contenido_html`; aquí
 * la tabla va en plural, como `politicas` y `tramites`, y el cuerpo es texto
 * plano (`contenido`, párrafos separados por una línea en blanco): guardar HTML
 * de un editor y mostrarlo tal cual en el sitio es un XSS almacenado, y el repo
 * no tiene un saneador de HTML que la titular haya aprobado.
 *
 * Los límites de título (150) y descripción (200) son los del requisito y los
 * impone la propia columna: la base no admite un título de 151 caracteres.
 *
 * La imagen no está aquí: vive en `file_media` (colección `imagen`), con su texto
 * alternativo en `metadata.alt`, y el panel y la API exigen el `alt`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('noticias', function (Blueprint $tabla): void {
            $tabla->id();
            // Identificador público; es también por donde se le adjunta la imagen (`file_media`).
            $tabla->uuid('uuid')->unique();
            $tabla->string('slug', 200)->unique();
            $tabla->string('titulo', 150);
            $tabla->string('descripcion_corta', 200);
            $tabla->text('contenido');
            $tabla->timestampTz('fecha_publicacion');
            $tabla->boolean('destacada')->default(false);
            // Nace como borrador: publicar es una decisión, y exige imagen con `alt`.
            $tabla->boolean('activo')->default(false);
            $tabla->foreignId('autor_id')->nullable()->constrained('users')->nullOnDelete();
            $tabla->timestamps();
            $tabla->softDeletes();
        });

        // La consulta del home (QC-05): las publicadas, de la más reciente a la más antigua.
        DB::statement(
            'CREATE INDEX idx_noticias_home ON noticias (fecha_publicacion DESC, id DESC) WHERE activo = true AND deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('noticias');
    }
};
