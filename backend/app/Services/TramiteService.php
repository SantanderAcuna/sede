<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\TramiteRepositoryInterface;
use App\Contracts\Services\TramiteServiceInterface;
use App\Exceptions\TramiteNoEncontrado;
use App\Models\Tramite;
use App\Support\Tramites\FiltrosTramite;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Los casos de uso del catálogo público de trámites.
 */
final class TramiteService implements TramiteServiceInterface
{
    public function __construct(private readonly TramiteRepositoryInterface $tramites) {}

    public function listar(FiltrosTramite $filtros): LengthAwarePaginator
    {
        return $this->tramites->paginar($filtros);
    }

    public function porSlug(string $slug): Tramite
    {
        return $this->tramites->porSlug($slug) ?? throw TramiteNoEncontrado::porSlug($slug);
    }
}
