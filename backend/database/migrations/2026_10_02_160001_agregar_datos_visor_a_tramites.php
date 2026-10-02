<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Datos ricos del visor de SUIT.
 *
 * El seeder ya no se limita a lo que devuelve `GetInfoBasicaEspecificaById`
 * (id, título, propósito, costo, tiempo, modalidad, link). Ahora ingiere la
 * ficha completa del visor, que tiene:
 *
 *  - `producto_final`     Lo que recibe el ciudadano cuando termina
 *  - `palabras_relacionadas` Sinónimos y términos de búsqueda secundarios
 *  - `url_manual_tramite_en_linea` PDF/manual del trámite en línea
 *  - `momentos`           Pasos del trámite con sus requisitos por tipo
 *  - `medios_resultado`   Canales por los que la Entidad entrega el resultado
 *  - `audiencias`          Tipos de ciudadano/empresa/organización que pueden
 *  - `cuentas`            Cuentas bancarias donde el ciudadano puede pagar
 *  - `seguimiento`        Canales de seguimiento del estado
 *
 * El seeder existente (`TramiteSeeder`) los pobla. Esta migración sólo agrega
 * la forma de la tabla; el contrato ya las declara y el modelo las acepta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramites', function (Blueprint $table): void {
            $table->text('producto_final')->nullable()->after('resultado');
            $table->text('palabras_relacionadas')->nullable()->after('producto_final');
            $table->string('url_manual_tramite_en_linea', 500)->nullable()->after('url_inicio');
            $table->json('momentos')->nullable()->after('documentos');
            $table->json('medios_resultado')->nullable()->after('cuentas');
            $table->json('audiencias')->nullable()->after('medios_resultado');
            $table->json('cuentas')->nullable()->after('audiencias');
            $table->json('seguimiento')->nullable()->after('canales_consulta_estado');
        });
    }

    public function down(): void
    {
        Schema::table('tramites', function (Blueprint $table): void {
            $table->dropColumn([
                'producto_final',
                'palabras_relacionadas',
                'url_manual_tramite_en_linea',
                'momentos',
                'medios_resultado',
                'audiencias',
                'cuentas',
                'seguimiento',
            ]);
        });
    }
};
