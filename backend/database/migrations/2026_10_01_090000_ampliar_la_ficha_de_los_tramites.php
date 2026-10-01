<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La ficha completa de un trámite.
 *
 * La migración anterior dejó los seis atributos obligatorios de la Guía §5.1.3
 * —modalidad, costo, término, canal de inicio, consulta del estado y
 * requisitos—. Esta añade lo que el §9.1 hace falta para poder **certificar** la
 * integración con Gov.co: dónde se atiende el trámite en persona, qué norma lo
 * faculta, por dónde se pregunta por él, qué entrega y a qué perfiles va
 * dirigido. Los seis siguen siendo los mismos seis; esto los acompaña.
 *
 * **Por qué el costo se parte en cinco columnas y no sigue en una.** La ficha
 * oficial no siempre declara un importe: para el impuesto predial devuelve
 * `Valor: null` junto a `TipoValor: "AVALUO_LIQUIDACION"`, es decir un costo que
 * se **calcula** con el avalúo del predio. La columna `costo` sigue guardando el
 * importe, porque cuando lo hay es un número y un céntimo perdido en una tasa es
 * un defecto legal; lo que se añade es *cómo* se determina ese importe, en qué
 * moneda y dónde se paga, que son las tres cosas que sin el número se quedaban
 * sin decir. Guardar «AVALUO_LIQUIDACION» en la columna decimal obligaría a
 * meter texto donde el motor espera un número y a que ninguna consulta pudiera
 * sumar el catálogo.
 *
 * **Por qué los cuatro bloques nuevos son `json` y no tablas.** Cada uno es una
 * lista corta —de uno a cinco elementos— que siempre se lee entera junto al
 * trámite y nunca se consulta por sus campos: no hay ninguna pregunta del tipo
 * «qué trámites se atienden los sábados». Una tabla por bloque añadiría cuatro
 * uniones a la consulta del catálogo para no responder a ninguna pregunta que
 * hoy exista. El día que la Entidad quiera cruzar trámites por punto de atención
 * o por norma, el cambio es esta migración y no el contrato.
 *
 * **Por qué la procedencia se amplía aquí y no en una tabla de auditoría.** El
 * contrato publica la procedencia campo por campo, así que tiene que estar donde
 * se lee el trámite: una tabla aparte que se consultara en cada ficha sería el
 * mismo dato con una unión de más. Y no sustituye a la auditoría del proyecto:
 * esto dice de dónde salió el dato, no quién lo cambió.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            // Qué obtiene quien adelanta el trámite, según la ficha oficial.
            $table->text('resultado')->nullable();

            // A quién está dirigido: «Ciudadano», «Extranjero», «Empresa privada»,
            // «Entidad pública». Sin esto no se puede decir a quién no le aplica.
            $table->json('perfiles')->nullable();

            // Los cuatro bloques de la ficha completa.
            $table->json('puntos_atencion')->nullable();
            $table->json('normativa')->nullable();
            $table->json('canales_consulta_estado')->nullable();

            // El costo, además del importe que ya guarda la columna `costo`.
            $table->string('costo_tipo_valor', 40)->nullable();
            $table->string('costo_moneda', 20)->nullable();
            $table->string('costo_url_pago', 500)->nullable();
            $table->text('costo_descripcion')->nullable();
            $table->json('costo_cuentas')->nullable();

            // La procedencia, campo por campo. `procedencia_api` guarda el
            // servicio del que se copiaron los datos de la ficha —no el catálogo—
            // para que la procedencia se pueda repetir.
            $table->string('procedencia_api', 500)->nullable();
            $table->json('procedencia_origen_por_campo')->nullable();
            $table->json('procedencia_derivados')->nullable();
            $table->json('procedencia_faltantes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            $table->dropColumn([
                'resultado',
                'perfiles',
                'puntos_atencion',
                'normativa',
                'canales_consulta_estado',
                'costo_tipo_valor',
                'costo_moneda',
                'costo_url_pago',
                'costo_descripcion',
                'costo_cuentas',
                'procedencia_api',
                'procedencia_origen_por_campo',
                'procedencia_derivados',
                'procedencia_faltantes',
            ]);
        });
    }
};
