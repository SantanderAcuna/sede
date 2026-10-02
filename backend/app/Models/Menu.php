<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Elemento del menú de navegación.
 *
 * @property int $id
 * @property string $slug
 * @property string $etiqueta
 * @property string|null $ruta
 * @property string|null $descripcion
 * @property int $orden
 * @property bool $visible
 * @property string $tipo
 * @property list<string>|null $roles
 * @property int|null $menu_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Menu extends Model
{
    protected $table = 'menus';

    /** @var list<string> */
    protected $fillable = [
        'slug',
        'etiqueta',
        'ruta',
        'descripcion',
        'orden',
        'visible',
        'tipo',
        'roles',
        'menu_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'visible' => 'boolean',
            'roles' => 'array',
        ];
    }

    /**
     * Submenús de segundo nivel.
     *
     * @return HasMany<Menu, $this>
     */
    public function hijos(): HasMany
    {
        return $this->hasMany(Menu::class, 'menu_id')->orderBy('orden');
    }

    /**
     * Raíces del menú (elementos de primer nivel).
     *
     * @param  Builder<Menu>  $query
     * @return Builder<Menu>
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('menu_id')->orderBy('orden');
    }

    /**
     * Filtra por rol. Si el ítem no tiene roles definidos, es visible para todos.
     *
     * @param  Builder<Menu>  $query
     * @return Builder<Menu>
     */
    public function scopeForRol(Builder $query, ?string $rol): Builder
    {
        if ($rol === null) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($rol): Builder {
            return $q->whereNull('roles')
                ->orWhereJsonContains('roles', $rol);
        });
    }
}
