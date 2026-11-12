<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Banner;
use Illuminate\Foundation\Http\FormRequest;

/** La imagen de una diapositiva y su texto alternativo, siempre juntos. Sin SVG; hasta 5 MB. */
final class SubirImagenBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('alt')) {
            $this->merge(['alt' => trim((string) $this->input('alt'))]);
        }
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'imagen' => ['required', 'file', 'max:5120', 'mimes:png,jpg,jpeg,webp'],
            'alt' => ['required', 'string', 'max:'.Banner::ALT_MAX],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'imagen.required' => 'Elija una imagen.',
            'imagen.max' => 'La imagen supera el tamaño máximo permitido (5 MB).',
            'imagen.mimes' => 'Solo se aceptan imágenes PNG, JPG o WebP.',
            'alt.required' => 'La imagen necesita su texto alternativo: describe lo que muestra para quien no la ve.',
        ];
    }
}
