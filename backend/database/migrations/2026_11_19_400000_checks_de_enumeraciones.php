<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CHECK en las enumeraciones que el código ya define (0D, D4; R5 y R18 del plan de robustez).
 *
 * Solo van las columnas cuya lista cerrada existe en el código (enums, constantes o reglas de
 * validación) y que los datos de staging cumplen (consulta del 2026-11-18). Las columnas de texto
 * sin lista (`costo_moneda`, tipo de cuenta, tipo de norma, `acto_tipo`…) NO llevan CHECK: sería
 * inventar un vocabulario; quedan en la línea base de la guarda con su destino.
 *
 * Cada restricción se agrega `NOT VALID` y se valida enseguida: así el bloqueo es breve y una fila
 * vieja inválida detiene la migración con el nombre de la restricción (todo corre en una
 * transacción, no queda nada a medias). El `NULL` pasa siempre, como en cualquier CHECK.
 */
return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string}> restricción => [tabla, condición] */
    private const CHECKS = [
        'consentimientos_evento_check' => ['consentimientos', "evento in ('aceptado', 'rechazado', 'revocado', 'caducado')"],
        'tramite_canales_canal_check' => ['tramite_canales', "canal in ('sede', 'presencial', 'telefonico', 'web', 'correo', 'otro')"],
        'tramite_requisitos_tipo_check' => ['tramite_requisitos', "tipo in ('documento', 'verificacion_institucional', 'solicitud', 'formulario', 'pago')"],
        'tramite_valoraciones_valor_check' => ['tramite_valoraciones', "valor in ('facil', 'dificil')"],
        'tramite_valoraciones_estrellas_check' => ['tramite_valoraciones', 'estrellas between 1 and 5'],
        'tramite_valoraciones_alguna_check' => ['tramite_valoraciones', 'valor is not null or estrellas is not null'],
        'tramites_type_check' => ['tramites', "type in ('tramites', 'opa', 'consultas')"],
        'tramites_modalidad_check' => ['tramites', "modalidad in ('en_linea', 'parcialmente_en_linea', 'presencial')"],
        'tramites_tiene_costo_check' => ['tramites', "tiene_costo in ('gratuito', 'con_costo')"],
        'tramites_canal_inicio_check' => ['tramites', "canal_inicio in ('propio', 'portal_nacional')"],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            DB::statement("set local lock_timeout = '3s'");

            foreach (self::CHECKS as $nombre => [$tabla, $condicion]) {
                DB::statement("alter table {$tabla} drop constraint if exists {$nombre}");
                DB::statement("alter table {$tabla} add constraint {$nombre} check ({$condicion}) not valid");
                DB::statement("alter table {$tabla} validate constraint {$nombre}");
            }
        });
    }

    public function down(): void
    {
        foreach (self::CHECKS as $nombre => [$tabla]) {
            DB::statement("alter table {$tabla} drop constraint if exists {$nombre}");
        }
    }
};
