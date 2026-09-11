<?php

namespace App\Application\Seguimiento;

use App\Domain\Jornadas\JornadaRepositoryInterface;
use RuntimeException;

/** Solo se puede activar el seguimiento con una jornada abierta: es la que lo detiene también al cerrarse. */
final class ActivarSeguimientoUseCase
{
    public function __construct(
        private readonly JornadaRepositoryInterface $jornadas,
    ) {}

    public function ejecutar(int $jornadaId): void
    {
        $jornada = $this->jornadas->buscarPorId($jornadaId);

        if ($jornada === null || ! $jornada->estaAbierta()) {
            throw new RuntimeException('La jornada debe estar abierta para iniciar el seguimiento.');
        }

        $this->jornadas->actualizarSeguimientoActivo($jornadaId, true);
    }
}
