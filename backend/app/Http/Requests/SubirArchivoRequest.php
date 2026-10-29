<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para subir un archivo.
 *
 * El tamaño máximo es 20 MB y coincide con `upload_max_filesize` de PHP
 * (`docker/app/php.ini`); `post_max_size` y `client_max_body_size` de nginx son
 * de 25 MB para dejar sitio al resto del formulario. Con los tres alineados, un
 * archivo de 21 MB llega a esta validación y recibe un 422 con mensaje, en vez de
 * un 413 opaco del servidor (H-13).
 *
 * SVG queda fuera de la lista a propósito: puede llevar `<script>`.
 */
final class SubirArchivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la autorización va por Policy
    }

    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'max:20480', 'mimes:pdf,png,jpg,jpeg,gif,webp,doc,docx,xls,xlsx,ppt,pptx,zip,rar'],
            'collection' => ['required', 'string', 'max:64'],
            'metadata' => ['sometimes', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archivo.max' => 'El archivo supera el tamaño máximo permitido (20 MB).',
            'archivo.uploaded' => 'El archivo no se pudo subir: supera el tamaño máximo permitido (20 MB).',
            'archivo.mimes' => 'Tipo de archivo no permitido. Se aceptan PDF, imágenes (PNG, JPG, GIF, WebP), documentos de Office, ZIP y RAR.',
        ];
    }
}
