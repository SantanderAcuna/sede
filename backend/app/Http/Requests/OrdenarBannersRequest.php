<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * El nuevo orden del carrusel y el orden que vio quien lo guarda.
 *
 * `antes` permite detectar que otra persona cambió la lista mientras esta la tenía abierta (409 en el servicio).
 * La comparación de la lista con la real se hace allí, con las filas bloqueadas: hacerla aquí, sin bloqueo,
 * deja pasar un cambio que ocurra entre la validación y la escritura.
 */
final class OrdenarBannersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Un uuid en mayúsculas es el mismo uuid: se normaliza para que la comparación no falle por la forma de escribirlo. */
    protected function prepareForValidation(): void
    {
        $normalizar = static fn (mixed $v): mixed => is_array($v)
            ? array_map(static fn (mixed $u): mixed => is_string($u) ? strtolower($u) : $u, $v)
            : $v;

        $this->merge([
            'uuids' => $normalizar($this->input('uuids')),
            'antes' => $normalizar($this->input('antes')),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'uuids' => ['required', 'array', 'min:1'],
            'uuids.*' => ['string', 'uuid', 'distinct'],
            'antes' => ['required', 'array', 'min:1'],
            'antes.*' => ['string', 'uuid', 'distinct'],
        ];
    }

    /** @return list<string> */
    public function uuids(): array
    {
        /** @var list<string> $uuids */
        $uuids = $this->validated('uuids');

        return array_values($uuids);
    }

    /** @return list<string> */
    public function antes(): array
    {
        /** @var list<string> $antes */
        $antes = $this->validated('antes');

        return array_values($antes);
    }
}
