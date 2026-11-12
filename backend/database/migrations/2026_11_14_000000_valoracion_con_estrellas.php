<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La valoración de una ficha admite dos cosas a la vez (el módulo 03 dice que «ambas aplican»): la
 * valoración rápida FÁCIL/DIFÍCIL (RF-B2-033/034) y la encuesta de 1 a 5 estrellas (RF-B1-094). Se
 * añade `estrellas` y el `valor` pasa a ser anulable, porque una valoración puede traer sólo las
 * estrellas, sólo el FÁCIL/DIFÍCIL, o las dos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramite_valoraciones', function (Blueprint $t): void {
            $t->unsignedTinyInteger('estrellas')->nullable()->after('valor');
            $t->string('valor', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tramite_valoraciones', function (Blueprint $t): void {
            $t->dropColumn('estrellas');
            $t->string('valor', 10)->nullable(false)->change();
        });
    }
};
