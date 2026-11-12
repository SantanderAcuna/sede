<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Noticia;
use Illuminate\Foundation\Http\FormRequest;

/**
 * La imagen que el editor inserta en el contenido, con su texto alternativo.
 *
 * Sólo PNG, JPG y WebP (sin SVG ni GIF), hasta 5 MB y 4000 px por lado: el límite de
 * píxeles frena las «bombas de descompresión», que pesan poco y ocupan gigas al abrirse.
 */
final class SubirImagenContenidoRequest extends FormRequest
{
    public const PERMISOS = ['noticias.gestionar', 'politicas.gestionar', 'entidad.gestionar'];

    public function authorize(): bool
    {
        $usuario = $this->user();

        foreach (self::PERMISOS as $permiso) {
            if ($usuario?->can($permiso) === true) {
                return true;
            }
        }

        return false;
    }

    protected function prepareForValidation(): void
    {
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
            'imagen' => ['required', 'file', 'max:5120', 'mimes:png,jpg,jpeg,webp', 'dimensions:max_width=4000,max_height=4000'],
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
            'imagen.dimensions' => 'La imagen es demasiado grande: el máximo son 4000 píxeles por lado.',
            'alt.required' => 'La imagen necesita su texto alternativo (alt): describe lo que muestra para quien no la ve.',
        ];
    }
}
