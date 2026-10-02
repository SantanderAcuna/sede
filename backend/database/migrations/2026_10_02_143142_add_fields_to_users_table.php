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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('estado', ['activo', 'inactivo', 'bloqueado', 'pendiente'])
                ->default('activo')
                ->after('password');
            $table->boolean('mfa_habilitado')->default(false)->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['estado', 'mfa_habilitado']);
        });
    }
};
