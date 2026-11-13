<?php

declare(strict_types=1);

use App\Support\RolesBaseDeDatos;
use Illuminate\Database\Migrations\Migration;

/**
 * Crea `sede_app` y `sede_lectura` (Fase 0A, D-0.1). La lógica vive en
 * `App\Support\RolesBaseDeDatos`, que también usa `bd:sincronizar-roles` para
 * aplicar de nuevo permisos y claves (una migración corrida no se repite).
 *
 * `down()` no borra los roles: son del clúster y pueden estar en uso por otra base.
 */
return new class extends Migration
{
    public function up(): void
    {
        RolesBaseDeDatos::aplicar($this->getConnection());
    }

    public function down(): void {}
};
