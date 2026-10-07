<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\DTOs\Auth\LoginCredentials;
use App\Http\Resources\UsuarioResource;
use Illuminate\Http\Request;

/**
 * Contrato para el servicio de autenticación.
 */
interface AuthServiceInterface
{
    /**
     * Intenta iniciar sesión con credenciales.
     *
     * @return array{success:bool,message:string,data?:array{require_mfa:bool,mfa_token:?string,csrf_token:?string,user:?UsuarioResource}}
     */
    public function login(LoginCredentials $credentials): array;

    /**
     * Cierra la sesión del usuario actual.
     */
    public function logout(Request $request): void;

    /**
     * Obtiene el perfil del usuario autenticado.
     */
    public function perfil(): UsuarioResource;
}
