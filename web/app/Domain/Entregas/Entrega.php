<?php

namespace App\Domain\Entregas;

use App\Domain\Entregas\Exceptions\EntregaInvalidaException;
use DateTimeImmutable;

final class Entrega
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $jornadaId,
        public readonly int $proveedorId,
        public readonly int $usuarioId,
        public readonly int $zonaId,
        public readonly int $vehiculoId,
        public readonly float $litros,
        public readonly int $tachos,
        public readonly ?string $observaciones,
        public readonly DateTimeImmutable $registradoEn,
        public readonly ?string $loteId,
        public readonly bool $anulada,
    ) {}

    public static function crear(
        int $jornadaId,
        int $proveedorId,
        int $usuarioId,
        int $zonaId,
        int $vehiculoId,
        float $litros,
        int $tachos,
        ?string $observaciones,
        DateTimeImmutable $registradoEn,
        ?string $loteId = null,
    ): self {
        self::validar($litros, $tachos);

        return new self(
            id: null,
            jornadaId: $jornadaId,
            proveedorId: $proveedorId,
            usuarioId: $usuarioId,
            zonaId: $zonaId,
            vehiculoId: $vehiculoId,
            litros: $litros,
            tachos: $tachos,
            observaciones: $observaciones !== null ? trim($observaciones) : null,
            registradoEn: $registradoEn,
            loteId: $loteId,
            anulada: false,
        );
    }

    public static function reconstruir(
        int $id,
        int $jornadaId,
        int $proveedorId,
        int $usuarioId,
        int $zonaId,
        int $vehiculoId,
        float $litros,
        int $tachos,
        ?string $observaciones,
        DateTimeImmutable $registradoEn,
        ?string $loteId,
        bool $anulada,
    ): self {
        return new self($id, $jornadaId, $proveedorId, $usuarioId, $zonaId, $vehiculoId, $litros, $tachos, $observaciones, $registradoEn, $loteId, $anulada);
    }

    private static function validar(float $litros, int $tachos): void
    {
        if ($litros <= 0.0) {
            throw EntregaInvalidaException::litrosNoPositivos();
        }

        if ($tachos <= 0) {
            throw EntregaInvalidaException::tachosInvalidos();
        }
    }
}
