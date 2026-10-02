<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Http\Resources\UsuarioResource;

/**
 * Contrato para el servicio de autenticación.
 */
interface AuthServiceInterface
{
    /**
     * Intenta iniciar sesión con credenciales.
     *
     * @param  array{email:string,password:string}  $credentials
     * @return array{success:bool,message:string,data?:array{require_mfa:bool,mfa_token:?string,csrf_token:?string,user:?UsuarioResource}}
     */
    public function login(array $credentials): array;

    /**
     * Cierra la sesión del usuario actual.
     */
    public function logout(): void;

    /**
     * Obtiene el perfil del usuario autenticado.
     */
    public function perfil(): UsuarioResource;
}
