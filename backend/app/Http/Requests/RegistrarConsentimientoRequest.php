<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Consentimiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el registro de un consentimiento (T-26, AC-26.4).
 *
 * El endpoint es público —lo llama el navegador del ciudadano—, así que la
 * validación es cerrada: sólo los eventos y categorías conocidos, y nada más se
 * guarda aunque llegue en el cuerpo.
 */
final class RegistrarConsentimientoRequest extends FormRequest
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
            'evento' => ['required', 'string', Rule::in(Consentimiento::EVENTOS)],
            'categorias' => ['present', 'array', 'max:'.count(Consentimiento::CATEGORIAS)],
            'categorias.*' => ['string', 'distinct', Rule::in(Consentimiento::CATEGORIAS)],
            'version_politica' => ['required', 'integer', 'min:1', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'evento.in' => 'El evento debe ser uno de: '.implode(', ', Consentimiento::EVENTOS).'.',
            'categorias.*.in' => 'Las categorías válidas son: '.implode(', ', Consentimiento::CATEGORIAS).'.',
        ];
    }
}
