<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Integraciones\FuenteFichaGovCoInterface;
use App\Contracts\Repositories\IngestaTramiteRepositoryInterface;
use App\Contracts\Services\IngestaTramitesInterface;
use App\Exceptions\FuenteNoDisponible;
use App\Models\Tramite;
use App\Services\GovCo\ClienteFichaGovCo;
use App\Support\Ingesta\MapeoFicha;
use App\Support\Tramites\FuenteSuit;
use App\Support\Tramites\SlugCatalogo;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * La ingesta de la ficha oficial de los trámites.
 *
 * Recorre los códigos del catálogo, pide la ficha de cada uno, la mapea y deja el
 * catálogo actualizado. Lo que este servicio decide, y por qué:
 *
 * # Reanudable
 *
 * Antes de pedir nada consulta el punto de control y **salta los códigos que ya
 * están completos**. La fuente del Estado limita la tasa y una recolección
 * completa hace más de mil peticiones, así que una ejecución que se corta a la
 * mitad es lo normal: sin esto, cada corte obligaría a empezar de cero y ninguna
 * ejecución llegaría al final.
 *
 * El trámite que se quedó a medias **sí se vuelve a pedir entero**, y es
 * deliberado: guardar su ficha parcial para terminarla después significaría
 * guardar también el teléfono móvil que la fuente trae en los puntos de atención,
 * que es justo lo que la ingesta descarta. Lo que no se repite es el trabajo
 * concluido, que es el que cuesta.
 *
 * # Idempotente y no destructiva
 *
 * `updateOrCreate` por código: volver a ejecutarla no crea filas y no duplica
 * nada. Y **no pisa lo que corrigió una persona**: sólo reescribe los trámites
 * cuya procedencia declarada sea la de la fuente. Un trámite cargado a mano —o
 * corregido por la Entidad, que al corregirlo pone su propia fuente— no lleva esa
 * marca y la ingesta lo respeta. Es el mismo patrón del sembrador, y tiene que
 * serlo: las dos escriben en el mismo catálogo y dos reglas distintas harían que
 * una deshiciera el trabajo de la otra.
 *
 * # Lo que no hace
 *
 * No decide qué trámites tiene la Alcaldía. Recibe la lista de códigos y sólo la
 * recorre; qué entra en el catálogo es una decisión de la Entidad y vive en la
 * fuente ratificada, que es SUIT.
 */
final class IngestaTramites implements IngestaTramitesInterface
{
    public function __construct(
        private readonly FuenteFichaGovCoInterface $fuente,
        private readonly IngestaTramiteRepositoryInterface $avance,
        private readonly MapeoFicha $mapeo,
        private readonly ClienteFichaGovCo $cliente,
    ) {}

    public function ejecutar(array $codigos, ?callable $aviso = null): array
    {
        // Los que ya están hechos. Se leen de una vez y no fila a fila: son 124 y
        // la consulta se repite en cada vuelta si no se trae de antemano.
        /** @var array<string, true> $hechos */
        $hechos = [];
        foreach ($this->avance->completos() as $codigo) {
            $hechos[$codigo] = true;
        }

        // Slug => código, de lo que ya hay en la base. Se parte de ahí para que una
        // ingesta no renombre lo publicado: un slug es la dirección de una ficha.
        /** @var array<string, string> $usados */
        $usados = Tramite::query()->pluck('codigo', 'slug')->all();

        $pedidos = 0;
        $reanudados = 0;
        $traidos = 0;
        $creados = 0;
        $actualizados = 0;
        $protegidos = 0;
        $completos = 0;

        /** @var list<array{codigo: string, faltan: list<string>}> $incompletos */
        $incompletos = [];

        /** @var array<string, int> $faltantesPorCampo */
        $faltantesPorCampo = [];

        /** @var list<array{codigo: string, motivo: string}> $fallidos */
        $fallidos = [];

        foreach ($codigos as $codigo) {
            $pedidos++;

            if (isset($hechos[$codigo])) {
                $reanudados++;

                if ($aviso !== null) {
                    $aviso(sprintf('  · %s — ya estaba hecho, se salta', $codigo));
                }

                continue;
            }

            try {
                $ficha = $this->fuente->ficha($codigo);
            } catch (FuenteNoDisponible $fallo) {
                // Un trámite que no se pudo traer no tumba la ejecución: se anota,
                // se deja su punto de control en `fallido` y se sigue con el
                // siguiente. Parar aquí perdería los 100 que sí se podían traer.
                $this->avance->fallar($codigo, $fallo->getMessage());
                $fallidos[] = ['codigo' => $codigo, 'motivo' => $fallo->getMessage()];

                if ($aviso !== null) {
                    $aviso(sprintf('  · %s — FALLÓ: %s', $codigo, $fallo->getMessage()));
                }

                continue;
            } catch (Throwable $fallo) {
                $this->avance->fallar($codigo, $fallo->getMessage());
                $fallidos[] = ['codigo' => $codigo, 'motivo' => $fallo->getMessage()];

                continue;
            }

            $traidos++;

            $existente = Tramite::query()->where('codigo', $codigo)->first();

            // La marca de la ingesta es la fuente declarada. Un trámite corregido
            // por una persona deja de tenerla y no se toca.
            if ($existente !== null && $existente->procedencia_fuente !== FuenteSuit::NOMBRE) {
                $protegidos++;
                // Se cierra igual: volver a pedir su ficha en cada ejecución sería
                // gastar contra una fuente que limita la tasa para no escribir nada.
                $this->avance->cerrar($codigo);

                if ($aviso !== null) {
                    $aviso(sprintf('  · %s — lo corrigió una persona, no se toca', $codigo));
                }

                continue;
            }

            // El slug ya publicado no se toca: es la dirección de una ficha y
            // cambiarlo rompería los enlaces que el ciudadano tenga guardados.
            $slug = $existente === null
                ? SlugCatalogo::para($ficha->nombre, $codigo, $usados)
                : $existente->slug;
            $atributos = $this->mapeo->atributos($ficha, $slug);
            $faltan = $this->mapeo->atributosQueFaltan($atributos);

            if ($faltan === []) {
                $completos++;
            } else {
                $incompletos[] = ['codigo' => $codigo, 'faltan' => $faltan];

                foreach ($faltan as $campo) {
                    $faltantesPorCampo[$campo] = ($faltantesPorCampo[$campo] ?? 0) + 1;
                }
            }

            // Se publica sólo lo que tiene sus seis atributos obligatorios, y una
            // vez publicado no se despublica: `publicado_en` ya puesto se conserva.
            $yaPublicado = $existente === null ? null : $existente->publicado_en;

            $atributos['publicado_en'] = $yaPublicado ?? ($faltan === [] ? Carbon::now() : null);

            // La escritura va envuelta porque un trámite que no se puede guardar
            // no puede llevarse por delante a los cien que vienen detrás: el
            // comando tiene que **decir** qué falló y seguir, no morir a mitad de
            // camino dejando el catálogo a medias sin decirlo. El caso no es
            // hipotético —pasó, con `T28661`, que la fuente no declara en días— y
            // por eso se marca como fallido y se cuenta.
            try {
                Tramite::updateOrCreate(['codigo' => $codigo], $atributos);
            } catch (Throwable $fallo) {
                $this->avance->fallar($codigo, $fallo->getMessage());
                $fallidos[] = ['codigo' => $codigo, 'motivo' => $fallo->getMessage()];

                if ($aviso !== null) {
                    $aviso(sprintf('  · %s — NO SE PUDO GUARDAR: %s', $codigo, $fallo->getMessage()));
                }

                // El trámite no se guardó, así que tampoco cuenta como traído ni
                // como completo: contarlo inflaría el informe justo en el número
                // que sirve para certificar.
                $traidos--;
                $completos -= $faltan === [] ? 1 : 0;

                if ($faltan !== []) {
                    array_pop($incompletos);

                    foreach ($faltan as $campo) {
                        $faltantesPorCampo[$campo]--;
                    }
                }

                continue;
            }

            if ($existente === null) {
                $creados++;
            } else {
                $actualizados++;
            }

            $this->avance->cerrar($codigo);

            if ($aviso !== null) {
                $aviso(sprintf('  · %s — %s', $codigo, $faltan === [] ? 'completo' : 'faltan: '.implode(', ', $faltan)));
            }
        }

        return [
            'pedidos' => $pedidos,
            'reanudados' => $reanudados,
            'traidos' => $traidos,
            'creados' => $creados,
            'actualizados' => $actualizados,
            'protegidos' => $protegidos,
            'completos' => $completos,
            'incompletos' => $incompletos,
            'faltantes_por_campo' => $faltantesPorCampo,
            'fallidos' => $fallidos,
            'esperas_por_tasa' => $this->cliente->esperasPorTasa(),
        ];
    }

    public function olvidarAvance(): int
    {
        return $this->avance->olvidar();
    }
}
