<?php

namespace App\Application\Seguimiento;

use App\Domain\Jornadas\JornadaRepositoryInterface;

/**
 * A propósito NUNCA cierra la jornada: eso es responsabilidad exclusiva de CerrarJornadaUseCase,
 * así "detener seguimiento" (botón manual) nunca finaliza la jornada por error.
 */
final class DesactivarSeguimientoUseCase
{
    public function __construct(
        private readonly JornadaRepositoryInterface $jornadas,
    ) {}

    public function ejecutar(int $jornadaId): void
    {
        $this->jornadas->actualizarSeguimientoActivo($jornadaId, false);
    }
}
