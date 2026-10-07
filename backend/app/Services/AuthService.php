<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\AuthRepositoryInterface;
use App\Contracts\Services\AuthServiceInterface;
use App\DTOs\Auth\LoginCredentials;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de autenticación con Sanctum (modo cookie-based SPA).
 *
 * Este servicio implementa el flujo de Sanctum para SPAs con cookies HttpOnly:
 *   1. GET /sanctum/csrf-cookie  → Laravel establece cookie XSRF-TOKEN
 *   2. POST /login (con X-XSRF-TOKEN) → auth()->login() + session()->regenerate()
 *   3. Cookie de sesión HttpOnly vinculada a la sesión en tabla sessions
 *
 * NO se usan tokens Bearer ni localStorage. La autorización se gestiona por
 * Policies (no por token abilities).
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

        // Establecer sesión con cookie HttpOnly en el guard 'web' explícitamente.
        // CRÍTICO: especificar 'web' y no usar auth()->login() sin argumentos,
        // porque en rutas API Sanctum puede cambiar el default guard a 'sanctum',
        // que no tiene implementación de login() por sesión.
        // Session fixation se previene regenerando el ID tras el login.
        Auth::guard('web')->login($user);
        request()->session()->regenerate();

        return [
            'success' => true,
            'message' => 'Sesión iniciada',
            'data' => [
                'require_mfa' => false,
                'mfa_token' => null,
                // Token CSRF real de la sesión — el cliente lo usa en el header
                // X-XSRF-TOKEN para peticiones que modifican estado (POST, PATCH, DELETE).
                'csrf_token' => request()->session()->token(),
                'user' => new UsuarioResource($user),
            ],
        ];
    }

    public function logout(Request $request): void
    {
        // CRÍTICO: usar explícitamente el guard 'web' para evitar que Sanctum
        // (que internamente usa RequestGuard en algunas configuraciones) intente
        // llamar logout() en un guard que no lo soporta, generando un error
        // BadMethodCallException que ensucia el log y deja la sesión inconsistente.
        $guard = Auth::guard('web');

        // Cerrar la sesión del usuario en el guard.
        $guard->logout();

        // Invalidar la sesión y regenerar el token CSRF para prevenir
        // session fixation tras el logout.
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function perfil(): UsuarioResource
    {
        /** @var User|null $user */
        $user = Auth::guard('web')->user();

        if ($user === null) {
            throw new \Illuminate\Auth\AuthenticationException('Usuario no autenticado.');
        }

        return new UsuarioResource($user);
    }
}
