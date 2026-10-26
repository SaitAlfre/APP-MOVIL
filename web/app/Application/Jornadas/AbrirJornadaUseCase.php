<?php

namespace App\Application\Jornadas;

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
        $hoy = new DateTimeImmutable('today', new \DateTimeZone('America/Lima'));

        $jornada = $this->jornadas->obtenerAbiertaPorUsuarioYFecha($usuarioId, $hoy);

        if ($jornada !== null) {
            return $jornada;
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
