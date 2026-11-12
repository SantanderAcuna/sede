<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Noticia;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Crear o editar una noticia desde el panel. Los límites son los del requisito
 * RF-01-013: título ≤ 150 y descripción ≤ 200. En la edición todo es opcional
 * (PATCH) y `imagen_alt` cambia el texto alternativo de la imagen ya subida.
 */
final class GuardarNoticiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la autorización va por el permiso de la ruta
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $r = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'titulo' => [$r, 'string', 'max:'.Noticia::TITULO_MAX],
            'descripcion_corta' => [$r, 'string', 'max:'.Noticia::DESCRIPCION_MAX],
            'contenido' => [$r, 'string'],
            'fecha_publicacion' => [$r, 'date'],
            'destacada' => ['sometimes', 'boolean'],
            'activo' => ['sometimes', 'boolean'],
            'imagen_alt' => ['sometimes', 'required', 'string', 'max:'.Noticia::ALT_MAX],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'titulo.max' => 'El título no puede pasar de '.Noticia::TITULO_MAX.' caracteres.',
            'descripcion_corta.max' => 'La descripción no puede pasar de '.Noticia::DESCRIPCION_MAX.' caracteres.',
            'imagen_alt.required' => 'La imagen necesita su texto alternativo (alt).',
        ];
    }
}
