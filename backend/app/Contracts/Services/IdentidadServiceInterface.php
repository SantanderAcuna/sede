<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Http\Resources\FooterResource;
use App\Http\Resources\MenuResource;
use App\Http\Resources\TopBarResource;

/**
 * Contrato para el servicio de Identidad.
 */
interface IdentidadServiceInterface
{
    /**
     * Obtiene la configuración del top bar.
     */
    public function obtenerTopBar(): TopBarResource;

    /**
     * Obtiene la configuración del footer.
     */
    public function obtenerFooter(): FooterResource;

    /**
     * Obtiene el árbol de menú.
     *
     * @param  string|null  $rol  Filtrar por rol (sitio, panel, admin). Null = menú público.
     * @return iterable<MenuResource>
     */
    public function obtenerMenu(?string $rol = null): iterable;
}
