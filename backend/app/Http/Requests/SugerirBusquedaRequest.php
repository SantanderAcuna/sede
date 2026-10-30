<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * La validación de las sugerencias del buscador (`GET /busqueda/sugerencias`).
 *
 * Las reglas son las del contrato: `q` entre 3 y 120 caracteres y `limite` entre 1 y
 * 10. Con menos de tres caracteres la respuesta es `422` y el cliente no debe
 * consultar; el servidor lo exige igualmente, porque un cliente distinto del nuestro
 * puede no respetarlo.
 *
 * El texto buscado es dato del ciudadano: aquí sólo se valida y se entrega, y nada de
 * este archivo lo escribe en el registro de la aplicación.
 */
final class SugerirBusquedaRequest extends FormRequest
{
    /** El máximo de sugerencias que el contrato permite. */
    public const LIMITE_MAXIMO = 10;

    public function authorize(): bool
    {
        // Pública: el contrato la declara con `security: []`.
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:3', 'max:120'],
            'limite' => ['sometimes', 'integer', 'min:1', 'max:'.self::LIMITE_MAXIMO],
        ];
    }

    /**
     * El texto escrito, tal cual llegó (el servicio lo normaliza).
     */
    public function texto(): string
    {
        return (string) $this->string('q');
    }

    public function limite(): int
    {
        return $this->integer('limite', self::LIMITE_MAXIMO);
    }

    /**
     * Los mensajes van en español: la aplicación no trae las traducciones de la
     * validación de Laravel y, sin esto, un ciudadano recibiría «The q field must be
     * at least 3 characters».
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.required' => 'Escriba qué desea buscar.',
            'q.string' => 'El texto de búsqueda debe ser texto.',
            'q.min' => 'Escriba al menos 3 caracteres para buscar.',
            'q.max' => 'El texto de búsqueda no puede tener más de 120 caracteres.',
            'limite.integer' => 'El campo limite debe ser un número entero.',
            'limite.min' => 'El campo limite no puede ser menor que 1.',
            'limite.max' => 'El campo limite no puede ser mayor que '.self::LIMITE_MAXIMO.'.',
        ];
    }
}
