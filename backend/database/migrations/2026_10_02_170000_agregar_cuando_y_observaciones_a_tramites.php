<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Los dos bloques del visor que la Sede todavía no publicaba.
 *
 * `cuando_se_puede_realizar` es el que el visor responde como «¿Cuándo se puede
 * realizar?». No lo publica como una frase sino como tres hechos distintos, y
 * por eso son tres columnas y no una: `fecha_cualquiera` es un booleano que
 * responde a 119 de los 123 trámites, `cuando_se_puede_realizar` guarda la
 * condición en prosa de los cuatro que la declaran, y `url_calendario` el
 * calendario externo del único que lo enlaza. Convertir los tres en un texto
 * habría dejado un dato que ya no se puede auditar contra la fuente.
 *
 * `observaciones_resultado` es la aclaración que el visor publica **bajo el
 * resultado** —de qué depende el plazo— y que hasta ahora se descartaba.
 *
 * Las cuatro son anulables: la fuente no las declara para todos los trámites.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramites', function (Blueprint $table): void {
            $table->boolean('fecha_cualquiera')->nullable()->after('resultado');
            $table->text('cuando_se_puede_realizar')->nullable()->after('fecha_cualquiera');
            $table->string('url_calendario', 500)->nullable()->after('cuando_se_puede_realizar');
            $table->text('observaciones_resultado')->nullable()->after('url_calendario');
        });
    }

    public function down(): void
    {
        Schema::table('tramites', function (Blueprint $table): void {
            $table->dropColumn([
                'fecha_cualquiera',
                'cuando_se_puede_realizar',
                'url_calendario',
                'observaciones_resultado',
            ]);
        });
    }
};
