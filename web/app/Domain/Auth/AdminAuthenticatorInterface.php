<?php

namespace App\Domain\Auth;

interface AdminAuthenticatorInterface
{
    public function intentar(string $email, string $password, bool $recordar = false): bool;

    public function usuarioActual(): ?AdminUsuario;

    public function cerrarSesion(): void;
}
