<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\AuthServiceInterface;
use App\DTOs\Auth\LoginCredentials;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de autenticación para el panel.
 *
 * @tags Panel — Auth
 */
final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
    ) {}

    /**
     * @operationId login
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Sesión iniciada",
     *   "data": { "require_mfa", "mfa_token", "csrf_token", "user" }
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $credentials = new LoginCredentials(
            email: $data['email'],
            password: $data['password'],
        );

        $result = $this->authService->login($credentials);

        if (! $result['success']) {
            return ApiResponse::error($result['message'], 401);
        }

        return ApiResponse::ok($result['data'] ?? [], $result['message']);
    }

    /**
     * @operationId logout
     *
     * @response 204
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return response()->json(null, 204);
    }

    /**
     * @operationId perfil
     *
     * @response 200 {
     *   "success": true,
     *   "data": { "id", "type": "usuario", "email", ... }
     * }
     */
    public function perfil(): JsonResponse
    {
        return ApiResponse::objeto($this->authService->perfil());
    }
}
