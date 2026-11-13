<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Tramites\FiltrosTramitePanel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Parámetros del listado de trámites del panel. La autorización la hace la Policy. */
final class ListarTramitesPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'estado' => ['sometimes', 'nullable', Rule::in(FiltrosTramitePanel::ESTADOS)],
            'type' => ['sometimes', 'nullable', Rule::in(FiltrosTramitePanel::TIPOS)],
            'ordenar_por' => ['sometimes', Rule::in(FiltrosTramitePanel::ORDENES)],
            'direccion' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }

    public function porPagina(): int
    {
        return (int) $this->validated('per_page', 20);
    }

    public function filtros(): FiltrosTramitePanel
    {
        $ordenarPor = (string) $this->validated('ordenar_por', FiltrosTramitePanel::ORDEN_ACTUALIZADO);
        $texto = trim((string) $this->validated('q', ''));
        $estado = (string) $this->validated('estado', '');
        $tipo = (string) $this->validated('type', '');

        return new FiltrosTramitePanel(
            texto: $texto === '' ? null : $texto,
            estado: $estado === '' ? null : $estado,
            ordenarPor: $ordenarPor,
            direccion: (string) $this->validated('direccion', $ordenarPor === FiltrosTramitePanel::ORDEN_NOMBRE ? 'asc' : 'desc'),
            tipo: $tipo === '' ? null : $tipo,
        );
    }
}
