<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Tipo de procedimiento que el Anexo 2.1 expone: `tramites`, `opa` o
 * `consultas`. La columna está aquí —y no en la tabla inicial— porque la fuente
 * oficial (SUIT) sólo clasifica trámites y la Entidad aún no ha separado los
 * tres grupos. Cuando lo haga, este valor se actualiza y el catálogo público
 * puede filtrar por grupo sin tocar la consulta.
 *
 * El valor por defecto es `tramites`, que es el que ya siembra el seeder: así
 * un trámite existente sin la columna recibe el mismo grupo al que ya
 * pertenecía en la práctica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramites', function (Blueprint $table): void {
            $table->string('type', 20)->default('tramites')->after('id');
            $table->index('type');
        });

        // Backfill explícito para los trámites ya creados: la cláusula `default`
        // sólo aplica a inserciones nuevas, no a filas existentes.
        \DB::table('tramites')->whereNull('type')->update(['type' => 'tramites']);
    }

    public function down(): void
    {
        Schema::table('tramites', function (Blueprint $table): void {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};