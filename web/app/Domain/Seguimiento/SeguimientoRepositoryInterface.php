<?php

namespace App\Domain\Seguimiento;

interface SeguimientoRepositoryInterface
{
    public function registrarPosicion(PosicionSeguimiento $posicion): PosicionSeguimiento;

    public function ultimaPosicion(int $jornadaId): ?PosicionSeguimiento;
}
