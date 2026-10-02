<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Tramites\FiltrosTramite;
use Illuminate\Foundation\Http\FormRequest;

/**
 * La validación del listado del catálogo.
 *
 * El listado no escribe nada, pero sí puede pedir mal: el contrato declara `422`
 * para `GET /tramites` y limita `per_page` a 100. Comprobarlo aquí y no en el
 * controlador mantiene la misma forma que las operaciones de escritura y deja al
 * servicio recibir valores que ya son válidos.
 *
 * Las reglas son las del contrato, y el defecto de `per_page` es el suyo —15—,
 * no el de Laravel: si divergieran, la primera página de la sede y la que
 * documenta el contrato tendrían tamaños distintos.
 */
final class ListarTramitesRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El catálogo es público: el contrato declara estas operaciones con
        // `security: []`.
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'buscar' => ['sometimes', 'nullable', 'string', 'max:120'],
            'categoria' => ['sometimes', 'nullable', 'string', 'max:120'],
            'type' => ['sometimes', 'nullable', 'string', 'in:tramites,opa,consultas'],
        ];
    }

    /**
     * Los filtros ya validados, tal como los consumen el servicio y el
     * repositorio.
     */
    public function filtros(): FiltrosTramite
    {
        $buscar = $this->string('buscar')->trim()->value();
        $categoria = $this->string('categoria')->trim()->value();
        $tipo = $this->string('type')->trim()->value();

        return new FiltrosTramite(
            pagina: $this->integer('page', 1),
            porPagina: $this->integer('per_page', 15),
            // Una cadena vacía no es un filtro: `?buscar=` devuelve el catálogo
            // entero, igual que no mandarlo. Distinguirlas obligaría a que la
            // sede mostrara «0 resultados» sobre una búsqueda que nadie hizo.
            buscar: $buscar === '' ? null : $buscar,
            categoria: $categoria === '' ? null : $categoria,
            tipo: $tipo === '' ? null : $tipo,
        );
    }

    /**
     * Los mensajes se escriben aquí y no se dejan al idioma de Laravel.
     *
     * La aplicación no trae el archivo de traducciones de la validación, así que
     * delegar en él devuelve los mensajes en inglés: una sede electrónica que
     * responde «The per_page field must not be greater than 100» a un ciudadano
     * que escribió en español está incumpliendo el artículo 14 del Decreto 2106
     * de 2019 por la puerta de atrás. La redacción de `per_page` es, además, la
     * que el contrato usa como ejemplo de respuesta `422`.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'page.integer' => 'La página debe ser un número entero.',
            'page.min' => 'La página empieza en 1.',
            'per_page.integer' => 'El campo per_page debe ser un número entero.',
            'per_page.min' => 'El campo per_page no puede ser menor que 1.',
            'per_page.max' => 'El campo per_page no puede ser mayor que 100.',
            'buscar.string' => 'El término de búsqueda debe ser texto.',
            'buscar.max' => 'El término de búsqueda no puede tener más de 120 caracteres.',
            'categoria.string' => 'La categoría debe ser texto.',
            'categoria.max' => 'La categoría no puede tener más de 120 caracteres.',
        ];
    }
}
