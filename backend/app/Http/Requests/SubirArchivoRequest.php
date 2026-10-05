<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para subir un archivo.
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
            'archivo' => ['required', 'file', 'max:51200', 'mimes:pdf,png,jpg,jpeg,gif,svg,webp,doc,docx,xls,xlsx,ppt,pptx,zip,rar'],
            'collection' => ['required', 'string', 'max:64'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
