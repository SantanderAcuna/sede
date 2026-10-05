<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

/**
 * Credenciales de acceso al panel.
 *
 * @readonly
 */
final readonly class LoginCredentials
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
