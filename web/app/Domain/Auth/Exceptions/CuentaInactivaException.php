<?php

namespace App\Domain\Auth\Exceptions;

use DomainException;

final class CuentaInactivaException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Esta cuenta de administrador está desactivada.');
    }
}
