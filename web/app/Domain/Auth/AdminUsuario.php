<?php

namespace App\Domain\Auth;

final class AdminUsuario
{
    public function __construct(
        public readonly int $id,
        public readonly string $nombres,
        public readonly string $email,
    ) {}
}
