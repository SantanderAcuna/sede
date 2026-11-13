<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Valida la creación de una política propia de la Entidad desde el panel.
 *
 * Mismas reglas de forma que la edición, pero el nombre y al menos un apartado son obligatorios.
 * Que la política quede completa lo decide `Politica::problemas()` en el controlador.
 */
final class CrearPoliticaRequest extends ActualizarPoliticaRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $reglas = parent::rules();
        $reglas['nombre'] = ['required', 'string', 'max:200'];
        $reglas['secciones'] = ['required', 'array', 'min:1', 'max:30'];

        return $reglas;
    }
}
