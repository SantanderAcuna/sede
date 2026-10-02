<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\EntidadRepositoryInterface;
use App\Contracts\Repositories\MenuRepositoryInterface;
use App\Contracts\Services\IdentidadServiceInterface;
use App\Http\Resources\FooterResource;
use App\Http\Resources\MenuResource;
use App\Http\Resources\TopBarResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Servicio para los elementos de identidad institucional.
 *
 * Agrupa top-bar, footer y menú de navegación.
 */
final class IdentidadService implements IdentidadServiceInterface
{
    public function __construct(
        private readonly EntidadRepositoryInterface $entidadRepository,
        private readonly MenuRepositoryInterface $menuRepository,
    ) {}

    /**
     * Obtiene la configuración del top bar.
     */
    public function obtenerTopBar(): TopBarResource
    {
        $entidad = $this->entidadRepository->findFirst();

        if ($entidad === null) {
            throw new ModelNotFoundException('No se encontró la entidad configurada.');
        }

        return new TopBarResource($entidad);
    }

    /**
     * Obtiene la configuración del footer.
     */
    public function obtenerFooter(): FooterResource
    {
        $entidad = $this->entidadRepository->findFirst();

        if ($entidad === null) {
            throw new ModelNotFoundException('No se encontró la entidad configurada.');
        }

        return new FooterResource($entidad);
    }

    /**
     * Obtiene el árbol de menú.
     *
     * @param  string|null  $rol  Filtrar por rol (sitio, panel, admin). Null = menú público.
     */
    public function obtenerMenu(?string $rol = null): MenuResource
    {
        $menu = $this->menuRepository->getVisibleTree($rol);

        return new MenuResource($menu);
    }
}
