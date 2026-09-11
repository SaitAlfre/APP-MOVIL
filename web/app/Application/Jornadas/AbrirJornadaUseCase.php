<?php

namespace App\Application\Jornadas;

use App\Domain\Jornadas\Exceptions\ZonaOcupadaException;
use App\Domain\Jornadas\Jornada;
use App\Domain\Jornadas\JornadaRepositoryInterface;
use DateTimeImmutable;

final class AbrirJornadaUseCase
{
    public function __construct(
        private readonly JornadaRepositoryInterface $jornadas,
    ) {}

    public function ejecutar(int $usuarioId, int $zonaId, int $vehiculoId): Jornada
    {
        $hoy = new DateTimeImmutable('today');

        $jornada = $this->jornadas->obtenerAbiertaPorUsuarioYFecha($usuarioId, $hoy);

        if ($jornada !== null) {
            return $jornada;
        }

        $ocupante = $this->jornadas->obtenerAbiertaPorZona($zonaId);
        if ($ocupante !== null && $ocupante->usuarioId !== $usuarioId) {
            throw new ZonaOcupadaException;
        }

        return $this->jornadas->insertar(new Jornada(
            id: null,
            usuarioId: $usuarioId,
            zonaId: $zonaId,
            vehiculoId: $vehiculoId,
            fecha: $hoy,
            abiertaEn: new DateTimeImmutable,
            cerradaEn: null,
            seguimientoActivo: false,
        ));
    }
}
