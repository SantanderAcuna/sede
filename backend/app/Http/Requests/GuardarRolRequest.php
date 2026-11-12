<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Acceso\ReglasDeAcceso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Alta (POST) y cambio (PATCH) de un rol con sus permisos. */
final class GuardarRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $crea = $this->isMethod('POST');

        return [
            'name' => [
                $crea ? 'required' : 'sometimes',
                'string', 'min:3', 'max:60', 'regex:/^[\pL\pN][\pL\pN \-]*$/u',
                Rule::notIn([ReglasDeAcceso::ROL_SUPER]),
                Rule::unique('roles', 'name')->ignore($this->route('id')),
            ],
            'permisos' => ['sometimes', 'array'],
            'permisos.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Escriba el nombre del rol.',
            'name.min' => 'El nombre del rol debe tener al menos 3 caracteres.',
            'name.regex' => 'Use solo letras, números, espacios y guiones en el nombre del rol.',
            'name.unique' => 'Ya existe un rol con ese nombre.',
            'name.not_in' => 'Ese nombre está reservado.',
            'permisos.*.exists' => 'Uno de los permisos elegidos no existe.',
        ];
    }
}
