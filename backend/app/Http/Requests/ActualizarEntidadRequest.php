<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida los datos para actualizar la entidad institucional.
 *
 * Todos los campos son opcionales para permitir actualizaciones parciales.
 * Los campos no enviados mantienen su valor actual.
 */
final class ActualizarEntidadRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para esta petición.
     */
    public function authorize(): bool
    {
        // La autorización se delega a la Policy o al middleware de Sanctum.
        // Este request solo valida los datos, no la autorización.
        return true;
    }

    /**
     * Obtiene las reglas de validación aplicables a los datos de la petición.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:200'],
            'sigla' => ['sometimes', 'nullable', 'string', 'max:20'],
            'nit' => ['sometimes', 'nullable', 'string', 'max:30'],
            'direccion' => ['sometimes', 'nullable', 'string', 'max:240'],
            'municipio' => ['sometimes', 'nullable', 'string', 'max:120'],
            'departamento' => ['sometimes', 'nullable', 'string', 'max:120'],
            'pais' => ['sometimes', 'nullable', 'string', 'max:80'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:40'],
            'linea_atencion' => ['sometimes', 'nullable', 'string', 'max:40'],
            'linea_gratuita' => ['sometimes', 'nullable', 'string', 'max:40'],
            'linea_anticorrupcion' => ['sometimes', 'nullable', 'string', 'max:40'],
            'correo_atencion' => ['sometimes', 'nullable', 'email', 'max:240'],
            'correo_notificaciones_judiciales' => ['sometimes', 'nullable', 'email', 'max:240'],
            'horario' => ['sometimes', 'nullable', 'string', 'max:240'],
            'codigo_postal' => ['sometimes', 'nullable', 'string', 'max:20'],
            'dominio' => ['sometimes', 'nullable', 'url', 'max:240'],
            'logo' => ['sometimes', 'nullable', 'string', 'max:500'],
            'redes' => ['sometimes', 'nullable', 'array'],
            'redes.*.red' => ['required_with:redes', 'string', 'in:facebook,instagram,x,youtube,linkedin'],
            'redes.*.url' => ['required_with:redes', 'url', 'max:500'],
            'politicas' => ['sometimes', 'nullable', 'array'],
            'politicas.*.slug' => ['required_with:politicas', 'string', 'max:100'],
            'politicas.*.nombre' => ['required_with:politicas', 'string', 'max:200'],
            'datos_por_confirmar' => ['sometimes', 'nullable', 'array'],
            'datos_por_confirmar.*' => ['string', 'max:200'],
        ];
    }

    /**
     * Obtiene los mensajes de error personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'correo_atencion.email' => 'El correo de atención debe ser una dirección de correo válida.',
            'correo_notificaciones_judiciales.email' => 'El correo de notificaciones judiciales debe ser una dirección de correo válida.',
            'dominio.url' => 'El dominio debe ser una URL válida (incluyendo http:// o https://).',
            'redes.*.red.in' => 'La red social debe ser una de las permitidas: facebook, instagram, x, youtube, linkedin.',
            'redes.*.url.url' => 'Cada URL de red social debe ser una dirección web válida.',
            'politicas.*.slug.required_with' => 'Cada política debe tener un identificador (slug).',
            'politicas.*.nombre.required_with' => 'Cada política debe tener un nombre.',
        ];
    }
}
