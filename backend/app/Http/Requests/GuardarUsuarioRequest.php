<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Alta (POST) y cambio (PATCH) de un usuario. En el cambio solo se envía lo que cambia.
 * La autorización —incluida la escalada de privilegios— la hace el servicio.
 */
final class GuardarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $crea = $this->isMethod('POST');
        $existente = $crea ? null : User::query()->where('uuid', (string) $this->route('uuid'))->value('id');
        $obligatorio = $crea ? 'required' : 'sometimes';

        return [
            'name' => [$obligatorio, 'string', 'min:3', 'max:120'],
            'email' => [$obligatorio, 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($existente)],
            'password' => [$obligatorio, 'string', Password::min(12)->letters()->numbers(), 'max:128'],
            'estado' => ['sometimes', Rule::in(ListarUsuariosPanelRequest::ESTADOS)],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Escriba el nombre de la persona.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'email.required' => 'Escriba el correo institucional.',
            'email.email' => 'Escriba un correo válido, por ejemplo nombre@santamarta.gov.co.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'password.required' => 'Escriba una contraseña temporal.',
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
            'password.letters' => 'La contraseña debe incluir letras.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'estado.in' => 'Elija un estado válido: activo, inactivo, bloqueado o pendiente.',
            'roles.*.exists' => 'Uno de los roles elegidos no existe.',
        ];
    }

    /** @return array<string, mixed> */
    public function datos(): array
    {
        return $this->validated();
    }
}
