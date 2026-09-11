<?php

namespace App\Domain\Auth;

use App\Domain\Usuarios\Rol;

final class OperadorSesion
{
    /** @param list<Rol> $roles */
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $nombres,
        public readonly array $roles,
    ) {}

    public function tieneRol(Rol $rol): bool
    {
        return in_array($rol, $this->roles, true);
    }
}
