<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Tramite;
use App\Support\Tramites\FiltrosTramitePanel;
use Illuminate\Pagination\LengthAwarePaginator;

interface TramitePanelRepositoryInterface
{
    /** @return LengthAwarePaginator<int, Tramite> */
    public function paginar(int $porPagina, FiltrosTramitePanel $filtros): LengthAwarePaginator;

    public function porUuid(string $uuid): ?Tramite;

    public function existeSlug(string $slug): bool;

    /** El siguiente código libre para el tipo dado: `T0001` (trámite), `O0001` (OPA), `C0001` (consulta). */
    public function siguienteCodigo(string $tipo): string;

    /**
     * Guarda el trámite y, cuando se recibe la ficha convertida, reemplaza sus filas hijas en una
     * transacción.
     *
     * @param  array{
     *     pasos: list<array{orden: int, titulo: string, descripcion: string|null}>,
     *     requisitos: list<array{orden: int, tipo: string, descripcion: string, obligatorio: bool, nota: string|null, url: string|null, paso: int|null}>,
     *     documentos: list<array<string, mixed>>,
     *     puntos_atencion: list<array<string, mixed>>,
     *     normativa: list<array<string, mixed>>,
     *     canales_consulta_estado: list<array<string, mixed>>,
     *     cuentas: list<array<string, mixed>>,
     *     audiencias: list<string>
     * }|null  $ficha
     */
    public function guardar(Tramite $tramite, ?array $ficha = null): Tramite;

    public function eliminar(Tramite $tramite): void;
}
