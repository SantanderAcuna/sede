<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\AuthRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Implementación del repositorio de autenticación.
 */
final class AuthRepository implements AuthRepositoryInterface
{
    /**
     * Busca un usuario por correo electrónico.
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Verifica si el usuario puede iniciar sesión.
     */
    public function canLogin(User $user): bool
    {
        if ($user->estado !== 'activo') {
            return false;
        }

        return true;
    }

    /**
     * Verifica la contraseña de un usuario.
     */
    public function verifyPassword(User $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }
}
