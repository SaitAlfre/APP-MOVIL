<?php

namespace App\Application\Proveedores;

/** Credenciales iniciales de la cuenta móvil; el PIN viaja en texto plano solo hasta el hash del modelo y nunca se audita. */
final class CredencialesCuentaMovil
{
    public function __construct(
        public readonly string $username,
        #[\SensitiveParameter]
        public readonly string $pin,
    ) {}
}
