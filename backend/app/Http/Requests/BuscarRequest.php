<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * La validación de los resultados completos (`GET /buscar`).
 *
 * Las mismas reglas de `q` que las sugerencias, más el filtro `tipo` —sólo los tipos
 * que el contrato enumera— y la paginación con los valores por defecto del contrato
 * (página 1, 15 por página, máximo 100). El texto buscado no se guarda ni se registra.
 */
final class BuscarRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Pública: el contrato la declara con `security: []`.
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:3', 'max:120'],
            'tipo' => ['sometimes', 'nullable', 'string', 'in:tramite,politica'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function texto(): string
    {
        return (string) $this->string('q');
    }

    /**
     * Una cadena vacía no es un filtro: `?tipo=` busca en todo, igual que no mandarlo.
     */
    public function tipo(): ?string
    {
        $tipo = $this->string('tipo')->trim()->value();

        return $tipo === '' ? null : $tipo;
    }

    public function pagina(): int
    {
        return $this->integer('page', 1);
    }

    public function porPagina(): int
    {
        return $this->integer('per_page', 15);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.required' => 'Escriba qué desea buscar.',
            'q.string' => 'El texto de búsqueda debe ser texto.',
            'q.min' => 'Escriba al menos 3 caracteres para buscar.',
            'q.max' => 'El texto de búsqueda no puede tener más de 120 caracteres.',
            'tipo.in' => 'El tipo de contenido debe ser tramite o politica.',
            'page.integer' => 'La página debe ser un número entero.',
            'page.min' => 'La página empieza en 1.',
            'per_page.integer' => 'El campo per_page debe ser un número entero.',
            'per_page.min' => 'El campo per_page no puede ser menor que 1.',
            'per_page.max' => 'El campo per_page no puede ser mayor que 100.',
        ];
    }
}
