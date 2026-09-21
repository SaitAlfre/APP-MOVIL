<?php

namespace App\Domain\Auth\Exceptions;

use DomainException;

final class CuentaBloqueadaException extends DomainException
{
    public function __construct(?string $mensaje = null)
    {
        parent::__construct($mensaje ?? 'Cuenta bloqueada temporalmente por demasiados intentos fallidos. Intenta de nuevo en unos minutos.');
    }
}
