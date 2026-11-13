<?php

declare(strict_types=1);

use App\Support\Tramites\ConversorFichaJson;
use App\Support\Tramites\TemaDeTramite;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * El catálogo de trámites pasa a ser **contenido propio de la sede**, con base relacional.
 *
 * Hasta aquí la tabla `tramites` era una copia del visor del SUIT: la ficha viajaba en doce
 * bloques JSON, cada fila llevaba ocho columnas de «procedencia» y había una tabla con el punto
 * de control de la ingesta. Eso hacía que lo que la Entidad escribía en el panel dependiera de
 * una fuente externa y que no se pudiera consultar, ordenar ni validar por partes.
 *
 * Esta migración:
 *  1. crea las tablas propias (categorías, pasos, requisitos, documentos, puntos de atención,
 *     normas, canales de consulta y cuentas);
 *  2. **traslada el contenido existente** fila a fila con `ConversorFichaJson` —no se pierde
 *     ningún trámite ni su ficha—, y asigna un tema inicial por palabras del nombre;
 *  3. retira lo que era del visor: procedencia, audiencias, perfiles, seguimiento, los bloques
 *     JSON ya trasladados y la tabla de la ingesta.
 *
 * El código (`T41040`…) y la dirección (`slug`) de cada trámite se conservan: son enlaces que
 * ya circulan. `url_ficha_gov_co` queda como enlace de referencia opcional.
 *
 * No es reversible: volver atrás exigiría reconstruir los bloques JSON y la procedencia, que ya
 * no existen. La copia previa es responsabilidad del despliegue.
 */
return new class extends Migration
{
    private const JSON_A_RETIRAR = [
        'requisitos', 'documentos', 'momentos', 'perfiles', 'puntos_atencion', 'normativa',
        'canales_consulta_estado', 'audiencias', 'cuentas', 'seguimiento', 'costo_cuentas', 'producto_final',
    ];

    private const PROCEDENCIA = [
        'procedencia_fuente', 'procedencia_url', 'procedencia_obtenido_en', 'procedencia_nota',
        'procedencia_api', 'procedencia_origen_por_campo', 'procedencia_derivados', 'procedencia_faltantes',
    ];

    /** Las dependencias en que el SUIT agrupa los trámites de la Alcaldía (decisión 3, 2026-06-05). */
    private const DEPENDENCIAS = [
        'DADSA', 'Dirección Jurídica', 'Movilidad', 'Gobierno', 'Planeación', 'Educación', 'Hacienda',
        'Salud', 'Catastro Multipropósito', 'INRED', 'Alcaldía Local 1', 'Alcaldía Local 2', 'Alcaldía Local 3',
    ];

    public function up(): void
    {
        Schema::create('dependencias', function (Blueprint $t): void {
            $t->id();
            $t->string('nombre', 160)->unique();
            $t->unsignedSmallInteger('orden')->default(0);
            $t->timestamps();
        });

        Schema::create('tramite_categorias', function (Blueprint $t): void {
            $t->id();
            $t->string('slug', 120)->unique();
            $t->string('nombre', 160);
            $t->unsignedSmallInteger('orden')->default(0);
            $t->timestamps();
        });

        Schema::table('tramites', function (Blueprint $t): void {
            $t->foreignId('categoria_id')->nullable()->constrained('tramite_categorias')->nullOnDelete();
            $t->foreignId('dependencia_id')->nullable()->constrained('dependencias')->restrictOnDelete();
            // Atributos del modelo de datos del módulo (`_bd` §1.3).
            $t->unsignedSmallInteger('nivel_transformacion')->nullable();
            $t->string('estado_estandarizado', 30)->default('en_digitalizacion');
            $t->string('nivel_autenticacion_requerido', 20)->default('bajo');
            $t->boolean('aplica_sap')->default(false);
            $t->unsignedSmallInteger('termino_sap_dias')->nullable();
            $t->string('tipo_silencio_administrativo', 20)->default('negativo');
            $t->boolean('requiere_concepto_dafp')->default(false);
            $t->unsignedInteger('solicitudes_por_anio')->nullable();
            $t->unsignedSmallInteger('bloque_digitalizacion')->nullable();
            $t->string('fase_digitalizacion', 30)->nullable();
            // Control de la actualización en el SUIT (RN-B1-006): cada cambio de contenido sube
            // `version_ficha`; «pendiente en SUIT» es `version_ficha > suit_version`.
            $t->unsignedInteger('version_ficha')->default(1);
            $t->unsignedInteger('suit_version')->default(1);
            $t->date('fecha_ultima_actualizacion_suit')->nullable();
            $t->softDeletes();
        });

        Schema::create('tramite_pasos', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('tramite_id')->constrained('tramites')->cascadeOnDelete();
            $t->unsignedSmallInteger('orden');
            $t->string('titulo', 500);
            $t->text('descripcion')->nullable();
            $t->index(['tramite_id', 'orden']);
        });

        Schema::create('tramite_requisitos', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('tramite_id')->constrained('tramites')->cascadeOnDelete();
            $t->foreignId('paso_id')->nullable()->constrained('tramite_pasos')->nullOnDelete();
            $t->unsignedSmallInteger('orden');
            $t->string('tipo', 40)->default('documento');
            $t->text('descripcion');
            $t->boolean('obligatorio')->default(true);
            $t->text('nota')->nullable();
            $t->string('url', 500)->nullable();
            $t->index(['tramite_id', 'orden']);
        });

        Schema::create('tramite_documentos', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('tramite_id')->constrained('tramites')->cascadeOnDelete();
            $t->unsignedSmallInteger('orden');
            $t->string('nombre', 300);
            $t->string('url', 500)->nullable();
            $t->string('formato', 30)->nullable();
            $t->index(['tramite_id', 'orden']);
        });

        Schema::create('tramite_puntos_atencion', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('tramite_id')->constrained('tramites')->cascadeOnDelete();
            $t->unsignedSmallInteger('orden');
            $t->string('nombre', 160);
            $t->string('direccion', 500);
            $t->string('telefono', 40)->nullable();
            $t->text('horario')->nullable();
            $t->string('municipio', 120)->nullable();
            $t->string('departamento', 120)->nullable();
            $t->decimal('latitud', 9, 6)->nullable();
            $t->decimal('longitud', 9, 6)->nullable();
            $t->index(['tramite_id', 'orden']);
        });

        Schema::create('tramite_normas', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('tramite_id')->constrained('tramites')->cascadeOnDelete();
            $t->unsignedSmallInteger('orden');
            $t->string('tipo', 200);
            $t->string('numero', 200)->nullable();
            $t->unsignedSmallInteger('anio')->nullable();
            $t->text('articulos')->nullable();
            $t->string('url', 500)->nullable();
            $t->index(['tramite_id', 'orden']);
        });

        Schema::create('tramite_canales', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('tramite_id')->constrained('tramites')->cascadeOnDelete();
            $t->unsignedSmallInteger('orden');
            $t->string('canal', 30);
            $t->boolean('habilitado')->default(true);
            $t->string('nombre', 160)->nullable();
            $t->string('url', 500)->nullable();
            $t->string('correo', 240)->nullable();
            $t->string('telefono', 40)->nullable();
            $t->text('horario')->nullable();
            $t->index(['tramite_id', 'orden']);
        });

        Schema::create('tramite_audiencias', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('tramite_id')->constrained('tramites')->cascadeOnDelete();
            $t->string('grupo', 30);
            $t->unique(['tramite_id', 'grupo']);
        });

        Schema::create('tramite_valoraciones', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('tramite_id')->constrained('tramites')->cascadeOnDelete();
            $t->string('valor', 10);
            $t->text('comentario')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['tramite_id', 'created_at']);
        });

        Schema::create('tramite_cuentas', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('tramite_id')->constrained('tramites')->cascadeOnDelete();
            $t->unsignedSmallInteger('orden');
            $t->string('banco', 120)->nullable();
            $t->string('tipo', 40)->nullable();
            $t->string('numero', 40)->nullable();
            $t->string('titular', 120)->nullable();
            $t->index(['tramite_id', 'orden']);
        });

        $this->trasladarContenido();

        Schema::table('tramites', function (Blueprint $t): void {
            $t->dropColumn([...self::JSON_A_RETIRAR, ...self::PROCEDENCIA, 'categoria_slug', 'categoria_nombre']);
        });

        Schema::dropIfExists('ingesta_tramites');
    }

    public function down(): void
    {
        throw new RuntimeException('Esta migración no es reversible: la procedencia y los bloques JSON del visor ya no existen.');
    }

    /** Traslada cada ficha a sus tablas; los medios de resultado se conservan como lista simple. */
    private function trasladarContenido(): void
    {
        foreach (self::DEPENDENCIAS as $orden => $nombre) {
            DB::table('dependencias')->insert(['nombre' => $nombre, 'orden' => $orden + 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Las fichas ya publicadas se dieron por reflejadas en el SUIT en la fecha de la recolección.
        DB::table('tramites')->update(['fecha_ultima_actualizacion_suit' => '2026-09-30']);

        $temas = [];
        foreach (TemaDeTramite::catalogo() as $orden => $tema) {
            $temas[$tema['slug']] = DB::table('tramite_categorias')->insertGetId([
                'slug' => $tema['slug'], 'nombre' => $tema['nombre'], 'orden' => $orden + 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('tramites')->orderBy('id')->chunkById(50, function ($filas) use ($temas): void {
            foreach ($filas as $fila) {
                $ficha = [];
                foreach ([...self::JSON_A_RETIRAR, 'medios_resultado'] as $campo) {
                    $ficha[$campo] = isset($fila->{$campo}) ? json_decode((string) $fila->{$campo}, true) : null;
                }
                $ficha['resultado'] = $fila->resultado;
                $c = ConversorFichaJson::convertir($ficha);

                DB::table('tramites')->where('id', $fila->id)->update([
                    'resultado' => $c['resultado'],
                    'categoria_id' => $temas[TemaDeTramite::slugPara((string) $fila->nombre)],
                    'medios_resultado' => json_encode(array_values(array_filter(
                        is_array($ficha['medios_resultado']) ? $ficha['medios_resultado'] : [],
                        'is_string',
                    )), JSON_UNESCAPED_UNICODE),
                ]);

                $this->insertar('tramite_pasos', (int) $fila->id, $c['pasos'], $idsDePaso);
                foreach ($c['requisitos'] as $r) {
                    DB::table('tramite_requisitos')->insert([
                        'tramite_id' => $fila->id,
                        'paso_id' => $r['paso'] === null ? null : ($idsDePaso[$r['paso']] ?? null),
                        'orden' => $r['orden'], 'tipo' => $r['tipo'], 'descripcion' => $r['descripcion'],
                        'obligatorio' => $r['obligatorio'], 'nota' => $r['nota'], 'url' => $r['url'],
                    ]);
                }
                $this->insertar('tramite_documentos', (int) $fila->id, $c['documentos']);
                $this->insertar('tramite_puntos_atencion', (int) $fila->id, $c['puntos_atencion']);
                $this->insertar('tramite_normas', (int) $fila->id, $c['normativa']);
                $this->insertar('tramite_canales', (int) $fila->id, $c['canales_consulta_estado']);
                $this->insertar('tramite_cuentas', (int) $fila->id, $c['cuentas']);
                foreach ($c['audiencias'] as $grupo) {
                    DB::table('tramite_audiencias')->insert(['tramite_id' => $fila->id, 'grupo' => $grupo]);
                }
            }
        });
    }

    /**
     * @param  list<array<string, mixed>>  $filas
     * @param  array<int, int>|null  $ids  Recibe el id de cada fila por su número de orden.
     */
    private function insertar(string $tabla, int $tramiteId, array $filas, ?array &$ids = null): void
    {
        $ids = [];
        foreach ($filas as $indice => $fila) {
            $datos = ['tramite_id' => $tramiteId, 'orden' => $fila['orden'] ?? $indice + 1] + $fila;
            $id = DB::table($tabla)->insertGetId($datos);
            $ids[(int) $datos['orden']] = $id;
        }
    }
};
