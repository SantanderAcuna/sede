<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Parámetros del listado público de noticias: página, tamaño (máx. 100, como el
 * resto de colecciones del contrato) y, opcionalmente, sólo las destacadas.
 */
final class ListarNoticiasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'destacada' => ['sometimes', 'boolean'],
        ];
    }
}
