<?php

namespace App\Application\Liquidaciones;

use App\Domain\Liquidaciones\Liquidacion;
use App\Domain\Liquidaciones\LiquidacionRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

final class MarcarLiquidacionPagadaUseCase
{
    public function __construct(
        private readonly LiquidacionRepositoryInterface $liquidaciones,
    ) {}

    public function ejecutar(int $liquidacionId): Liquidacion
    {
        $liquidacion = $this->liquidaciones->buscarPorId($liquidacionId);

        if ($liquidacion === null) {
            throw new RuntimeException('La liquidación no existe.');
        }

        return $this->liquidaciones->guardar($liquidacion->marcarPagada(new DateTimeImmutable));
    }
}
