<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UsuarioPanelServiceInterface
{
    /** @return LengthAwarePaginator<int, User> */
    public function listar(int $porPagina, ?string $texto, ?string $estado, ?string $rol): LengthAwarePaginator;

    public function porUuid(string $uuid): ?User;

    /** @param array{name: string, email: string, password: string, estado?: string, roles?: list<string>} $datos */
    public function crear(User $actor, array $datos): User;

    /**
     * Solo viajan los campos que cambian. La clave, si llega, se guarda cifrada y no se registra.
     *
     * @param  array<string, mixed>  $cambios
     */
    public function actualizar(User $actor, User $objetivo, array $cambios): User;
}
