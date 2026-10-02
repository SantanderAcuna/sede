<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de Menú.
 */
interface MenuRepositoryInterface
{
    /**
     * Obtiene el árbol de menú visible.
     *
     * @param  string|null  $rol  Filtrar por rol (sitio, panel, admin). Null = todos.
     * @return Collection<int, Menu>
     */
    public function getVisibleTree(?string $rol = null): Collection;
}
