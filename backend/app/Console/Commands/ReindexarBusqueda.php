<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Busqueda\TextoBuscable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Recalcula el texto de búsqueda de trámites y políticas (T-22).
 *
 * `busqueda` y `busqueda_titulo` son columnas derivadas: los modelos las rellenan al
 * guardar y la migración las rellenó una vez. Este comando existe para el día en que
 * cambie `TextoBuscable::normalizar` —el texto ya guardado queda desfasado respecto
 * del que escribe el ciudadano— o en que alguien edite filas con SQL directo.
 *
 * Es **idempotente**: sólo escribe las filas cuyo texto cambiaría, así que una segunda
 * ejecución seguida no toca nada y lo informa. Escribe con el constructor de consultas y
 * no con el modelo, para no alterar `updated_at` ni disparar eventos por un recálculo.
 */
final class ReindexarBusqueda extends Command
{
    protected $signature = 'busqueda:reindexar
        {--simular : Sólo cuenta las filas desfasadas, sin escribir}';

    protected $description = 'Recalcula el texto de búsqueda de trámites y políticas.';

    public function handle(): int
    {
        $simular = $this->option('simular') === true;

        $tramites = $this->reindexar(
            'tramites',
            ['id', 'nombre', 'resumen', 'busqueda', 'busqueda_titulo'],
            fn (stdClass $fila): string => TextoBuscable::deTramite((string) $fila->nombre, $fila->resumen),
            $simular,
        );

        $politicas = $this->reindexar(
            'politicas',
            ['id', 'nombre', 'secciones', 'busqueda', 'busqueda_titulo'],
            fn (stdClass $fila): string => TextoBuscable::dePolitica((string) $fila->nombre, $this->secciones($fila->secciones)),
            $simular,
        );

        $this->info(sprintf(
            '%s: trámites %d de %d, políticas %d de %d.',
            $simular ? 'Desfasadas (simulación, no se escribió nada)' : 'Reindexadas',
            $tramites['cambiadas'],
            $tramites['total'],
            $politicas['cambiadas'],
            $politicas['total'],
        ));

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $columnas
     * @param  callable(stdClass): string  $texto
     * @return array{total: int, cambiadas: int}
     */
    private function reindexar(string $tabla, array $columnas, callable $texto, bool $simular): array
    {
        $total = 0;
        $cambiadas = 0;

        // Por bloques: el catálogo cabe en memoria hoy, pero el comando no debe
        // depender de que siga siendo así.
        DB::table($tabla)->select($columnas)->orderBy('id')->chunk(200, function ($filas) use ($tabla, $texto, $simular, &$total, &$cambiadas): void {
            foreach ($filas as $fila) {
                $total++;

                $busqueda = $texto($fila);
                $titulo = TextoBuscable::normalizar((string) $fila->nombre);

                if ($busqueda === $fila->busqueda && $titulo === $fila->busqueda_titulo) {
                    continue;
                }

                $cambiadas++;

                if (! $simular) {
                    DB::table($tabla)->where('id', $fila->id)->update([
                        'busqueda' => $busqueda,
                        'busqueda_titulo' => $titulo,
                    ]);
                }
            }
        });

        return ['total' => $total, 'cambiadas' => $cambiadas];
    }

    /**
     * `secciones` es una columna `json`: con el constructor de consultas llega como
     * texto, no como arreglo.
     *
     * @return list<array{id?: string, titulo?: string, parrafos?: list<string>}>
     */
    private function secciones(mixed $valor): array
    {
        if (is_string($valor)) {
            $valor = json_decode($valor, true);
        }

        /** @var list<array{id?: string, titulo?: string, parrafos?: list<string>}> $secciones */
        $secciones = is_array($valor) ? array_values($valor) : [];

        return $secciones;
    }
}
