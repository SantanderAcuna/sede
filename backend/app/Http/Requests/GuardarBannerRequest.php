<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Banner;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta (POST) y cambio (PATCH) de una diapositiva. Al cambiar solo se envía lo que cambia.
 *
 * El enlace es una ruta del sitio (`/transparencia`) o una dirección `https://`: se rechazan
 * `javascript:`, `data:`, `http://` y `//otro-dominio` (que el navegador toma por una URL externa).
 */
final class GuardarBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['titulo', 'descripcion', 'enlace', 'imagen_alt'] as $campo) {
            if ($this->has($campo) && is_string($this->input($campo))) {
                $valor = trim((string) $this->input($campo));
                $this->merge([$campo => $valor === '' && $campo !== 'titulo' ? null : $valor]);
            }
        }
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $obligatorio = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'titulo' => [$obligatorio, 'string', 'min:1', 'max:'.Banner::TITULO_MAX],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:'.Banner::DESCRIPCION_MAX],
            'enlace' => ['sometimes', 'nullable', 'string', 'max:500', self::enlaceValido(...)],
            'activo' => ['sometimes', 'boolean'],
            'vigente_desde' => ['sometimes', 'nullable', 'date'],
            'vigente_hasta' => ['sometimes', 'nullable', 'date', 'after:vigente_desde'],
            'imagen_alt' => ['sometimes', 'nullable', 'string', 'max:'.Banner::ALT_MAX],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'titulo.required' => 'Escriba el título de la diapositiva.',
            'titulo.max' => 'El título admite hasta '.Banner::TITULO_MAX.' caracteres.',
            'descripcion.max' => 'La descripción admite hasta '.Banner::DESCRIPCION_MAX.' caracteres.',
            'vigente_hasta.after' => 'La fecha de fin debe ser posterior a la de inicio.',
        ];
    }

    /** @param Closure(string): mixed $fallar */
    private static function enlaceValido(string $atributo, mixed $valor, Closure $fallar): void
    {
        if (! is_string($valor)) {
            return;
        }
        $esRuta = preg_match('#^/(?!/)[^\s\\\\]*$#', $valor) === 1;
        $esHttps = preg_match('#^https://[^\s/$.?\#].[^\s]*$#i', $valor) === 1;
        if (! $esRuta && ! $esHttps) {
            $fallar('El enlace debe ser una ruta del sitio (por ejemplo /transparencia) o una dirección que empiece por https://.');
        }
    }
}
