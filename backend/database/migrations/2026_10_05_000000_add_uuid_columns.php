<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

/**
 * Añade columna uuid a las tablas de dominio como identificador público.
 *
 * Se añade como columna normal (no primary key) para no romper las relaciones
 * de clave ajena existentes que usan `id`. El `uuid` sirve como identificador
 * expuesto externamente (en URLs, tokens, recursos API) en lugar del id
 * secuencial, que permite enumeración.
 *
 * R-40: UUID en vez de auto-increment.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Users: nullable + unique (tabla con datos preexistentes en staging)
        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        // Las demás tablas: nullable + unique desde el inicio
        Schema::table('entidads', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        Schema::table('menus', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        Schema::table('tramites', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        Schema::table('ingesta_tramites', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        // Poblar filas existentes con UUID (compatible con PostgreSQL y SQLite en tests)
        foreach (['users'] as $table) {
            DB::table($table)->whereNull('uuid')->update(['uuid' => Uuid::uuid4()->toString()]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });

        Schema::table('entidads', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });

        Schema::table('menus', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });

        Schema::table('tramites', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });

        Schema::table('ingesta_tramites', function (Blueprint $table): void {
            $table->dropColumn('uuid');
        });
    }
};
