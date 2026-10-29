<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Consentimiento;
use Illuminate\Console\Command;

/**
 * Borra los registros de consentimiento que pasaron el plazo de retención (T-26).
 *
 * El plazo sale de `config('consentimientos.retencion_dias')`; `--dias` lo
 * sustituye para una ejecución concreta. Con `--simular` sólo cuenta, sin borrar.
 */
final class DepurarConsentimientos extends Command
{
    protected $signature = 'consentimientos:depurar
        {--dias= : Días de retención (por defecto, el de la configuración)}
        {--simular : Sólo cuenta los registros, sin borrarlos}';

    protected $description = 'Borra los consentimientos de cookies que superaron el plazo de retención.';

    public function handle(): int
    {
        $opcion = $this->option('dias');
        $dias = is_numeric($opcion) ? (int) $opcion : (int) config('consentimientos.retencion_dias');

        if ($dias < 1) {
            $this->error('La retención debe ser de al menos un día.');

            return self::INVALID;
        }

        $consulta = Consentimiento::query()->where('registrado_en', '<', now()->subDays($dias));

        if ($this->option('simular') === true) {
            $this->info(sprintf('%d registro(s) superan los %d días (simulación, no se borró nada).', $consulta->count(), $dias));

            return self::SUCCESS;
        }

        $borrados = $consulta->delete();
        $this->info(sprintf('%d registro(s) borrados por superar los %d días.', $borrados, $dias));

        return self::SUCCESS;
    }
}
