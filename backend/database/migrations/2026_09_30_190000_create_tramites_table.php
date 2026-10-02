<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * El catálogo de trámites de la Entidad, **completo en una sola migración**.
 *
 * # Por qué está todo aquí
 *
 * La tabla se construyó en seis migraciones: la creación, una ampliación de la
 * ficha, un cambio de nulabilidad de los seis atributos, el tipo del Anexo 2.1,
 * los datos del visor y los dos bloques de «cuándo» y observaciones. Leer «qué
 * campos tiene un trámite» obligaba a leer los seis archivos y a reconstruir el
 * estado final en la cabeza, que es exactamente donde se cuelan los errores: una
 * columna que una migración añade y otra vuelve a declarar.
 *
 * Aquí el esquema se lee de arriba abajo. **No es un resumen: es el mismo
 * esquema.** Se generó a partir de la base ya migrada y se comprobó columna por
 * columna —tipo, nulabilidad, valor por defecto y los cinco índices— contra la
 * salida de las seis migraciones anteriores.
 *
 * # Qué pasa con los entornos ya migrados
 *
 * Esta migración **conserva su nombre de archivo original**
 * (`2026_09_30_190000_create_tramites_table`), así que una base que ya migró —la
 * de pruebas, por ejemplo— tiene su fila en la tabla `migrations` y no vuelve a
 * ejecutarla. Una base nueva la ejecuta una vez y queda entera. Las cinco
 * migraciones que se retiraron dejan filas huérfanas en `migrations` de los
 * entornos viejos: no molestan a nadie y borrarlas sería tocar una base en
 * producción por estética.
 *
 * # Dos decisiones que no se leen en las columnas
 *
 * - **`url_ficha_gov_co` es obligatoria para publicar, no para existir.** La
 *   columna admite nulo y la publicación se decide con `publicado_en` más la
 *   presencia de la ficha (el repositorio es el único que lo comprueba). Así un
 *   trámite sin ficha puede existir mientras la Entidad la consigue, sin que la
 *   sede lo publique: borrarlo obligaría a volver a cargarlo entero.
 * - **`busqueda` guarda el nombre y el resumen sin tildes y en minúsculas.** La
 *   búsqueda del catálogo tiene que encontrar «tramite» cuando el catálogo dice
 *   «trámite», y eso no se resuelve igual en los dos motores que este proyecto
 *   usa —SQLite en las pruebas, PostgreSQL en producción—: `unaccent` es una
 *   extensión que hay que instalar en la base, y `LIKE` no la aplica solo. Una
 *   columna ya normalizada funciona igual en los dos y se puede indexar el día
 *   que el catálogo crezca.
 *
 * La forma de la tabla la fija el contrato (`TramiteItem`), no al revés: los
 * nombres de las columnas son los de los campos que viajan en la respuesta.
 * Traducir en la capa de salida obligaría a mantener dos vocabularios y a que la
 * equivocación de uno no se viera en el otro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tramites', function (Blueprint $table) {
            $table->id();

            // --- Identidad ---------------------------------------------------
            $table->string('codigo', 20)->unique();
            $table->string('slug', 160)->unique();
            $table->string('nombre', 200);
            $table->text('resumen')->nullable();

            /*
             * El tipo del Anexo 2.1: `tramites`, `opa` o `consultas`. La fuente
             * oficial —SUIT— sólo clasifica trámites y la Entidad aún no ha
             * separado los tres grupos; el valor por defecto es el que ya siembra
             * el sembrador, así que un trámite existente recibe el grupo al que
             * ya pertenecía en la práctica.
             */
            $table->string('type', 20)->default('tramites');
            $table->index('type');

            /*
             * --- Los seis atributos obligatorios de la Guía §5.1.3 ------------
             *
             * **Admiten nulo, y es deliberado.** Un trámite puede existir
             * mientras la Entidad consigue el dato que le falta; lo que no puede
             * es **publicarse** sin los seis. La publicación se decide con
             * `publicado_en`, y el repositorio es el único que comprueba las dos
             * cosas. Poner `NOT NULL` aquí obligaría a inventarse un valor para
             * poder guardar el trámite, que es justo lo que no se hace.
             */
            $table->string('modalidad', 30)->nullable();
            $table->string('tiene_costo', 20)->nullable();
            $table->unsignedSmallInteger('tiempo_solucion_dias')->nullable();
            $table->string('canal_inicio', 30)->nullable();
            $table->string('consulta_estado', 500)->nullable();
            $table->json('requisitos')->nullable();
            $table->json('documentos')->nullable();

            // --- La ficha completa del visor ---------------------------------
            // Qué obtiene quien adelanta el trámite, según la ficha oficial.
            $table->text('resultado')->nullable();
            $table->text('producto_final')->nullable();

            // De qué depende el plazo, que el visor publica bajo el resultado.
            $table->text('observaciones_resultado')->nullable();

            /*
             * «¿Cuándo se puede realizar?». El visor no lo responde con una frase
             * sino con tres hechos: casi siempre «cualquier fecha» (el booleano),
             * a veces una condición en prosa, y una vez un calendario externo. Se
             * guardan los tres por separado para que el dato se pueda auditar
             * contra la fuente en vez de quedar fundido en un texto.
             */
            $table->boolean('fecha_cualquiera')->nullable();
            $table->text('cuando_se_puede_realizar')->nullable();
            $table->string('url_calendario', 500)->nullable();

            /*
             * A quién está dirigido el trámite: «Ciudadano», «Extranjeros»,
             * «Instituciones o dependencias públicas», «Organizaciones». Sin esto
             * no se puede decir a quién no le aplica.
             */
            $table->json('perfiles')->nullable();

            // Los pasos, con sus requisitos y las audiencias de cada uno.
            $table->json('momentos')->nullable();

            // Los cuatro bloques de la ficha.
            $table->json('puntos_atencion')->nullable();
            $table->json('normativa')->nullable();
            $table->json('canales_consulta_estado')->nullable();

            // Medios por los que se entrega el resultado, y las cuentas de
            // recaudo y los canales de seguimiento del visor.
            $table->json('medios_resultado')->nullable();
            $table->json('audiencias')->nullable();
            $table->json('cuentas')->nullable();
            $table->json('seguimiento')->nullable();

            // Palabras clave secundarias del visor, separadas por comas.
            $table->text('palabras_relacionadas')->nullable();

            // --- Costo --------------------------------------------------------
            // El importe, cuando lo haya. En decimal y nunca en coma flotante:
            // un céntimo perdido en una tasa es un defecto legal.
            $table->decimal('costo', 12, 2)->nullable();
            $table->string('costo_tipo_valor', 40)->nullable();
            $table->string('costo_moneda', 20)->nullable();
            $table->string('costo_url_pago', 500)->nullable();
            $table->text('costo_descripcion')->nullable();
            $table->json('costo_cuentas')->nullable();

            // --- Enlaces ------------------------------------------------------
            $table->string('url_inicio', 500)->nullable();
            $table->string('url_manual_tramite_en_linea', 500)->nullable();

            // La ficha en GOV.CO. Sin ella el trámite no se publica.
            $table->string('url_ficha_gov_co', 500)->nullable();

            // --- Categoría ----------------------------------------------------
            $table->string('categoria_slug', 120)->nullable();
            $table->string('categoria_nombre', 150)->nullable();

            // Texto buscable: nombre y resumen sin tildes, en minúsculas.
            $table->text('busqueda');

            /*
             * --- Procedencia --------------------------------------------------
             *
             * De dónde salió el dato y cuándo se obtuvo. El ciudadano tiene
             * derecho a saberlo (Ley 1712 de 2014, artículo 11.b), y la sede a
             * poder demostrar que no lo inventó.
             *
             * Va aquí y no en una tabla de auditoría aparte porque el contrato
             * publica la procedencia campo por campo: una tabla que se consultara
             * en cada ficha sería el mismo dato con una unión de más. Y no
             * sustituye a la auditoría del proyecto —esto dice de dónde salió el
             * dato, no quién lo cambió—.
             *
             * `procedencia_api` guarda el servicio del que se copiaron los datos
             * de la ficha —no el catálogo— para que la procedencia se pueda
             * repetir.
             */
            $table->string('procedencia_fuente', 120)->nullable();
            $table->string('procedencia_url', 500)->nullable();
            $table->date('procedencia_obtenido_en')->nullable();
            $table->string('procedencia_nota', 500)->nullable();
            $table->string('procedencia_api', 500)->nullable();
            $table->json('procedencia_origen_por_campo')->nullable();
            $table->json('procedencia_derivados')->nullable();
            $table->json('procedencia_faltantes')->nullable();

            /*
             * Cuándo se publicó. Nulo significa «no publicado»: es la marca que
             * separa lo que el ciudadano puede ver de lo que todavía no.
             */
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
