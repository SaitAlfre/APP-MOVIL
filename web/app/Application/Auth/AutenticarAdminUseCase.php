<?php

namespace App\Application\Auth;

use App\Domain\Auth\AdminAuthenticatorInterface;

final class AutenticarAdminUseCase
{
    public function __construct(
        private readonly AdminAuthenticatorInterface $authenticator,
    ) {}

    public function ejecutar(string $email, string $password, bool $recordar = false): bool
    {
        return $this->authenticator->intentar($email, $password, $recordar);
    }
}
