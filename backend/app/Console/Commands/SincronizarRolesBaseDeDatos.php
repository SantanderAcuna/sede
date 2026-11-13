<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\RolesBaseDeDatos;
use Illuminate\Console\Command;

/**
 * Vuelve a aplicar roles, claves y permisos de la base. Se corre tras cada migración
 * del despliegue y es la vía para ROTAR la clave de `sede_app` o `sede_lectura`.
 * Debe ejecutarse con el rol dueño (`--database=pgsql_migracion`).
 */
final class SincronizarRolesBaseDeDatos extends Command
{
    protected $signature = 'bd:sincronizar-roles {--database= : Conexión con el rol dueño}';

    protected $description = 'Aplica de nuevo los roles sede_app y sede_lectura (claves y permisos).';

    public function handle(): int
    {
        RolesBaseDeDatos::aplicar($this->option('database') ?: null);
        $this->components->info('Roles de la base sincronizados.');

        return self::SUCCESS;
    }
}
