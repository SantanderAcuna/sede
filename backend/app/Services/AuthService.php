<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\AuthRepositoryInterface;
use App\Contracts\Services\AuthServiceInterface;
use App\DTOs\Auth\LoginCredentials;
use App\Http\Resources\UsuarioResource;
use App\Models\User;

/**
 * Servicio de autenticación con Sanctum.
 */
final class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
    ) {}

    /**
     * @return array{success:bool,message:string,data?:array{require_mfa:bool,mfa_token:?string,csrf_token:?string,user:?UsuarioResource}}
     */
    public function login(LoginCredentials $credentials): array
    {
        $user = $this->authRepository->findByEmail($credentials->email);

        if ($user === null) {
            return [
                'success' => false,
                'message' => 'Las credenciales no son válidas.',
            ];
        }

        if (! $this->authRepository->verifyPassword($user, $credentials->password)) {
            return [
                'success' => false,
                'message' => 'Las credenciales no son válidas.',
            ];
        }

        if (! $this->authRepository->canLogin($user)) {
            return [
                'success' => false,
                'message' => 'La cuenta no está activa.',
            ];
        }

        // Crear token Sanctum
        $token = $user->createToken('panel')->plainTextToken;

        return [
            'success' => true,
            'message' => 'Sesión iniciada',
            'data' => [
                'require_mfa' => false,
                'mfa_token' => null,
                'csrf_token' => $token,
                'user' => new UsuarioResource($user),
            ],
        ];
    }

    public function logout(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user !== null) {
            $user->currentAccessToken()->delete();
        }
    }

    public function perfil(): UsuarioResource
    {
        /** @var User $user */
        $user = auth()->user();

        return new UsuarioResource($user);
    }
}
