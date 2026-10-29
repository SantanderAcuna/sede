<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la edición de una política desde el panel (T-20).
 *
 * La forma se valida aquí; que la política quede completa (todos sus apartados,
 * acto administrativo cuando ya no es un ejemplo) lo decide `Politica::problemas()`
 * en el controlador, que es quien ve el resultado de combinar lo guardado con lo
 * recibido.
 */
final class ActualizarPoliticaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:200'],
            'version' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'vigente_desde' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'acto_tipo' => ['sometimes', 'nullable', 'string', 'max:60'],
            'acto_numero' => ['sometimes', 'nullable', 'string', 'max:60'],
            'acto_fecha' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'es_ejemplo' => ['sometimes', 'boolean'],
            'secciones' => ['sometimes', 'array', 'min:1', 'max:30'],
            'secciones.*.id' => ['required_with:secciones', 'string', 'regex:/^[a-z0-9-]+$/', 'max:80', 'distinct'],
            'secciones.*.titulo' => ['required_with:secciones', 'string', 'max:200'],
            'secciones.*.parrafos' => ['required_with:secciones', 'array', 'min:1', 'max:30'],
            'secciones.*.parrafos.*' => ['string', 'max:5000'],
        ];
    }
}
