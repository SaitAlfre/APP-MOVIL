<?php

namespace App\Domain\Jornadas\Exceptions;

use DomainException;

final class ZonaOcupadaException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Esta zona ya tiene un acopiador con una jornada abierta. Solo puede haber una jornada activa por zona a la vez.');
    }
}
