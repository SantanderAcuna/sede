<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\User;

/**
 * Contrato para el repositorio de autenticación.
 */
interface AuthRepositoryInterface
{
    /**
     * Busca un usuario por correo electrónico.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Verifica si el usuario puede iniciar sesión.
     */
    public function canLogin(User $user): bool;

    /**
     * Verifica la contraseña de un usuario.
     */
    public function verifyPassword(User $user, string $password): bool;
}
