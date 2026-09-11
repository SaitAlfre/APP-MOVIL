<?php

namespace App\Application\Auth;

use App\Domain\Auth\OperadorAuthenticatorInterface;

final class CerrarSesionOperadorUseCase
{
    public function __construct(
        private readonly OperadorAuthenticatorInterface $authenticator,
    ) {}

    public function ejecutar(): void
    {
        $this->authenticator->cerrarSesion();
    }
}
