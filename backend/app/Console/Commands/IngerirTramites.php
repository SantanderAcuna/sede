<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Services\IngestaTramitesInterface;
use App\Models\Tramite;
use App\Services\GovCo\ClienteFichaGovCo;
use App\Services\ReconciliacionSuit;
use App\Support\Ingesta\MapeoFicha;
use App\Support\Tramites\FuenteSuit;
use Illuminate\Console\Command;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * Trae la ficha oficial de cada trámite y completa el catálogo.
 *
 * # Por qué es un comando y no un sembrador más
 *
 * El sembrador lee una copia congelada del catálogo de SUIT y la escribe. La
 * ingesta **sale a la red**: pide la ficha oficial de cada trámite a la API de
 * contenido de GOV.CO, que es otra cosa. Un sembrador que de pronto hace mil
 * peticiones dejaría de poder ejecutarse en la integración continua, y `migrate
 * --seed` —lo que corre `make preparar`— dejaría de ser una operación local.
 *
 * Además la ingesta necesita cosas que un sembrador no tiene: un punto de control
 * por trámite para poder reanudar, espera exponencial ante el límite de tasa de la
 * fuente y un informe que diga qué falta.
 *
 * # El informe es la mitad del comando
 *
 * Un catálogo a medias que no dice dónde está a medias es peor que uno incompleto
 * declarado. Por eso el informe dice, al terminar: cuántos trámites se trajeron,
 * cuántos fallaron **y por qué**, cuántos quedaron con sus seis atributos
 * obligatorios, y **qué campo le falta a cada uno** de los que no.
 *
 * Y añade la conciliación con el listado del sitio del Distrito, que es lo que la
 * Entidad necesita para decidir: **el comando la reporta y no la resuelve**. Qué
 * trámites tiene la Alcaldía es una decisión suya, no del programa.
 */
final class IngerirTramites extends Command
{
    protected $signature = 'tramites:ingerir
        {--codigo=* : Trae sólo estos códigos, con la forma T#####}
        {--limite=0 : Trae como mucho esta cantidad de trámites}
        {--desde-cero : Olvida el avance y vuelve a traer todo}
        {--sin-conciliacion : Omite la conciliación con el sitio del Distrito}
        {--detalle : Imprime el avance trámite a trámite}';

    protected $description = 'Trae la ficha oficial de cada trámite y completa el catálogo.';

    public function handle(IngestaTramitesInterface $ingesta, ReconciliacionSuit $conciliacion): int
    {
        try {
            $codigos = $this->codigos();
        } catch (RuntimeException $fallo) {
            $this->error($fallo->getMessage());

            return self::FAILURE;
        }

        if ($codigos === []) {
            $this->error('No hay ningún código que traer.');

            return self::FAILURE;
        }

        if ($this->option('desde-cero') === true) {
            $olvidados = $ingesta->olvidarAvance();
            $this->warn(sprintf('Se olvidó el avance: %d puntos de control borrados. Se vuelve a traer todo.', $olvidados));
        }

        $limite = (int) $this->option('limite');

        if ($limite > 0) {
            $codigos = array_slice($codigos, 0, $limite);
        }

        $this->info(sprintf('Trámites a traer: %d.', count($codigos)));

        $detalle = $this->option('detalle') === true;

        $aviso = $detalle
            ? function (string $linea): void {
                $this->output->writeln($linea);
            }
        : null;

        $resultado = $ingesta->ejecutar($codigos, $aviso);

        $this->informe($resultado);

        if ($this->option('sin-conciliacion') !== true) {
            $this->conciliacion($conciliacion);
        }

        // Un trámite que falló deja la ejecución en error para que un proceso
        // automático lo note. Si no falló ninguno, cero.
        return $resultado['fallidos'] === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Los códigos que hay que traer.
     *
     * Salen del catálogo congelado de SUIT —la fuente ratificada— y no del sitio
     * del Distrito: **se siembra SUIT**, y qué trámites tiene la Alcaldía es una
     * decisión de la Entidad que el comando reporta pero no toma.
     *
     * @return list<string>
     */
    private function codigos(): array
    {
        $pedidos = $this->option('codigo');

        if (is_array($pedidos) && $pedidos !== []) {
            /** @var list<string> $codigos */
            $codigos = array_values(array_filter(
                array_map(static fn (mixed $codigo): string => trim((string) $codigo), $pedidos),
                static fn (string $codigo): bool => $codigo !== '',
            ));

            return $codigos;
        }

        $codigos = [];

        foreach ($this->filasDeSuit() as $fila) {
            $codigo = $fila['id'] ?? null;

            if (is_string($codigo) && trim($codigo) !== '') {
                $codigos[] = trim($codigo);
            }
        }

        return $codigos;
    }

    /**
     * Los títulos con los que SUIT registra cada trámite, por código.
     *
     * Es lo que la conciliación compara contra el listado del Distrito. Se lee de
     * la copia congelada —la fuente ratificada— y no de `tramites.nombre`, que
     * deja de ser el nombre de SUIT en cuanto la ingesta escribe el
     * `NombreEstandarizado` de la ficha oficial. La razón está explicada donde se
     * usa.
     *
     * @return array<string, string>
     */
    private function titulosDeSuit(): array
    {
        $titulos = [];

        foreach ($this->filasDeSuit() as $fila) {
            $codigo = $fila['id'] ?? null;
            $titulo = $fila['titulo'] ?? null;

            if (is_string($codigo) && trim($codigo) !== '' && is_string($titulo) && trim($titulo) !== '') {
                $titulos[trim($codigo)] = trim($titulo);
            }
        }

        return $titulos;
    }

    /**
     * Las filas de la copia congelada del catálogo de SUIT.
     *
     * La usan los dos caminos que leen el catálogo ratificado —qué trámites hay
     * que traer y con qué título los registra SUIT— porque tienen que leer
     * exactamente el mismo archivo: dos lecturas divergentes darían dos catálogos
     * distintos según para qué se mirara.
     *
     * @return list<array<string, mixed>>
     */
    private function filasDeSuit(): array
    {
        $ruta = database_path(FuenteSuit::COPIA_CATALOGO);

        if (! is_file($ruta)) {
            throw new RuntimeException(sprintf(
                'No está la copia del catálogo de SUIT en %s: sin ella no hay qué traer.',
                $ruta,
            ));
        }

        try {
            $contenido = json_decode((string) file_get_contents($ruta), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new RuntimeException(sprintf('La copia del catálogo de SUIT en %s no es un JSON legible.', $ruta), 0, $error);
        } catch (Throwable $error) {
            throw new RuntimeException(sprintf('No se pudo leer la copia del catálogo de SUIT en %s.', $ruta), 0, $error);
        }

        if (! is_array($contenido) || ! isset($contenido['tramites']) || ! is_array($contenido['tramites'])) {
            throw new RuntimeException(sprintf('La copia del catálogo de SUIT en %s no tiene la forma esperada.', $ruta));
        }

        /** @var list<array<string, mixed>> $filas */
        $filas = array_values(array_filter($contenido['tramites'], 'is_array'));

        return $filas;
    }

    /**
     * El informe de la ingesta.
     *
     * @param  array{
     *     pedidos: int,
     *     reanudados: int,
     *     traidos: int,
     *     creados: int,
     *     actualizados: int,
     *     protegidos: int,
     *     completos: int,
     *     incompletos: list<array{codigo: string, faltan: list<string>}>,
     *     faltantes_por_campo: array<string, int>,
     *     fallidos: list<array{codigo: string, motivo: string}>,
     *     esperas_por_tasa: int,
     * } $resultado
     */
    private function informe(array $resultado): void
    {
        $this->newLine();
        $this->info('── Ingesta ─────────────────────────────────────────────');

        $this->line(sprintf(
            'Pedidos: %d · ya estaban hechos: %d · fichas traídas: %d',
            $resultado['pedidos'],
            $resultado['reanudados'],
            $resultado['traidos'],
        ));
        $this->line(sprintf(
            'Creados: %d · actualizados: %d · dejados sin tocar porque los corrigió una persona: %d',
            $resultado['creados'],
            $resultado['actualizados'],
            $resultado['protegidos'],
        ));

        // La cuenta que importa para poder certificar la integración: cuántos
        // trámites tienen sus seis atributos obligatorios. Se cuenta sobre el
        // catálogo entero y no sobre lo que se acaba de traer, porque una ingesta
        // reanudada no toca los que ya estaban y el informe diría «0 completos» de
        // una ejecución que no tenía nada que hacer.
        $delCatalogo = Tramite::query()->get();
        $conSeis = 0;

        /** @var array<string, int> $faltantesEnElCatalogo */
        $faltantesEnElCatalogo = [];

        foreach ($delCatalogo as $tramite) {
            $faltan = $this->atributosQueFaltan($tramite);

            if ($faltan === []) {
                $conSeis++;

                continue;
            }

            foreach ($faltan as $campo) {
                $faltantesEnElCatalogo[$campo] = ($faltantesEnElCatalogo[$campo] ?? 0) + 1;
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Catálogo: %d trámites en la base · %d con los seis atributos obligatorios · %d sin ellos',
            $delCatalogo->count(),
            $conSeis,
            $delCatalogo->count() - $conSeis,
        ));

        if ($conSeis === $delCatalogo->count() && $delCatalogo->count() > 0) {
            // El número sale del catálogo y no se escribe a mano. Estaba fijado en
            // «Los 124», y con el catálogo a 123 —que es como quedó mientras
            // T28661 no tenía término— la frase habría dicho 124 sobre una cuenta
            // que el propio informe acababa de imprimir como 123: la línea que
            // declara el catálogo listo para certificar sería justo la que miente.
            $this->line(sprintf(
                'Los %d tienen sus seis atributos: la información está lista para certificarse en el SUIT.',
                $conSeis,
            ));
        }

        if ($faltantesEnElCatalogo !== []) {
            $this->newLine();
            $this->warn('Campos obligatorios que faltan en el catálogo, por campo:');

            foreach ($faltantesEnElCatalogo as $campo => $cuantos) {
                $this->line(sprintf('  · %s: %d', $campo, $cuantos));
            }
        }

        if ($resultado['incompletos'] !== []) {
            $this->newLine();
            $this->warn('Los trámites a los que les falta algo, con lo que les falta:');

            foreach ($resultado['incompletos'] as $incompleto) {
                $this->line(sprintf('  · %s — %s', $incompleto['codigo'], implode(', ', $incompleto['faltan'])));
            }
        }

        if ($resultado['fallidos'] !== []) {
            $this->newLine();
            $this->error(sprintf('Fallaron %d trámites. El motivo de cada uno:', count($resultado['fallidos'])));

            foreach ($resultado['fallidos'] as $fallo) {
                $this->line(sprintf('  · %s — %s', $fallo['codigo'], $fallo['motivo']));
            }

            $this->line('Vuelva a ejecutar el comando: los que ya se trajeron no se repiten.');
        }

        $this->newLine();
        $this->line(sprintf(
            'Esperas por límite de tasa de la fuente: %d (tope declarado: %d reintentos por petición).',
            $resultado['esperas_por_tasa'],
            ClienteFichaGovCo::MAXIMO_REINTENTOS,
        ));
    }

    /**
     * Los atributos obligatorios que le faltan a un trámite ya guardado.
     *
     * @return list<string>
     */
    private function atributosQueFaltan(Tramite $tramite): array
    {
        $valores = [
            'modalidad' => $tramite->modalidad?->value,
            'tiene_costo' => $tramite->tiene_costo?->value,
            'tiempo_solucion_dias' => $tramite->tiempo_solucion_dias,
            'canal_inicio' => $tramite->canal_inicio?->value,
            'consulta_estado' => $tramite->consulta_estado,
            'requisitos' => $tramite->requisitos,
            'url_ficha_gov_co' => $tramite->url_ficha_gov_co,
        ];

        $faltan = [];

        // Se recorre la lista de atributos del mapeo y no la de este arreglo, para
        // que añadir un atributo obligatorio al contrato no deje este informe sin
        // contarlo: un atributo que no esté en `$valores` sale como ausente, que es
        // el lado por el que conviene equivocarse —informar de menos nunca—.
        foreach (MapeoFicha::ATRIBUTOS as $atributo) {
            $valor = $valores[$atributo] ?? null;

            if ($valor === null || $valor === '' || $valor === []) {
                $faltan[] = $atributo;
            }
        }

        return $faltan;
    }

    /**
     * La conciliación con el listado del sitio del Distrito.
     *
     * **Reporta y no decide.** El comando no añade, borra ni renombra nada a partir
     * de esto: enumera las diferencias con sus códigos y sus nombres para que la
     * Entidad las resuelva. Elegir por su cuenta qué trámites tiene la Alcaldía
     * sería publicar y despublicar actos administrativos sin competencia para
     * ello.
     *
     * **Los nombres que se comparan son los de SUIT, no los del catálogo, y la
     * diferencia no es un detalle.** Hasta ahora esta comparación se hacía contra
     * `tramites.nombre`, y eso deja de ser el nombre de SUIT en cuanto la ingesta
     * escribe: la ficha oficial devuelve `NombreEstandarizado`, que corrige la
     * puntuación de la fuente —«clubes deportivos,clubes promotores» pasa a
     * «clubes deportivos, clubes promotores»— y con ello reescribe el nombre de 7
     * de los 124 trámites.
     *
     * El efecto se midió: la misma conciliación da **23** discrepancias de nombre
     * contra los títulos de SUIT y **20** contra el catálogo ya ingestado. Sólo
     * cambia esa cifra; las otras cuatro —16, 2, 8 y 2— salen iguales por los dos
     * caminos, porque los códigos no se tocan. Se comparan los títulos de SUIT
     * porque es lo que el informe **dice** que compara —«el mismo código con
     * nombres distintos entre las dos fuentes»— y porque el número que la Entidad
     * usa para decidir no puede depender de si la ingesta se ha ejecutado ya o no:
     * un informe que cambia según cuándo se mire no sirve para decidir.
     */
    private function conciliacion(ReconciliacionSuit $conciliacion): void
    {
        try {
            $comparacion = $conciliacion->comparar($this->titulosDeSuit());
        } catch (RuntimeException $fallo) {
            $this->newLine();
            $this->error($fallo->getMessage());

            return;
        }

        $this->newLine();
        $this->info('── Conciliación con el sitio del Distrito ──────────────');
        $this->line(sprintf(
            'SUIT: %d códigos · sitio del Distrito: %d entradas, %d códigos únicos (%s)',
            $comparacion['codigos_suit'],
            $comparacion['entradas_distrito'],
            $comparacion['codigos_distrito'],
            ReconciliacionSuit::URL_DISTRITO,
        ));

        $this->newLine();
        $this->warn('Estas diferencias las resuelve la Entidad. El comando no cambia nada por ellas.');

        $this->line(sprintf(
            '\n  · En SUIT y NO en el sitio del Distrito: %d',
            count($comparacion['solo_suit']),
        ));
        foreach ($comparacion['solo_suit'] as $fila) {
            $this->line(sprintf('      %s — %s', $fila['codigo'], $fila['nombre']));
        }

        $this->line(sprintf(
            '\n  · En el sitio del Distrito y NO en SUIT: %d',
            count($comparacion['solo_distrito']),
        ));
        foreach ($comparacion['solo_distrito'] as $fila) {
            $this->line(sprintf('      %s — %s', $fila['codigo'], $fila['nombre']));
        }

        $this->line(sprintf(
            '\n  · Códigos repetidos en el listado del Distrito: %d',
            count($comparacion['duplicados']),
        ));
        foreach ($comparacion['duplicados'] as $fila) {
            $this->line(sprintf('      %s — %d veces', $fila['codigo'], count($fila['nombres'])));
        }

        $this->line(sprintf(
            '\n  · El mismo trámite con dos nombres distintos: %d',
            count($comparacion['dos_nombres']),
        ));
        foreach ($comparacion['dos_nombres'] as $fila) {
            $this->line(sprintf('      %s — %s', $fila['codigo'], implode(' | ', $fila['nombres'])));
        }

        $this->line(sprintf(
            '\n  · El mismo código con nombres distintos entre las dos fuentes: %d',
            count($comparacion['nombres_diferentes']),
        ));
        foreach ($comparacion['nombres_diferentes'] as $fila) {
            $this->line(sprintf(
                '      %s — SUIT: «%s» · Distrito: «%s»',
                $fila['codigo'],
                $fila['suit'],
                implode('» / «', $fila['distrito']),
            ));
        }
    }
}
