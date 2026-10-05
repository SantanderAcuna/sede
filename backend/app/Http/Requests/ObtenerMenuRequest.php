<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para obtener el árbol de menú institucional.
 */
final class ObtenerMenuRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para esta petición.
     */
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
            'rol' => ['sometimes', 'string', 'in:sitio,panel,admin'],
        ];
    }
}
