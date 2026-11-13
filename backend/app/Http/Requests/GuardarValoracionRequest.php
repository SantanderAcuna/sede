<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\TramiteValoracion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * La valoración pública de una ficha. Una valoración vale con la rápida FÁCIL/DIFÍCIL, con las
 * estrellas, o con las dos: por eso cada una es obligatoria sólo cuando falta la otra, para que un
 * envío sin ninguna no se guarde vacío.
 */
final class GuardarValoracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Pública: el contrato declara la operación con `security: []`.
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'valor' => ['required_without:estrellas', 'nullable', Rule::in([TramiteValoracion::FACIL, TramiteValoracion::DIFICIL])],
            'estrellas' => ['required_without:valor', 'nullable', 'integer', 'between:1,5'],
            'comentario' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'valor.required_without' => 'Diga si fue fácil o difícil, o califique con estrellas.',
            'valor.in' => 'La valoración rápida sólo puede ser «fácil» o «difícil».',
            'estrellas.required_without' => 'Califique con estrellas, o diga si fue fácil o difícil.',
            'estrellas.integer' => 'Las estrellas se cuentan con un número.',
            'estrellas.between' => 'Las estrellas van de 1 a 5.',
            'comentario.max' => 'El comentario no puede tener más de 1000 caracteres.',
        ];
    }
}
