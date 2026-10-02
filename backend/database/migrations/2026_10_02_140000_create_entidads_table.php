<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entidads', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 200);
            $table->string('sigla', 20)->nullable();
            $table->string('nit', 30)->nullable();
            $table->string('direccion', 240)->nullable();
            $table->string('municipio', 120)->nullable();
            $table->string('departamento', 120)->nullable();
            $table->string('pais', 80)->nullable()->default('Colombia');
            $table->string('telefono', 40)->nullable();
            $table->string('linea_atencion', 40)->nullable();
            $table->string('linea_gratuita', 40)->nullable();
            $table->string('linea_anticorrupcion', 40)->nullable();
            $table->string('correo_atencion', 240)->nullable();
            $table->string('correo_notificaciones_judiciales', 240)->nullable();
            $table->string('horario', 240)->nullable();
            $table->string('codigo_postal', 20)->nullable();
            $table->string('dominio', 240)->nullable();
            $table->string('logo', 500)->nullable();
            // Relaciones como JSON (primer slice - se normaliza en slices posteriores si es necesario)
            $table->json('redes')->nullable();
            $table->json('politicas')->nullable();
            $table->json('datos_por_confirmar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entidads');
    }
};
