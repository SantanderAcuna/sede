<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla centralizada de archivos subidos.
 *
 * Sistema polimórfico: un archivo puede pertenecer a cualquier modelo (trámite,
 * entidad, usuario, etc.) sin necesidad de tablas intermedias. Cada registro
 * representa un archivo en disco con sus metadatos.
 *
 * R-52: FilesMedia polymorphic.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_media', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('model_uuid')->nullable()->index();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id')->nullable();

            // Identificación del archivo
            $table->string('collection', 64);
            $table->string('filename', 255);
            $table->string('original_filename', 255);
            $table->string('mime_type', 128);
            $table->unsignedBigInteger('size'); // bytes

            // Localización en disco
            $table->string('disk', 32)->default('public');
            $table->string('path', 512);

            // Metadatos opcionales
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Índices para consulta eficiente
            $table->index(['model_type', 'model_id', 'collection']);
            $table->unique(['model_type', 'model_id', 'collection', 'filename'],
                'file_media_model_collection_filename_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_media');
    }
};
