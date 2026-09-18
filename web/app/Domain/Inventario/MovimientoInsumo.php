<?php

namespace App\Domain\Inventario;

use DateTimeImmutable;

/** Fila de solo lectura del historial (ledger) de movimientos de un insumo. */
final class MovimientoInsumo
{
    public function __construct(
        public readonly int $id,
        public readonly int $insumoId,
        public readonly TipoMovimientoInsumo $tipo,
        public readonly float $cantidad,
        public readonly string $unidad,
        public readonly ?int $loteProduccionId,
        public readonly ?int $entradaInsumoId,
        public readonly ?string $motivo,
        public readonly int $usuarioId,
        public readonly ?string $observaciones,
        public readonly DateTimeImmutable $fecha,
    ) {}
}
