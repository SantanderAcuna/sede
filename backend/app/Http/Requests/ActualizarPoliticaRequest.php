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
     * Los párrafos en blanco se descartan antes de validar.
     *
     * Laravel convierte «   » en `null` (y `null` no es una cadena): el rechazo
     * llegaba como «validation.string», que no le dice nada a quien edita. Sin los
     * párrafos vacíos, un apartado sin texto llega a `Politica::problemas()`, que
     * explica qué apartado falta.
     */
    protected function prepareForValidation(): void
    {
        $secciones = $this->input('secciones');
        if (! is_array($secciones)) {
            return;
        }

        foreach ($secciones as $i => $seccion) {
            if (is_array($seccion) && isset($seccion['parrafos']) && is_array($seccion['parrafos'])) {
                $secciones[$i]['parrafos'] = array_values(array_filter(
                    $seccion['parrafos'],
                    static fn (mixed $parrafo): bool => $parrafo !== null && (! is_string($parrafo) || trim($parrafo) !== ''),
                ));
            }
        }

        $this->merge(['secciones' => $secciones]);
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
            'secciones.*.parrafos' => ['present', 'array', 'max:30'],
            'secciones.*.parrafos.*' => ['string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'secciones.*.id.required_with' => 'Cada apartado necesita un identificador.',
            'secciones.*.id.regex' => 'El identificador de un apartado sólo admite minúsculas, números y guiones.',
            'secciones.*.id.distinct' => 'Hay dos apartados con el mismo identificador.',
            'secciones.*.titulo.required_with' => 'Cada apartado necesita un título.',
            'secciones.*.parrafos.present' => 'Cada apartado necesita su texto.',
            'secciones.*.parrafos.array' => 'El texto de un apartado debe ser una lista de párrafos.',
            'secciones.*.parrafos.*.string' => 'Cada párrafo debe ser texto.',
            'secciones.*.parrafos.*.max' => 'Un párrafo no puede superar los 5000 caracteres.',
            'secciones.min' => 'La política necesita al menos un apartado.',
            'secciones.max' => 'Una política no puede tener más de 30 apartados.',
        ];
    }
}
