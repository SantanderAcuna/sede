<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El catálogo de trámites de la Entidad.
     *
     * La forma de la tabla la fija el contrato (`TramiteItem`), no al revés: los
     * nombres de las columnas son los de los campos que viajan en la respuesta.
     * Traducir en la capa de salida obligaría a mantener dos vocabularios y a
     * que la equivocación de uno no se viera en el otro.
     *
     * Dos decisiones que no se leen en las columnas:
     *
     * - **`url_ficha_gov_co` es obligatoria para publicar, no para existir.** La
     *   columna admite nulo y la publicación se decide con `publicado_en` más la
     *   presencia de la ficha (el repositorio es el único que lo comprueba). Así
     *   un trámite sin ficha puede existir mientras la Entidad la consigue, sin
     *   que la sede lo publique: borrarlo obligaría a volver a cargarlo entero.
     * - **`busqueda` guarda el nombre y el resumen sin tildes y en minúsculas.**
     *   La búsqueda del catálogo tiene que encontrar «tramite» cuando el catálogo
     *   dice «trámite», y eso no se resuelve igual en los dos motores que este
     *   proyecto usa —SQLite en las pruebas, PostgreSQL en producción—: `unaccent`
     *   es una extensión que hay que instalar en la base, y `LIKE` no la aplica
     *   solo. Una columna ya normalizada funciona igual en los dos y se puede
     *   indexar el día que el catálogo crezca.
     */
    public function up(): void
    {
        Schema::create('tramites', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 20)->unique();
            $table->string('slug', 160)->unique();
            $table->string('nombre', 200);
            $table->text('resumen')->nullable();

            // Los seis atributos obligatorios de la Guía §5.1.3.
            $table->string('modalidad', 30);
            $table->string('tiene_costo', 20);
            $table->unsignedSmallInteger('tiempo_solucion_dias');
            $table->string('canal_inicio', 30);
            $table->string('consulta_estado', 500);
            $table->json('requisitos');
            $table->json('documentos');

            // El importe, cuando lo haya. En decimal y nunca en coma flotante:
            // un céntimo perdido en una tasa es un defecto legal.
            $table->decimal('costo', 12, 2)->nullable();
            $table->string('url_inicio', 500)->nullable();

            // La ficha en GOV.CO. Sin ella el trámite no se publica.
            $table->string('url_ficha_gov_co', 500)->nullable();

            $table->string('categoria_slug', 120)->nullable();
            $table->string('categoria_nombre', 150)->nullable();

            // Texto buscable: nombre y resumen sin tildes, en minúsculas.
            $table->text('busqueda');

            // De dónde salió el dato y cuándo se obtuvo. El ciudadano tiene
            // derecho a saberlo (Ley 1712 de 2014, artículo 11.b), y la sede a
            // poder demostrar que no lo inventó.
            $table->string('procedencia_fuente', 120)->nullable();
            $table->string('procedencia_url', 500)->nullable();
            $table->date('procedencia_obtenido_en')->nullable();
            $table->string('procedencia_nota', 500)->nullable();

            // Cuándo se publicó. Nulo significa «no publicado»: es la marca que
            // separa lo que el ciudadano puede ver de lo que todavía no.
            $table->timestamp('publicado_en')->nullable();

            $table->timestamps();

            // El listado público pregunta siempre por lo publicado y ordena por
            // nombre. El índice sigue esa consulta y no las columnas sueltas.
            $table->index(['publicado_en', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tramites');
    }
};
