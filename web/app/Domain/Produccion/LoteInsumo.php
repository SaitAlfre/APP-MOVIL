<?php

namespace App\Domain\Produccion;

/** Detalle persistido de un insumo dentro de un lote: necesario, reservado y consumido. */
final class LoteInsumo
{
    public function __construct(
        public readonly int $id,
        public readonly int $loteProduccionId,
        public readonly int $insumoId,
        public readonly string $unidad,
        public readonly float $cantidadNecesaria,
        public readonly float $cantidadReservada,
        public readonly ?float $cantidadConsumida,
    ) {}

    public function faltante(): float
    {
        return max(0.0, round($this->cantidadNecesaria - $this->cantidadReservada, 3));
    }

    public function consumoInformado(): bool
    {
        return $this->cantidadConsumida !== null;
    }

    public function reservaPendienteDeLiberar(): float
    {
        return max(0.0, round($this->cantidadReservada - ($this->cantidadConsumida ?? 0.0), 3));
    }
}
