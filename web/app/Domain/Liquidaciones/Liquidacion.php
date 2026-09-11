<?php

namespace App\Domain\Liquidaciones;

use App\Domain\Liquidaciones\Exceptions\LiquidacionInvalidaException;
use DateTimeImmutable;

final class Liquidacion
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $proveedorId,
        public readonly DateTimeImmutable $periodoInicio,
        public readonly DateTimeImmutable $periodoFin,
        public readonly float $litrosTotales,
        public readonly float $precioLitro,
        public readonly float $montoTotal,
        public readonly EstadoLiquidacion $estado,
        public readonly DateTimeImmutable $generadaEn,
        public readonly ?DateTimeImmutable $pagadaEn,
    ) {}

    public static function generar(
        int $proveedorId,
        DateTimeImmutable $periodoInicio,
        DateTimeImmutable $periodoFin,
        float $litrosTotales,
        float $precioLitro,
        DateTimeImmutable $generadaEn,
    ): self {
        if ($periodoFin < $periodoInicio) {
            throw LiquidacionInvalidaException::periodoInvalido();
        }

        if ($precioLitro <= 0.0) {
            throw LiquidacionInvalidaException::precioInvalido();
        }

        if ($litrosTotales <= 0.0) {
            throw LiquidacionInvalidaException::sinEntregas();
        }

        return new self(
            id: null,
            proveedorId: $proveedorId,
            periodoInicio: $periodoInicio,
            periodoFin: $periodoFin,
            litrosTotales: $litrosTotales,
            precioLitro: $precioLitro,
            montoTotal: round($litrosTotales * $precioLitro, 2),
            estado: EstadoLiquidacion::Pendiente,
            generadaEn: $generadaEn,
            pagadaEn: null,
        );
    }

    public static function reconstruir(
        int $id,
        int $proveedorId,
        DateTimeImmutable $periodoInicio,
        DateTimeImmutable $periodoFin,
        float $litrosTotales,
        float $precioLitro,
        float $montoTotal,
        EstadoLiquidacion $estado,
        DateTimeImmutable $generadaEn,
        ?DateTimeImmutable $pagadaEn,
    ): self {
        return new self($id, $proveedorId, $periodoInicio, $periodoFin, $litrosTotales, $precioLitro, $montoTotal, $estado, $generadaEn, $pagadaEn);
    }

    public function marcarPagada(DateTimeImmutable $pagadaEn): self
    {
        if ($this->estado === EstadoLiquidacion::Pagada) {
            throw LiquidacionInvalidaException::yaPagada();
        }

        return new self($this->id, $this->proveedorId, $this->periodoInicio, $this->periodoFin, $this->litrosTotales, $this->precioLitro, $this->montoTotal, EstadoLiquidacion::Pagada, $this->generadaEn, $pagadaEn);
    }
}
