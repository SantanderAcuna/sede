<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Parámetros del listado de usuarios del panel. La autorización la hace la ruta. */
final class ListarUsuariosPanelRequest extends FormRequest
{
    public const ESTADOS = ['activo', 'inactivo', 'bloqueado', 'pendiente'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'estado' => ['sometimes', 'nullable', Rule::in(self::ESTADOS)],
            'rol' => ['sometimes', 'nullable', 'string', 'max:60'],
        ];
    }
}
