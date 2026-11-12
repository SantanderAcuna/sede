<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Tramites\FiltrosTramite;
use Illuminate\Foundation\Http\FormRequest;

/**
 * La validación del listado del catálogo.
 *
 * El listado no escribe nada, pero sí puede pedir mal: el contrato declara `422` para
 * `GET /tramites` y limita `per_page` a 100. Comprobarlo aquí y no en el controlador mantiene la
 * misma forma que las operaciones de escritura y deja al servicio recibir valores que ya son
 * válidos. Las reglas son las del contrato y el defecto de `per_page` es el suyo.
 */
final class ListarTramitesRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El catálogo es público: el contrato declara estas operaciones con `security: []`.
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
            'modalidad' => ['sometimes', 'nullable', 'string', 'in:en_linea,parcialmente_en_linea,presencial'],
            'costo' => ['sometimes', 'nullable', 'string', 'in:gratuito,con_costo'],
            'dependencia' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'solicitante' => ['sometimes', 'nullable', 'string', 'max:40'],
        ];
    }

    /**
     * Los filtros ya validados, tal como los consumen el servicio y el repositorio.
     */
    public function filtros(): FiltrosTramite
    {
        $buscar = $this->string('buscar')->trim()->value();
        $categoria = $this->string('categoria')->trim()->value();
        $tipo = $this->string('type')->trim()->value();
        $modalidad = $this->string('modalidad')->trim()->value();
        $costo = $this->string('costo')->trim()->value();
        $solicitante = $this->string('solicitante')->trim()->value();
        $dependencia = $this->integer('dependencia', 0);

        return new FiltrosTramite(
            pagina: $this->integer('page', 1),
            porPagina: $this->integer('per_page', 15),
            // Una cadena vacía no es un filtro: `?buscar=` devuelve el catálogo entero, igual que no
            // mandarlo. Distinguirlas obligaría a mostrar «0 resultados» sobre una búsqueda que
            // nadie hizo.
            buscar: $buscar === '' ? null : $buscar,
            categoria: $categoria === '' ? null : $categoria,
            tipo: $tipo === '' ? null : $tipo,
            modalidad: $modalidad === '' ? null : $modalidad,
            costo: $costo === '' ? null : $costo,
            dependencia: $dependencia === 0 ? null : $dependencia,
            solicitante: $solicitante === '' ? null : $solicitante,
        );
    }

    /**
     * Los mensajes se escriben aquí y no se dejan al idioma de Laravel: una sede que responde
     * «The per_page field must not be greater than 100» a un ciudadano que escribió en español
     * incumple el artículo 14 del Decreto 2106 de 2019 por la puerta de atrás.
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
            'type.in' => 'El grupo debe ser tramites, opa o consultas.',
            'modalidad.in' => 'La modalidad debe ser en_linea, parcialmente_en_linea o presencial.',
            'costo.in' => 'El costo debe ser gratuito o con_costo.',
            'dependencia.integer' => 'La dependencia debe identificarse con un número.',
            'solicitante.max' => 'El tipo de solicitante no puede tener más de 40 caracteres.',
        ];
    }
}
