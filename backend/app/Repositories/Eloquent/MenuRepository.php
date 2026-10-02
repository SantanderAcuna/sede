<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\MenuRepositoryInterface;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Collection;

/**
 * Implementación Eloquent del repositorio de Menú.
 */
final class MenuRepository implements MenuRepositoryInterface
{
    /**
     * Obtiene el árbol de menú visible.
     *
     * @param  string|null  $rol  Filtrar por rol (sitio, panel, admin). Null = todos.
     * @return Collection<int, Menu>
     */
    public function getVisibleTree(?string $rol = null): Collection
    {
        return Menu::query()
            ->roots()
            ->where('visible', true)
            ->forRol($rol)
            ->with('hijos', fn ($q) => $q->where('visible', true)->forRol($rol)->orderBy('orden'))
            ->orderBy('orden')
            ->get();
    }
}
