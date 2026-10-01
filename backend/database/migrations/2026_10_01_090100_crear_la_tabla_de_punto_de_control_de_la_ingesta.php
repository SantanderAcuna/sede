<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El punto de control de la ingesta del catálogo.
 *
 * Existe por una razón concreta y medida: la API de contenido de GOV.CO **limita
 * la tasa** y, al pasarse, deja de responder. Una recolección de 124 trámites
 * hace más de mil peticiones, así que una ejecución que se corte a la mitad es lo
 * normal, no la excepción. Sin un punto de control por trámite, cada corte
 * obliga a empezar de cero y ninguna ejecución llega al final.
 *
 * **Qué guarda y qué no.** Guarda el estado de cada trámite —si ya se trajo
 * entero, cuántas veces se intentó y por qué falló— y **nada del contenido que se
 * trajo**. Es una decisión de protección de datos, no de ahorro: la respuesta de
 * la fuente trae el teléfono móvil de algunos puntos de atención y los buzones
 * del área responsable, y la ingesta los descarta al publicar. Copiarlos aquí
 * «por si hay que reanudar» los dejaría guardados en un segundo sitio donde nadie
 * los mira, que es exactamente lo contrario de descartarlos. La Ley 1581 de 2012
 * no distingue entre la copia que se publica y la que se archiva.
 *
 * Por eso la reanudación vuelve a pedir el trámite que se quedó a medias y salta
 * entero el que ya terminó: lo que no se repite es el trabajo concluido, que es
 * el que cuesta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingesta_tramites', function (Blueprint $table) {
            $table->id();

            // El código del trámite en SUIT, con la forma `T#####`. Es la clave de
            // la reanudación: un trámite es el que ya está terminado o no lo está.
            $table->string('codigo', 20)->unique();

            // `pendiente`, `completo` o `fallido`. Se guarda como texto y no como
            // booleano porque «falló» y «todavía no se ha intentado» son estados
            // distintos y confundirlos haría que un trámite fallido pareciera
            // pendiente y se reintentara para siempre sin que nadie lo viera.
            $table->string('estado', 20);

            // Cuántas veces se intentó y con qué error, para que el informe pueda
            // decir por qué falló y no sólo cuántos fallaron.
            $table->unsignedSmallInteger('intentos')->default(0);
            $table->text('error')->nullable();

            // Cuándo se dio por terminado. Nulo mientras no lo esté, que es lo
            // que distingue un pendiente de un completo sin mirar el estado.
            $table->timestamp('terminado_en')->nullable();

            $table->timestamps();

            // La consulta de la reanudación pregunta siempre por el estado; el
            // índice sigue esa consulta y no la columna suelta.
            $table->index(['estado', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingesta_tramites');
    }
};
