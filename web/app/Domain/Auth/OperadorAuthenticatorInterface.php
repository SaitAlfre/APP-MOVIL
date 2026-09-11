<?php

namespace App\Domain\Auth;

interface OperadorAuthenticatorInterface
{
    public function intentar(string $username, string $pin): bool;

    public function usuarioActual(): ?OperadorSesion;

    public function cerrarSesion(): void;
}
