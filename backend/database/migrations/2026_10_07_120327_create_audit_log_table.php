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
        Schema::create('audit_log', function (Blueprint $tabla): void {
            $tabla->id();
            $tabla->string('accion', 50);
            $tabla->string('recurso', 255);
            $tabla->unsignedBigInteger('recurso_id')->nullable();
            $tabla->json('cambios')->nullable();
            $tabla->string('ip_origen', 45)->nullable();
            $tabla->string('user_agent', 500)->nullable();
            $tabla->foreignId('causer_id')->nullable()->constrained('users')->nullOnDelete();
            $tabla->string('causer_type')->nullable();
            $tabla->dateTime('created_at')->useCurrent();

            $tabla->index(['causer_id', 'created_at']);
            $tabla->index(['recurso', 'created_at']);
            $tabla->index(['accion', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
