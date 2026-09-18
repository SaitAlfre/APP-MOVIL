<?php

namespace App\Domain\Inventario;

use App\Domain\Inventario\Exceptions\InsumoInvalidoException;
use DateTimeImmutable;

final class EntradaInsumo
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $insumoId,
        public readonly ?int $proveedorId,
        public readonly ?int $entregaId,
        public readonly float $cantidad,
        public readonly string $unidad,
        public readonly DateTimeImmutable $fecha,
        public readonly ?float $costoUnitario,
        public readonly ?float $costoTotal,
        public readonly ?string $documentoReferencia,
        public readonly ?string $loteOrigen,
        public readonly ?DateTimeImmutable $vencimiento,
        public readonly ?string $observaciones,
        public readonly int $usuarioId,
    ) {}

    public static function crear(
        int $insumoId,
        ?int $proveedorId,
        ?int $entregaId,
        float $cantidad,
        string $unidad,
        DateTimeImmutable $fecha,
        ?float $costoUnitario,
        ?float $costoTotal,
        ?string $documentoReferencia,
        ?string $loteOrigen,
        ?DateTimeImmutable $vencimiento,
        ?string $observaciones,
        int $usuarioId,
    ): self {
        if ($cantidad <= 0.0) {
            throw InsumoInvalidoException::cantidadInvalida();
        }

        if (trim($unidad) === '') {
            throw InsumoInvalidoException::unidadVacia();
        }

        return new self(
            id: null,
            insumoId: $insumoId,
            proveedorId: $proveedorId,
            entregaId: $entregaId,
            cantidad: $cantidad,
            unidad: trim($unidad),
            fecha: $fecha,
            costoUnitario: $costoUnitario,
            costoTotal: $costoTotal ?? ($costoUnitario !== null ? round($costoUnitario * $cantidad, 2) : null),
            documentoReferencia: $documentoReferencia !== null && trim($documentoReferencia) !== '' ? trim($documentoReferencia) : null,
            loteOrigen: $loteOrigen !== null && trim($loteOrigen) !== '' ? trim($loteOrigen) : null,
            vencimiento: $vencimiento,
            observaciones: $observaciones !== null && trim($observaciones) !== '' ? trim($observaciones) : null,
            usuarioId: $usuarioId,
        );
    }

    public static function reconstruir(
        int $id,
        int $insumoId,
        ?int $proveedorId,
        ?int $entregaId,
        float $cantidad,
        string $unidad,
        DateTimeImmutable $fecha,
        ?float $costoUnitario,
        ?float $costoTotal,
        ?string $documentoReferencia,
        ?string $loteOrigen,
        ?DateTimeImmutable $vencimiento,
        ?string $observaciones,
        int $usuarioId,
    ): self {
        return new self($id, $insumoId, $proveedorId, $entregaId, $cantidad, $unidad, $fecha, $costoUnitario, $costoTotal, $documentoReferencia, $loteOrigen, $vencimiento, $observaciones, $usuarioId);
    }
}
