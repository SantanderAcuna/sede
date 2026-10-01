<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los seis atributos obligatorios pasan a admitir nulo.
 *
 * La migración original los declaró `NOT NULL`, y era correcto entonces: el único
 * camino que escribía en la tabla era el sembrador, y el sembrador **descarta** el
 * trámite al que le falta un atributo, así que una fila sin ellos no podía
 * existir.
 *
 * La ingesta de la ficha oficial rompe ese supuesto y deja de ser cierto. La
 * fuente del Estado no declara la modalidad de algún trámite, ni su término, ni
 * sus requisitos, y ante eso hay dos salidas: descartar el trámite —y entonces el
 * ciudadano no lo encuentra en la sede, que es peor que encontrarlo a medias— o
 * **guardarlo incompleto y decir qué le falta**. Se elige la segunda, y para eso
 * las columnas tienen que admitir nulo.
 *
 * **Esto no afloja ninguna regla de publicación.** Lo que decide si un trámite se
 * publica no son las restricciones de la tabla, sino `publicado_en` y la presencia
 * de la ficha, y las dos las comprueba el repositorio, que es la única puerta por
 * la que el ciudadano llega al catálogo. La ingesta sólo pone `publicado_en` a los
 * trámites que tienen sus seis atributos; los demás existen en la base, los ve la
 * Entidad y no los ve nadie más. Es exactamente el mismo trato que ya recibía un
 * trámite sin ficha en GOV.CO.
 *
 * La alternativa —una tabla aparte para los trámites incompletos— sería peor: el
 * día que la Entidad arregla el dato habría que mover la fila de una tabla a otra,
 * y con ella su identificador, que es lo que rompe los enlaces ya publicados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            $table->string('modalidad', 30)->nullable()->change();
            $table->string('tiene_costo', 20)->nullable()->change();
            $table->unsignedSmallInteger('tiempo_solucion_dias')->nullable()->change();
            $table->string('canal_inicio', 30)->nullable()->change();
            $table->string('consulta_estado', 500)->nullable()->change();
            $table->json('requisitos')->nullable()->change();
            $table->json('documentos')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Volver a `NOT NULL` obliga a rellenar las filas incompletas con algo, y
        // cualquier valor que se inventara aquí sería un dato falso en el catálogo.
        // Por eso la reversión no rellena: deja las columnas como estaban en forma
        // y no toca el contenido.
        Schema::table('tramites', function (Blueprint $table) {
            $table->string('modalidad', 30)->nullable(false)->change();
            $table->string('tiene_costo', 20)->nullable(false)->change();
            $table->unsignedSmallInteger('tiempo_solucion_dias')->nullable(false)->change();
            $table->string('canal_inicio', 30)->nullable(false)->change();
            $table->string('consulta_estado', 500)->nullable(false)->change();
            $table->json('requisitos')->nullable(false)->change();
            $table->json('documentos')->nullable(false)->change();
        });
    }
};
