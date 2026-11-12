<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\EstadoNoticia;
use App\Support\Noticias\FiltrosNoticiasPanel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Parámetros del listado de noticias del panel: página, tamaño, búsqueda por título,
 * estado y orden. La autorización la hace la Policy en el controlador.
 */
final class ListarNoticiasPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'estado' => ['sometimes', 'nullable', Rule::enum(EstadoNoticia::class)],
            'ordenar_por' => ['sometimes', Rule::in(FiltrosNoticiasPanel::ORDENES)],
            'direccion' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }

    public function porPagina(): int
    {
        return (int) $this->validated('per_page', 20);
    }

    public function filtros(): FiltrosNoticiasPanel
    {
        $ordenarPor = (string) $this->validated('ordenar_por', FiltrosNoticiasPanel::ORDEN_FECHA);
        $titulo = trim((string) $this->validated('q', ''));
        $estado = (string) $this->validated('estado', '');

        return new FiltrosNoticiasPanel(
            titulo: $titulo === '' ? null : $titulo,
            estado: $estado === '' ? null : EstadoNoticia::from($estado),
            ordenarPor: $ordenarPor,
            // Por defecto lo más natural en cada columna: lo más reciente primero y la A a la Z.
            direccion: (string) $this->validated(
                'direccion',
                $ordenarPor === FiltrosNoticiasPanel::ORDEN_TITULO ? 'asc' : 'desc',
            ),
        );
    }
}
