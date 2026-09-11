<?php

namespace App\Application\Auth;

use App\Domain\Auth\AdminAuthenticatorInterface;

final class CerrarSesionAdminUseCase
{
    public function __construct(
        private readonly AdminAuthenticatorInterface $authenticator,
    ) {}

    public function ejecutar(): void
    {
        $this->authenticator->cerrarSesion();
    }
}
