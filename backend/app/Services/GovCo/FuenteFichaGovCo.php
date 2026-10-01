<?php

declare(strict_types=1);

namespace App\Services\GovCo;

use App\Contracts\Integraciones\FuenteFichaGovCoInterface;
use App\Support\Ingesta\AccionFuente;
use App\Support\Ingesta\FichaFuente;

/**
 * La ficha oficial, leída de la API de contenido de GOV.CO.
 *
 * # Qué endpoints se piden, y por qué
 *
 * | Endpoint | Aporta |
 * |---|---|
 * | `FichaTramite/GetTipoTramiteFichaEspecificaById` | La modalidad, el propósito y el enlace del trámite |
 * | `ServiciosYTramites/GetServicioYTramiteEspecifico` | Si tiene costo y el término declarado |
 * | `FichaTramite/GetDataFichaResult` | Qué se obtiene y la observación del término |
 * | `FichaTramite/GetTiposAudienciaById` | Los perfiles a los que va dirigido |
 * | `FichaTramite/GetPuntosAtencionById` | Los puntos de atención presencial |
 * | `FichaTramite/GetNormatividadById` | La normativa que lo faculta |
 * | `FichaTramite/GetMediosSeguimientoPersonal` y `…NoPersonal` | Por dónde se sigue |
 * | `FichaTramite/GetMomentosByIdAudiencia` | Los pasos, por perfil |
 * | `FichaTramite/GetDataFichaByIdTramiteAudienciaIdMomento` | Los requisitos y el pago de cada paso |
 *
 * # Un endpoint de la lista no sirve, y se dice
 *
 * La recolección anterior anotó `GetPagosByMomentoIdAudiencia/{id}/{detalle}/{momentoId}`
 * como la fuente del importe, la moneda, la URL de pago y las cuentas. **Ese
 * endpoint responde HTTP 500 para todo lo que se le pidió**, con
 * `{"errors":[{"title":"Parameter value '377309' is out of range."}]}`, y lo mismo
 * con el `MomentoId`, con el `AccionCondicionId` y con un perfil numérico. No hay
 * manera de hacerlo contestar.
 *
 * No hace falta: **los mismos datos vienen dentro del bloque `PAGO` de
 * `GetDataFichaByIdTramiteAudienciaIdMomento`** —`PagoEnlineaUrl`, `Moneda`,
 * `TipoValor`, `Valor` y `entidadesPago` con sus cinco cuentas—, que es el
 * endpoint que sí responde. Por eso éste es el que se pide y aquél no se pide: no
 * se llama a un servicio que se sabe roto para luego descartar su error.
 *
 * # Los perfiles y los momentos
 *
 * `GetMomentosByIdAudiencia` exige el **texto** del perfil, no su índice, y
 * devuelve los mismos pasos para los cuatro. Se comprobó sobre `T2674`: los
 * identificadores de momento —`461896`, `461897`— y sus descripciones son
 * idénticos para `Ciudadano`, `Extranjero`, `Empresa privada` y `Entidad pública`.
 * Por eso los pasos se piden **una sola vez**, con el primer perfil declarado, y
 * la lista completa de perfiles se publica igual: pedir cuatro veces lo mismo
 * multiplicaría por cuatro el gasto contra una fuente que limita la tasa para no
 * ganar ningún dato.
 *
 * Ese es el límite conocido de esta ingesta, y se declara en vez de disimularse:
 * si la fuente empezara a devolver pasos distintos por perfil, esta lectura se
 * quedaría con los del primero.
 *
 * **Y no hay ninguna comprobación automática de eso.** Una versión anterior de
 * este comentario afirmaba que se comprobaba «en cada ejecución con la opción
 * `--verificar-perfiles`»; esa opción no existe en `tramites:ingerir`, así que la
 * afirmación era falsa y una sede electrónica no puede permitirse documentar una
 * verificación que nadie hace. La comprobación se hizo **a mano, una vez**, sobre
 * `T2674`, y eso es todo lo que se puede afirmar: los pasos de los cuatro
 * perfiles coincidían aquel día. Comprobarlo en cada ejecución multiplicaría por
 * cuatro el gasto contra una fuente que limita la tasa, así que no se hace, y
 * queda declarado como límite abierto en vez de como garantía.
 */
final class FuenteFichaGovCo implements FuenteFichaGovCoInterface
{
    /**
     * El perfil con el que se piden los pasos cuando la fuente no declara ninguno.
     *
     * No es una invención: es el perfil que la fuente devuelve para todos los
     * trámites comprobados, y el que la recolección anterior usó.
     */
    private const PERFIL_POR_DEFECTO = 'Ciudadano';

    public function __construct(private readonly ClienteFichaGovCo $cliente) {}

    public function ficha(string $codigo): FichaFuente
    {
        $numero = ClienteFichaGovCo::numero($codigo);

        /** @var list<string> $faltantes */
        $faltantes = [];

        // Cada bloque se pide por separado y se tolera que falle: la fuente del
        // Estado devuelve 500 para un endpoint concreto con cierta frecuencia, y
        // perder la ficha entera por la observación del término sería peor que
        // publicarla sin ese dato. Lo que sí se anota es que faltó.
        $tipo = ClienteFichaGovCo::opcional(fn (): mixed => $this->cliente->pedir("FichaTramite/GetTipoTramiteFichaEspecificaById/{$numero}", $codigo));
        if ($tipo['fallo'] !== null) {
            $faltantes[] = 'modalidad';
        }

        $servicio = ClienteFichaGovCo::opcional(fn (): mixed => $this->cliente->pedir("ServiciosYTramites/GetServicioYTramiteEspecifico/{$numero}", $codigo));
        if ($servicio['fallo'] !== null) {
            $faltantes[] = 'costo';
        }

        $resultado = ClienteFichaGovCo::opcional(fn (): mixed => $this->cliente->pedir("FichaTramite/GetDataFichaResult/{$numero}", $codigo));
        if ($resultado['fallo'] !== null) {
            $faltantes[] = 'resultado';
        }

        $audiencias = ClienteFichaGovCo::opcional(fn (): mixed => $this->cliente->pedir("FichaTramite/GetTiposAudienciaById/{$numero}", $codigo));
        if ($audiencias['fallo'] !== null) {
            $faltantes[] = 'perfiles';
        }

        $puntos = ClienteFichaGovCo::opcional(fn (): mixed => $this->cliente->pedir("FichaTramite/GetPuntosAtencionById/{$numero}", $codigo));
        if ($puntos['fallo'] !== null) {
            $faltantes[] = 'puntos_atencion';
        }

        $normativa = ClienteFichaGovCo::opcional(fn (): mixed => $this->cliente->pedir("FichaTramite/GetNormatividadById/{$numero}", $codigo));
        if ($normativa['fallo'] !== null) {
            $faltantes[] = 'normativa';
        }

        $seguimientoPersonal = ClienteFichaGovCo::opcional(fn (): mixed => $this->cliente->pedir("FichaTramite/GetMediosSeguimientoPersonal/{$numero}", $codigo));
        if ($seguimientoPersonal['fallo'] !== null) {
            $faltantes[] = 'canales_consulta_estado';
        }

        $seguimientoNoPersonal = ClienteFichaGovCo::opcional(fn (): mixed => $this->cliente->pedir("FichaTramite/GetMediosSeguimientoNoPersonal/{$numero}", $codigo));
        if ($seguimientoNoPersonal['fallo'] !== null) {
            $faltantes[] = 'canales_consulta_estado';
        }

        $bloqueTipo = ClienteFichaGovCo::objeto($tipo['valor']);
        $bloqueServicio = ClienteFichaGovCo::objeto($servicio['valor']);
        $bloqueResultado = ClienteFichaGovCo::objeto($resultado['valor']);

        $perfiles = ClienteFichaGovCo::lista($audiencias['valor']);

        $acciones = $this->acciones($numero, $codigo, $perfiles, $faltantes);

        return new FichaFuente(
            codigo: $codigo,
            nombre: ClienteFichaGovCo::texto($bloqueTipo, 'NombreEstandarizado')
                ?? ClienteFichaGovCo::texto($bloqueServicio, 'NombreTramite'),
            proposito: ClienteFichaGovCo::texto($bloqueTipo, 'Proposito')
                ?? ClienteFichaGovCo::texto($bloqueServicio, 'DescripcionTramite'),
            tipoTramite: ClienteFichaGovCo::texto($bloqueTipo, 'Tipotramite'),
            urlTramiteEnLinea: ClienteFichaGovCo::texto($bloqueTipo, 'UrlTramiteEnLinea'),
            paginaWeb: ClienteFichaGovCo::texto($bloqueTipo, 'PaginaWeb'),
            costoDeclarado: ClienteFichaGovCo::texto($bloqueServicio, 'Costo'),
            tiempoObtencion: ClienteFichaGovCo::texto($bloqueServicio, 'TiempoObtencion'),
            resultadoObtiene: ClienteFichaGovCo::texto($bloqueResultado, 'ResultadoObtiene'),
            observacionTiempo: ClienteFichaGovCo::texto($bloqueResultado, 'ObservacionTiempoObtencion'),
            perfiles: $perfiles,
            puntosAtencion: ClienteFichaGovCo::lista($puntos['valor']),
            normativa: ClienteFichaGovCo::lista($normativa['valor']),
            seguimientoPersonal: ClienteFichaGovCo::lista($seguimientoPersonal['valor']),
            seguimientoNoPersonal: ClienteFichaGovCo::lista($seguimientoNoPersonal['valor']),
            acciones: $acciones,
            faltantes: array_values(array_unique($faltantes)),
        );
    }

    /**
     * Los pasos del trámite y lo que cada uno exige.
     *
     * Se piden los pasos del primer perfil declarado —el segundo parámetro va como
     * **texto**, que es la trampa que ya hizo fracasar una recolección anterior— y
     * de cada paso se piden sus acciones, que es donde viven los documentos, las
     * condiciones, las solicitudes y el pago.
     *
     * @param  list<array<string, mixed>>  $perfiles
     * @param  list<string>  $faltantes
     * @return list<AccionFuente>
     */
    private function acciones(string $numero, string $codigo, array $perfiles, array &$faltantes): array
    {
        $perfil = ClienteFichaGovCo::texto($perfiles[0] ?? [], 'detalle') ?? self::PERFIL_POR_DEFECTO;
        $perfilUrl = rawurlencode($perfil);

        $momentos = ClienteFichaGovCo::opcional(
            fn (): mixed => $this->cliente->pedir("FichaTramite/GetMomentosByIdAudiencia/{$numero}/{$perfilUrl}", $codigo)
        );

        if ($momentos['fallo'] !== null) {
            $faltantes[] = 'requisitos';

            return [];
        }

        $acciones = [];

        foreach (ClienteFichaGovCo::lista($momentos['valor']) as $momento) {
            $momentoId = ClienteFichaGovCo::texto($momento, 'MomentoId');

            // Sin identificador de momento no se puede pedir su contenido, y un
            // paso sin identificador no es un paso: se salta en vez de inventarle
            // uno.
            if ($momentoId === null) {
                continue;
            }

            $descripcion = ClienteFichaGovCo::texto($momento, 'Descripcion');

            $detalle = ClienteFichaGovCo::opcional(
                fn (): mixed => $this->cliente->pedir(
                    "FichaTramite/GetDataFichaByIdTramiteAudienciaIdMomento/{$numero}/{$perfilUrl}/{$momentoId}",
                    $codigo,
                )
            );

            if ($detalle['fallo'] !== null) {
                $faltantes[] = 'requisitos';

                continue;
            }

            $bloque = ClienteFichaGovCo::objeto($detalle['valor']);

            foreach (ClienteFichaGovCo::lista($bloque['acciones'] ?? []) as $accion) {
                $acciones[] = new AccionFuente(
                    tipo: ClienteFichaGovCo::texto($accion, 'TipoAccionCondicion') ?? 'OTRO',
                    orden: ClienteFichaGovCo::entero($accion, 'Orden') ?? 0,
                    momento: $descripcion,
                    cruda: $accion,
                );
            }
        }

        // El orden de la fuente no es estable entre momentos —cada uno numera sus
        // acciones desde uno—, así que se ordena por el momento en que aparecieron
        // y por su orden dentro de él, que es el recorrido que sigue el ciudadano.
        usort($acciones, static fn (AccionFuente $a, AccionFuente $b): int => $a->orden <=> $b->orden);

        return $acciones;
    }
}
