<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Sondas de salud de la sede.
 *
 * Dos sondas con propósitos distintos, y la diferencia importa:
 *
 * - **Vida** responde mientras el proceso esté en pie, aunque una dependencia
 *   esté caída. Que falle significa que el proceso está muerto y hay que
 *   reiniciarlo.
 * - **Preparación** responde sólo si las dependencias contestan. Que falle
 *   significa que el proceso vive pero no puede atender, y el punto de entrada
 *   no debe enviarle tráfico.
 *
 * Confundirlas produce reinicios en bucle cuando lo que falla es la base de
 * datos: el proceso está sano y se le mata una y otra vez.
 *
 * Ninguna revela el motivo del fallo. El nombre del componente caído se declara,
 * porque estas rutas sólo se atienden desde la red interna; el error concreto,
 * nunca.
 */
final class SaludController
{
    /**
     * Sonda de vida.
     *
     * No consulta ninguna dependencia a propósito.
     */
    public function vida(): JsonResponse
    {
        return response()->json(['estado' => 'vivo']);
    }

    /**
     * Sonda de preparación.
     *
     * Comprueba las tres dependencias que la aplicación necesita para atender
     * una petición: la base de datos, la caché y el almacenamiento.
     */
    public function preparacion(): JsonResponse
    {
        $componentes = [
            'base_de_datos' => $this->responde(fn () => DB::connection()->getPdo() !== null),
            'cache' => $this->responde(fn () => Redis::connection()->ping() !== null),
            'almacenamiento' => $this->responde(fn () => Storage::disk()->exists('.') !== null),
        ];

        $listo = ! in_array(false, $componentes, true);

        return response()->json(
            [
                'estado' => $listo ? 'listo' : 'no_listo',
                'componentes' => $componentes,
            ],
            $listo ? 200 : 503,
        );
    }

    /**
     * Ejecuta una comprobación y devuelve si respondió.
     *
     * El error se descarta deliberadamente: la respuesta no debe revelar la
     * implementación ni el motivo del fallo.
     */
    private function responde(callable $comprobacion): bool
    {
        try {
            return (bool) $comprobacion();
        } catch (Throwable) {
            return false;
        }
    }
}
