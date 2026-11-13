<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Base de las Policies del panel: cada acción de un módulo es un permiso propio (`modulo.accion`),
 * así que «ver» no da «editar» ni «eliminar». Ver `CatalogoPermisos`.
 *
 * Una acción que el módulo no tiene se declara con `false`: no hay permiso que la conceda.
 */
abstract class PoliticaDeModulo
{
    /** Clave del módulo en `CatalogoPermisos`, p. ej. `noticias`. */
    abstract protected function modulo(): string;

    protected function puede(User $usuario, string $accion): bool
    {
        return $usuario->can($this->modulo().'.'.$accion);
    }
}
