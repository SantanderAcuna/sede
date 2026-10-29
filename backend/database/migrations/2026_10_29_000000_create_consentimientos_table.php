<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de consentimientos de cookies (T-26, AC-26.4; RF-01-D01).
 *
 * Es la prueba, ante una reclamación, de qué categorías aceptó o rechazó un
 * navegador y bajo qué versión de la política. **No guarda datos personales
 * directos**: ni IP, ni agente de usuario, ni identificador de sesión, ni de
 * cuenta. Por eso no hay columna alguna que identifique a quien consintió, y la
 * prueba `ConsentimientosTest` falla si alguien añade una.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimientos', function (Blueprint $tabla): void {
            $tabla->id();
            // aceptado | rechazado | revocado | caducado (ver `Consentimiento::EVENTOS`).
            $tabla->string('evento', 20);
            // Categorías vigentes tras el evento; `[]` = sólo las necesarias.
            $tabla->json('categorias');
            $tabla->unsignedSmallInteger('version_politica');
            // La fecha la pone el servidor: una hora escrita por el navegador no es prueba.
            $tabla->dateTime('registrado_en')->useCurrent();

            $tabla->index('registrado_en');
            $tabla->index(['evento', 'registrado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimientos');
    }
};
