<?php

namespace App\Domain\Auth;

interface OperadorAuthenticatorInterface
{
    public function intentar(string $username, string $pin): bool;

    /** Comprueba usuario y PIN sin abrir sesión (clientes sin cookie, como la app móvil). */
    public function validar(string $username, string $pin): bool;

    public function usuarioActual(): ?OperadorSesion;

    public function cerrarSesion(): void;
}
