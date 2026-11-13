<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Noticia;
use Illuminate\Foundation\Http\FormRequest;

/**
 * La imagen de una noticia y su texto alternativo, siempre juntos: sin `alt` no
 * se guarda la imagen (Res. MinTIC 1519/2020, Anexo 2). Sólo formatos de imagen
 * (sin SVG, que puede llevar scripts) y hasta 5 MB.
 */
final class SubirImagenNoticiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Un alt de espacios es un alt vacío.
        if ($this->has('alt')) {
            $this->merge(['alt' => trim((string) $this->input('alt'))]);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'imagen' => ['required', 'file', 'max:5120', 'mimes:png,jpg,jpeg,webp'],
            'alt' => ['required', 'string', 'max:'.Noticia::ALT_MAX],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'imagen.max' => 'La imagen supera el tamaño máximo permitido (5 MB).',
            'imagen.mimes' => 'Sólo se aceptan imágenes PNG, JPG o WebP.',
            'alt.required' => 'La imagen necesita su texto alternativo (alt): describe lo que muestra para quien no la ve.',
        ];
    }
}
