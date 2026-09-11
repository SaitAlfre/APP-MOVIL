<?php

namespace App\Application\Seguimiento;

use App\Domain\Jornadas\JornadaRepositoryInterface;
use App\Domain\Seguimiento\PosicionSeguimiento;
use App\Domain\Seguimiento\SeguimientoRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

final class RegistrarPosicionUseCase
{
    public function __construct(
        private readonly SeguimientoRepositoryInterface $seguimiento,
        private readonly JornadaRepositoryInterface $jornadas,
    ) {}

    public function ejecutar(int $jornadaId, float $lat, float $lng, ?float $precisionM): PosicionSeguimiento
    {
        $jornada = $this->jornadas->buscarPorId($jornadaId);

        if ($jornada === null || ! $jornada->estaAbierta()) {
            throw new RuntimeException('La jornada debe estar abierta para registrar posiciones.');
        }

        if (! $jornada->seguimientoActivo) {
            $this->jornadas->actualizarSeguimientoActivo($jornadaId, true);
        }

        return $this->seguimiento->registrarPosicion(new PosicionSeguimiento(
            id: null,
            jornadaId: $jornadaId,
            lat: $lat,
            lng: $lng,
            precisionM: $precisionM,
            capturadaEn: new DateTimeImmutable,
        ));
    }
}
