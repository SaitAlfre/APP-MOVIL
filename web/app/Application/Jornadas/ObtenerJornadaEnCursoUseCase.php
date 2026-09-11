<?php

namespace App\Application\Jornadas;

use App\Domain\Jornadas\Jornada;
use App\Domain\Jornadas\JornadaRepositoryInterface;
use DateTimeImmutable;

final class ObtenerJornadaEnCursoUseCase
{
    public function __construct(
        private readonly JornadaRepositoryInterface $jornadas,
    ) {}

    public function ejecutar(int $usuarioId): ?Jornada
    {
        return $this->jornadas->obtenerAbiertaPorUsuarioYFecha($usuarioId, new DateTimeImmutable('today'));
    }
}
