<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Las diapositivas del carrusel de la portada (banners), administradas desde el panel.
 *
 * La imagen no está aquí: vive en `file_media` (colección `imagen`) con su texto alternativo en
 * `metadata.alt`, igual que las noticias. Nace como borrador: publicar exige imagen con `alt`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $tabla): void {
            $tabla->id();
            $tabla->uuid('uuid')->unique();
            $tabla->string('titulo', 80);
            $tabla->string('descripcion', 200)->nullable();
            // Ruta del sitio (`/transparencia`) o dirección https: la valida el servidor.
            $tabla->string('enlace', 500)->nullable();
            $tabla->unsignedInteger('orden')->default(0);
            $tabla->boolean('activo')->default(false);
            $tabla->timestampTz('vigente_desde')->nullable();
            $tabla->timestampTz('vigente_hasta')->nullable();
            $tabla->timestamps();
            $tabla->softDeletes();
        });

        // La lectura del sitio: las activas y no eliminadas, por orden.
        DB::statement('CREATE INDEX idx_banners_orden ON banners (orden, id) WHERE activo = true AND deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
