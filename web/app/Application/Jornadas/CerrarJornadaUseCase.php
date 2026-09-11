<?php

namespace App\Application\Jornadas;

use App\Domain\Jornadas\JornadaRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

final class CerrarJornadaUseCase
{
    public function __construct(
        private readonly JornadaRepositoryInterface $jornadas,
    ) {}

    public function ejecutar(int $jornadaId): void
    {
        $jornada = $this->jornadas->buscarPorId($jornadaId);

        if ($jornada === null) {
            throw new RuntimeException('La jornada no existe.');
        }

        if (! $jornada->estaAbierta()) {
            return;
        }

        // El seguimiento solo existe durante una jornada abierta: cerrarla siempre lo detiene.
        $this->jornadas->actualizarSeguimientoActivo($jornadaId, false);
        $this->jornadas->cerrar($jornadaId, new DateTimeImmutable);
    }
}
