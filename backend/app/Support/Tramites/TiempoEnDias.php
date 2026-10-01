<?php

declare(strict_types=1);

namespace App\Support\Tramites;

/**
 * El término de solución declarado por la fuente, en días.
 *
 * La fuente lo declara en texto libre —«10 DIA(S) HÁBIL(ES)», «2 HORA(S)»,
 * «3 MES(ES)»— y una de cada cuatro fichas lo declara en una unidad que no son
 * días. Convertir sin decirlo dejaría un dato que **parece declarado por la fuente
 * y no lo está**, así que la conversión viaja con la nota que la explica y el
 * ciudadano la lee en la ficha del trámite.
 *
 * No se distingue «DIA(S)» de «DIA(S) HÁBIL(ES)»: el sufijo aparece y desaparece
 * entre fichas del mismo tamaño —hay trámites de «90 DIA(S)» y de «90 DIA(S)
 * HÁBIL(ES)»—, así que tratarlo como una unidad distinta sería leer en la fuente
 * una distinción que no hace. Es un límite conocido y se declara en vez de
 * disimularse.
 *
 * Vive en `Support` y no en un servicio porque lo usan dos caminos que no pueden
 * discrepar: la siembra del catálogo desde la copia congelada de SUIT y la ingesta
 * de la ficha oficial. Dos conversiones distintas del mismo término darían dos
 * plazos distintos para el mismo trámite según por dónde entrara.
 */
final class TiempoEnDias
{
    /** Un día de término son ocho horas de jornada hábil. */
    public const HORAS_POR_DIA = 8;

    /** Un mes de término son treinta días. */
    public const DIAS_POR_MES = 30;

    /**
     * Convierte el término declarado y explica la conversión cuando la hubo.
     *
     * @return array{dias: int, nota: string|null}|null
     *                                                  Nulo cuando la fuente no lo declara o no se entiende: un término
     *                                                  ilegible se descarta, no se aproxima.
     */
    public static function desde(?string $declarado): ?array
    {
        if ($declarado === null || trim($declarado) === '') {
            return null;
        }

        $declarado = trim($declarado);

        if (preg_match('/^(\d+)\s*(DIA|HORA|MES)/u', mb_strtoupper($declarado), $coincidencias) !== 1) {
            return null;
        }

        $cantidad = (int) $coincidencias[1];

        if ($coincidencias[2] === 'HORA') {
            // Hacia arriba y nunca a cero: un trámite de dos horas se resuelve
            // dentro de la jornada, no en cero días.
            $dias = max(1, (int) ceil($cantidad / self::HORAS_POR_DIA));

            return [
                'dias' => $dias,
                'nota' => sprintf(
                    'El término lo declara la fuente como «%s» y se publica en %s: una jornada son %d horas.',
                    $declarado,
                    self::enDias($dias),
                    self::HORAS_POR_DIA,
                ),
            ];
        }

        if ($coincidencias[2] === 'MES') {
            $dias = $cantidad * self::DIAS_POR_MES;

            return [
                'dias' => $dias,
                'nota' => sprintf(
                    'El término lo declara la fuente como «%s» y se publica en %s: la fuente no lo declara en días hábiles y no se convierte sin el calendario de días hábiles del Distrito.',
                    $declarado,
                    self::enDias($dias),
                ),
            ];
        }

        return ['dias' => $cantidad, 'nota' => null];
    }

    /**
     * «1 día hábil» o «3 días hábiles».
     *
     * La nota la lee el ciudadano en la ficha del trámite, y «se publica en 1
     * días» es exactamente el detalle que hace dudar de todo lo demás.
     */
    public static function enDias(int $dias): string
    {
        return $dias === 1 ? '1 día hábil' : sprintf('%d días hábiles', $dias);
    }
}
